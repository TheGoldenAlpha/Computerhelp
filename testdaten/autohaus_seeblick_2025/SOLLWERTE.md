# Sollwerte Testmandant Autohaus Seeblick AG, Geschäftsjahr 2025

Mandanten-ID: `5c2df529-87e5-5cd5-a2b3-19d463057958`. Geschäftsjahr 01.01.2025 bis 31.12.2025, effektive MWST-Abrechnung (versteuerungsart 1).
Generiert von `generate.py`. Alle Werte stammen aus denselben Buchungen, die in `autohaus_seeblick_2025.sql` stehen.

## Mengengerüst

| Was | Anzahl |
|---|---:|
| Belege total (fibubuchung_header) | 1067 |
| davon Eröffnungsbuchungen (Belegnummer leer, visible 3) | 36 |
| davon Einzelbuchungen (Journal) | 754 |
| davon Sammelbuchungen | 277 |
| Belegnummern | 1001 bis 2031 |
| Buchungszeilen total (fibubuchung_body) | 4225 |
| Buchungszeilen visible 1 | 3201 |
| MWST-Zeilen visible 2 | 952 |
| Eröffnungszeilen visible 3 | 72 |
| Konten im Kontenplan | 177 (davon buchbar, 4-stellig: 142) |
| bebuchte Konten | 82 |
| Summe aller Soll-Zeilen | 41'095'662.22 |
| Summe aller Haben-Zeilen | 41'095'662.22 |

Benutzte MWST-Codes: E0, I26, I38, I81, M26, M38, M81, V26, V38, V81.

## Eröffnungsbilanz per 01.01.2025

Startsaldo wie in View `kontostart_vfibu` (bezogen auf die Kontoseite). Gegenkonto aller Eröffnungsbuchungen ist **9100 Eröffnungsbilanz**. Sein Saldo muss danach **0.00** sein.

| Konto | Bezeichnung | Typ | Seite | startsaldo (View) | Bilanzwert |
|---|---|---|---|---:|---:|
| 1000 | Kasse | AK | S | 6'850.40 | 6'850.40 |
| 1010 | PostFinance | AK | S | 18'240.15 | 18'240.15 |
| 1020 | Bankguthaben ZKB Kontokorrent | AK | S | 462'486.75 | 462'486.75 |
| 1060 | Wertschriften | AK | S | 38'500.00 | 38'500.00 |
| 1100 | Forderungen aus Lieferungen und Leistungen (Debitoren) | AK | S | 168'420.30 | 168'420.30 |
| 1109 | Delkredere | AK | H | 8'400.00 | -8'400.00 |
| 1140 | Vorschüsse und Darlehen | AK | S | 5'000.00 | 5'000.00 |
| 1176 | Verrechnungssteuer | AK | S | 1'225.00 | 1'225.00 |
| 1200 | Handelswaren Neuwagen | AK | S | 845'000.00 | 845'000.00 |
| 1201 | Handelswaren Occasionen | AK | S | 412'500.00 | 412'500.00 |
| 1202 | Ersatzteile und Zubehör (Lager) | AK | S | 186'300.00 | 186'300.00 |
| 1300 | Bezahlter Aufwand des Folgejahres | AK | S | 9'600.00 | 9'600.00 |
| 1500 | Maschinen und Apparate | AK | S | 185'000.00 | 185'000.00 |
| 1509 | Wertberichtigungen Maschinen und Apparate | AK | H | 92'500.00 | -92'500.00 |
| 1510 | Mobiliar und Einrichtungen | AK | S | 78'000.00 | 78'000.00 |
| 1519 | Wertberichtigungen Mobiliar und Einrichtungen | AK | H | 31'200.00 | -31'200.00 |
| 1520 | Büromaschinen, Informatik, Kommunikationstechnologie | AK | S | 42'000.00 | 42'000.00 |
| 1529 | Wertberichtigungen Büromaschinen, Informatik, Kommunikationstechnologie | AK | H | 25'200.00 | -25'200.00 |
| 1530 | Fahrzeuge | AK | S | 96'000.00 | 96'000.00 |
| 1539 | Wertberichtigungen Fahrzeuge | AK | H | 38'400.00 | -38'400.00 |
| 1540 | Werkzeuge und Geräte | AK | S | 64'000.00 | 64'000.00 |
| 1549 | Wertberichtigungen Werkzeuge und Geräte | AK | H | 28'800.00 | -28'800.00 |
| 2000 | Verbindlichkeiten aus Lieferungen und Leistungen (Kreditoren) | PA | H | 386'540.20 | 386'540.20 |
| 2030 | Erhaltene Anzahlungen | PA | H | 45'000.00 | 45'000.00 |
| 2100 | Bankverbindlichkeiten | PA | H | 650'000.00 | 650'000.00 |
| 2201 | Abrechnungskonto MWST | PA | H | 28'764.35 | 28'764.35 |
| 2208 | Direkte Steuern | PA | H | 24'500.00 | 24'500.00 |
| 2270 | Sozialversicherungen und Vorsorgeeinrichtungen | PA | H | 18'640.50 | 18'640.50 |
| 2279 | Quellensteuer | PA | H | 1'240.00 | 1'240.00 |
| 2300 | Noch nicht bezahlter Aufwand | PA | H | 12'800.00 | 12'800.00 |
| 2330 | Kurzfristige Rückstellungen | PA | H | 35'000.00 | 35'000.00 |
| 2450 | Darlehen | PA | H | 250'000.00 | 250'000.00 |
| 2800 | Aktienkapital | PA | H | 500'000.00 | 500'000.00 |
| 2950 | Gesetzliche Gewinnreserve | PA | H | 100'000.00 | 100'000.00 |
| 2960 | Freiwillige Gewinnreserven | PA | H | 150'000.00 | 150'000.00 |
| 2970 | Gewinnvortrag oder Verlustvortrag | PA | H | 192'137.55 | 192'137.55 |
| | **Total Aktiven (netto, nach Wertberichtigungen)** | | | | **2'394'622.60** |
| | **Total Passiven** | | | | **2'394'622.60** |
| | **Differenz** | | | | **0.00** |
| 9100 | Eröffnungsbilanz (Saldo) | | | 0.00 | |

> **Achtung Minuskonten:** Die Wertberichtigungs-Konten (1109, 1509, 1519, 1529, 1539, 1549) sind Aktivkonten mit Kontoseite **H**. Ihr `startsaldo` ist positiv. Das Eröffnungsbilanz-Fenster rechnet `Sum(if(kontotype = 'AK', startsaldo, 0))`. Damit zeigt es Total Aktiven **2'843'622.60** statt **2'394'622.60** und eine Differenz von **449'000.00** (= 2 × 224'500.00 Wertberichtigungen). Richtig ist Differenz 0.00. Wenn deine App 0.00 zeigt, behandelt sie Minuskonten korrekt. Sonst ist das ein Fehler in der Summenformel (Abhilfe: `if(konto_seite = 'S', startsaldo, -startsaldo)` für AK und umgekehrt für PA).

## Saldenliste per 31.12.2025 (alle bebuchten Konten)

`saldo` = Wert der View `kontosaldo_live_vfibu` (bezogen auf die Kontoseite, inkl. Eröffnung). Soll/Haben = Verkehrszahlen ohne Eröffnung.

| Konto | Bezeichnung | Seite | Eröffnung | Soll 2025 | Haben 2025 | saldo (View) |
|---|---|---|---:|---:|---:|---:|
| 1000 | Kasse | S | 6'850.40 | 314'908.80 | 318'262.10 | 3'497.10 |
| 1010 | PostFinance | S | 18'240.15 | 0.00 | 180.00 | 18'060.15 |
| 1020 | Bankguthaben ZKB Kontokorrent | S | 462'486.75 | 8'999'178.42 | 9'126'414.21 | 335'250.96 |
| 1060 | Wertschriften | S | 38'500.00 | 0.00 | 8'500.00 | 30'000.00 |
| 1100 | Forderungen aus Lieferungen und Leistungen (Debitoren) | S | 168'420.30 | 8'302'455.35 | 7'944'546.47 | 526'329.18 |
| 1109 | Delkredere | H | 8'400.00 | 0.00 | 17'900.00 | 26'300.00 |
| 1140 | Vorschüsse und Darlehen | S | 5'000.00 | 0.00 | 5'000.00 | 0.00 |
| 1170 | Vorsteuer MWST Material, Waren, Dienstleistungen, Energie | S | 0.00 | 520'548.43 | 520'548.43 | 0.00 |
| 1171 | Vorsteuer MWST Investitionen, übriger Betriebsaufwand | S | 0.00 | 18'055.17 | 18'055.17 | 0.00 |
| 1176 | Verrechnungssteuer | S | 1'225.00 | 490.00 | 1'225.00 | 490.00 |
| 1200 | Handelswaren Neuwagen | S | 845'000.00 | 67'000.00 | 0.00 | 912'000.00 |
| 1201 | Handelswaren Occasionen | S | 412'500.00 | 0.00 | 14'500.00 | 398'000.00 |
| 1202 | Ersatzteile und Zubehör (Lager) | S | 186'300.00 | 7'150.00 | 0.00 | 193'450.00 |
| 1300 | Bezahlter Aufwand des Folgejahres | S | 9'600.00 | 10'200.00 | 9'600.00 | 10'200.00 |
| 1500 | Maschinen und Apparate | S | 185'000.00 | 35'615.17 | 0.00 | 220'615.17 |
| 1509 | Wertberichtigungen Maschinen und Apparate | H | 92'500.00 | 0.00 | 22'062.00 | 114'562.00 |
| 1510 | Mobiliar und Einrichtungen | S | 78'000.00 | 20'721.55 | 0.00 | 98'721.55 |
| 1519 | Wertberichtigungen Mobiliar und Einrichtungen | H | 31'200.00 | 0.00 | 12'340.00 | 43'540.00 |
| 1520 | Büromaschinen, Informatik, Kommunikationstechnologie | S | 42'000.00 | 9'111.93 | 0.00 | 51'111.93 |
| 1529 | Wertberichtigungen Büromaschinen, Informatik, Kommunikationstechnologie | H | 25'200.00 | 0.00 | 12'778.00 | 37'978.00 |
| 1530 | Fahrzeuge | S | 96'000.00 | 0.00 | 0.00 | 96'000.00 |
| 1539 | Wertberichtigungen Fahrzeuge | H | 38'400.00 | 0.00 | 19'200.00 | 57'600.00 |
| 1540 | Werkzeuge und Geräte | S | 64'000.00 | 13'691.03 | 0.00 | 77'691.03 |
| 1549 | Wertberichtigungen Werkzeuge und Geräte | H | 28'800.00 | 0.00 | 11'654.00 | 40'454.00 |
| 2000 | Verbindlichkeiten aus Lieferungen und Leistungen (Kreditoren) | H | 386'540.20 | 6'990'576.94 | 7'053'078.92 | 449'042.18 |
| 2030 | Erhaltene Anzahlungen | H | 45'000.00 | 75'000.00 | 40'000.00 | 10'000.00 |
| 2100 | Bankverbindlichkeiten | H | 650'000.00 | 0.00 | 0.00 | 650'000.00 |
| 2200 | Geschuldete MWST (Umsatzsteuer) | H | 0.00 | 732'785.57 | 732'785.57 | 0.00 |
| 2201 | Abrechnungskonto MWST | H | 28'764.35 | 174'804.82 | 195'523.85 | 49'483.38 |
| 2206 | Verrechnungssteuer | H | 0.00 | 42'000.00 | 42'000.00 | 0.00 |
| 2208 | Direkte Steuern | H | 24'500.00 | 24'500.00 | 29'200.00 | 29'200.00 |
| 2261 | Beschlossene Ausschüttungen | H | 0.00 | 120'000.00 | 120'000.00 | 0.00 |
| 2270 | Sozialversicherungen und Vorsorgeeinrichtungen | H | 18'640.50 | 230'134.35 | 295'853.25 | 84'359.40 |
| 2279 | Quellensteuer | H | 1'240.00 | 4'061.50 | 3'334.50 | 513.00 |
| 2300 | Noch nicht bezahlter Aufwand | H | 12'800.00 | 12'800.00 | 13'900.00 | 13'900.00 |
| 2330 | Kurzfristige Rückstellungen | H | 35'000.00 | 0.00 | 5'000.00 | 40'000.00 |
| 2450 | Darlehen | H | 250'000.00 | 50'000.00 | 0.00 | 200'000.00 |
| 2800 | Aktienkapital | H | 500'000.00 | 0.00 | 0.00 | 500'000.00 |
| 2950 | Gesetzliche Gewinnreserve | H | 100'000.00 | 0.00 | 0.00 | 100'000.00 |
| 2960 | Freiwillige Gewinnreserven | H | 150'000.00 | 0.00 | 0.00 | 150'000.00 |
| 2970 | Gewinnvortrag oder Verlustvortrag | H | 192'137.55 | 120'000.00 | 0.00 | 72'137.55 |
| 3200 | Handelserlöse Neuwagen | H | 0.00 | 0.00 | 5'308'926.91 | 5'308'926.91 |
| 3210 | Handelserlöse Occasionen | H | 0.00 | 0.00 | 2'524'694.35 | 2'524'694.35 |
| 3220 | Handelserlöse Ersatzteile und Zubehör | H | 0.00 | 0.00 | 540'228.20 | 540'228.20 |
| 3230 | Erlöse Shop / Kiosk (Lebensmittel) | H | 0.00 | 0.00 | 22'823.10 | 22'823.10 |
| 3400 | Dienstleistungserlöse Werkstatt | H | 0.00 | 0.00 | 484'232.16 | 484'232.16 |
| 3410 | Dienstleistungserlöse Carrosserie / Spritzwerk | H | 0.00 | 0.00 | 203'173.32 | 203'173.32 |
| 3420 | Erlöse Ersatzwagen / Vermietung | H | 0.00 | 0.00 | 34'047.82 | 34'047.82 |
| 3800 | Erlösminderungen | S | 0.00 | 1'498.61 | 0.00 | 1'498.61 |
| 3805 | Verluste Forderungen (Debitoren), Veränderung Delkredere | S | 0.00 | 23'899.44 | 0.00 | 23'899.44 |
| 4000 | Materialaufwand Werkstatt / Lack | S | 0.00 | 93'060.12 | 0.00 | 93'060.12 |
| 4200 | Handelswarenaufwand Neuwagen | S | 0.00 | 4'726'699.33 | 67'000.00 | 4'659'699.33 |
| 4210 | Handelswarenaufwand Occasionen | S | 0.00 | 1'829'991.00 | 0.00 | 1'829'991.00 |
| 4220 | Aufwand Ersatzteile und Zubehör | S | 0.00 | 403'696.19 | 7'150.00 | 396'546.19 |
| 4230 | Warenaufwand Shop / Kiosk | S | 0.00 | 12'779.45 | 0.00 | 12'779.45 |
| 4400 | Aufwand für bezogene Dienstleistungen | S | 0.00 | 35'508.05 | 0.00 | 35'508.05 |
| 4900 | Aufwandminderungen | H | 0.00 | 0.00 | 24'064.62 | 24'064.62 |
| 5000 | Lohnaufwand Geschäftsleitung | S | 0.00 | 162'500.00 | 0.00 | 162'500.00 |
| 5010 | Lohnaufwand Verkauf | S | 0.00 | 243'226.45 | 0.00 | 243'226.45 |
| 5020 | Lohnaufwand Werkstatt | S | 0.00 | 334'750.00 | 0.00 | 334'750.00 |
| 5030 | Lohnaufwand Carrosserie | S | 0.00 | 157'300.00 | 0.00 | 157'300.00 |
| 5040 | Lohnaufwand Ersatzteillager | S | 0.00 | 71'500.00 | 0.00 | 71'500.00 |
| 5050 | Lohnaufwand Administration | S | 0.00 | 148'720.00 | 0.00 | 148'720.00 |
| 5700 | Sozialversicherungsaufwand | S | 0.00 | 157'150.05 | 0.00 | 157'150.05 |
| 5800 | Übriger Personalaufwand | S | 0.00 | 5'260.00 | 0.00 | 5'260.00 |
| 6000 | Raumaufwand | S | 0.00 | 190'200.00 | 0.00 | 190'200.00 |
| 6100 | Unterhalt, Reparaturen, Ersatz mobile Sachanlagen | S | 0.00 | 10'727.43 | 0.00 | 10'727.43 |
| 6105 | Leasingaufwand mobile Sachanlagen | S | 0.00 | 3'599.40 | 0.00 | 3'599.40 |
| 6200 | Fahrzeug- und Transportaufwand | S | 0.00 | 19'121.47 | 0.00 | 19'121.47 |
| 6260 | Fahrzeugleasing und -mieten | S | 0.00 | 13'154.52 | 0.00 | 13'154.52 |
| 6300 | Sachversicherungen, Abgaben, Gebühren, Bewilligungen | S | 0.00 | 39'550.00 | 10'200.00 | 29'350.00 |
| 6400 | Energie- und Entsorgungsaufwand | S | 0.00 | 35'264.85 | 3'000.00 | 32'264.85 |
| 6500 | Verwaltungsaufwand | S | 0.00 | 25'144.41 | 9'800.00 | 15'344.41 |
| 6570 | Informatikaufwand inkl. Leasing | S | 0.00 | 9'884.16 | 0.00 | 9'884.16 |
| 6600 | Werbeaufwand | S | 0.00 | 20'469.81 | 0.00 | 20'469.81 |
| 6700 | Sonstiger betrieblicher Aufwand | S | 0.00 | 28'768.95 | 0.00 | 28'768.95 |
| 6800 | Abschreibungen und Wertberichtigungen auf Positionen des Anlagevermögens | S | 0.00 | 78'034.00 | 0.00 | 78'034.00 |
| 6900 | Finanzaufwand | S | 0.00 | 30'970.30 | 0.00 | 30'970.30 |
| 6950 | Finanzertrag | H | 0.00 | 0.00 | 1'812.35 | 1'812.35 |
| 7500 | Ertrag Vermietung Monteurzimmer | H | 0.00 | 0.00 | 22'822.72 | 22'822.72 |
| 8900 | Direkte Steuern | S | 0.00 | 49'200.00 | 0.00 | 49'200.00 |
| 9100 | Eröffnungsbilanz | H | 0.00 | 0.00 | 0.00 | 0.00 |

## Erfolgsrechnung 01.01. bis 31.12.2025

| Konto | Bezeichnung | Betrag |
|---|---|---:|
| 3200 | Handelserlöse Neuwagen | 5'308'926.91 |
| 3210 | Handelserlöse Occasionen | 2'524'694.35 |
| 3220 | Handelserlöse Ersatzteile und Zubehör | 540'228.20 |
| 3230 | Erlöse Shop / Kiosk (Lebensmittel) | 22'823.10 |
| 3400 | Dienstleistungserlöse Werkstatt | 484'232.16 |
| 3410 | Dienstleistungserlöse Carrosserie / Spritzwerk | 203'173.32 |
| 3420 | Erlöse Ersatzwagen / Vermietung | 34'047.82 |
| 4900 | Aufwandminderungen | 24'064.62 |
| 6950 | Finanzertrag | 1'812.35 |
| 7500 | Ertrag Vermietung Monteurzimmer | 22'822.72 |
| | **Total Ertrag** | **9'166'825.55** |
| 3800 | Erlösminderungen | 1'498.61 |
| 3805 | Verluste Forderungen (Debitoren), Veränderung Delkredere | 23'899.44 |
| 4000 | Materialaufwand Werkstatt / Lack | 93'060.12 |
| 4200 | Handelswarenaufwand Neuwagen | 4'659'699.33 |
| 4210 | Handelswarenaufwand Occasionen | 1'829'991.00 |
| 4220 | Aufwand Ersatzteile und Zubehör | 396'546.19 |
| 4230 | Warenaufwand Shop / Kiosk | 12'779.45 |
| 4400 | Aufwand für bezogene Dienstleistungen | 35'508.05 |
| 5000 | Lohnaufwand Geschäftsleitung | 162'500.00 |
| 5010 | Lohnaufwand Verkauf | 243'226.45 |
| 5020 | Lohnaufwand Werkstatt | 334'750.00 |
| 5030 | Lohnaufwand Carrosserie | 157'300.00 |
| 5040 | Lohnaufwand Ersatzteillager | 71'500.00 |
| 5050 | Lohnaufwand Administration | 148'720.00 |
| 5700 | Sozialversicherungsaufwand | 157'150.05 |
| 5800 | Übriger Personalaufwand | 5'260.00 |
| 6000 | Raumaufwand | 190'200.00 |
| 6100 | Unterhalt, Reparaturen, Ersatz mobile Sachanlagen | 10'727.43 |
| 6105 | Leasingaufwand mobile Sachanlagen | 3'599.40 |
| 6200 | Fahrzeug- und Transportaufwand | 19'121.47 |
| 6260 | Fahrzeugleasing und -mieten | 13'154.52 |
| 6300 | Sachversicherungen, Abgaben, Gebühren, Bewilligungen | 29'350.00 |
| 6400 | Energie- und Entsorgungsaufwand | 32'264.85 |
| 6500 | Verwaltungsaufwand | 15'344.41 |
| 6570 | Informatikaufwand inkl. Leasing | 9'884.16 |
| 6600 | Werbeaufwand | 20'469.81 |
| 6700 | Sonstiger betrieblicher Aufwand | 28'768.95 |
| 6800 | Abschreibungen und Wertberichtigungen auf Positionen des Anlagevermögens | 78'034.00 |
| 6900 | Finanzaufwand | 30'970.30 |
| 8900 | Direkte Steuern | 49'200.00 |
| | **Total Aufwand** | **8'864'477.99** |
| | **Jahresgewinn 2025** | **302'347.56** |

Typ ER mit Kontoseite S oder Typ AU mit Kontoseite H kommt in diesem Mandanten nicht vor (3800/3805 sind AU, 4900/6950 sind ER). Die Summen der App über `kontotype` müssen daher exakt diese Werte ergeben.

## Bilanz per 31.12.2025 (vor Gewinnverbuchung)

| Konto | Bezeichnung | Betrag |
|---|---|---:|
| 1000 | Kasse | 3'497.10 |
| 1010 | PostFinance | 18'060.15 |
| 1020 | Bankguthaben ZKB Kontokorrent | 335'250.96 |
| 1060 | Wertschriften | 30'000.00 |
| 1100 | Forderungen aus Lieferungen und Leistungen (Debitoren) | 526'329.18 |
| 1109 | Delkredere | -26'300.00 |
| 1176 | Verrechnungssteuer | 490.00 |
| 1200 | Handelswaren Neuwagen | 912'000.00 |
| 1201 | Handelswaren Occasionen | 398'000.00 |
| 1202 | Ersatzteile und Zubehör (Lager) | 193'450.00 |
| 1300 | Bezahlter Aufwand des Folgejahres | 10'200.00 |
| 1500 | Maschinen und Apparate | 220'615.17 |
| 1509 | Wertberichtigungen Maschinen und Apparate | -114'562.00 |
| 1510 | Mobiliar und Einrichtungen | 98'721.55 |
| 1519 | Wertberichtigungen Mobiliar und Einrichtungen | -43'540.00 |
| 1520 | Büromaschinen, Informatik, Kommunikationstechnologie | 51'111.93 |
| 1529 | Wertberichtigungen Büromaschinen, Informatik, Kommunikationstechnologie | -37'978.00 |
| 1530 | Fahrzeuge | 96'000.00 |
| 1539 | Wertberichtigungen Fahrzeuge | -57'600.00 |
| 1540 | Werkzeuge und Geräte | 77'691.03 |
| 1549 | Wertberichtigungen Werkzeuge und Geräte | -40'454.00 |
| | **Total Aktiven (netto)** | **2'650'983.07** |
| 2000 | Verbindlichkeiten aus Lieferungen und Leistungen (Kreditoren) | 449'042.18 |
| 2030 | Erhaltene Anzahlungen | 10'000.00 |
| 2100 | Bankverbindlichkeiten | 650'000.00 |
| 2201 | Abrechnungskonto MWST | 49'483.38 |
| 2208 | Direkte Steuern | 29'200.00 |
| 2270 | Sozialversicherungen und Vorsorgeeinrichtungen | 84'359.40 |
| 2279 | Quellensteuer | 513.00 |
| 2300 | Noch nicht bezahlter Aufwand | 13'900.00 |
| 2330 | Kurzfristige Rückstellungen | 40'000.00 |
| 2450 | Darlehen | 200'000.00 |
| 2800 | Aktienkapital | 500'000.00 |
| 2950 | Gesetzliche Gewinnreserve | 100'000.00 |
| 2960 | Freiwillige Gewinnreserven | 150'000.00 |
| 2970 | Gewinnvortrag oder Verlustvortrag | 72'137.55 |
| | **Total Passiven (ohne Jahresgewinn)** | **2'348'635.51** |
| | **Aktiven - Passiven = Jahresgewinn** | **302'347.56** |

Kontrolle: Gewinn laut Erfolgsrechnung = Gewinn laut Bilanz = **302'347.56**.

> Eine Bilanz, die AK-Salden roh addiert (`Sum(... kontosaldo_live_vfibu_saldo ...)` ohne Vorzeichen für Minuskonten), zeigt Total Aktiven **3'291'851.07**. Das ist um 2 × 320'434.00 (Wertberichtigungen/Delkredere) zu hoch.

## MWST-Auswertung (Belegdatum, effektive Methode)

Pro Quartal und Steuercode: Netto = Summe der Zeilen mit visible 1, Steuer = Summe der MWST-Zeilen mit visible 2. Vorzeichen: Umsatz (V/E) im Haben positiv, Vorsteuer (M/I) im Soll positiv. Gegenbuchungen wie Gutschrift, Debitorenverlust und Skonto sind dadurch negativ.

### Q1/2025

| Code | Bezeichnung | Satz | Netto | Steuer |
|---|---|---:|---:|---:|
| V26 | Verkauf reduzierter Satz 2.6% | 2.6% | 5'196.20 | 135.10 |
| V38 | Verkauf Sondersatz Beherbergung 3.8% | 3.8% | 6'579.96 | 250.04 |
| V81 | Verkauf Normalsatz 8.1% | 8.1% | 2'135'135.61 | 172'945.99 |
| E0 | Export steuerbefreit 0% | 0% | 19'900.00 | 0.00 |
| M26 | Material/Dienstleistungen reduzierter Satz 2.6% | 2.6% | 2'883.29 | 74.96 |
| M81 | Material/Dienstleistungen Normalsatz 8.1% | 8.1% | 1'859'028.34 | 150'581.32 |
| I26 | Investitionen reduzierter Satz 2.6% | 2.6% | 1'077.63 | 28.02 |
| I38 | Investitionen Sondersatz Beherbergung 3.8% | 3.8% | 1'439.31 | 54.69 |
| I81 | Investitionen Normalsatz 8.1% | 8.1% | 79'233.99 | 6'417.96 |
| | **Umsatzsteuer** | | | **173'331.13** |
| | **Vorsteuer Material/DL (1170)** | | | **150'656.28** |
| | **Vorsteuer Inv./Betriebsaufwand (1171)** | | | **6'500.67** |
| | **Zahllast ESTV** | | | **16'174.18** |

### Q2/2025

| Code | Bezeichnung | Satz | Netto | Steuer |
|---|---|---:|---:|---:|
| V26 | Verkauf reduzierter Satz 2.6% | 2.6% | 6'398.10 | 166.35 |
| V38 | Verkauf Sondersatz Beherbergung 3.8% | 3.8% | 5'404.62 | 205.38 |
| V81 | Verkauf Normalsatz 8.1% | 8.1% | 2'378'879.48 | 192'689.17 |
| E0 | Export steuerbefreit 0% | 0% | 18'800.00 | 0.00 |
| M26 | Material/Dienstleistungen reduzierter Satz 2.6% | 2.6% | 3'008.53 | 78.22 |
| M38 | Material/Dienstleistungen Sondersatz Beherbergung  | 3.8% | 439.31 | 16.69 |
| M81 | Material/Dienstleistungen Normalsatz 8.1% | 8.1% | 1'579'382.42 | 127'929.96 |
| I26 | Investitionen reduzierter Satz 2.6% | 2.6% | 819.30 | 21.30 |
| I81 | Investitionen Normalsatz 8.1% | 8.1% | 45'843.34 | 3'713.31 |
| | **Umsatzsteuer** | | | **193'060.90** |
| | **Vorsteuer Material/DL (1170)** | | | **128'024.87** |
| | **Vorsteuer Inv./Betriebsaufwand (1171)** | | | **3'734.61** |
| | **Zahllast ESTV** | | | **61'301.42** |

### Q3/2025

| Code | Bezeichnung | Satz | Netto | Steuer |
|---|---|---:|---:|---:|
| V26 | Verkauf reduzierter Satz 2.6% | 2.6% | 5'615.14 | 146.01 |
| V38 | Verkauf Sondersatz Beherbergung 3.8% | 3.8% | 5'838.15 | 221.85 |
| V81 | Verkauf Normalsatz 8.1% | 8.1% | 2'314'040.72 | 187'437.33 |
| E0 | Export steuerbefreit 0% | 0% | 10'200.00 | 0.00 |
| M26 | Material/Dienstleistungen reduzierter Satz 2.6% | 2.6% | 3'468.43 | 90.17 |
| M81 | Material/Dienstleistungen Normalsatz 8.1% | 8.1% | 1'538'361.02 | 124'607.28 |
| I26 | Investitionen reduzierter Satz 2.6% | 2.6% | 889.67 | 23.13 |
| I38 | Investitionen Sondersatz Beherbergung 3.8% | 3.8% | 600.00 | 22.80 |
| I81 | Investitionen Normalsatz 8.1% | 8.1% | 42'449.20 | 3'438.40 |
| | **Umsatzsteuer** | | | **187'805.19** |
| | **Vorsteuer Material/DL (1170)** | | | **124'697.45** |
| | **Vorsteuer Inv./Betriebsaufwand (1171)** | | | **3'484.33** |
| | **Zahllast ESTV** | | | **59'623.41** |

### Q4/2025

| Code | Bezeichnung | Satz | Netto | Steuer |
|---|---|---:|---:|---:|
| V26 | Verkauf reduzierter Satz 2.6% | 2.6% | 5'613.66 | 145.94 |
| V38 | Verkauf Sondersatz Beherbergung 3.8% | 3.8% | 4'999.99 | 190.01 |
| V81 | Verkauf Normalsatz 8.1% | 8.1% | 2'193'148.90 | 177'645.05 |
| E0 | Export steuerbefreit 0% | 0% | 17'700.00 | 0.00 |
| M26 | Material/Dienstleistungen reduzierter Satz 2.6% | 2.6% | 3'419.20 | 88.90 |
| M81 | Material/Dienstleistungen Normalsatz 8.1% | 8.1% | 1'421'378.98 | 115'131.70 |
| I26 | Investitionen reduzierter Satz 2.6% | 2.6% | 732.02 | 19.03 |
| I81 | Investitionen Normalsatz 8.1% | 8.1% | 53'290.22 | 4'316.53 |
| | **Umsatzsteuer** | | | **177'981.00** |
| | **Vorsteuer Material/DL (1170)** | | | **115'220.60** |
| | **Vorsteuer Inv./Betriebsaufwand (1171)** | | | **4'335.56** |
| | **Zahllast ESTV** | | | **58'424.84** |

Die Zahllast jedes Quartals wird am Quartalsende per Sammelbuchung auf **2201 Abrechnungskonto MWST** übertragen. Bezahlt wird sie im 2. Monat danach. Q4/2025 ist per 31.12. offen.
Per 31.12.2025: 2200 = 0.00, 1170 = 0.00, 1171 = 0.00, 2201 = 49'483.38 (= Zahllast Q4).

