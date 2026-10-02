# FIBU – Fehler-TODO (komplette Code-Stücke)

So ersetzt du eine **Funktion**: In PowerBuilder das Objekt öffnen, die Funktion anwählen, den ganzen Code im Script-Editor markieren (Ctrl+A) und durch den Block hier ersetzen. Die erste Zeile (`public function … ;`) gehört zum Block.

So ersetzt du ein **DataWindow**: Rechtsklick auf das DataWindow → **Edit Source**, alles markieren (Ctrl+A) und den ganzen Inhalt der mitgelieferten `.srd`-Datei aus `datawindows/` einfügen. Dann speichern.

> Grundlage ist dein Code aus dem ersten ZIP (02.10.2026). Falls du eine dieser Funktionen oder DataWindows seither geändert hast, vergleiche vorher kurz.

Punkt 1 (MWST-Rundung) und das „div.“ im Kontoblatt hast du schon erledigt.

---
## 2. MWST-Zusammenfassung: Korrekturen mit richtigem Vorzeichen
Objekt **`nvo_datamodel_vfibu`**, Funktion **`of_get_mwst_zusammenfassung`**, komplett ersetzen. Geändert ist je die erste Zeile der beiden Cursor (`CASE WHEN b.sollhaben …`).

```
public function integer of_get_mwst_zusammenfassung (date adt_von, date adt_bis, datastore ads_ziel);string ls_mid, ls_code, ls_name, ls_vorher_code, ls_vorher_name
decimal ld_satz, ld_steuerbar, ld_steuer, ld_vorher_satz
decimal ld_code_steuerbar, ld_code_steuer
decimal ld_satz_steuerbar, ld_satz_steuer
decimal ld_sektion_steuerbar, ld_sektion_steuer
decimal ld_gesamt_umsatzst, ld_gesamt_vorsteuer
decimal ld_saldo, ld_vorzeichen, ld_gebucht
long ll_durchgang, ll_beleg
date ldt_datum
string ls_text

ls_mid = gnvo_app.of_get_mandanten_id()
ads_ziel.Reset()
ld_saldo = 0
ld_gesamt_umsatzst = 0
ld_gesamt_vorsteuer = 0

for ll_durchgang = 1 to 2
	if ll_durchgang = 1 then
		ld_vorzeichen = -1
	else
		ld_vorzeichen = 1
	end if

	ls_vorher_code = ""
	ls_vorher_name = ""
	ld_vorher_satz = -1
	ld_code_steuerbar = 0
	ld_code_steuer = 0
	ld_satz_steuerbar = 0
	ld_satz_steuer = 0
	ld_sektion_steuerbar = 0
	ld_sektion_steuer = 0

	if ll_durchgang = 1 then
		DECLARE lc_mwst_v CURSOR FOR
		SELECT b.steuercode, sc.name, sc.steuersatz, h.belegdatum, h.belegnummer, b.text, CASE WHEN b.sollhaben = 'H' THEN b.betrag ELSE -b.betrag END, CASE WHEN b.sollhaben = 'H' THEN COALESCE(t.betrag, 0) ELSE -COALESCE(t.betrag, 0) END
		FROM (SELECT bidheader, sollhaben, betrag, text, steuercode,
		             ROW_NUMBER() OVER (PARTITION BY bidheader, steuercode, sollhaben, text ORDER BY betrag) AS nr
		      FROM fibubuchung_body WHERE visible = '1') b
		JOIN fibubuchung_header h ON h.bid = b.bidheader
		JOIN steuercode sc ON sc.code = b.steuercode AND sc.mid = h.mid AND sc.year = h.buchungsjahr
		LEFT JOIN (SELECT bidheader, sollhaben, betrag, text, steuercode,
		                  ROW_NUMBER() OVER (PARTITION BY bidheader, steuercode, sollhaben, text ORDER BY betrag) AS nr
		           FROM fibubuchung_body WHERE visible = '2') t
		  ON t.bidheader = b.bidheader AND t.steuercode = b.steuercode AND t.sollhaben = b.sollhaben
		 AND t.text = LEFT('MWST - ' || b.text, 100) AND t.nr = b.nr
		WHERE LOWER(CAST(h.mid AS varchar)) = LOWER(:ls_mid)
		  AND b.steuercode IS NOT NULL AND b.steuercode <> ''
		  AND h.belegdatum >= :adt_von AND h.belegdatum <= :adt_bis
		  AND (b.steuercode LIKE 'V%' OR b.steuercode LIKE 'E%')
		ORDER BY sc.steuersatz ASC, b.steuercode ASC, h.belegdatum ASC, h.belegnummer ASC;
		OPEN lc_mwst_v;
		if sqlca.sqlcode <> 0 then
			messagebox("Fehler", "MWST-Daten (Verkauf) konnten nicht geladen werden: " + sqlca.sqlerrtext)
			return -1
		end if
		FETCH lc_mwst_v INTO :ls_code, :ls_name, :ld_satz, :ldt_datum, :ll_beleg, :ls_text, :ld_steuerbar, :ld_gebucht;
	else
		DECLARE lc_mwst_mi CURSOR FOR
		SELECT b.steuercode, sc.name, sc.steuersatz, h.belegdatum, h.belegnummer, b.text, CASE WHEN b.sollhaben = 'S' THEN b.betrag ELSE -b.betrag END, CASE WHEN b.sollhaben = 'S' THEN COALESCE(t.betrag, 0) ELSE -COALESCE(t.betrag, 0) END
		FROM (SELECT bidheader, sollhaben, betrag, text, steuercode,
		             ROW_NUMBER() OVER (PARTITION BY bidheader, steuercode, sollhaben, text ORDER BY betrag) AS nr
		      FROM fibubuchung_body WHERE visible = '1') b
		JOIN fibubuchung_header h ON h.bid = b.bidheader
		JOIN steuercode sc ON sc.code = b.steuercode AND sc.mid = h.mid AND sc.year = h.buchungsjahr
		LEFT JOIN (SELECT bidheader, sollhaben, betrag, text, steuercode,
		                  ROW_NUMBER() OVER (PARTITION BY bidheader, steuercode, sollhaben, text ORDER BY betrag) AS nr
		           FROM fibubuchung_body WHERE visible = '2') t
		  ON t.bidheader = b.bidheader AND t.steuercode = b.steuercode AND t.sollhaben = b.sollhaben
		 AND t.text = LEFT('MWST - ' || b.text, 100) AND t.nr = b.nr
		WHERE LOWER(CAST(h.mid AS varchar)) = LOWER(:ls_mid)
		  AND b.steuercode IS NOT NULL AND b.steuercode <> ''
		  AND h.belegdatum >= :adt_von AND h.belegdatum <= :adt_bis
		  AND b.steuercode NOT LIKE 'V%' AND b.steuercode NOT LIKE 'E%'
		ORDER BY sc.steuersatz ASC, b.steuercode ASC, h.belegdatum ASC, h.belegnummer ASC;
		OPEN lc_mwst_mi;
		if sqlca.sqlcode <> 0 then
			messagebox("Fehler", "MWST-Daten (Einkauf) konnten nicht geladen werden: " + sqlca.sqlerrtext)
			return -1
		end if
		FETCH lc_mwst_mi INTO :ls_code, :ls_name, :ld_satz, :ldt_datum, :ll_beleg, :ls_text, :ld_steuerbar, :ld_gebucht;
	end if

	do while sqlca.sqlcode = 0
		if ls_vorher_code <> "" and ls_code <> ls_vorher_code then
			of_mwst_summenzeile(ads_ziel, "K", "Total Codes: " + ls_vorher_code + " " + ls_vorher_name, ld_code_steuerbar * ld_vorzeichen, ld_code_steuer * ld_vorzeichen, 0, 0, ld_saldo)
			ld_code_steuerbar = 0
			ld_code_steuer = 0
		end if

		if ld_vorher_satz >= 0 and ld_satz <> ld_vorher_satz then
			of_mwst_summenzeile(ads_ziel, "P", "Total Prozentsatz: " + String(ld_vorher_satz, "0.00") + "%", ld_satz_steuerbar * ld_vorzeichen, ld_satz_steuer * ld_vorzeichen, 0, 0, ld_saldo)
			ld_satz_steuerbar = 0
			ld_satz_steuer = 0
		end if

		ld_steuer = of_mwst_detailzeile(ads_ziel, ls_code, ld_satz, ldt_datum, ll_beleg, ls_text, ld_steuerbar, ld_vorzeichen, ll_durchgang, ld_saldo, ld_gebucht)

		ld_code_steuerbar += ld_steuerbar
		ld_code_steuer += ld_steuer
		ld_satz_steuerbar += ld_steuerbar
		ld_satz_steuer += ld_steuer
		ld_sektion_steuerbar += ld_steuerbar
		ld_sektion_steuer += ld_steuer
		ls_vorher_code = ls_code
		ls_vorher_name = ls_name
		ld_vorher_satz = ld_satz

		if ll_durchgang = 1 then
			FETCH lc_mwst_v INTO :ls_code, :ls_name, :ld_satz, :ldt_datum, :ll_beleg, :ls_text, :ld_steuerbar, :ld_gebucht;
		else
			FETCH lc_mwst_mi INTO :ls_code, :ls_name, :ld_satz, :ldt_datum, :ll_beleg, :ls_text, :ld_steuerbar, :ld_gebucht;
		end if
	loop

	if ll_durchgang = 1 then
		CLOSE lc_mwst_v;
	else
		CLOSE lc_mwst_mi;
	end if

	if ls_vorher_code <> "" then
		of_mwst_summenzeile(ads_ziel, "K", "Total Codes: " + ls_vorher_code + " " + ls_vorher_name, ld_code_steuerbar * ld_vorzeichen, ld_code_steuer * ld_vorzeichen, 0, 0, ld_saldo)
		of_mwst_summenzeile(ads_ziel, "P", "Total Prozentsatz: " + String(ld_vorher_satz, "0.00") + "%", ld_satz_steuerbar * ld_vorzeichen, ld_satz_steuer * ld_vorzeichen, 0, 0, ld_saldo)
		if ll_durchgang = 1 then
			of_mwst_summenzeile(ads_ziel, "S", "Total geschuldet:", ld_sektion_steuerbar * ld_vorzeichen, ld_sektion_steuer * ld_vorzeichen, 0, ld_sektion_steuer, ld_saldo)
			ld_gesamt_umsatzst = ld_sektion_steuer
		else
			of_mwst_summenzeile(ads_ziel, "S", "Total Vorsteuer:", ld_sektion_steuerbar * ld_vorzeichen, ld_sektion_steuer * ld_vorzeichen, ld_sektion_steuer, 0, ld_saldo)
			ld_gesamt_vorsteuer = ld_sektion_steuer
		end if
	end if
next

of_mwst_summenzeile(ads_ziel, "G", "Gesamtbetrag (Vorsteuer - Umsatzsteuer):", 0, 0, ld_gesamt_vorsteuer, ld_gesamt_umsatzst, ld_gesamt_vorsteuer - ld_gesamt_umsatzst)

return 0
end function
```

Danach muss die MWST-Zusammenfassung für den Testmandanten zeigen: Umsatzsteuer **732'178.22**, Vorsteuer **536'654.37**, Zahllast **195'523.85**.

---
## 3. Minuskonten (Wertberichtigungen, Delkredere) in Bilanz und Eröffnungsbilanz
### 3a) Views: in `setup.txt` Zeile 14 und 15 ersetzen **und** einmal in der DB ausführen

```sql
CREATE OR REPLACE VIEW public.kontostart_vfibu AS SELECT k.kontonr, k.mandanten_id, k.year, COALESCE((SELECT SUM(CASE WHEN (CASE WHEN k.kontotype IN ('AK', 'AU') THEN 'S' ELSE 'H' END) = b.sollhaben THEN b.betrag ELSE -b.betrag END) FROM public.fibubuchung_body b JOIN public.fibubuchung_header h ON h.bid = b.bidheader WHERE b.konto = k.kontonr AND b.visible = '3' AND h.buchungsjahr = k.year AND LOWER(CAST(h.mid AS varchar)) = LOWER(CAST(k.mandanten_id AS varchar))), 0) AS startsaldo FROM public.kontenrahmen_vfibu k;
CREATE OR REPLACE VIEW public.kontosaldo_live_vfibu AS SELECT k.kontonr, k.mandanten_id, k.year, COALESCE((SELECT SUM(CASE WHEN (CASE WHEN k.kontotype IN ('AK', 'AU') THEN 'S' ELSE 'H' END) = b.sollhaben THEN b.betrag ELSE -b.betrag END) FROM public.fibubuchung_body b JOIN public.fibubuchung_header h ON h.bid = b.bidheader WHERE b.konto = k.kontonr AND h.buchungsjahr = k.year AND LOWER(CAST(h.mid AS varchar)) = LOWER(CAST(k.mandanten_id AS varchar))), 0) AS saldo FROM public.kontenrahmen_vfibu k;
```

### 3b) Saldovortrag
Objekt **`nvo_datamodel_vfibu`**, Funktion **`of_book_saldovortrag`**, komplett ersetzen. Geändert ist nur das `SELECT CASE WHEN kontotype …`.

```
public function integer of_book_saldovortrag (string as_kontonr, decimal adl_saldo, string as_eb_konto, string as_text);string ls_bid, ls_bbid, ls_username, ls_mid, ls_soll, ls_haben, ls_seite, ls_gjs, ls_text
decimal ld_betrag
integer li_year
date ldt_datum

if adl_saldo = 0 then return 0

ls_mid = gnvo_app.of_get_mandanten_id()
ls_username = gnvo_app.of_get_winuser()
li_year = gnvo_app.of_get_year()
ld_betrag = Abs(adl_saldo)

SELECT CASE WHEN kontotype IN ('AK', 'AU') THEN 'S' ELSE 'H' END INTO :ls_seite
FROM kontenrahmen_vfibu
WHERE kontonr = :as_kontonr
  AND LOWER(CAST(mandanten_id AS varchar)) = LOWER(:ls_mid)
  AND year = :li_year;
if sqlca.sqlcode <> 0 then
	messagebox("Saldovortrag", "Konto " + as_kontonr + " nicht gefunden.")
	return -1
end if

ldt_datum = of_get_gj_von(li_year)
if ldt_datum = Date(1900, 1, 1) then
	messagebox("Saldovortrag", "Für das Geschäftsjahr " + String(li_year) + " ist kein Zeitraum hinterlegt.")
	return -1
end if
ls_text = as_text

if ls_seite = 'S' then
	if adl_saldo > 0 then
		ls_soll = as_kontonr
		ls_haben = as_eb_konto
	else
		ls_soll = as_eb_konto
		ls_haben = as_kontonr
	end if
else
	if adl_saldo > 0 then
		ls_haben = as_kontonr
		ls_soll = as_eb_konto
	else
		ls_haben = as_eb_konto
		ls_soll = as_kontonr
	end if
end if

ls_bid = invo_utilities_vfibu.of_create_guid_vfibu()
insert into fibubuchung_header (bid, belegdatum, buchungsdatum, username, belegnummer, buchungsjahr, mid)
values (:ls_bid, :ldt_datum, :ldt_datum, :ls_username, NULL, :li_year, :ls_mid);
if sqlca.sqlcode <> 0 then
	messagebox("Fehler Saldovortrag Header", sqlca.sqlerrtext)
	return -1
end if

ls_bbid = invo_utilities_vfibu.of_create_guid_vfibu()
insert into fibubuchung_body (bbid, bidheader, konto, betrag, text, sollhaben, visible)
values (:ls_bbid, :ls_bid, :ls_soll, :ld_betrag, :ls_text, 'S', '3');
if sqlca.sqlcode <> 0 then
	messagebox("Fehler Saldovortrag Soll", sqlca.sqlerrtext)
	return -1
end if

ls_bbid = invo_utilities_vfibu.of_create_guid_vfibu()
insert into fibubuchung_body (bbid, bidheader, konto, betrag, text, sollhaben, visible)
values (:ls_bbid, :ls_bid, :ls_haben, :ld_betrag, :ls_text, 'H', '3');
if sqlca.sqlcode <> 0 then
	messagebox("Fehler Saldovortrag Haben", sqlca.sqlerrtext)
	return -1
end if

return 0

end function
```

Hinweis: In der Eröffnungsbilanz-Erfassung gibst du Minuskonten (z. B. 1509) danach **negativ** ein.

### 3c) Bilanz mit Zeile Jahresgewinn/-verlust
DataWindows **`d_bilanz2_vfibu`** und **`d_print_bilanz_vfibu`**: jeweils Edit Source → ganzen Inhalt ersetzen durch `datawindows/d_bilanz2_vfibu.srd` bzw. `datawindows/d_print_bilanz_vfibu.srd`. Neu sind das Summary-Band und zwei Felder `c_gewinn_text` / `c_gewinn`. Beim Testmandanten muss **Jahresgewinn 302'347.56** stehen.

---
## 4. Erfolgsrechnung am Bildschirm (Spalte `saldo` existiert nicht)
DataWindow **`d_erfolgsrechnung_vfibu`**: Edit Source → ganzen Inhalt ersetzen durch `datawindows/d_erfolgsrechnung_vfibu.srd`.

---
## 5. Buchungen importieren
### 5a) `nvo_datamodel_vfibu` → `of_insert_buchung_header` komplett ersetzen

```
public function integer of_insert_buchung_header (string as_bid, date adt_belegdatum, date adt_buchungsdatum, string as_text, string as_belegnr);string ls_username, ls_mid
long ll_year

ls_username = gnvo_app.of_get_winuser()
ls_mid = gnvo_app.of_get_mandanten_id()
ll_year = gnvo_app.of_get_year()

if not of_is_datum_im_gj(adt_belegdatum, ll_year) then
	MessageBox("Datum ausserhalb Geschäftsjahr", "Das Belegdatum liegt nicht im Geschäftsjahr " + of_format_gj(ll_year) + " (" + of_format_gj_lang(ll_year) + ")." + "~n~n" + "Bitte Datum oder Jahr prüfen.")
	return -1
end if

INSERT INTO fibubuchung_header (bid, belegdatum, buchungsdatum, erfassungsdatum, belegnummer, username, buchungsjahr, mid)
VALUES (:as_bid, :adt_belegdatum, :adt_buchungsdatum, CURRENT_TIMESTAMP, :as_belegnr, :ls_username, :ll_year, :ls_mid)
USING SQLCA;

if sqlca.sqlcode <> 0 then
	rollback;
	messagebox("Fehler Header", sqlca.sqlerrtext)
	return -1
end if

return 0
end function
```

### 5b) `nvo_datamodel_vfibu` → `of_insert_buchung_body` komplett ersetzen
Die Funktion bekommt einen 5. Parameter `string as_text`. Im Function-Painter oben bei den Argumenten **`as_text` (string)** hinzufügen, dann den Code ersetzen.

```
public function integer of_insert_buchung_body (string as_bidheader, string as_konto, string as_betrag, string as_sollhaben, string as_text);string ls_bbid

ls_bbid = invo_utilities_vfibu.of_create_guid_vfibu()

INSERT INTO fibubuchung_body (bbid, bidheader, konto, betrag, text, sollhaben, visible)
VALUES (:ls_bbid, :as_bidheader, :as_konto, :as_betrag, :as_text, :as_sollhaben, '1')
USING SQLCA;

if sqlca.sqlcode <> 0 then
	rollback;
	messagebox("Fehler Body", sqlca.sqlerrtext)
	return -1
end if

return 0
end function
```

### 5c) `nuo_buchungstabelle_vfibu` → `of_import_buchungen` komplett ersetzen

```
public function integer of_import_buchungen ();integer li_answer, li_ret, li_pos
string ls_pathname, ls_filename, ls_filter
string ls_line, ls_rest
string ls_belegnr, ls_belegdatum, ls_buchungsdatum, ls_text, ls_konto, ls_sh, ls_betrag
string ls_prev_belegnr, ls_bid
long ll_file, ll_count
date ld_belegdatum, ld_buchungsdatum

li_answer = MessageBox("Buchungen importieren", &
	"Bitte wählen Sie eine .txt-Datei mit folgendem Format:" + "~n~n" + &
	"Spalten mit Tabulator getrennt, eine Zeile pro Buchungszeile:" + "~n~n" + &
	"BelegNr	Belegdatum	Buchungsdatum	Buchungstext	Konto	S/H	Betrag" + "~n" + &
	"1	01.01.2025	01.01.2025	Bankeinzahlung	1000	S	500.00" + "~n" + &
	"1	01.01.2025	01.01.2025	Bankeinzahlung	1020	H	500.00" + "~n" + &
	"2	02.01.2025	02.01.2025	Miete	6000	S	1200.00" + "~n" + &
	"2	02.01.2025	02.01.2025	Miete	1020	H	1200.00" + "~n~n" + &
	"Pflichtfelder: Alle Spalten" + "~n" + &
	"Zeilen mit gleicher Belegnummer gehören zur gleichen Buchung." + "~n" + &
	"Erfasser und Erfassungsdatum werden automatisch gesetzt." + "~n~n" + &
	"Fortfahren?", Question!, YesNo!)

if li_answer = 2 then return 0

ls_filter = "Textdateien (*.txt),*.txt"
li_ret = GetFileOpenName("Buchungen auswählen", ls_pathname, ls_filename, "txt", ls_filter)
if li_ret < 1 then return 0

ll_file = FileOpen(ls_pathname, LineMode!, Read!, LockRead!)
if ll_file < 1 then
	messagebox("Fehler", "Datei konnte nicht geöffnet werden.", StopSign!)
	return -1
end if

ls_prev_belegnr = ""
ll_count = 0

do while FileRead(ll_file, ls_line) > 0
	if Trim(ls_line) = "" then continue

	ls_rest = ls_line
	li_pos = Pos(ls_rest, "~t")
	if li_pos = 0 then continue
	ls_belegnr = Left(ls_rest, li_pos - 1)
	ls_rest = Mid(ls_rest, li_pos + 1)

	li_pos = Pos(ls_rest, "~t")
	ls_belegdatum = Left(ls_rest, li_pos - 1)
	ls_rest = Mid(ls_rest, li_pos + 1)

	li_pos = Pos(ls_rest, "~t")
	ls_buchungsdatum = Left(ls_rest, li_pos - 1)
	ls_rest = Mid(ls_rest, li_pos + 1)

	li_pos = Pos(ls_rest, "~t")
	ls_text = Left(ls_rest, li_pos - 1)
	ls_rest = Mid(ls_rest, li_pos + 1)

	li_pos = Pos(ls_rest, "~t")
	ls_konto = Left(ls_rest, li_pos - 1)
	ls_rest = Mid(ls_rest, li_pos + 1)

	li_pos = Pos(ls_rest, "~t")
	ls_sh = Left(ls_rest, li_pos - 1)
	ls_rest = Mid(ls_rest, li_pos + 1)

	ls_betrag = Trim(ls_rest)

	if ls_belegnr <> ls_prev_belegnr then
		ls_bid = invo_utilities.of_create_guid_vfibu()
		ld_belegdatum = Date(ls_belegdatum)
		ld_buchungsdatum = Date(ls_buchungsdatum)

		if invo_datamodel_vfibu.of_insert_buchung_header(ls_bid, ld_belegdatum, ld_buchungsdatum, ls_text, ls_belegnr) <> 0 then
			invo_datamodel_vfibu.of_rollback()
			FileClose(ll_file)
			return -1
		end if
		ls_prev_belegnr = ls_belegnr
	end if

	if invo_datamodel_vfibu.of_insert_buchung_body(ls_bid, ls_konto, ls_betrag, ls_sh, ls_text) <> 0 then
		invo_datamodel_vfibu.of_rollback()
		FileClose(ll_file)
		return -1
	end if

	ll_count ++
loop

FileClose(ll_file)
invo_datamodel_vfibu.of_commit()
messagebox("Erfolg", string(ll_count) + " Buchungszeilen importiert.", Information!)
return 0
end function
```

---
## 6. Konto mit Buchungen darf nicht gelöscht werden
Objekt **`nuo_edit_menu_vfibu`**, Funktion **`of_delete_konto`**, komplett ersetzen.

```
public function integer of_delete_konto (string as_uuid);long i
string ls_name
integer li_result
datastore lds_konto

// check
if invo_datamodel_vfibu.of_count_buchungen(invo_datamodel_vfibu.of_get_konto_field(as_uuid, "kontonr")) > 0 then
    messagebox("Löschen nicht möglich", "Auf dieses Konto wurden im Jahr " + invo_datamodel_vfibu.of_format_gj(gnvo_app.of_get_year()) + " bereits Buchungen erfasst.", StopSign!)
    return 0
end if

lds_konto = invo_datamodel_vfibu.of_get_model("kr")

for i = 1 to lds_konto.rowcount()
    if lds_konto.getitemstring(i, "parent_konto") = as_uuid then
        messagebox("Löschen nicht möglich", "Dieses Konto hat Unterkonten. Löschen Sie zuerst die entsprechenden Unterkonten und erst dann dieses.", StopSign!)
        return 0
    end if
next

for i = 1 to lds_konto.rowcount()
    if lds_konto.getitemstring(i, "id") = as_uuid then
        ls_name = lds_konto.getitemstring(i, "name")
        exit
    end if
next

li_result = messagebox("Konto löschen", "Sind Sie sich sicher, dass Sie das Konto '" + ls_name + "' löschen wollen?", Exclamation!, YesNo!)
if li_result = 2 then return 0

if invo_datamodel_vfibu.of_delete_konto(as_uuid) <> 1 then
    return -1
end if

close(w_edit_menu_vfibu)
return 0
end function
```

---
## 7. Einzelbuchung: Konten prüfen
Objekt **`nuo_new_journal_vfibu`**.
### 7a) `of_book` komplett ersetzen

```
public function integer of_book (long al_row, datastore ads_data);string ls_sk, ls_hk, ls_bt, ls_steuercode_soll, ls_steuercode_haben
long ll_bnr
date ldt_bdat, ldt_belegdat
decimal ld_betrag
boolean lb_valid, lb_sammel

ll_bnr = invo_datamodel_vfibu.of_get_next_belegnummer()
ads_data.SetItem(al_row, "belegnummer", ll_bnr)

ls_sk = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "sollkonto"))
ls_hk = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "habenkonto"))
ls_bt = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "buchungstext"))
ls_steuercode_soll = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "steuercode_soll"))
ls_steuercode_haben = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "steuercode_haben"))
ldt_bdat = Today()
ldt_belegdat = invo_utilities_vfibu.of_checknull(ads_data.GetItemDate(al_row, "belegdatum"))
ld_betrag = invo_utilities_vfibu.of_checknull(ads_data.GetItemNumber(al_row, "betrag"))

if ls_sk = "" or ls_hk = "" then
	messagebox("Eingabefehler", "Bitte Soll- und Habenkonto erfassen.")
	return -1
end if
if not invo_datamodel_vfibu.of_konto_existiert(ls_sk) or not invo_datamodel_vfibu.of_konto_existiert(ls_hk) then
	messagebox("Eingabefehler", "Konto existiert nicht oder ist keine buchbare Position.")
	return -1
end if

lb_valid = false
lb_sammel = false

if len(ls_sk) > 0 and len(ls_hk) > 0 then
	if isnumber(ls_sk) and isnumber(ls_hk) then
		lb_valid = true
	end if
else
	if len(ls_sk) > 0 then
		if isnumber(ls_sk) then
			lb_valid = true
		end if
	else
		if isnumber(ls_hk) then
			lb_valid = true
		end if
	end if
	lb_sammel = true
end if

if lb_valid then
	lb_valid = false
	if ld_betrag > 0 and ldt_bdat <> Date(1900, 1, 1) and ls_bt <> "" then
		if ldt_belegdat = Date(1900, 1, 1) then
			ldt_belegdat = ldt_bdat
		end if

		if invo_datamodel_vfibu.of_book_journal(ls_sk, ls_hk, ls_bt, ldt_belegdat, ldt_bdat, ld_betrag, ll_bnr, ls_steuercode_soll, ls_steuercode_haben) <> 0 then
			return -1
		end if

		lb_valid = true
	end if
end if

if not lb_valid then
	messagebox("Eingabefehler", "Bitte Eingaben korrigieren.")
	return -1
end if

return 0
end function
```

### 7b) `of_update` komplett ersetzen

```
public function integer of_update (long al_row, datastore ads_data, string as_bid);string ls_sk, ls_hk, ls_bt, ls_steuercode_soll, ls_steuercode_haben
date ldt_belegdat
decimal ld_betrag

ls_sk = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "sollkonto"))
ls_hk = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "habenkonto"))
ls_bt = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "buchungstext"))
ldt_belegdat = invo_utilities_vfibu.of_checknull(ads_data.GetItemDate(al_row, "belegdatum"))
ld_betrag = invo_utilities_vfibu.of_checknull(ads_data.GetItemNumber(al_row, "betrag"))
ls_steuercode_soll = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "steuercode_soll"))
ls_steuercode_haben = invo_utilities_vfibu.of_checknull(ads_data.GetItemString(al_row, "steuercode_haben"))

if ls_sk = "" or ls_hk = "" or ls_bt = "" or ld_betrag <= 0 or ldt_belegdat = Date(1900, 1, 1) then
	messagebox("Eingabefehler", "Bitte Eingaben korrigieren.")
	return -1
end if

if ls_sk = "" or ls_hk = "" then
	messagebox("Eingabefehler", "Bitte Soll- und Habenkonto erfassen.")
	return -1
end if
if not invo_datamodel_vfibu.of_konto_existiert(ls_sk) or not invo_datamodel_vfibu.of_konto_existiert(ls_hk) then
	messagebox("Eingabefehler", "Konto existiert nicht oder ist keine buchbare Position.")
	return -1
end if

return invo_datamodel_vfibu.of_update_journal(as_bid, ls_sk, ls_hk, ls_bt, ldt_belegdat, ld_betrag, ls_steuercode_soll, ls_steuercode_haben)
end function
```

---
## 8. Abgeschlossenes Jahr: Bearbeiten und Kopieren sperren
Objekt **`nuo_buchungstabelle_vfibu`**.
### 8a) `of_edit_buchung` komplett ersetzen

```
public subroutine of_edit_buchung (string as_bid);nvo_datamodel_vfibu lnvo

if as_bid = "" or IsNull(as_bid) then return

lnvo = gnvo_app.of_get_model()

if lnvo.of_is_year_closed(gnvo_app.of_get_year()) = 1 then
	messagebox("Nicht möglich", "Dieses Geschäftsjahr ist abgeschlossen. Buchungen können nicht mehr geändert oder kopiert werden.")
	return
end if

if lnvo.of_is_saldovortrag(as_bid) then
	messagebox("Nicht möglich", "Saldovorträge können hier nicht bearbeitet werden. Passen Sie stattdessen die Startsaldi an.")
	return
end if

if lnvo.of_count_body(as_bid) > 2 then
	OpenWithParm(w_new_sammelbuchung_vfibu, "EDIT:" + as_bid)
else
	OpenWithParm(w_new_journal_vfibu, "EDIT:" + as_bid)
end if
end subroutine
```

### 8b) `of_copy_buchung` komplett ersetzen

```
public subroutine of_copy_buchung (string as_bid);nvo_datamodel_vfibu lnvo

if as_bid = "" or IsNull(as_bid) then return

lnvo = gnvo_app.of_get_model()

if lnvo.of_is_year_closed(gnvo_app.of_get_year()) = 1 then
	messagebox("Nicht möglich", "Dieses Geschäftsjahr ist abgeschlossen. Buchungen können nicht mehr geändert oder kopiert werden.")
	return
end if

if lnvo.of_is_saldovortrag(as_bid) then
	messagebox("Nicht möglich", "Saldovorträge können nicht kopiert werden.")
	return
end if

if lnvo.of_count_body(as_bid) > 2 then
	OpenWithParm(w_new_sammelbuchung_vfibu, "COPY:" + as_bid)
else
	OpenWithParm(w_new_journal_vfibu, "COPY:" + as_bid)
end if
end subroutine
```

---
## 9. Lange Texte mit MWST (DB-Fehler bei über 100 Zeichen)
Objekt **`nvo_datamodel_vfibu`**.
### 9a) `of_book_journal` komplett ersetzen

```
public function integer of_book_journal (string as_sollkonto, string as_habenkonto, string as_text, date adt_belegdatum, date adt_buchungsdatum, decimal adl_betrag, long al_belegnummer, string as_steuercode_soll, string as_steuercode_haben);string ls_bid, ls_username, ls_mandant
long ll_bjahr
decimal ld_satz, ld_steuer, ld_netto
string ls_steuerkonto

ll_bjahr = gnvo_app.of_get_year()
if not of_is_datum_im_gj(adt_belegdatum, ll_bjahr) then
	MessageBox("Datum ausserhalb Geschäftsjahr", "Das Belegdatum liegt nicht im Geschäftsjahr " + of_format_gj(ll_bjahr) + " (" + of_format_gj_lang(ll_bjahr) + ")." + "~n~n" + "Bitte Datum oder Jahr prüfen.")
	return -1
end if
ls_mandant = gnvo_app.of_get_mandanten_id()
ls_username = gnvo_app.of_get_winuser()

ls_bid = invo_utilities_vfibu.of_create_guid_vfibu()

insert into fibubuchung_header(bid, belegdatum, buchungsdatum, username, belegnummer, buchungsjahr, mid)
values (:ls_bid, :adt_belegdatum, :adt_buchungsdatum, :ls_username, :al_belegnummer, :ll_bjahr, :ls_mandant);
if sqlca.sqlcode <> 0 then
	rollback;
	messagebox("Fehler Header", "SQLCode: " + string(sqlca.sqlcode) + "~n" + sqlca.sqlerrtext)
	return -1
end if

ld_netto = adl_betrag
ld_steuer = 0
if Len(Trim(as_steuercode_soll)) > 0 then
	if not of_get_steuercode_info(as_steuercode_soll, ld_satz, ls_steuerkonto) then
		rollback;
		messagebox("Eingabefehler", "Steuercode " + as_steuercode_soll + " ist ungültig, nicht aktiv oder hat kein gültiges Steuerkonto.")
		return -1
	end if
	ld_steuer = round(adl_betrag * ld_satz / (100 + ld_satz), 2)
	ld_netto = adl_betrag - ld_steuer
end if
if of_book_body(ls_bid, as_sollkonto, ld_netto, "S", as_text, as_steuercode_soll, "1") <> 0 then return -1
if ld_steuer > 0 then
	if of_book_body(ls_bid, ls_steuerkonto, ld_steuer, "S", Left("MWST - " + as_text, 100), as_steuercode_soll, "2") <> 0 then return -1
end if

ld_netto = adl_betrag
ld_steuer = 0
if Len(Trim(as_steuercode_haben)) > 0 then
	if not of_get_steuercode_info(as_steuercode_haben, ld_satz, ls_steuerkonto) then
		rollback;
		messagebox("Eingabefehler", "Steuercode " + as_steuercode_haben + " ist ungültig, nicht aktiv oder hat kein gültiges Steuerkonto.")
		return -1
	end if
	ld_steuer = round(adl_betrag * ld_satz / (100 + ld_satz), 2)
	ld_netto = adl_betrag - ld_steuer
end if
if of_book_body(ls_bid, as_habenkonto, ld_netto, "H", as_text, as_steuercode_haben, "1") <> 0 then return -1
if ld_steuer > 0 then
	if of_book_body(ls_bid, ls_steuerkonto, ld_steuer, "H", Left("MWST - " + as_text, 100), as_steuercode_haben, "2") <> 0 then return -1
end if

commit;
return 0
end function
```

### 9b) `of_update_journal` komplett ersetzen

```
public function integer of_update_journal (string as_bid, string as_sollkonto, string as_habenkonto, string as_text, date adt_belegdatum, decimal adl_betrag, string as_steuercode_soll, string as_steuercode_haben);long ll_bjahr
decimal ld_satz, ld_steuer, ld_netto
string ls_steuerkonto

ll_bjahr = gnvo_app.of_get_year()
if not of_is_datum_im_gj(adt_belegdatum, ll_bjahr) then
	MessageBox("Datum ausserhalb Geschäftsjahr", "Das Belegdatum liegt nicht im Geschäftsjahr " + of_format_gj(ll_bjahr) + " (" + of_format_gj_lang(ll_bjahr) + ")." + "~n~n" + "Bitte Datum oder Jahr prüfen.")
	return -1
end if

UPDATE fibubuchung_header
SET belegdatum = :adt_belegdatum
WHERE LOWER(CAST(bid AS varchar)) = LOWER(:as_bid);
if sqlca.sqlcode <> 0 then
	rollback;
	messagebox("Fehler Header", sqlca.sqlerrtext)
	return -1
end if

DELETE FROM fibubuchung_body
WHERE LOWER(CAST(bidheader AS varchar)) = LOWER(:as_bid);
if sqlca.sqlcode <> 0 then
	rollback;
	messagebox("Fehler", "Alte Buchungszeilen konnten nicht gelöscht werden: " + sqlca.sqlerrtext)
	return -1
end if

ld_netto = adl_betrag
ld_steuer = 0
if Len(Trim(as_steuercode_soll)) > 0 then
	if not of_get_steuercode_info(as_steuercode_soll, ld_satz, ls_steuerkonto) then
		rollback;
		messagebox("Eingabefehler", "Steuercode " + as_steuercode_soll + " ist ungültig, nicht aktiv oder hat kein gültiges Steuerkonto.")
		return -1
	end if
	ld_steuer = round(adl_betrag * ld_satz / (100 + ld_satz), 2)
	ld_netto = adl_betrag - ld_steuer
end if
if of_book_body(as_bid, as_sollkonto, ld_netto, "S", as_text, as_steuercode_soll, "1") <> 0 then return -1
if ld_steuer > 0 then
	if of_book_body(as_bid, ls_steuerkonto, ld_steuer, "S", Left("MWST - " + as_text, 100), as_steuercode_soll, "2") <> 0 then return -1
end if

ld_netto = adl_betrag
ld_steuer = 0
if Len(Trim(as_steuercode_haben)) > 0 then
	if not of_get_steuercode_info(as_steuercode_haben, ld_satz, ls_steuerkonto) then
		rollback;
		messagebox("Eingabefehler", "Steuercode " + as_steuercode_haben + " ist ungültig, nicht aktiv oder hat kein gültiges Steuerkonto.")
		return -1
	end if
	ld_steuer = round(adl_betrag * ld_satz / (100 + ld_satz), 2)
	ld_netto = adl_betrag - ld_steuer
end if
if of_book_body(as_bid, as_habenkonto, ld_netto, "H", as_text, as_steuercode_haben, "1") <> 0 then return -1
if ld_steuer > 0 then
	if of_book_body(as_bid, ls_steuerkonto, ld_steuer, "H", Left("MWST - " + as_text, 100), as_steuercode_haben, "2") <> 0 then return -1
end if

commit;
return 0
end function
```

---
## 10. Mandantenfilter
Objekt **`nvo_datamodel_vfibu`**.
### 10a) `of_get_next_belegnummer` komplett ersetzen

```
public function long of_get_next_belegnummer ();long ll_bjahr, ll_bnrmax
string ls_mid

ll_bjahr = gnvo_app.of_get_year()
ls_mid = gnvo_app.of_get_mandanten_id()

select max(belegnummer) into :ll_bnrmax from fibubuchung_header where buchungsjahr = :ll_bjahr and LOWER(CAST(mid AS varchar)) = LOWER(:ls_mid);

ll_bnrmax = invo_utilities_vfibu.of_checknull(ll_bnrmax)
if ll_bnrmax = 0 then
	ll_bnrmax = 1000
end if

return ll_bnrmax + 1
end function
```

### 10b) `of_count_buchungen` komplett ersetzen

```
public function long of_count_buchungen (string as_konto);long ll_count, ll_jahr
string ls_mid

ll_jahr = long(gnvo_app.of_get_year())
ll_count = 0
ls_mid = gnvo_app.of_get_mandanten_id()

select count(*) into :ll_count
  from fibubuchung_body b
  join fibubuchung_header h on b.bidheader = h.bid
 where b.konto = :as_konto
   and h.buchungsjahr = :ll_jahr
   and LOWER(CAST(h.mid AS varchar)) = LOWER(:ls_mid);

return ll_count
end function
```

---
## 11. Journal: Zeilen pro Beleg sortiert (Soll vor Haben, MWST-Zeile nach der Hauptzeile)
DataWindows **`d_buchungstabelle_vfibu`** und **`d_print_journal_vfibu`**: Edit Source → ganzen Inhalt durch die gleichnamige Datei in `datawindows/` ersetzen.

---
## 12. Druckkopf: Firmenname statt Personenname
Objekt **`nvo_datamodel_vfibu`**, Funktion **`of_get_mandant_name`**, komplett ersetzen.

```
public function string of_get_mandant_name ();string ls_mandant_id, ls_vorname, ls_nachname, ls_name
datastore lds_mandant
long ll_row, i

ls_mandant_id = gnvo_app.of_get_mandanten_id()

if isnull(ls_mandant_id) or trim(ls_mandant_id) = "" then return ""

lds_mandant = create datastore
lds_mandant.dataobject = "d_choose_ma_vfibu"
lds_mandant.settransobject(sqlca)
lds_mandant.retrieve()

for i = 1 to lds_mandant.rowcount()
	if lower(lds_mandant.getitemstring(i, "id")) = lower(ls_mandant_id) then
		ll_row = i
		exit
	end if
next

if ll_row > 0 then
	ls_name = lds_mandant.getitemstring(ll_row, "company_name")
	if isnull(ls_name) then ls_name = ""
	if trim(ls_name) = "" then
		ls_vorname = lds_mandant.getitemstring(ll_row, "firstname")
		ls_nachname = lds_mandant.getitemstring(ll_row, "lastname")
		if isnull(ls_vorname) then ls_vorname = ""
		if isnull(ls_nachname) then ls_nachname = ""
		ls_name = trim(ls_vorname + " " + ls_nachname)
	end if
else
	ls_name = ""
end if

destroy lds_mandant
return ls_name
end function
```

---
## 13. MWST-Code E0 mit Steuerkonto anlegen
Objekt **`nvo_datamodel_vfibu`**, Funktion **`of_insert_estv_rates`**, komplett ersetzen.

```
public function long of_insert_estv_rates (double ad_std, double ad_red, double ad_spc, long al_year, string as_mid, datawindow adw);string ls_praefix[3], ls_praefixname[3], ls_satzname[3], ls_stkonto[3], ls_kat[3]
string ls_code, ls_name, ls_suffix, ls_err
decimal ld_satz[3], ld_aktuell
long ll_count, ll_row, ll_neu, i, j

ls_praefix[1] = "V"
ls_praefixname[1] = "Verkauf"
ls_praefix[2] = "M"
ls_praefixname[2] = "Material/Dienstleistungen"
ls_praefix[3] = "I"
ls_praefixname[3] = "Investitionen"

for j = 1 to 3
	ls_stkonto[j] = of_get_standard_steuerkonto(ls_praefix[j])
next

ld_satz[1] = ad_std
ls_satzname[1] = "Normalsatz"
ls_kat[1] = "N"
ld_satz[2] = ad_red
ls_satzname[2] = "reduzierter Satz"
ls_kat[2] = "R"
ld_satz[3] = ad_spc
ls_satzname[3] = "Sondersatz Beherbergung"
ls_kat[3] = "S"

for i = 1 to 3
	ld_aktuell = ld_satz[i]
	if ld_aktuell > 0 then
		ls_suffix = string(integer(Round(ld_aktuell * 10, 0)))
		for j = 1 to 3
			ls_code = ls_praefix[j] + ls_suffix
			ls_name = Left(ls_praefixname[j] + " " + ls_satzname[i] + " " + string(ld_aktuell) + "%", 50)

			SELECT count(*) INTO :ll_count
			FROM public.steuercode
			WHERE code = :ls_code AND year = :al_year
			  AND LOWER(CAST(mid AS varchar)) = LOWER(:as_mid);
			if sqlca.sqlcode < 0 then
				ls_err = sqlca.sqlerrtext
				rollback;
				messagebox("SQL Fehler", "SELECT: " + ls_err)
				return -1
			end if
			if ll_count = 0 and adw.Find("code = '" + ls_code + "'", 1, adw.RowCount()) = 0 then
				ll_row = adw.InsertRow(0)
				adw.SetItem(ll_row, "code", ls_code)
				adw.SetItem(ll_row, "name", ls_name)
				adw.SetItem(ll_row, "steuersatz", ld_aktuell)
				adw.SetItem(ll_row, "saldosteuersatz", "0")
				adw.SetItem(ll_row, "mwst_typ", ls_praefix[j] + ls_kat[i])
				if ls_stkonto[j] <> "" then adw.SetItem(ll_row, "steuerkonto", ls_stkonto[j])
				ll_neu++
			end if
		next
	end if
next

ls_code = "E0"
SELECT count(*) INTO :ll_count
FROM public.steuercode
WHERE code = :ls_code AND year = :al_year
  AND LOWER(CAST(mid AS varchar)) = LOWER(:as_mid);
if sqlca.sqlcode < 0 then
	ls_err = sqlca.sqlerrtext
	rollback;
	messagebox("SQL Fehler", "SELECT: " + ls_err)
	return -1
end if
if ll_count = 0 and adw.Find("code = '" + ls_code + "'", 1, adw.RowCount()) = 0 then
	ll_row = adw.InsertRow(0)
	adw.SetItem(ll_row, "code", ls_code)
	adw.SetItem(ll_row, "name", "Export steuerbefreit 0%")
	adw.SetItem(ll_row, "steuersatz", 0)
	adw.SetItem(ll_row, "saldosteuersatz", "0")
	adw.SetItem(ll_row, "mwst_typ", "E0")
	if ls_stkonto[1] <> "" then adw.SetItem(ll_row, "steuerkonto", ls_stkonto[1])
	ll_neu++
end if

return ll_neu
end function
```

---
## Danach
Alles neu builden (Full Build), dann den Abschluss des Testmandanten nochmals als PDF drucken. Diese Werte müssen dann stehen:

| | Soll |
|---|---:|
| Bilanz Total Aktiven | 2'650'983.07 |
| Bilanz Total Passiven | 2'348'635.51 |
| Jahresgewinn | 302'347.56 |
| MWST Umsatzsteuer | 732'178.22 |
| MWST Vorsteuer | 536'654.37 |
| MWST Zahllast | 195'523.85 |
