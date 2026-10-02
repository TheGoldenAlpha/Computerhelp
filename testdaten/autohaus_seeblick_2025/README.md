# Testmandant Autohaus Seeblick AG, Geschäftsjahr 2025

Ein komplettes Buchhaltungsjahr für die FIBU (PowerBuilder, PostgreSQL). Es dient zum Prüfen von Eröffnungsbilanz, Journal, Sammelbuchungen, MWST, Bilanz und Erfolgsrechnung.

| Datei | Inhalt |
|---|---|
| `autohaus_seeblick_2025.sql` | Daten zum Einspielen: Mandant, Jahr, Kontenplan, MWST-Codes, EB-Konto, 1'067 Belege mit 4'225 Zeilen |
| `SOLLWERTE.md` | alle erwarteten Resultate: Eröffnungsbilanz, Saldenliste, ER, Bilanz, MWST pro Quartal und Code |
| `pruefabfragen.sql` | SQL zum Nachprüfen direkt in der DB (pgAdmin/psql) |
| `verify_db.py` | automatischer Abgleich DB ↔ Sollwerte (`ALLES OK` oder Liste der Abweichungen) |
| `Kontoplan KMU Autohaus Seeblick.txt` | der verwendete Kontenplan im Import-Format der App (ANSI, Tab), zum Ablegen unter `Files/kontoplan/costum/` |
| `generate.py` | Generator (deterministisch, gleicher Output bei jedem Lauf) |
| `vorlage_kontoplan_kmu_ag.txt` | Quelle: `Files/kontoplan/Kontoplan kmu Juristische Person AG.txt` (als UTF-8) |

## Einspielen

```bash
# Schema muss existieren (Files/db_syntax/postgres/setup.txt)
psql -h localhost -p 5431 -U postgres -d test_ch_migration -f autohaus_seeblick_2025.sql
# optional: automatisch prüfen
python3 verify_db.py "psql -h localhost -p 5431 -U postgres -d test_ch_migration"
```

Das Skript läuft in einer Transaktion. Es löscht zuerst nur die Daten dieses Mandanten (ID `5c2df529-87e5-5cd5-a2b3-19d463057958`) und kann darum beliebig oft eingespielt werden. In der App dann Mandant **Autohaus Seeblick AG** und Jahr **2025** wählen.

## Was drin ist

**Firma:** Autohaus mit den Abteilungen Neuwagen, Occasionen, Werkstatt, Carrosserie/Spritzwerk, Ersatzteillager und Shop/Kiosk, dazu 13 Mitarbeitende. Der Kontenplan ist die KMU-Vorlage für AGs, ergänzt um Abteilungskonten (1201/1202, 3210–3230, 3410/3420, 4210–4230, 5010–5050), PostFinance 1010 und **9100 Eröffnungsbilanz** (EB- und UE-Konto).

**MWST-Codes**, so angelegt wie mit `of_insert_estv_rates` (8.1 / 2.6 / 3.8): V81, M81, I81, V26, M26, I26, V38, M38, I38 und **E0**. Alle 10 Codes werden bebucht.

**Buchungen.** Die Logik ist 1:1 von `of_book_journal` und der Sammelbuchung übernommen: Bruttobetrag, MWST mit `round(betrag * satz / (100 + satz), 2)`, Netto-Zeile mit visible 1, MWST-Zeile mit visible 2 und dem Text `MWST - …`.

- **Eröffnungsbilanz:** 36 Vortragsbuchungen (visible 3, ohne Belegnummer) gegen 9100, inklusive Minuskonten (Delkredere, Wertberichtigungen)
- **Neuwagenverkäufe:** teils mit **Eintausch** (Sammelbuchung Debitor + Occasion an Erlös V81), teils mit Verrechnung von **Anzahlungen** (2030)
- **Occasionen:** Verkauf V81, Ankauf über Auktion M81, Ankauf von Privatpersonen ohne MWST, **Export nach DE mit E0**
- **Werkstatt/Carrosserie:** wöchentliche Sammelbuchung, mehrere Erlöskonten, je auf Rechnung und bar
- **Theke/Shop:** wöchentliche Kassen-Sammelbuchung mit V81 und **V26** (Lebensmittel)
- **Monteurzimmer:** Vermietung mit **V38**. Hotels mit **I38** und **M38**, Fachzeitschriften/Kaffee mit **I26**, Shop-Einkauf mit **M26**
- **Löhne:** monatliche Sammelbuchung mit 5 Abteilungs-Lohnkonten, AN-Abzügen, Quellensteuer (Grenzgänger), Rückzahlung eines Lohnvorschusses und Nettolohn pro Person. Dazu AG-Beiträge, 13. Monatslohn und Verkaufsprovisionen
- **Zahlungsverkehr:** wöchentliche Sammelbuchungen für Zahlungseingänge und Zahlungsläufe mit **Skonto** (4900, M81 → Vorsteuerkorrektur)
- **MWST-Quartalsabrechnung:** Sammelbuchung 2200/1170/1171 → 2201, Zahlung jeweils im 2. Monat danach
- **Sonderfälle:** Gutschrift mit Erlösminderung (V81 im Soll), **Debitorenverlust mit MWST-Korrektur**, Lieferantenbonus (M81 im Haben), Dividende mit **Verrechnungssteuer**, VST-Rückforderung, Darlehensamortisation, Wertschriftenverkauf, Steuern
- **Abschluss 31.12.:** indirekte Abschreibungen, Delkredere 5 %, Bestandesänderungen gemäss Inventur, transitorische Aktiven/Passiven (inklusive Auflösung der Vorjahrespositionen im Januar), Garantierückstellung, Steuerrückstellung

**Ergebnis:** Jahresgewinn **302'347.56**, Bilanzsumme netto **2'650'983.07**. Alle Details stehen in `SOLLWERTE.md`.

## Gegen eine echte PostgreSQL 16 mit `setup.txt` geprüft

Mit `verify_db.py` geprüft, alles `OK`:
- alle 1'067 Belege ausgeglichen, alle Konten existieren und sind buchbar
- `kontosaldo_live_vfibu` und `kontostart_vfibu` stimmen für **jedes** Konto mit den Sollwerten überein, 9100 hat Saldo 0.00
- alle Steuercodes sind gültig nach der Abfrage von `of_get_steuercode_info`
- die MWST-Summen pro Quartal und Code stimmen mit der Zuordnungslogik von `of_get_mwst_zusammenfassung` überein (Netto-Zeile ↔ MWST-Zeile über Text und ROW_NUMBER)
- Gewinn laut ER = Aktiven − Passiven

## Beim Erstellen aufgefallen (App-Code)

1. **Minuskonten in Eröffnungsbilanz und Bilanz.** Die Totale summieren `startsaldo` bzw. `saldo` pro `kontotype` ohne Vorzeichen. Wertberichtigungen und Delkredere (AK mit Kontoseite H) werden deshalb **addiert statt abgezogen**. Eröffnungsbilanz in diesem Mandanten: Total Aktiven roh **2'843'622.60** statt **2'394'622.60**, angezeigte Differenz **449'000.00** statt 0.00. Die App darf hier 0.00 zeigen, sonst ist die Summenformel falsch.
2. **E0 ohne Steuerkonto.** `of_insert_estv_rates` legt E0 ohne `steuerkonto` an. `of_get_steuercode_info` verlangt aber ein existierendes Steuerkonto, dadurch ist E0 so nicht buchbar („Steuercode … ungültig“). In diesen Testdaten hat E0 deshalb das Steuerkonto 2200. Es entsteht keine MWST-Zeile, weil die Steuer 0 ist.
3. **Belegnummern über alle Mandanten.** `of_get_next_belegnummer` nimmt `max(belegnummer)` aller Mandanten des Jahres, ohne nach `mid` zu filtern. Neue Belege in diesem Mandanten setzen darum bei der höchsten Nummer irgendeines Mandanten in 2025 fort.
4. **`test_data.txt` passt nicht zum aktuellen Schema.** Es nutzt `mandanten_vfibu.versteuerungsart`, `jahre` ohne `gj_von`/`gj_bis` und `kontenrahmen_vfibu.saldo`. Mit dem heutigen `setup.txt` bricht es ab.
