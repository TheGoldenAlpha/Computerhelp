#!/usr/bin/env python3
"""
Scannt GameBanana-Member-IDs und sucht alle User, deren Name dem Muster
"The ... Alpha" entspricht, z. B.:

    The Golden Alpha
    xX The Golden Alpha Xx
    [TAG] the dark alpha 2

Treffer landen in einer CSV-Datei (id, name, profil-url).
Nur Python-Standardbibliothek, keine Installation noetig.

Beispiele:
    python3 gb_alpha_scan.py                       # IDs 1..700000, 200 parallele Requests
    python3 gb_alpha_scan.py --workers 100         # weniger parallel, falls Rate-Limits kommen
    python3 gb_alpha_scan.py --status-every 10000  # nach 10k alle 10k statt alle 25k IDs Status
    python3 gb_alpha_scan.py --start 1 --end 50000 # Teilbereich
    python3 gb_alpha_scan.py --strict              # nur Name beginnt mit "The" und endet mit "Alpha"

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

API_HOST = "gamebanana.com"
API_PATH = "/apiv11/Member/{id}/ProfilePage"
PROFILE_URL = "https://gamebanana.com/members/{id}"
USER_AGENT = "gb-alpha-scan/1.1 (name search script)"

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


def _pause_all(seconds):
    global _pause_until, _rate_limited
    with _pause_lock:
        _pause_until = max(_pause_until, time.time() + seconds)
        _rate_limited += 1


def _wait_if_paused():
    wait = _pause_until - time.time()
    if wait > 0:
        time.sleep(wait)


def _conn(timeout):
    """Eine dauerhafte HTTPS-Verbindung pro Thread (Keep-Alive, kein neuer TLS-Handshake pro Request)."""
    c = getattr(_local, "conn", None)
    if c is None:
        c = http.client.HTTPSConnection(API_HOST, timeout=timeout, context=_ssl_ctx)
        _local.conn = c
    return c


def _reset_conn():
    c = getattr(_local, "conn", None)
    if c is not None:
        c.close()
    _local.conn = None


def fetch_name(member_id, timeout, max_retries):
    """Gibt den Namen zurueck, None wenn es die ID nicht gibt."""
    path = API_PATH.format(id=member_id)
    headers = {"User-Agent": USER_AGENT, "Accept": "application/json"}
    delay = 2.0
    for attempt in range(max_retries + 1):
        _wait_if_paused()
        try:
            c = _conn(timeout)
            c.request("GET", path, headers=headers)
            resp = c.getresponse()
            body = resp.read()
            if resp.status == 200:
                return json.loads(body.decode("utf-8", "replace")).get("_sName")
            if resp.status in (404, 410):
                return None  # ID existiert nicht / geloescht
            if resp.status in (429, 500, 502, 503, 504) and attempt < max_retries:
                retry_after = resp.getheader("Retry-After")
                wait = float(retry_after) if retry_after and retry_after.isdigit() else delay
                if resp.status == 429:
                    _pause_all(wait)
                else:
                    time.sleep(wait)
                delay = min(delay * 2, 60)
                continue
            return None
        except (http.client.HTTPException, OSError, json.JSONDecodeError):
            _reset_conn()
            if attempt < max_retries:
                time.sleep(delay)
                delay = min(delay * 2, 60)
                continue
            raise
    return None


def fmt_duration(seconds):
    seconds = int(seconds)
    h, rest = divmod(seconds, 3600)
    m, s = divmod(rest, 60)
    return f"{h}h {m:02d}m" if h else f"{m}m {s:02d}s"


def main():
    p = argparse.ArgumentParser(description="Sucht GameBanana-User mit 'The ... Alpha' im Namen.")
    p.add_argument("--start", type=int, default=1)
    p.add_argument("--end", type=int, default=700_000)
    p.add_argument("--workers", type=int, default=200, help="parallele Requests (Standard: 200)")
    p.add_argument("--status-every", type=int, default=25_000, help="Status nach 100, 1000, 10000 IDs, danach alle N IDs (Standard: 25000)")
    p.add_argument("--out", default="alpha_users.csv")
    p.add_argument("--strict", action="store_true", help="nur 'The ... Alpha' ohne etwas davor/danach")
    p.add_argument("--timeout", type=float, default=15)
    p.add_argument("--retries", type=int, default=5)
    p.add_argument("--chunk", type=int, default=5000, help="IDs pro Block (Fortschritt wird pro Block gespeichert)")
    args = p.parse_args()

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
    stats = {"found": 0, "errors": 0, "exists": 0}
    t0 = time.time()
    total = args.end - start + 1
    # Status nach 100, 1k, 10k gescannten IDs, danach alle --status-every (25k, 50k, 75k, ...)
    def milestones():
        yield from (100, 1_000, 10_000)
        n = args.status_every
        while True:
            if n > 10_000:
                yield n
            n += args.status_every

    ms = milestones()
    next_status = start - 1 + next(ms)

    print(f"Scanne IDs {start}..{args.end} mit {args.workers} parallelen Requests, "
          f"Status nach 100, 1000, 10000 und danach alle {args.status_every} IDs")

    def check(member_id):
        try:
            name = fetch_name(member_id, args.timeout, args.retries)
        except Exception:
            with lock:
                stats["errors"] += 1
            print(f"[!] ID {member_id} fehlgeschlagen", file=sys.stderr)
            return None
        if name is None:
            return None
        with lock:
            stats["exists"] += 1
        if pattern.search(name):
            return (member_id, name)
        return None

    def status(upto, final=False):
        done = upto - start + 1
        elapsed = time.time() - t0
        rate = done / max(elapsed, 1e-6)
        eta = (total - done) / rate if rate else 0
        label = "FERTIG" if final else "STATUS"
        print(f"[{label}] {upto:,}/{args.end:,} ({done / total:.1%}) | "
              f"{rate:.0f} IDs/s | Laufzeit {fmt_duration(elapsed)} | Rest ca. {fmt_duration(eta)} | "
              f"User: {stats['exists']:,} | Treffer: {stats['found']} | "
              f"Fehler: {stats['errors']} | Rate-Limits: {_rate_limited}".replace(",", "'"),
              flush=True)

    last_done = start - 1
    try:
        with ThreadPoolExecutor(max_workers=args.workers) as pool:
            chunk_start = start
            while chunk_start <= args.end:
                # Block endet spaetestens beim naechsten Status-Punkt, damit der Status puenktlich kommt
                chunk_end = min(chunk_start + args.chunk - 1, args.end, next_status)
                hits = [h for h in pool.map(check, range(chunk_start, chunk_end + 1)) if h]
                for member_id, name in hits:
                    writer.writerow([member_id, name, PROFILE_URL.format(id=member_id)])
                    print(f"[+] {member_id}: {name}", flush=True)
                stats["found"] += len(hits)
                out.flush()
                with open(progress_file, "w") as f:
                    f.write(str(chunk_end))
                last_done = chunk_end
                chunk_start = chunk_end + 1

                if chunk_end >= next_status and chunk_end < args.end:
                    status(chunk_end)
                    next_status = start - 1 + next(ms)
    except KeyboardInterrupt:
        print(f"\nAbgebrochen bei ID {last_done} – Fortschritt gespeichert, "
              f"einfach denselben Befehl nochmal starten zum Fortsetzen.")
        os._exit(1)  # nicht auf die restlichen laufenden Requests warten
    finally:
        out.close()

    status(args.end, final=True)
    print(f"{stats['found']} Treffer in {args.out}")


if __name__ == "__main__":
    main()
