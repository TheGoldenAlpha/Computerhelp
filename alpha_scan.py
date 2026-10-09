#!/usr/bin/env python3
"""
Scannt GoBattle-User-IDs ueber eine JSON-API und sucht alle User, deren Name
dem Muster "The ... Alpha" entspricht, z. B.:

    The Golden Alpha
    xX The Golden Alpha Xx
    [TAG] the dark alpha 2

Nur Python-Standardbibliothek (und Git, wenn die Git-Sicherung benutzt wird).

Standardmaessig wird die GoBattle-Profil-API abgefragt
(https://alpha.gobattle.io/api.php/api/profile/{id}, Name steht in user.nick).

    python alpha_scan.py --test 12345           # nur eine ID abfragen und Antwort zeigen
    python alpha_scan.py                        # IDs 1..700000 scannen
    python alpha_scan.py --start 1 --end 350000 # nur ein Teilbereich (z. B. fuer einen zweiten Rechner)
    python alpha_scan.py --strict               # nur Name beginnt mit "The" und endet mit "Alpha"
    python alpha_scan.py --no-git               # ohne Git, nur lokal speichern

Fortschritt und Git-Sicherung
-----------------------------
Alles liegt im Ordner "scan" neben diesem Script (pro Bereich ein Satz Dateien,
der Bereich steht im Dateinamen, z. B. 1-700000):

    scan/progress_1-700000.txt      die letzte fertig geprueft ID
    scan/log_1-700000.txt           alle Zeilen, die auch in der Shell stehen (mit Uhrzeit)
    scan/alpha_users_1-700000.csv   die Treffer (id, name, profil)
    scan/failed_1-700000.txt        IDs, die auch nach Wiederholungen nicht abgefragt werden konnten

* Nach jedem kleinen Stueck (--chunk, Standard 20 IDs) wird die letzte fertige ID gespeichert.
* Alle 30 Sekunden (--status-interval) wird der Ordner "scan" per Git gesichert
  (commit, pull --rebase, push auf den aktuellen Branch).
* Beim Start holt das Script zuerst den neuesten Stand aus Git (pull) und macht
  bei der letzten gespeicherten ID weiter. So kann man an einem Ort aufhoeren
  und an einem anderen Ort weitermachen.
* Laufen zwei Rechner gleichzeitig, muessen sie verschiedene Bereiche scannen
  (--start/--end), sonst pruefen sie dieselben IDs doppelt und ihre Dateien
  geraten sich in die Quere.

Tempo
-----
GoBattle erlaubt pro IP nur etwa 1 Profil-Abfrage pro Sekunde (bei dir gemessen: 0.6-0.85).
Das Script schickt deshalb die Anfragen in gleichmaessigem Abstand, statt zu probieren und
nach jeder Ablehnung (503) eine feste Pause zu machen. Der Abstand passt sich selbst an:
nach einem 503 wird er laenger, nach jeder erfolgreichen Antwort ganz leicht kuerzer. So
pendelt er sich knapp unter dem Limit des Servers ein, es gibt nur wenige Ablehnungen, und
das Tempo ist trotzdem so hoch, wie der Server es erlaubt. Ist ein Retry-After-Header da,
wird er beachtet. Schneller als das Limit des Servers geht es nicht.
2 parallele Requests sind voreingestellt (das reicht, damit sich Antwortzeiten ueberlappen).

Abbrechen mit Strg+C ist ok: Der Fortschritt ist gespeichert und beim naechsten
Start wird automatisch fortgesetzt.
"""

import argparse
import csv
import http.client
import json
import os
import re
import socket
import ssl
import subprocess
import sys
import threading
import time
from concurrent.futures import ThreadPoolExecutor
from urllib.parse import urlsplit

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

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

# Bei Rate-Limit (429/503) pausieren alle Threads gemeinsam bis zu diesem Zeitpunkt
_pause_lock = threading.Lock()
_pause_until = 0.0
_rate_limited = 0
_net_retries = 0  # Timeouts / abgebrochene Verbindungen, die wiederholt werden
_codes = {}  # HTTP-Statuscodes der Antworten, zur Diagnose im Status
_codes_lock = threading.Lock()

# ---------------------------------------------------------------------------
# Log: alles, was in der Shell steht, kommt zusaetzlich mit Uhrzeit in eine Textdatei
# ---------------------------------------------------------------------------
_log_lock = threading.RLock()
_log_path = None
_log_pending = []  # Zeilen, die vor dem Festlegen der Logdatei anfielen


def log(msg):
    """Zeile in die Shell schreiben und (mit Uhrzeit) in die Logdatei."""
    with _log_lock:
        print(msg, flush=True)
        line = f"[{time.strftime('%Y-%m-%d %H:%M:%S')}] {msg}\n"
        if _log_path is None:
            _log_pending.append(line)
            return
        _append_log(line)


def _append_log(text):
    try:
        with open(_log_path, "a", encoding="utf-8", newline="\n") as f:
            f.write(text)
    except OSError as e:
        print(f"[!] Logdatei nicht beschreibbar: {e}", flush=True)


def set_log_file(path):
    global _log_path
    with _log_lock:
        _log_path = path
        if _log_pending:
            _append_log("".join(_log_pending))
            _log_pending.clear()


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


class Pacer:
    """Gleichmaessiges Tempo: Der Abstand zwischen zwei Anfragen passt sich an.

    Nach einem 503 wird der Abstand laenger, nach jeder erfolgreichen Antwort etwas kuerzer.
    Dadurch landet das Tempo knapp unter dem Limit des Servers (etwa jede 25. Anfrage wird abgelehnt)."""

    def __init__(self, start_interval, min_interval, max_interval=30.0):
        self.interval = float(start_interval)
        self.min = float(min_interval)
        self.max = float(max_interval)
        self.next_time = 0.0
        self.last_cut = 0.0
        self.fast_start = True  # bis zur ersten Ablehnung schnell herantasten, danach vorsichtig
        self.lock = threading.Lock()

    def wait_turn(self):
        """Wartet, bis die naechste Anfrage dran ist (Startzeiten haben gleichmaessigen Abstand)."""
        with self.lock:
            now = time.monotonic()
            start = max(now, self.next_time)
            self.next_time = start + self.interval
        if start > now:
            time.sleep(start - now)

    def success(self):
        with self.lock:
            self.interval = max(self.min, self.interval * (0.95 if self.fast_start else 0.99))

    def rejected(self):
        with self.lock:
            self.fast_start = False
            now = time.monotonic()
            # Mehrere 503 von Anfragen, die schon unterwegs waren, zaehlen nur einmal
            if now - self.last_cut > self.interval:
                self.interval = min(self.max, self.interval * 1.3)
                self.last_cut = now
            # kurze Abkuehlung: die naechste Anfrage kommt erst nach einem vollen Abstand
            self.next_time = max(self.next_time, now + self.interval)


pacer = None  # wird in main() gesetzt


def _count_rate_limit():
    global _rate_limited
    with _pause_lock:
        _rate_limited += 1


def _pause_all(seconds):
    """Alle Requests warten (nur fuer einen Retry-After-Header des Servers)."""
    global _pause_until
    with _pause_lock:
        new_until = time.time() + seconds
        if seconds >= 5 and new_until > _pause_until + 1:
            log(f"[i] Rate-Limit: Server verlangt {seconds:g}s Pause, alle Requests warten")
        _pause_until = max(_pause_until, new_until)


_first_429_seen = threading.Event()


def _report_first_429(resp):
    """Beim ersten Rate-Limit einmal zeigen, was der Server zum Limit sagt (hilft beim Einstellen)."""
    if _first_429_seen.is_set():
        return
    _first_429_seen.set()
    info = [f"{k}: {v}" for k, v in resp.getheaders()
            if k.lower() == "retry-after" or "ratelimit" in k.lower() or "rate-limit" in k.lower()]
    log(f"[i] Erstes Rate-Limit (HTTP {resp.status}). Header vom Server: "
        + (", ".join(info) or "keine Limit-Angaben"))


def _count_net_retry():
    global _net_retries
    with _pause_lock:
        _net_retries += 1


def _wait_if_paused():
    while True:
        wait = _pause_until - time.time()
        if wait <= 0:
            return
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
        # Pause erst NACH dem Platz im Limiter abwarten: sonst starten alle wartenden Threads
        # gleichzeitig los, sobald die Pause vorbei ist, und bekommen fast nur 503 zurueck.
        _wait_if_paused()
        pacer.wait_turn()
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
    """Gibt den Namen zurueck, None wenn es die ID nicht gibt.

    Bei einer unklaren Antwort (Serverfehler, kein JSON, Verbindungsabbruch) wird eine
    Ausnahme geworfen. Die ID zaehlt dann als fehlgeschlagen und wird in failed_*.txt notiert,
    statt still als "gibt es nicht" durchzugehen."""
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
                pacer.rejected()  # Abstand wird laenger, die naechste Anfrage wartet einen vollen Abstand
                _count_rate_limit()
                retry_after = resp.getheader("Retry-After")
                _report_first_429(resp)
                _reset_conn()  # nach 429 frische Verbindung, falls der Server die alte haengen laesst
                if retry_after and retry_after.isdigit():
                    _pause_all(float(retry_after))
                rate_limit_retries += 1
                if rate_limit_retries > 1000:
                    raise RuntimeError("zu viele Rate-Limits")
                continue
            if resp.status == 200:
                limiter.success()
                pacer.success()
                try:
                    data = json.loads(body.decode("utf-8", "replace"))
                except json.JSONDecodeError:
                    raise RuntimeError("HTTP 200, aber kein JSON")
                return extract_name(data)
            if resp.status in (404, 410):
                limiter.success()
                pacer.success()
                return None  # ID existiert nicht / geloescht
            # alles andere (z. B. 5xx): kurz wiederholen, danach als fehlgeschlagen melden
            if attempt < max_retries:
                attempt += 1
                time.sleep(delay)
                delay = min(delay * 2, 10)
                continue
            raise RuntimeError(f"HTTP {resp.status}")
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


# ---------------------------------------------------------------------------
# Fortschritt speichern und per Git sichern
# ---------------------------------------------------------------------------
_git_ident = []  # wird gesetzt, wenn auf dem Rechner kein Git-Name/E-Mail eingestellt ist


def _git(*args, timeout=120):
    """Git im Ordner dieses Scripts ausfuehren. Gibt immer ein Ergebnis zurueck (nie eine Ausnahme)."""
    try:
        return subprocess.run(["git", "-C", SCRIPT_DIR] + _git_ident + list(args), capture_output=True,
                              text=True, encoding="utf-8", errors="replace", timeout=timeout)
    except (OSError, subprocess.TimeoutExpired) as e:
        return subprocess.CompletedProcess(args, 255, "", str(e))


def _err(r):
    return ((r.stderr or "") + (r.stdout or "")).strip().replace("\n", " ")[:300]


def git_branch():
    """Name des aktuellen Branches oder None (kein Git-Ordner / losgeloester HEAD)."""
    r = _git("rev-parse", "--abbrev-ref", "HEAD")
    name = r.stdout.strip()
    if r.returncode != 0 or not name or name == "HEAD":
        return None
    if not _git("config", "user.email").stdout.strip():
        _git_ident[:] = ["-c", "user.name=alpha-scan", "-c", "user.email=alpha-scan@localhost"]
    return name


def git_pull(branch):
    """Neuesten Stand holen. Gibt (ok, Text) zurueck."""
    with _log_lock:
        r = _git("pull", "--rebase", "--autostash", "-q", "origin", branch)
        if r.returncode != 0:
            _git("rebase", "--abort")
            return False, _err(r)
    return True, "ok"


def git_sync(branch, msg):
    """Ordner scan sichern: commit, neuesten Stand holen, push. Gibt (ok, Text) zurueck."""
    # Waehrend Git arbeitet, schreibt kein anderer Thread in die Logdatei
    with _log_lock:
        _git("add", "scan")
        if _git("status", "--porcelain", "--", "scan").stdout.strip():
            r = _git("commit", "-q", "-m", msg, "--", "scan")
            if r.returncode != 0:
                return False, "commit: " + _err(r)
        for _ in range(3):
            r = _git("pull", "--rebase", "--autostash", "-q", "origin", branch)
            if r.returncode != 0:
                _git("rebase", "--abort")
                return False, "pull: " + _err(r)
            r = _git("push", "-q", "origin", f"HEAD:{branch}")
            if r.returncode == 0:
                return True, "ok"
        return False, "push: " + _err(r)


def read_progress(path):
    try:
        with open(path, encoding="utf-8") as f:
            return int(f.read().strip() or 0)
    except (OSError, ValueError):
        return 0


def write_progress(path, value):
    """Atomar schreiben (erst temporaere Datei, dann ersetzen), damit nie eine halbe Datei entsteht."""
    tmp = path + ".tmp"
    with open(tmp, "w", encoding="utf-8", newline="\n") as f:
        f.write(f"{value}\n")
    os.replace(tmp, path)


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
    p.add_argument("--workers", type=int, default=2,
                   help="hoechstens so viele parallele Requests (Standard: 2, GoBattle erlaubt kaum mehr)")
    p.add_argument("--start-workers", type=int, default=1,
                   help="mit so vielen parallelen Requests anfangen (Standard: 1)")
    p.add_argument("--status-interval", type=float, default=30,
                   help="Status ins Log und Git-Sicherung alle N Sekunden (Standard: 30)")
    p.add_argument("--strict", action="store_true", help="nur 'The ... Alpha' ohne etwas davor/danach")
    p.add_argument("--timeout", type=float, default=15)
    p.add_argument("--retries", type=int, default=3, help="Wiederholungen bei Serverfehlern (Standard: 3)")
    p.add_argument("--start-interval", type=float, default=1.0,
                   help="Abstand zwischen zwei Anfragen am Anfang in Sekunden (Standard: 1.0, passt sich selbst an)")
    p.add_argument("--min-interval", type=float, default=0.1,
                   help="kuerzester erlaubter Abstand in Sekunden (Standard: 0.1)")
    p.add_argument("--chunk", type=int, default=20,
                   help="IDs pro Stueck; nach jedem Stueck wird die letzte fertige ID gespeichert (Standard: 20)")
    p.add_argument("--no-git", action="store_true", help="nichts per Git holen oder sichern, nur lokal speichern")
    args = p.parse_args()

    for stream in (sys.stdout, sys.stderr):
        try:
            stream.reconfigure(errors="replace")  # Namen mit Sonderzeichen sollen die Shell nicht abstuerzen lassen
        except (AttributeError, ValueError):
            pass

    global limiter, pacer, API_SCHEME, API_HOST, API_PATH, NAME_FIELD
    if "{id}" not in args.url:
        p.error("--url muss {id} enthalten")
    u = urlsplit(args.url)
    API_SCHEME, API_HOST = u.scheme or "https", u.netloc
    API_PATH = (u.path or "/") + (f"?{u.query}" if u.query else "")
    NAME_FIELD = None if args.name_field == "auto" else args.name_field
    limiter = Limiter(args.start_workers, args.workers)
    pacer = Pacer(args.start_interval, args.min_interval)

    if args.test is not None:
        run_test(args.test, args.timeout)
        return
    pattern = STRICT_RE if args.strict else LOOSE_RE

    # 1. Neuesten Stand aus Git holen, BEVOR eigene Dateien angelegt werden
    #    (sonst koennten lokale Dateien mit denen aus Git kollidieren)
    branch = None
    if not args.no_git:
        branch = git_branch()
        if branch is None:
            log("[!] Kein Git-Branch gefunden - speichere nur lokal (mit --no-git ist diese Meldung weg).")
        else:
            ok, text = git_pull(branch)
            log(f"[git] Neuesten Stand geholt (Branch {branch})" if ok
                else f"[!] git pull fehlgeschlagen, mache mit dem lokalen Stand weiter: {text}")

    # 2. Dateien pro Bereich
    scan_dir = os.path.join(SCRIPT_DIR, "scan")
    os.makedirs(scan_dir, exist_ok=True)
    tag = f"{args.start}-{args.end}"
    progress_file = os.path.join(scan_dir, f"progress_{tag}.txt")
    hits_file = os.path.join(scan_dir, f"alpha_users_{tag}.csv")
    failed_file = os.path.join(scan_dir, f"failed_{tag}.txt")
    set_log_file(os.path.join(scan_dir, f"log_{tag}.txt"))
    log(f"=== Start auf {socket.gethostname()} | Bereich {tag} | Python {sys.version.split()[0]} ===")

    start = args.start
    saved = read_progress(progress_file)
    if saved >= start:
        start = saved + 1
        log(f"Setze fort ab ID {start} (letzte gespeicherte ID: {saved})")

    if start > args.end:
        log("Bereich ist schon komplett gescannt.")
        return

    new_file = not os.path.exists(hits_file)
    out = open(hits_file, "a", newline="", encoding="utf-8")
    writer = csv.writer(out)
    if new_file:
        writer.writerow(["id", "name", "profil"])
        out.flush()

    lock = threading.Lock()
    stats = {"found": 0, "errors": 0, "exists": 0, "done": 0}
    t0 = time.time()
    total = args.end - start + 1
    sync_state = {"text": "-"}

    log(f"Scanne IDs {start}..{args.end} mit bis zu {args.workers} parallelen Requests, "
        f"Status und Git-Sicherung alle {args.status_interval:g} Sekunden")

    def check(member_id):
        """Gibt ('hit', id, name), ('fail', id, grund) oder None zurueck."""
        try:
            name = fetch_name(member_id, args.timeout, args.retries)
        except Exception as e:
            with lock:
                stats["errors"] += 1
                stats["done"] += 1
            return ("fail", member_id, str(e)[:80] or type(e).__name__)
        with lock:
            stats["done"] += 1
            if name is not None:
                stats["exists"] += 1
        if name is not None and pattern.search(name):
            return ("hit", member_id, name)
        return None

    last = {"t": t0, "done": 0}
    saved_up_to = {"id": start - 1}

    def n(x):
        return f"{x:,}".replace(",", "'")

    def status(final=False):
        now = time.time()
        done = stats["done"]
        elapsed = now - t0
        # Tempo der letzten Periode, das ist aussagekraeftiger als der Gesamtschnitt
        rate = (done - last["done"]) / max(now - last["t"], 1e-6)
        avg = done / max(elapsed, 1e-6)
        last.update(t=now, done=done)
        eta = (total - done) / avg if avg > 0 else float("inf")
        eta_txt = fmt_duration(eta) if eta != float("inf") else "?"
        label = "FERTIG" if final else "STATUS"
        pause_left = _pause_until - now
        pause_txt = f" | PAUSE noch {pause_left:.0f}s" if pause_left > 0 else ""
        log(f"[{label}] {n(start - 1 + done)}/{n(args.end)} ({done / total:.1%}) | "
            f"{rate:.2f} IDs/s (Schnitt {avg:.2f}) | Abstand {pacer.interval:.2f}s | "
            f"Parallel: {limiter.active}/{int(limiter.limit)} | "
            f"Laufzeit {fmt_duration(elapsed)} | Rest ca. {eta_txt} (nach Schnitt) | "
            f"User: {n(stats['exists'])} | Treffer: {stats['found']} | "
            f"Fehler: {stats['errors']} | Rate-Limits: {n(_rate_limited)} | "
            f"Timeouts/Verbindungsfehler: {_net_retries} | "
            f"HTTP: {' '.join(f'{k}x{n(v)}' for k, v in sorted(_codes.items())) or '-'} | "
            f"Gespeichert bis ID {n(saved_up_to['id'])} | Git: {sync_state['text']}"
            f"{pause_txt}")

    stop_status = threading.Event()

    def status_loop():
        while not stop_status.wait(args.status_interval):
            status()

    threading.Thread(target=status_loop, daemon=True).start()

    def do_sync(reason):
        """Fortschritt per Git sichern und das Ergebnis im Status anzeigen."""
        if branch is None:
            return
        ok, text = git_sync(branch, f"Scan {tag}: bis ID {saved_up_to['id']} ({socket.gethostname()}, {reason})")
        sync_state["text"] = "gesichert" if ok else "FEHLER"
        if not ok:
            log(f"[!] Git-Sicherung fehlgeschlagen (der Scan laeuft trotzdem weiter und speichert lokal): {text}")

    last_sync = time.time()
    try:
        with ThreadPoolExecutor(max_workers=args.workers) as pool:
            chunk_start = start
            while chunk_start <= args.end:
                chunk_end = min(chunk_start + args.chunk - 1, args.end)
                results = [r for r in pool.map(check, range(chunk_start, chunk_end + 1)) if r]
                hits = [r for r in results if r[0] == "hit"]
                fails = [r for r in results if r[0] == "fail"]
                for _, member_id, name in hits:
                    writer.writerow([member_id, name, args.profile_url.replace("{id}", str(member_id))])
                if hits:
                    out.flush()
                if fails:
                    with open(failed_file, "a", encoding="utf-8", newline="\n") as f:
                        for _, member_id, reason in fails:
                            f.write(f"{member_id}\n")
                for _, member_id, name in hits:
                    log(f"[+] {member_id}: {name}")
                for _, member_id, reason in fails:
                    log(f"[!] ID {member_id} fehlgeschlagen ({reason}) - steht in {os.path.basename(failed_file)}")
                stats["found"] += len(hits)
                # Treffer sind jetzt geschrieben, erst danach gilt der Block als gespeichert
                write_progress(progress_file, chunk_end)
                saved_up_to["id"] = chunk_end
                chunk_start = chunk_end + 1
                if time.time() - last_sync >= args.status_interval:
                    do_sync("laufend")
                    last_sync = time.time()
    except KeyboardInterrupt:
        log(f"Abgebrochen bei ID {saved_up_to['id']} - Fortschritt ist gespeichert, "
            f"einfach denselben Befehl nochmal starten zum Fortsetzen.")
        out.flush()
        try:
            do_sync("abgebrochen")
        except KeyboardInterrupt:
            pass
        os._exit(1)  # nicht auf die restlichen laufenden Requests warten
    finally:
        out.close()

    stop_status.set()
    status(final=True)
    do_sync("fertig")
    log(f"{stats['found']} Treffer in dieser Sitzung, alle Treffer: {hits_file}")


if __name__ == "__main__":
    main()
