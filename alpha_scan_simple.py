#!/usr/bin/env python3
"""
alpha_scan_simple.py – einfache Version des GoBattle-Scans
============================================================

Dieses Script sucht alle GoBattle-Spieler, deren Name zum Muster "The ... Alpha" passt.
Es ist die EINFACHE Variante von alpha_scan.py. Die Anfragen laufen über die Bibliothek "requests"
(einmal installieren:  pip install requests). Jeder Thread hat seine eigene requests.Session:
siehe _session() weiter unten.

* Der Abstand zwischen zwei Anfragen tastet sich vorsichtig an das Limit heran (siehe "RATE-LIMIT" unten).
  Mit  --fixed  bleibt er fest bei --delay.
* Das Rate-Limit sitzt an genau zwei Stellen (siehe unten):
      >>> STELLE 1:  limit_before_request()      (vor jeder Anfrage)
      >>> STELLE 2:  limit_after_response(...)   (nach jeder Antwort)
  Beide Stellen sind im Code mit  ##### RATE-LIMIT  markiert. Suche im Script nach "RATE-LIMIT".
* Fehler wie HTTP 503 (= Rate-Limit von GoBattle) werden trotzdem aufgenommen: gezählt, ins Log
  geschrieben, dieselbe ID wird einige Male nochmal versucht, und wenn es nicht klappt, steht
  die ID in scan/failed_<Bereich>.txt. Mit  --retry-failed  kannst du diese IDs später nochmal prüfen.

Wichtig zum Limit: GoBattle begrenzt pro IP-Adresse (Antwort 503, Header x-rate-limit-key = deine IP).
Halte dich an dieses Limit. Keine Proxys, keine IP-Wechsel, nicht über den Selah-Worker-Proxy abfragen.

Aufruf
------
    python alpha_scan_simple.py                       # macht bei der letzten gespeicherten ID weiter
    python alpha_scan_simple.py --test 12345          # nur eine ID abfragen und die Antwort zeigen
    python alpha_scan_simple.py --fixed               # Abstand nicht anpassen, immer --delay (Standard 1.5 s)
    python alpha_scan_simple.py --retry-failed        # nur die IDs aus failed_*.txt nochmal prüfen
    python alpha_scan_simple.py --no-git              # ohne Git, nur lokal speichern
    python alpha_scan_simple.py --start 1 --end 350000   # Teilbereich (z. B. für einen zweiten Rechner)

Dateien (Ordner "scan", gleiche Dateien wie bei alpha_scan.py, man kann also nahtlos wechseln)
---------------------------------------------------------------------------------------------
    scan/progress_<Bereich>.txt     letzte fertig geprüfte ID
    scan/log_<Bereich>.txt          alles, was in der Shell steht, mit Uhrzeit
    scan/alpha_users_<Bereich>.csv  die Treffer (id, name, profil)
    scan/failed_<Bereich>.txt       IDs, die auch nach Wiederholungen nicht abgefragt werden konnten

Ablauf im Überblick (wo passiert was?)
--------------------------------------
    main()
      1. git pull holen                                    -> git_pull()
      2. letzte gespeicherte ID lesen                      -> read_progress()
      3. IDs in kleinen Stücken (--chunk, 20 IDs) abarbeiten
            für jede ID:  check()  ->  fetch_name()
                                          ├─ limit_before_request()      <<< RATE-LIMIT STELLE 1
                                          ├─ HTTP-Anfrage
                                          ├─ limit_after_response()      <<< RATE-LIMIT STELLE 2
                                          └─ 200 = Name, 404 = gibt es nicht,
                                             503 = Rate-Limit (zählen, loggen, nochmal versuchen),
                                             anderer Fehler = nochmal versuchen, sonst failed_*.txt
      4. nach jedem Stück: Treffer in die CSV, dann Fortschritt speichern   -> write_progress()
      5. alle 30 Sekunden: Status ausgeben und per Git sichern             -> git_sync()
"""

import argparse
import csv
import json
import os
import re
import socket
import subprocess
import sys
import threading
import time
from concurrent.futures import ThreadPoolExecutor

try:
    import requests
except ImportError:
    print("Die Bibliothek 'requests' fehlt. Einmal installieren mit:  python -m pip install requests")
    sys.exit(2)

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

API_URL = "https://alpha.gobattle.io/api.php/api/profile/{id}"
NAME_FIELD = "user.nick"  # Pfad zum Namen in der JSON-Antwort
USER_AGENT = "Mozilla/5.0 (alpha-name-scan)"

# "The" ... "Alpha" irgendwo im Namen (auch ohne Leerzeichen, z. B. "TheGoldenAlpha")
LOOSE_RE = re.compile(r"the.*alpha", re.IGNORECASE)
# Name beginnt mit "The" und endet mit "Alpha"
STRICT_RE = re.compile(r"^\s*the\b.*\balpha\s*$", re.IGNORECASE)

# werden in main() gesetzt
MAX_503_RETRIES = 3  # so oft wird dieselbe ID bei HTTP 503 nochmal versucht, danach -> failed_*.txt


# =====================================================================================
# ##### RATE-LIMIT  –  HIER STEHT DAS LIMIT  #####
# =====================================================================================
#
# Es gibt zwei Stellen. Beide werden von JEDEM Thread aufgerufen (standardmässig 2 Threads,
# siehe --workers). Gemeinsamer Zustand liegt in den Variablen direkt unter diesem Kommentar
# und wird mit _limit_lock geschützt.
#
# Was wir über das Limit von GoBattle wissen (gemessen, nicht offiziell):
#   * Es gilt pro IP-Adresse (Header x-rate-limit-key). Zu schnell = HTTP 503.
#   * Der Server schickt keine Angabe, wie viel noch erlaubt ist, und keine Wartezeit.
#   * Es verhält sich wie ein Eimer, in den dauernd Tropfen fallen, mit einem Vorrat von ca. 15 bis 20
#     Anfragen. Die Tropfenrate (= das Dauer-Limit) ist nicht festgelegt und war zu verschiedenen Zeiten
#     verschieden: 0.5 bis 0.67 IDs/s und mehr. Darum wird sie hier nicht fest eingestellt, sondern gesucht.
#
# Was das Limit unten tut (Abstand zwischen zwei Anfragen, "vorsichtig herantasten"):
#   * Start mit dem Abstand --delay (1.5 s).
#   * Gab es --probe-seconds lang (30 s) keine 503, wird der Abstand um 3 % kürzer (nie unter --min-delay).
#     Die Wartezeit ist absichtlich lang: Ein Eimer leert sich langsam, eine zu schnelle Stufe zeigt sich
#     erst nach Minuten. Wer zu schnell stufenweise kürzt, würde das Limit überschiessen.
#   * Bei einer 503: Abstand 5 % länger (mehrere gleichzeitige 503 zählen einmal), eine Abkühlpause von
#     einem Abstand, und der Probe-Timer startet neu. Nie über --max-delay.
#   * Mit --fixed wird alles abgeschaltet: fester Abstand --delay, wie in der Version vorher.
#   * Das ist ein sauberes Einhalten des Limits, kein Umgehen: Bei 503 wird das Script langsamer.
# -------------------------------------------------------------------------------------

_limit_lock = threading.Lock()
_interval = 1.5        # aktueller Abstand zwischen zwei Anfragen in Sekunden (wird in main() gesetzt)
_min_interval = 1.0    # kürzester erlaubter Abstand (--min-delay)
_max_interval = 2.2    # längster erlaubter Abstand (--max-delay)
_probe_seconds = 30.0  # so lange ohne 503, bevor der Abstand kürzer wird (--probe-seconds)
_fixed = False         # True = nichts anpassen (--fixed)
_next_time = 0.0       # frühester Zeitpunkt (time.monotonic()), zu dem die nächste Anfrage starten darf
_last_change = 0.0     # wann der Abstand zuletzt geändert wurde (länger oder kürzer)
_last_raise = 0.0      # wann der Abstand zuletzt wegen einer 503 verlängert wurde


def limit_interval():
    """Aktueller Abstand (für die Statuszeile)."""
    return _interval


def limit_before_request():
    """##### RATE-LIMIT STELLE 1: Wird VOR JEDER Anfrage aufgerufen.

    Aufgabe: warten, bis die nächste Anfrage erlaubt ist. Kehrt die Funktion zurück, geht die
    Anfrage sofort raus.

    Jeder Thread reserviert sich hier einen Startzeitpunkt (_next_time) und wartet bis dahin.
    So haben alle Anfragen zusammen, egal wie viele Threads laufen, den Abstand _interval.
    Das Warten selbst passiert ausserhalb des Locks, damit sich die Threads nicht gegenseitig bremsen."""
    global _next_time, _last_change
    with _limit_lock:
        now = time.monotonic()
        if _last_change == 0.0:
            _last_change = now  # Probe-Timer startet mit der ersten Anfrage
        start = max(now, _next_time)
        _next_time = start + _interval
    if start > now:
        time.sleep(start - now)


def limit_after_response(status, headers):
    """##### RATE-LIMIT STELLE 2: Wird NACH JEDER Antwort aufgerufen (auch bei 503).

    status:  HTTP-Statuscode (200, 404, 503, ...)
    headers: Liste von (Name, Wert), z. B. [("x-rate-limit-key", "212.41.217.65"), ...]

    503 (oder 429) heisst "zu schnell": Abstand verlängern und kurz abkühlen.
    Alles andere heisst "durchgekommen": Läuft es schon lange ohne 503, vorsichtig kürzer werden.
    Das Wiederholen der ID bei 503 erledigt fetch_name() selbst."""
    global _interval, _next_time, _last_change, _last_raise
    if _fixed:
        return
    with _limit_lock:
        now = time.monotonic()
        if status in (429, 503):
            # Mehrere 503 von Anfragen, die schon unterwegs waren, zählen nur einmal
            if now - _last_raise > _interval:
                _interval = min(_max_interval, _interval * 1.05)
                _last_raise = now
                _last_change = now
            # Abkühlpause: die nächste Anfrage kommt erst nach einem vollen Abstand
            _next_time = max(_next_time, now + _interval)
        elif now - _last_change >= _probe_seconds and _interval > _min_interval:
            _interval = max(_min_interval, _interval * 0.97)
            _last_change = now


# =====================================================================================
# Log: alles, was in der Shell steht, kommt zusätzlich mit Uhrzeit in eine Textdatei
# =====================================================================================
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


# =====================================================================================
# Zähler für den Status (Fehler wie 503 werden hier aufgenommen)
# =====================================================================================
_stat_lock = threading.Lock()
_codes = {}          # HTTP-Statuscode -> Anzahl, z. B. {200: 13, 404: 22, 503: 12}
_rate_limited = 0    # Anzahl 503/429-Antworten
_net_errors = 0      # Timeouts / abgebrochene Verbindungen
_first_503_seen = threading.Event()
_local = threading.local()  # pro Thread eine eigene requests.Session


def _count_code(code):
    with _stat_lock:
        _codes[code] = _codes.get(code, 0) + 1


# =====================================================================================
# HTTP (requests)
# =====================================================================================
def _session():
    """Die requests.Session DIESES Threads (wird beim ersten Aufruf angelegt).

    Pro Thread gibt es eine eigene Session, weil eine Session nicht dafür gebaut ist, von mehreren
    Threads gleichzeitig benutzt zu werden. Sie hält die Verbindung offen (Keep-Alive), das spart
    bei jeder Anfrage den neuen TLS-Handshake.
    Für das Rate-Limit brauchst du die Session nicht: sie ist hier nur für das Senden da."""
    sess = getattr(_local, "session", None)
    if sess is None:
        sess = requests.Session()
        sess.headers.update({"User-Agent": USER_AGENT, "Accept": "application/json"})
        _local.session = sess
    return sess


def _reset_session():
    """Session schliessen und beim nächsten Aufruf neu anlegen (nach 503 oder Verbindungsproblem)."""
    sess = getattr(_local, "session", None)
    if sess is not None:
        sess.close()
    _local.session = None


def _get_path(data, dotted):
    for part in dotted.split("."):
        if isinstance(data, list):
            data = data[int(part)] if part.isdigit() and int(part) < len(data) else None
        elif isinstance(data, dict):
            data = data.get(part)
        else:
            return None
    return data


def extract_name(data):
    v = _get_path(data, NAME_FIELD)
    return v if isinstance(v, str) and v.strip() else None


def fetch_name(member_id, timeout, max_retries):
    """Fragt eine ID ab. Gibt den Namen zurück, None wenn es die ID nicht gibt.

    Bei einer unklaren Antwort wird eine Ausnahme geworfen: Die ID zählt dann als
    fehlgeschlagen und wird in failed_*.txt notiert (statt still als "gibt es nicht" zu gelten)."""
    global _rate_limited, _net_errors
    url = API_URL.replace("{id}", str(member_id))
    tries_503 = 0
    tries_other = 0
    delay = 1.0
    while True:
        limit_before_request()                      # <<< RATE-LIMIT STELLE 1 (vor der Anfrage)
        try:
            resp = _session().get(url, timeout=timeout)
        except requests.RequestException:
            # Timeout oder Verbindung abgebrochen
            _reset_session()
            with _stat_lock:
                _net_errors += 1
            if tries_other < max_retries:
                tries_other += 1
                time.sleep(delay)
                delay = min(delay * 2, 10)
                continue
            raise

        status = resp.status_code
        _count_code(status)
        limit_after_response(status, list(resp.headers.items()))   # <<< RATE-LIMIT STELLE 2 (nach der Antwort)

        if status in (429, 503):
            # GoBattle meldet sein Rate-Limit mit 503. Das ist KEIN Fehler der ID: zählen,
            # beim ersten Mal die Header zeigen, dieselbe ID nochmal versuchen.
            with _stat_lock:
                _rate_limited += 1
            if not _first_503_seen.is_set():
                _first_503_seen.set()
                info = ", ".join(f"{k}: {v}" for k, v in resp.headers.items()
                                 if k.lower() == "retry-after" or "rate" in k.lower())
                log(f"[i] Erstes Rate-Limit (HTTP {status}). Header vom Server: {info or 'keine Limit-Angaben'}")
            _reset_session()  # frische Verbindung, falls der Server die alte hängen lässt
            tries_503 += 1
            if tries_503 > MAX_503_RETRIES:
                # aufgeben: die ID kommt in failed_*.txt und kann später mit --retry-failed geprüft werden
                raise RuntimeError(f"HTTP {status} (Rate-Limit), {MAX_503_RETRIES} Wiederholungen erfolglos")
            continue

        if status == 200:
            try:
                data = resp.json()
            except ValueError:
                raise RuntimeError("HTTP 200, aber kein JSON")
            return extract_name(data)

        if status in (404, 410):
            return None  # ID existiert nicht / gelöscht

        # alles andere (z. B. 500): kurz wiederholen, danach als fehlgeschlagen melden
        if tries_other < max_retries:
            tries_other += 1
            time.sleep(delay)
            delay = min(delay * 2, 10)
            continue
        raise RuntimeError(f"HTTP {status}")


def run_test(member_id, timeout):
    """Eine ID abfragen und alles zeigen (prüft URL und Namensfeld)."""
    url = API_URL.replace("{id}", str(member_id))
    print(f"GET {url}")
    resp = _session().get(url, timeout=timeout)
    print(f"HTTP {resp.status_code} {resp.reason}")
    print("Antwort (Anfang):")
    print(resp.text[:1500])
    try:
        name = extract_name(resp.json())
    except ValueError:
        print("\n[!] Antwort ist kein JSON.")
        return
    print(f"\n[OK] Gefundener Name: {name!r}" if name else "\n[!] Kein Name gefunden.")


# =====================================================================================
# Fortschritt speichern und per Git sichern
# =====================================================================================
_git_ident = []  # wird gesetzt, wenn auf dem Rechner kein Git-Name/E-Mail eingestellt ist


def _git(*args, timeout=120):
    """Git im Ordner dieses Scripts ausführen. Gibt immer ein Ergebnis zurück (nie eine Ausnahme)."""
    try:
        return subprocess.run(["git", "-C", SCRIPT_DIR] + _git_ident + list(args), capture_output=True,
                              text=True, encoding="utf-8", errors="replace", timeout=timeout)
    except (OSError, subprocess.TimeoutExpired) as e:
        return subprocess.CompletedProcess(args, 255, "", str(e))


def _err(r):
    return ((r.stderr or "") + (r.stdout or "")).strip().replace("\n", " ")[:300]


def git_branch():
    """Name des aktuellen Branches oder None (kein Git-Ordner)."""
    r = _git("rev-parse", "--abbrev-ref", "HEAD")
    name = r.stdout.strip()
    if r.returncode != 0 or not name or name == "HEAD":
        return None
    if not _git("config", "user.email").stdout.strip():
        _git_ident[:] = ["-c", "user.name=alpha-scan", "-c", "user.email=alpha-scan@localhost"]
    return name


def git_pull(branch):
    with _log_lock:
        r = _git("pull", "--rebase", "--autostash", "-q", "origin", branch)
        if r.returncode != 0:
            _git("rebase", "--abort")
            return False, _err(r)
    return True, "ok"


def git_sync(branch, msg):
    """Ordner scan sichern: add, commit, push.

    Normalfall (nur ein Rechner schreibt): Das geht schnell und braucht keinen Pull.
    Nur wenn der Push abgelehnt wird (z. B. weil ein anderer Rechner neuer gepusht hat),
    wird zuerst der neueste Stand geholt (pull --rebase) und nochmal gepusht.
    Es werden nur *.txt und *.csv gesichert, damit nie eine halbfertige .tmp-Datei in Git landet."""
    with _log_lock:  # kurz: währenddessen schreibt kein anderer Thread in die Dateien
        _git("add", "--", "scan/*.txt")
        _git("add", "--", "scan/*.csv")
        if _git("diff", "--cached", "--quiet", "--", "scan").returncode == 1:
            r = _git("commit", "-q", "-m", msg, "--", "scan")
            if r.returncode != 0:
                return False, "commit: " + _err(r)
    # Push ohne Lock: er fasst die Dateien im Ordner nicht an, der Scan läuft währenddessen weiter
    r = _git("push", "-q", "origin", f"HEAD:{branch}")
    if r.returncode == 0:
        return True, "ok"
    for _ in range(3):
        with _log_lock:  # Pull mit autostash fasst die Dateien an, darum jetzt mit Lock
            r = _git("pull", "--rebase", "--autostash", "-q", "origin", branch)
            if r.returncode != 0:
                _git("rebase", "--abort")
                return False, "pull: " + _err(r)
        r = _git("push", "-q", "origin", f"HEAD:{branch}")
        if r.returncode == 0:
            return True, "ok"
    return False, "push: " + _err(r)


def read_progress(path):
    """Letzte gespeicherte ID. 0, wenn es die Datei noch nicht gibt.

    Ist die Datei da, aber leer oder kaputt (z. B. nach einem Stromausfall), bricht das Script ab,
    statt still bei ID 1 neu anzufangen."""
    try:
        with open(path, encoding="utf-8") as f:
            text = f.read().strip()
    except FileNotFoundError:
        return 0
    try:
        return int(text)
    except ValueError:
        print(f"[!] Die Fortschrittsdatei {path} ist leer oder kaputt (Stromausfall?).\n"
              f"    Stelle die letzte gesicherte Version aus Git wieder her mit:\n"
              f"        git checkout -- scan\n"
              f"    und starte das Script danach nochmal.")
        sys.exit(2)  # 2 = Fehler, den ein automatischer Neustart nicht löst


def write_progress(path, value):
    """Atomar und dauerhaft schreiben: erst temporäre Datei, auf die Festplatte zwingen (fsync),
    dann ersetzen. So bleibt nach einem Absturz oder Stromausfall die alte oder die neue Version
    ganz erhalten, nie eine leere."""
    tmp = path + ".tmp"
    with open(tmp, "w", encoding="utf-8", newline="\n") as f:
        f.write(f"{value}\n")
        f.flush()
        os.fsync(f.fileno())
    os.replace(tmp, path)


def fmt_duration(seconds):
    seconds = int(seconds)
    h, rest = divmod(seconds, 3600)
    m, s = divmod(rest, 60)
    return f"{h}h {m:02d}m" if h else f"{m}m {s:02d}s"


def n(x):
    return f"{x:,}".replace(",", "'")


# =====================================================================================
# main
# =====================================================================================
def main():
    global API_URL, MAX_503_RETRIES, _interval, _min_interval, _max_interval, _probe_seconds, _fixed
    p = argparse.ArgumentParser(description="Sucht User mit 'The ... Alpha' im Namen (einfache Version).")
    p.add_argument("--test", type=int, metavar="ID", help="nur diese eine ID abfragen und die Antwort zeigen")
    p.add_argument("--start", type=int, default=1)
    p.add_argument("--end", type=int, default=700_000)
    p.add_argument("--workers", type=int, default=2, help="parallele Anfragen (Standard: 2)")
    p.add_argument("--delay", type=float, default=1.5,
                   help="Start-Abstand in Sekunden zwischen zwei Anfragen (Standard: 1.5 = 0.67 IDs/s). "
                        "Er passt sich selbst an, ausser mit --fixed.")
    p.add_argument("--min-delay", type=float, default=1.0,
                   help="kürzester Abstand, auf den das Script beim Herantasten gehen darf (Standard: 1.0)")
    p.add_argument("--max-delay", type=float, default=2.2,
                   help="längster Abstand, auf den das Script nach vielen 503 gehen darf (Standard: 10)")
    p.add_argument("--probe-seconds", type=float, default=30.0,
                   help="so viele Sekunden ohne 503, bevor der Abstand um 3 %% kürzer wird (Standard: 30)")
    p.add_argument("--fixed", action="store_true",
                   help="Abstand nicht anpassen, immer --delay (wie die Version vorher)")
    p.add_argument("--retries-503", type=int, default=3,
                   help="so oft dieselbe ID bei HTTP 503 nochmal versucht wird (Standard: 3)")
    p.add_argument("--retries", type=int, default=3, help="Wiederholungen bei Serverfehlern/Timeouts (Standard: 3)")
    p.add_argument("--timeout", type=float, default=15)
    p.add_argument("--chunk", type=int, default=20,
                   help="IDs pro Stück; nach jedem Stück wird der Fortschritt gespeichert (Standard: 20)")
    p.add_argument("--status-interval", type=float, default=30,
                   help="Status und Git-Sicherung alle N Sekunden (Standard: 30)")
    p.add_argument("--max-minutes", type=float, default=0,
                   help="nach so vielen Minuten sauber aufhören (Fortschritt wird gespeichert und gesichert). "
                        "0 = unbegrenzt (Standard). Praktisch zum Testen verschiedener --delay-Werte.")
    p.add_argument("--strict", action="store_true", help="nur 'The ... Alpha' ohne etwas davor/danach")
    p.add_argument("--retry-failed", action="store_true",
                   help="nur die IDs aus scan/failed_<Bereich>.txt nochmal prüfen (Fortschritt bleibt unverändert)")
    p.add_argument("--no-git", action="store_true", help="nichts per Git holen oder sichern, nur lokal speichern")
    p.add_argument("--url", default=API_URL, help="API-URL mit {id} (Standard: GoBattle-Profil-API)")
    args = p.parse_args()

    for stream in (sys.stdout, sys.stderr):
        try:
            stream.reconfigure(errors="replace")  # Namen mit Sonderzeichen sollen die Shell nicht abstürzen lassen
        except (AttributeError, ValueError):
            pass

    if "{id}" not in args.url:
        p.error("--url muss {id} enthalten")
    API_URL = args.url
    _interval = args.delay
    _min_interval = min(args.min_delay, args.delay)
    _max_interval = max(args.max_delay, args.delay)
    _probe_seconds = args.probe_seconds
    _fixed = args.fixed
    MAX_503_RETRIES = args.retries_503

    if args.test is not None:
        run_test(args.test, args.timeout)
        return
    pattern = STRICT_RE if args.strict else LOOSE_RE

    # 1. Neuesten Stand aus Git holen, BEVOR eigene Dateien angelegt werden
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
    log(f"=== Start (einfache Version) auf {socket.gethostname()} | Bereich {tag} | Python {sys.version.split()[0]} ===")

    # 3. Welche IDs sind dran?
    if args.retry_failed:
        try:
            with open(failed_file, encoding="utf-8") as f:
                todo = sorted({int(x) for x in f.read().split() if x.strip().isdigit()})
        except OSError:
            todo = []
        if not todo:
            log("Keine fehlgeschlagenen IDs vorhanden.")
            return
        log(f"Prüfe {len(todo)} fehlgeschlagene IDs nochmal")
        start_id = todo[0]
    else:
        start_id = args.start
        saved = read_progress(progress_file)
        if saved >= start_id:
            start_id = saved + 1
            log(f"Setze fort ab ID {start_id} (letzte gespeicherte ID: {saved})")
        if start_id > args.end:
            log("Bereich ist schon komplett gescannt.")
            return
        todo = range(start_id, args.end + 1)

    new_file = not os.path.exists(hits_file)
    out = open(hits_file, "a", newline="", encoding="utf-8")
    writer = csv.writer(out)
    if new_file:
        writer.writerow(["id", "name", "profil"])
        out.flush()

    lock = threading.Lock()
    stats = {"found": 0, "errors": 0, "exists": 0, "done": 0}
    t0 = time.time()
    total = len(todo)
    sync_state = {"text": "-"}
    saved_up_to = {"id": start_id - 1}
    last = {"t": t0, "done": 0}
    still_failed = []  # nur für --retry-failed

    log(f"Scanne {n(total)} IDs ab {start_id} mit {args.workers} parallelen Anfragen "
        f"(Start-Abstand {args.delay:g}s{', fest' if args.fixed else ', passt sich an'}), Status und Git-Sicherung alle {args.status_interval:g} Sekunden")

    def check(member_id):
        """Gibt ('hit', id, name), ('fail', id, grund) oder None zurück."""
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

    def status(final=False):
        now = time.time()
        done = stats["done"]
        elapsed = now - t0
        rate = (done - last["done"]) / max(now - last["t"], 1e-6)  # Tempo der letzten Periode
        avg = done / max(elapsed, 1e-6)
        last.update(t=now, done=done)
        eta = fmt_duration((total - done) / avg) if avg > 0 else "?"
        log(f"[{'FERTIG' if final else 'STATUS'}] bis ID {n(saved_up_to['id'])} | {done}/{n(total)} ({done / total:.1%}) | "
            f"{rate:.2f} IDs/s (Schnitt {avg:.2f}) | Abstand {limit_interval():.2f}s | Laufzeit {fmt_duration(elapsed)} | Rest ca. {eta} | "
            f"User: {n(stats['exists'])} | Treffer: {stats['found']} | Fehler: {stats['errors']} | "
            f"Rate-Limits (503): {n(_rate_limited)} | Timeouts/Verbindungsfehler: {_net_errors} | "
            f"HTTP: {' '.join(f'{k}x{n(v)}' for k, v in sorted(_codes.items())) or '-'} | Git: {sync_state['text']}")

    stop_status = threading.Event()

    def status_loop():
        while not stop_status.wait(args.status_interval):
            status()

    threading.Thread(target=status_loop, daemon=True).start()

    sync_thread = {"t": None}

    def do_sync(reason, wait=False):
        """Git-Sicherung. Läuft im Hintergrund, damit der Scan dabei nicht stillsteht.
        Mit wait=True wird auf das Ende gewartet (beim Beenden)."""
        if branch is None:
            return
        t = sync_thread["t"]
        if t is not None and t.is_alive():
            if not wait:
                return  # die letzte Sicherung läuft noch
            t.join()
        msg = f"Scan {tag}: bis ID {saved_up_to['id']} ({socket.gethostname()}, {reason})"

        def work():
            t_start = time.time()
            ok, text = git_sync(branch, msg)
            sync_state["text"] = f"gesichert ({time.time() - t_start:.1f}s)" if ok else "FEHLER"
            if not ok:
                log(f"[!] Git-Sicherung fehlgeschlagen (der Scan läuft trotzdem weiter und speichert lokal): {text}")

        th = threading.Thread(target=work, daemon=True)
        sync_thread["t"] = th
        th.start()
        if wait:
            th.join()

    # 4. Abarbeiten in kleinen Stücken. Nach jedem Stück: Treffer schreiben, DANN Fortschritt speichern.
    last_sync = time.time()
    ids = list(todo)
    try:
        with ThreadPoolExecutor(max_workers=args.workers) as pool:
            pos = 0
            while pos < len(ids):
                block = ids[pos:pos + args.chunk]
                results = [r for r in pool.map(check, block) if r]
                hits = [r for r in results if r[0] == "hit"]
                fails = [r for r in results if r[0] == "fail"]
                with _log_lock:  # Dateien nur schreiben, wenn Git gerade nicht daran arbeitet
                    for _, member_id, name in hits:
                        writer.writerow([member_id, name, f"https://selahgb.org/search.html?id={member_id}"])
                    if hits:
                        out.flush()
                        os.fsync(out.fileno())
                    if fails and not args.retry_failed:
                        with open(failed_file, "a", encoding="utf-8", newline="\n") as f:
                            for _, member_id, _reason in fails:
                                f.write(f"{member_id}\n")
                    still_failed.extend(m for _, m, _r in fails)
                    for _, member_id, name in hits:
                        log(f"[+] {member_id}: {name}")
                    for _, member_id, reason in fails:
                        log(f"[!] ID {member_id} fehlgeschlagen ({reason})")
                    stats["found"] += len(hits)
                    pos += len(block)
                    if not args.retry_failed:
                        write_progress(progress_file, block[-1])
                    saved_up_to["id"] = block[-1]
                if time.time() - last_sync >= args.status_interval:
                    do_sync("laufend")
                    last_sync = time.time()
                if args.max_minutes and time.time() - t0 >= args.max_minutes * 60:
                    log(f"[i] --max-minutes {args.max_minutes:g} erreicht, höre sauber auf.")
                    break
    except KeyboardInterrupt:
        log(f"Abgebrochen bei ID {saved_up_to['id']} - Fortschritt ist gespeichert, "
            f"einfach denselben Befehl nochmal starten zum Fortsetzen.")
        out.flush()
        try:
            do_sync("abgebrochen", wait=True)
        except KeyboardInterrupt:
            pass
        os._exit(130)  # 130 = vom Benutzer abgebrochen; nicht auf die restlichen laufenden Anfragen warten
    finally:
        out.close()

    if args.retry_failed:
        # Datei neu schreiben: nur IDs behalten, die immer noch nicht klappen
        with open(failed_file, "w", encoding="utf-8", newline="\n") as f:
            for m in still_failed:
                f.write(f"{m}\n")
        log(f"{len(still_failed)} IDs sind weiterhin fehlgeschlagen")

    stop_status.set()
    status(final=True)
    do_sync("fertig", wait=True)
    log(f"{stats['found']} Treffer in dieser Sitzung, alle Treffer: {hits_file}")


if __name__ == "__main__":
    main()
