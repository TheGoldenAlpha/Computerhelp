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
    python3 gb_alpha_scan.py                       # IDs 1..700000
    python3 gb_alpha_scan.py --start 1 --end 50000 # Teilbereich
    python3 gb_alpha_scan.py --workers 16          # schneller (vorsichtig!)
    python3 gb_alpha_scan.py --strict              # nur Name beginnt mit "The" und endet mit "Alpha"

Abbrechen mit Strg+C ist ok: Der Fortschritt wird in einer .progress-Datei
gespeichert und beim naechsten Start automatisch fortgesetzt.
"""

import argparse
import csv
import json
import os
import re
import sys
import threading
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor

API_URL = "https://gamebanana.com/apiv11/Member/{id}/ProfilePage"
PROFILE_URL = "https://gamebanana.com/members/{id}"
USER_AGENT = "gb-alpha-scan/1.0 (name search script)"

# "The" ... "Alpha" irgendwo im Namen (auch ohne Leerzeichen, z. B. "TheGoldenAlpha")
LOOSE_RE = re.compile(r"the.*alpha", re.IGNORECASE)
# Name beginnt mit "The" und endet mit "Alpha"
STRICT_RE = re.compile(r"^\s*the\b.*\balpha\s*$", re.IGNORECASE)


def fetch_name(member_id, timeout, max_retries):
    """Gibt den Namen zurueck, None wenn es die ID nicht gibt."""
    url = API_URL.format(id=member_id)
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT, "Accept": "application/json"})
    delay = 2.0
    for attempt in range(max_retries + 1):
        try:
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                data = json.loads(resp.read().decode("utf-8", "replace"))
                return data.get("_sName")
        except urllib.error.HTTPError as e:
            if e.code in (404, 410):
                return None  # ID existiert nicht / geloescht
            if e.code in (429, 500, 502, 503, 504) and attempt < max_retries:
                retry_after = e.headers.get("Retry-After")
                wait = float(retry_after) if retry_after and retry_after.isdigit() else delay
                time.sleep(wait)
                delay = min(delay * 2, 60)
                continue
            return None
        except (urllib.error.URLError, TimeoutError, ConnectionError, json.JSONDecodeError):
            if attempt < max_retries:
                time.sleep(delay)
                delay = min(delay * 2, 60)
                continue
            raise
    return None


def main():
    p = argparse.ArgumentParser(description="Sucht GameBanana-User mit 'The ... Alpha' im Namen.")
    p.add_argument("--start", type=int, default=1)
    p.add_argument("--end", type=int, default=700_000)
    p.add_argument("--workers", type=int, default=8, help="parallele Requests (Standard: 8)")
    p.add_argument("--out", default="alpha_users.csv")
    p.add_argument("--strict", action="store_true", help="nur 'The ... Alpha' ohne etwas davor/danach")
    p.add_argument("--timeout", type=float, default=15)
    p.add_argument("--retries", type=int, default=5)
    p.add_argument("--chunk", type=int, default=2000, help="IDs pro Block (Fortschritt wird pro Block gespeichert)")
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

    new_file = not os.path.exists(args.out)
    out = open(args.out, "a", newline="", encoding="utf-8")
    writer = csv.writer(out)
    if new_file:
        writer.writerow(["id", "name", "profil"])
        out.flush()

    lock = threading.Lock()
    stats = {"checked": 0, "found": 0, "errors": 0}
    t0 = time.time()
    total = args.end - start + 1

    def check(member_id):
        try:
            name = fetch_name(member_id, args.timeout, args.retries)
        except Exception:
            with lock:
                stats["errors"] += 1
                print(f"\n[!] ID {member_id} fehlgeschlagen", file=sys.stderr)
            return None
        with lock:
            stats["checked"] += 1
        if name and pattern.search(name):
            return (member_id, name)
        return None

    try:
        with ThreadPoolExecutor(max_workers=args.workers) as pool:
            for chunk_start in range(start, args.end + 1, args.chunk):
                chunk_end = min(chunk_start + args.chunk - 1, args.end)
                hits = [h for h in pool.map(check, range(chunk_start, chunk_end + 1)) if h]
                for member_id, name in hits:
                    writer.writerow([member_id, name, PROFILE_URL.format(id=member_id)])
                    print(f"\n[+] {member_id}: {name}")
                stats["found"] += len(hits)
                out.flush()
                with open(progress_file, "w") as f:
                    f.write(str(chunk_end))

                done = chunk_end - start + 1
                rate = done / max(time.time() - t0, 1e-6)
                eta = (total - done) / rate if rate else 0
                print(f"\r{chunk_end}/{args.end}  {done / total:6.2%}  "
                      f"{rate:6.1f} IDs/s  ETA {eta / 3600:5.1f}h  "
                      f"Treffer: {stats['found']}  Fehler: {stats['errors']}   ", end="", flush=True)
    except KeyboardInterrupt:
        print("\nAbgebrochen – Fortschritt gespeichert, einfach neu starten zum Fortsetzen.")
    finally:
        out.close()

    print(f"\nFertig. {stats['found']} Treffer in {args.out}")


if __name__ == "__main__":
    main()
