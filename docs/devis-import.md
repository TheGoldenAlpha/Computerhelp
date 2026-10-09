# Devis-Import (NPK-Leistungsverzeichnis .chd) – Prüfanleitung

Wir sind die IT-Firma und importieren Devis (Leistungsverzeichnisse nach NPK/CRB) als
`.chd`-Datei. Zum Vergleich gibt es meistens das Original-PDF vom Architekten.

## Was geprüft wird – und was nicht

- **Nur Layout-/Importfehler**: alles, wo die `.chd` vom Original-PDF abweicht.
- **Inhalt ist egal**, wenn er im Original-PDF genauso steht (Tippfehler, falsche
  Verweise, unplausible Mengen, Termine usw.). Das ist Sache des Architekten, nicht unsere.
- Bei Unsicherheit: Ist es im PDF auch so? → Ja = ignorieren.

## Antwortformat (Wunsch des Users)

- **Zu jedem Fund immer einen eindeutigen Suchtext angeben**, mit dem der User per Ctrl+F
  direkt hinspringen kann.
- Der Suchtext muss **genau 1× vorkommen** und **auf einer einzigen Zeile** stehen,
  also keine Silbentrennung („lie-/fern“) überspannen. Am besten gilt das im PDF und in der .chd.
- Gibt es keinen eindeutigen Text: die Fortsetzungsnummer oben auf der nächsten PDF-Seite
  nehmen (z. B. `176.300`), die steht nur einmal im PDF.
- Antwort auf Deutsch, kurz, als Tabelle oder Liste: Position | Fehler | Ctrl+F-Text.

## Prüfskript

```
python3 -I tools/chd_check.py DATEI.chd [ORIGINAL.pdf]
```

Das Skript findet alle unten genannten Fehlerarten. Mit PDF vergleicht es zusätzlich alle
Mengen, Einheiten und Positionsnummern und schlägt zu jedem Fund einen Ctrl+F-Text vor.
Danach trotzdem kurz selbst drüberschauen, z. B. Seiten als Bild rendern mit
`pdftoppm -r 60 -png`.

## Aufbau der .chd

- Tab-getrennt, **Latin-1**, CRLF, 42 Spalten pro Zeile. Zeile 1 ist der Kopf (Objektname).
- Wichtige Spalten (1-basiert):
  - 2 = Menge (`0.00` = Textzeile, `per` = nur Einheitspreis)
  - 3 = Einheit
  - 6 = Zeilentyp: `X` Text, `l` Mengenzeile, `/` per-Zeile
  - 7 = Positionsnummer, wie sie angezeigt wird (mit `R` und Einrückung)
  - 10 = Text
  - 28 = NPK-Schlüssel `Kapitel.Pos.Unterpos`, z. B. `181.163.100`
  - 29 = `PNPK`
- Jede Zeile gehört über Spalte 28 zu einer Position. Was in Spalte 10 steht, wird
  Positionstext.

## Typische Importfehler (gefunden beim Devis BKP 421, Bedri GmbH, Okt. 2026)

1. **Seitenfusszeilen im Positionstext**: „Zwischentotal BKP-Nr. … 0.00“ und „Total“ stehen
   als Textzeilen in der .chd (20×) und landen nach dem Import mitten in Positionen.
   Ctrl+F `Zwischentotal` im importierten LV.
2. **Unterpositionsnummer im Text wird als Position gelesen**: Steht im Text am
   Zeilenanfang etwas wie „.340 und .350“, wird `.340` zur Positionsnummer. Alle folgenden
   Unterpositionen bis zur nächsten Hauptposition fallen dann in diese falsche Position.
   Ihre Nummern stehen als Text darin, und die Position hat mehrere Mengen.
   Beispiel: 455.204, .205 und .313 → alle unter „455.340“.
   **Das ist der wichtigste Fehler, weil Mengen an der falschen Position hängen.**
3. **Doppelte Textzeile über Seitenumbruch**: Die letzte Zeile vor dem Seitenumbruch steht
   nach dem Umbruch nochmals da. Beispiel Pos. 425: „U'pos.-Gruppe .400 und für das“.
   Im PDF ist die zweite Zeile unsichtbar im Textlayer, Ctrl+F findet sie aber 2×.
4. **Zeichensatz**: Typografische Anführungszeichen „ “ werden zu `ÿ` (0xFF in Latin-1).
   Beispiele: `ÿRÿ vor der Positionsnummer`, `ÿPflege von Grün- und Freiflächenÿ`.
   Ctrl+F `ÿ`.
5. **Menge auf leerer Textzeile**: Ist die letzte Textzeile zu lang, steht die Menge allein
   auf einer leeren Zeile. Meist harmlos, kann aber eine Leerzeile erzeugen.
6. **Fortsetzungsnummern** wie `163.100` oben auf einer Seite sind normal. Der Schlüssel
   in Spalte 28 stimmt, das ist kein Fehler.
7. **Überschriften allein am Seitenende** (Hurenkinder/Schusterjungen) sind normal beim
   NPK-Druck, kein Fehler.

## Kontrolle, dass sonst alles stimmt

- Anzahl Mengenzeilen in .chd = Anzahl im PDF. Beim Devis BKP 421 waren es 119.
- Jede Menge muss am gleichen NPK-Schlüssel mit gleicher Einheit hängen.
- `R`-Kennzeichnungen (Reservepositionen) müssen gleich sein.
- Den Text normalisiert vergleichen: Trennstriche entfernen, nur Wörter vergleichen.
  Übrig bleiben sollten nur die Fehler oben.
