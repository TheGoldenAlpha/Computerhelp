# Computerhelp – Hinweise für Claude

## Devis-Import (.chd / NPK-Leistungsverzeichnisse)

Wenn ein Devis (.chd und/oder PDF) geprüft werden soll, zuerst `docs/devis-import.md`
lesen und das Skript `tools/chd_check.py` laufen lassen.

Kurzregeln:
- Nur **Layout-/Importfehler** melden. Inhaltliche Fehler, die im Original-PDF
  genauso stehen, sind egal.
- Zu **jedem** Fund einen **eindeutigen Ctrl+F-Suchtext** angeben: genau 1× vorhanden,
  auf einer Zeile, ohne Silbentrennung.
- Wichtigste Fehler: Seitenfuss „Zwischentotal“ im Positionstext, „.340“ im Text wird als
  Position gelesen und verschluckt Unterpositionen, doppelte Zeile am Seitenumbruch,
  `ÿ` statt Anführungszeichen.
- Antworten auf Deutsch, kurz.
