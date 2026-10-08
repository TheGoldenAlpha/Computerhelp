# Computerhelp

## GoBattle-Scan (`alpha_scan.py`)

Sucht alle GoBattle-Spieler mit einem Namen nach dem Muster "The ... Alpha".
Das Script braucht nur Python und Git, es muss nichts installiert werden.

### Starten

```
python alpha_scan.py
```

Das Script macht alles selbst:

1. Beim Start holt es mit `git pull` den neuesten Stand und macht bei der letzten gespeicherten ID weiter.
2. Nach jedem kleinen Stück (20 IDs) speichert es die letzte fertige ID in eine Datei.
3. Alle 30 Sekunden sichert es den Ordner `scan` per Git (`commit`, `pull`, `push`).

Wenn du aufhörst (Strg+C, Neustart, Stromausfall), startest du es einfach wieder, auch auf einem anderen Rechner.
Bei einem Stromausfall gehen höchstens die IDs der letzten 30 Sekunden verloren, die werden dann nochmal geprüft.

### Was im Ordner `scan` liegt

Pro Bereich gibt es einen Satz Dateien. Der Bereich steht im Dateinamen, z. B. `1-700000`.

| Datei | Inhalt |
|---|---|
| `progress_1-700000.txt` | die letzte fertig geprüfte ID |
| `log_1-700000.txt` | alles, was auch in der Shell steht, mit Uhrzeit |
| `alpha_users_1-700000.csv` | die Treffer (ID, Name, Profil-Link) |
| `failed_1-700000.txt` | IDs, die nicht abgefragt werden konnten (nur wenn es welche gab) |

### Auf mehreren Rechnern gleichzeitig (z. B. Firma und zuhause)

Jeder Rechner bekommt einen eigenen Bereich, sonst prüfen beide dieselben IDs:

```
# zuhause
python alpha_scan.py --start 1 --end 350000

# in der Firma
python alpha_scan.py --start 350001 --end 700000
```

Das Rate-Limit von GoBattle gilt pro IP-Adresse. Jeder Rechner hält sich einzeln daran: Das Script schickt die Anfragen in gleichmässigem Abstand. Der Abstand wird nach einem 503 länger und nach jeder erfolgreichen Antwort leicht kürzer. So bleibt das Tempo knapp unter dem Limit, mit nur wenigen Ablehnungen (in der Statuszeile steht der aktuelle `Abstand`).

### Weitere Optionen

| Option | Wirkung |
|---|---|
| `--test 12345` | nur eine ID abfragen und die Antwort zeigen |
| `--strict` | nur Namen, die mit "The" beginnen und mit "Alpha" enden |
| `--no-git` | nichts per Git holen oder sichern, nur lokal speichern |
| `--status-interval 60` | Status und Git-Sicherung alle 60 statt 30 Sekunden |

### Einfache Version zum Selberbauen (`alpha_scan_simple.py`)

Gleiche Dateien und gleicher Fortschritt wie `alpha_scan.py`, aber ohne automatische Tempo-Anpassung.
Das Rate-Limit baust du selbst in zwei Funktionen ein, im Script mit `RATE-LIMIT` markiert:
`limit_before_request()` (vor jeder Anfrage) und `limit_after_response()` (nach jeder Antwort).
Die Anfragen laufen über `requests` (pro Thread eine eigene Session, siehe `_session()`).
Antworten mit HTTP 503 werden gezählt, geloggt, bis zu 3 Mal wiederholt und danach in `failed_*.txt` notiert.
Mit `--retry-failed` werden diese IDs später nochmal geprüft.

```
pip install requests   # einmalig
python alpha_scan_simple.py --delay 1.2
```

### Automatisch starten (`start_scan.bat`)

Doppelklick oder in der Eingabeaufforderung `start_scan.bat`. Die Datei holt vor jedem Start per Git die neueste
Version, installiert `requests` falls nötig, startet `alpha_scan_simple.py` und startet es nach einem Absturz nach
30 Sekunden automatisch neu. Bei Strg+C, bei einem Fehler, den ein Neustart nicht löst, und am Ende hält sie an.
