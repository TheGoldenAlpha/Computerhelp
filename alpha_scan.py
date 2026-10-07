#!/usr/bin/env python3
"""
Scannt GoBattle-User-IDs ueber eine JSON-API und sucht alle User, deren Name
dem Muster "The ... Alpha" entspricht, z. B.:

    The Golden Alpha
    xX The Golden Alpha Xx
    [TAG] the dark alpha 2

Treffer landen in einer CSV-Datei (id, name, profil-url).
Nur Python-Standardbibliothek, keine Installation noetig.

Standardmaessig wird die GoBattle-Profil-API abgefragt
(https://alpha.gobattle.io/api.php/api/profile/{id}, Name steht in user.nick).

    python alpha_scan.py --test 12345           # nur eine ID abfragen und Antwort zeigen
    python alpha_scan.py                        # IDs 1..700000 scannen
    python alpha_scan.py --workers 100          # hoechstens 100 parallel
    python alpha_scan.py --status-interval 10   # Status alle 10 statt 30 Sekunden
    python alpha_scan.py --start 1 --end 50000  # Teilbereich
    python alpha_scan.py --strict               # nur Name beginnt mit "The" und endet mit "Alpha"

Die Zahl paralleler Requests passt sich automatisch an: Bei Rate-Limits (429)
wird sie halbiert, solange alles klappt, steigt sie langsam bis --workers.

Abbrechen mit Strg+C ist ok: Der Fortschritt wird in einer .progress-Datei
gespeichert und beim naechsten Start automatisch fortgesetzt.
"""

import argparse
import csv
import http.client
import json
import os
import re
import ssl
import sys
import threading
import time
from concurrent.futures import ThreadPoolExecutor
from urllib.parse import urlsplit

# werden in main() aus --url gesetzt
API_SCHEME = "https"
API_HOST = ""
API_PATH = ""
NAME_FIELD = None  # z. B. "data.username"; None = automatisch suchen
USER_AGENT = "Mozilla/5.0 (alpha-name-scan)"

# Feldnamen, unter denen der Name ueblicherweise steht (in dieser Reihenfolge probiert)
NAME_KEYS = ("username", "userName", "name", "nickname", "nick", "displayName", "display_name", "playerName")

# "The" ... "Alpha" irgendwo im Namen (auch ohne Leerzeichen, z. B. "TheGoldenAlpha")
LOOSE_RE = re.compile(r"the.*alpha", re.IGNORECASE)
# Name beginnt mit "The" und endet mit "Alpha"
STRICT_RE = re.compile(r"^\s*the\b.*\balpha\s*$", re.IGNORECASE)

_ssl_ctx = ssl.create_default_context()
_local = threading.local()

# Bei Rate-Limit (429) pausieren alle Threads gemeinsam bis zu diesem Zeitpunkt
_pause_lock = threading.Lock()
_pause_until = 0.0
_rate_limited = 0
_net_retries = 0  # Timeouts / abgebrochene Verbindungen, die wiederholt werden
_codes = {}  # HTTP-Statuscodes der Antworten, zur Diagnose im Status
_codes_lock = threading.Lock()


def _count_code(code):
    with _codes_lock:
        _codes[code] = _codes.get(code, 0) + 1


class Limiter:
    """Begrenzt parallele Requests und passt das Limit an (wie TCP: bei 429 halbieren, sonst langsam erhoehen)."""

    def __init__(self, start, maximum):
        self.limit = float(min(start, maximum))
        self.maximum = maximum
        self.active = 0
        self.cond = threading.Condition()
        self.last_cut = 0.0

    def acquire(self):
        with self.cond:
            while self.active >= int(self.limit):
                self.cond.wait()
            self.active += 1

    def release(self):
        with self.cond:
            self.active -= 1
            self.cond.notify()

    def success(self):
        with self.cond:
            if self.limit < self.maximum:
                before = int(self.limit)
                self.limit = min(self.maximum, self.limit + 1 / self.limit)
                if int(self.limit) > before:
                    self.cond.notify()

    def rate_limited(self):
        with self.cond:
            now = time.time()
            # viele gleichzeitige 429 zaehlen nur einmal, sonst faellt das Limit sofort auf 1
            if now - self.last_cut > 2:
                self.limit = max(1.0, self.limit / 2)
                self.last_cut = now


limiter = None  # wird in main() gesetzt


def _pause_all(seconds):
    global _pause_until, _rate_limited
    with _pause_lock:
        new_until = time.time() + seconds
        if seconds >= 5 and new_until > _pause_until + 1:
            sys.stdout.write(f"[i] Rate-Limit: Server verlangt {seconds:g}s Pause, alle Requests warten\n")
        _pause_until = max(_pause_until, new_until)
        _rate_limited += 1


_first_429_seen = threading.Event()


def _report_first_429(resp):
    """Beim ersten Rate-Limit einmal zeigen, was der Server zum Limit sagt (hilft beim Einstellen)."""
    if _first_429_seen.is_set():
        return
    _first_429_seen.set()
    info = [f"{k}: {v}" for k, v in resp.getheaders()
            if k.lower() == "retry-after" or "ratelimit" in k.lower() or "rate-limit" in k.lower()]
    sys.stdout.write(f"[i] Erstes Rate-Limit (HTTP {resp.status}). Header vom Server: "
                     + (", ".join(info) or "keine Limit-Angaben") + "\n")


def _count_net_retry():
    global _net_retries
    with _pause_lock:
        _net_retries += 1


def _wait_if_paused():
    wait = _pause_until - time.time()
    if wait > 0:
        time.sleep(wait)


def _conn(timeout):
    """Eine dauerhafte HTTPS-Verbindung pro Thread (Keep-Alive, kein neuer TLS-Handshake pro Request)."""
    c = getattr(_local, "conn", None)
    if c is None:
        if API_SCHEME == "http":
            c = http.client.HTTPConnection(API_HOST, timeout=timeout)
        else:
            c = http.client.HTTPSConnection(API_HOST, timeout=timeout, context=_ssl_ctx)
        _local.conn = c
    return c


def _reset_conn():
    c = getattr(_local, "conn", None)
    if c is not None:
        c.close()
    _local.conn = None


def _request(path, headers, timeout):
    """Ein einzelner Request, belegt dabei einen Platz im Limiter."""
    limiter.acquire()
    try:
        c = _conn(timeout)
        c.request("GET", path, headers=headers)
        resp = c.getresponse()
        return resp, resp.read()
    finally:
        limiter.release()


def _get_path(data, dotted):
    for part in dotted.split("."):
        if isinstance(data, list):
            data = data[int(part)] if part.isdigit() and int(part) < len(data) else None
        elif isinstance(data, dict):
            data = data.get(part)
        else:
            return None
    return data


def _find_name(data, depth=0):
    """Sucht rekursiv nach einem typischen Namensfeld."""
    if depth > 4:
        return None
    if isinstance(data, dict):
        for k in NAME_KEYS:
            v = data.get(k)
            if isinstance(v, str) and v.strip():
                return v
        for v in data.values():
            found = _find_name(v, depth + 1)
            if found:
                return found
    elif isinstance(data, list) and data:
        return _find_name(data[0], depth + 1)
    return None


def extract_name(data):
    if NAME_FIELD:
        v = _get_path(data, NAME_FIELD)
        return v if isinstance(v, str) and v.strip() else None
    return _find_name(data)


def fetch_name(member_id, timeout, max_retries):
    """Gibt den Namen zurueck, None wenn es die ID nicht gibt."""
    path = API_PATH.replace("{id}", str(member_id))
    headers = {"User-Agent": USER_AGENT, "Accept": "application/json"}
    delay = 1.0
    attempt = 0
    rate_limit_retries = 0
    while True:
        _wait_if_paused()
        try:
            resp, body = _request(path, headers, timeout)
            _count_code(resp.status)
            if resp.status in (429, 503):
                # GoBattle meldet sein Rate-Limit mit 503 statt 429.
                # Rate-Limits zaehlen nicht als Fehlversuch: drosseln, warten, dieselbe ID nochmal.
                limiter.rate_limited()
                retry_after = resp.getheader("Retry-After")
                _report_first_429(resp)
                _reset_conn()  # nach 429 frische Verbindung, falls der Server die alte haengen laesst
                _pause_all(float(retry_after) if retry_after and retry_after.isdigit() else 1.0)
                rate_limit_retries += 1
                if rate_limit_retries > 1000:
                    raise RuntimeError("zu viele Rate-Limits")
                continue
            if resp.status == 200:
                limiter.success()
                try:
                    data = json.loads(body.decode("utf-8", "replace"))
                except json.JSONDecodeError:
                    return None  # kein JSON = kein Profil, nicht wiederholen
                return extract_name(data)
            if 400 <= resp.status < 500:
                limiter.success()
                return None  # ID existiert nicht / geloescht
            # 5xx: kurz einmal wiederholen, nicht ewig warten
            # (manche APIs antworten auf nicht existierende IDs mit 500)
            if resp.status >= 500 and attempt < max_retries:
                attempt += 1
                time.sleep(delay)
                delay = min(delay * 2, 10)
                continue
            limiter.success()
            return None
        except (http.client.HTTPException, OSError):
            _reset_conn()
            _count_net_retry()
            if attempt < max_retries:
                attempt += 1
                time.sleep(delay)
                delay = min(delay * 2, 10)
                continue
            raise


def fmt_duration(seconds):
    seconds = int(seconds)
    h, rest = divmod(seconds, 3600)
    m, s = divmod(rest, 60)
    return f"{h}h {m:02d}m" if h else f"{m}m {s:02d}s"


def run_test(member_id, timeout):
    """Eine ID abfragen und alles zeigen, damit man sieht, ob URL und Namensfeld stimmen."""
    path = API_PATH.replace("{id}", str(member_id))
    print(f"GET {API_SCHEME}://{API_HOST}{path}")
    resp, body = _request(path, {"User-Agent": USER_AGENT, "Accept": "application/json"}, timeout)
    print(f"HTTP {resp.status} {resp.reason}")
    text = body.decode("utf-8", "replace")
    print("Antwort (Anfang):")
    print(text[:1500])
    try:
        data = json.loads(text)
    except json.JSONDecodeError:
        print("\n[!] Antwort ist kein JSON. Das ist wahrscheinlich die Webseite, nicht die API-URL.")
        return
    name = extract_name(data)
    if name:
        print(f"\n[OK] Gefundener Name: {name!r}")
    else:
        print("\n[!] Kein Name gefunden. Schau oben, wo der Name steht, und gib ihn mit --name-field an "
              "(z. B. --name-field user.nick).")


def main():
    p = argparse.ArgumentParser(description="Sucht User mit 'The ... Alpha' im Namen.")
    p.add_argument("--url", default="https://alpha.gobattle.io/api.php/api/profile/{id}",
                   help="API-URL mit {id} (Standard: GoBattle-Profil-API)")
    p.add_argument("--name-field", default="user.nick",
                   help="Pfad zum Namen im JSON (Standard: user.nick, 'auto' = automatisch suchen)")
    p.add_argument("--profile-url", default="https://selahgb.org/search.html?id={id}",
                   help="Profil-Link mit {id} fuer die CSV")
    p.add_argument("--test", type=int, metavar="ID", help="nur diese eine ID abfragen und die Antwort zeigen")
    p.add_argument("--start", type=int, default=1)
    p.add_argument("--end", type=int, default=700_000)
    p.add_argument("--workers", type=int, default=200, help="hoechstens so viele parallele Requests (Standard: 200)")
    p.add_argument("--start-workers", type=int, default=20, help="mit so vielen parallelen Requests anfangen (Standard: 20)")
    p.add_argument("--status-interval", type=float, default=30, help="Status alle N Sekunden (Standard: 30)")
    p.add_argument("--out", default="alpha_users.csv")
    p.add_argument("--strict", action="store_true", help="nur 'The ... Alpha' ohne etwas davor/danach")
    p.add_argument("--timeout", type=float, default=15)
    p.add_argument("--retries", type=int, default=1, help="Wiederholungen bei Serverfehlern (Standard: 1)")
    p.add_argument("--chunk", type=int, default=5000, help="IDs pro Block (Fortschritt wird pro Block gespeichert)")
    args = p.parse_args()

    global limiter, API_SCHEME, API_HOST, API_PATH, NAME_FIELD
    if "{id}" not in args.url:
        p.error("--url muss {id} enthalten")
    u = urlsplit(args.url)
    API_SCHEME, API_HOST = u.scheme or "https", u.netloc
    API_PATH = (u.path or "/") + (f"?{u.query}" if u.query else "")
    NAME_FIELD = None if args.name_field == "auto" else args.name_field
    limiter = Limiter(args.start_workers, args.workers)

    if args.test is not None:
        run_test(args.test, args.timeout)
        return
    pattern = STRICT_RE if args.strict else LOOSE_RE
    progress_file = args.out + ".progress"

    start = args.start
    if os.path.exists(progress_file):
        with open(progress_file) as f:
            saved = int(f.read().strip() or 0)
        if saved >= start:
            start = saved + 1
            print(f"Setze fort ab ID {start} (aus {progress_file})")

    if start > args.end:
        print("Bereich ist schon komplett gescannt. Zum Neustarten die .progress-Datei loeschen.")
        return

    new_file = not os.path.exists(args.out)
    out = open(args.out, "a", newline="", encoding="utf-8")
    writer = csv.writer(out)
    if new_file:
        writer.writerow(["id", "name", "profil"])
        out.flush()

    lock = threading.Lock()
    stats = {"found": 0, "errors": 0, "exists": 0, "done": 0}
    t0 = time.time()
    total = args.end - start + 1

    print(f"Scanne IDs {start}..{args.end} mit bis zu {args.workers} parallelen Requests, "
          f"Status alle {args.status_interval:g} Sekunden")

    def check(member_id):
        try:
            name = fetch_name(member_id, args.timeout, args.retries)
        except Exception:
            with lock:
                stats["errors"] += 1
                stats["done"] += 1
            print(f"[!] ID {member_id} fehlgeschlagen", file=sys.stderr)
            return None
        with lock:
            stats["done"] += 1
            if name is not None:
                stats["exists"] += 1
        if name is None:
            return None
        if pattern.search(name):
            return (member_id, name)
        return None

    last = {"t": t0, "done": 0}

    def status(final=False):
        now = time.time()
        done = stats["done"]
        elapsed = now - t0
        # Tempo der letzten Periode, das ist aussagekraeftiger als der Gesamtschnitt
        rate = (done - last["done"]) / max(now - last["t"], 1e-6)
        avg = done / max(elapsed, 1e-6)
        last.update(t=now, done=done)
        eta = (total - done) / rate if rate > 0 else float("inf")
        eta_txt = fmt_duration(eta) if eta != float("inf") else "?"
        label = "FERTIG" if final else "STATUS"
        pause_left = _pause_until - now
        pause_txt = f" | PAUSE noch {pause_left:.0f}s" if pause_left > 0 else ""
        print(f"[{label}] {start - 1 + done:,}/{args.end:,} ({done / total:.1%}) | "
              f"{rate:.0f} IDs/s (Schnitt {avg:.0f}) | Parallel: {limiter.active}/{int(limiter.limit)} | "
              f"Laufzeit {fmt_duration(elapsed)} | Rest ca. {eta_txt} | "
              f"User: {stats['exists']:,} | Treffer: {stats['found']} | "
              f"Fehler: {stats['errors']} | Rate-Limits: {_rate_limited} | "
              f"Timeouts/Verbindungsfehler: {_net_retries} | "
              f"HTTP: {' '.join(f'{k}x{v}' for k, v in sorted(_codes.items())) or '-'}"
              f"{pause_txt}".replace(",", "'"),
              flush=True)

    stop_status = threading.Event()

    def status_loop():
        while not stop_status.wait(args.status_interval):
            status()

    threading.Thread(target=status_loop, daemon=True).start()

    last_done = start - 1
    try:
        with ThreadPoolExecutor(max_workers=args.workers) as pool:
            chunk_start = start
            while chunk_start <= args.end:
                chunk_end = min(chunk_start + args.chunk - 1, args.end)
                hits = [h for h in pool.map(check, range(chunk_start, chunk_end + 1)) if h]
                for member_id, name in hits:
                    writer.writerow([member_id, name, args.profile_url.replace("{id}", str(member_id))])
                    print(f"[+] {member_id}: {name}", flush=True)
                stats["found"] += len(hits)
                out.flush()
                with open(progress_file, "w") as f:
                    f.write(str(chunk_end))
                last_done = chunk_end
                chunk_start = chunk_end + 1
    except KeyboardInterrupt:
        print(f"\nAbgebrochen bei ID {last_done} – Fortschritt gespeichert, "
              f"einfach denselben Befehl nochmal starten zum Fortsetzen.")
        os._exit(1)  # nicht auf die restlichen laufenden Requests warten
    finally:
        out.close()

    stop_status.set()
    status(final=True)
    print(f"{stats['found']} Treffer in {args.out}")


if __name__ == "__main__":
    main()
