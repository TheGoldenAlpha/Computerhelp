# Claude-Änderungen – Upgrade 7.x → PHP 8.3

Datum: 2026-04-21  
Bearbeitet von: Claude (claude-sonnet-4-6)  
Branch: `claude/fix-upgrade-errors-pj5Rk`

---

## Übersicht

Beim Upgrade von PHP 7.x auf PHP 8.3 haben sich in zwei Dateien kritische Fehler eingeschlichen. Alle Korrekturen sind unten detailliert aufgeführt.

---

## Datei 1: `include/presentation.php`

### Änderung 1.1 – `var`-Eigenschaft → `public` (Zeile 12)

**Problem:** `var` ist in PHP 8.x deprecated und erzeugt Warnungen.

| Vorher | Nachher |
|--------|---------|
| `var $searchvalue;` | `public $searchvalue;` |

---

### Änderung 1.2 – `each()` entfernt (8 Stellen)

**Problem:** `each()` wurde in PHP 7.2 deprecated und in PHP 8.0 vollständig **entfernt**. Führt zu einem Fatal Error.

**Ersatz:** `while(list($val, $opt)=each($vals))` → `foreach($vals as $val => $opt)`

| Funktion | Zeile (original) | Änderung |
|----------|-----------------|----------|
| `WriteCombo()` | 30 | `each()` → `foreach()` |
| `WriteComboDirect()` | 53 | `each()` → `foreach()` |
| `WriteComboFlex()` (Zweig: keine Felder) | 83 | `each()` → `foreach()` |
| `WriteComboFlex()` (Zweig: mit Feldern) | 113 | `each()` → `foreach()` |
| `WriteComboExt()` | 163 | `each()` → `foreach()` |
| `WriteComboONCHANGE()` | 182 | `each()` → `foreach()` |
| `WriteSelectionFlex()` (Zweig: keine Felder) | 223 | `each()` → `foreach()` |
| `WriteSelectionFlex()` (Zweig: mit Feldern) | 252 | `each()` → `foreach()` |

**Hinweis:** Beim Zweig „keine Felder" in `WriteSelectionFlex()` (Zeile 222–225 original) war zusätzlich ein Logikfehler vorhanden: Das `<option>`-Tag wurde ausserhalb der Schleife gerendert. Dies wurde im Zuge der `foreach`-Umstellung ebenfalls korrigiert – der `$res .=`-Aufruf wurde in den Schleifenkörper verschoben.

---

### Änderung 1.3 – `ereg_replace()` entfernt (3 Stellen)

**Problem:** `ereg_replace()` wurde in PHP 5.3 deprecated und in PHP 7.0 vollständig **entfernt**. Führt zu einem Fatal Error.

**Ersatz:** Da nur Leerzeichen entfernt werden (kein Regex-Muster benötigt), wird `str_replace()` verwendet.

| Funktion | Zeile (original) | Vorher | Nachher |
|----------|-----------------|--------|---------|
| (Turnier-Anzeige, `final==0`) | 1237 | `ereg_replace(" ", "", $tournaments[$i]['location'])` | `str_replace(" ", "", $tournaments[$i]['location'])` |
| (Turnier-Anzeige, `final==1`) | 1247 | `ereg_replace(" ", "", $tournaments[$i]['location'])` | `str_replace(" ", "", $tournaments[$i]['location'])` |
| `sendMail()` | 1269 | `ereg_replace(" ", "", $temp[$i]['location'])` | `str_replace(" ", "", $temp[$i]['location'])` |

---

## Datei 2: `admin/include/db_handler.php`

### Änderung 2.1 – `var`-Eigenschaften → `public` (Zeilen 10–30)

**Problem:** `var` ist in PHP 8.x deprecated.

Alle 21 Eigenschaften der Klasse `db_utils` wurden von `var $` auf `public $` umgestellt:

`$db`, `$version`, `$status`, `$db_lnk`, `$db_host`, `$db_user`, `$db_pass`, `$ftp_host`, `$ftp_user`, `$ftp_pass`, `$ftp_path`, `$cur_table`, `$table_defs`, `$table_struct`, `$pictures`, `$backgrounds`, `$iconsDir`, `$sms_gw_u_p`, `$last_ins_call`, `$fields`, `$log`

---

### Änderung 2.2 – PHPMailer 5.x API → PHPMailer 6.x API (Funktion `smtpmailer()`, Zeilen 2315–2354)

**Problem:** Die Datei importiert PHPMailer 6.x (`use PHPMailer\PHPMailer\PHPMailer`), rief aber noch die veraltete PHPMailer 5.x-API mit Großbuchstaben-Methoden auf. Diese Methoden existieren in PHPMailer 6.x nicht mehr → Fatal Error.

| Zeile (original) | Vorher (PHPMailer 5.x) | Nachher (PHPMailer 6.x) |
|-----------------|------------------------|--------------------------|
| 2320 | `$mail->IsSMTP()` | `$mail->isSMTP()` |
| 2329 | `$mail->SetFrom($from, $from_name, 0)` | `$mail->setFrom($from, $from_name, 0)` |
| 2331 | `$mail->AddReplyTo($replyto, $replyto)` | `$mail->addReplyTo($replyto, $replyto)` |
| 2333 | `$mail->AddReplyTo($from, $from_name)` | `$mail->addReplyTo($from, $from_name)` |
| 2335 | `$mail->IsHTML($HTML)` | `$mail->isHTML($HTML)` |
| 2345 | `$mail->AddAddress($to)` | `$mail->addAddress($to)` |
| 2347 | `$mail->Send()` | `$mail->send()` |

---

## Nicht verändert

- `admin/ckeditor/ckeditor_php4.php`: Enthält ebenfalls `var $`-Deklarationen, ist jedoch eine **externe Drittanbieter-Bibliothek** (CKEditor PHP4 Compatibility Wrapper) und wurde bewusst nicht angefasst.
- `/include/class.phpmailer.php` (PHPMailer 5.2.22): Alte Bibliothek bleibt unverändert vorhanden (wird nicht mehr aktiv eingebunden).

---

## Zusammenfassung

| # | Datei | Art | Schwere |
|---|-------|-----|---------|
| 1 | `include/presentation.php` | `var` → `public` | Mittel |
| 2 | `include/presentation.php` | 8× `each()` → `foreach()` | Kritisch |
| 3 | `include/presentation.php` | 3× `ereg_replace()` → `str_replace()` | Kritisch |
| 4 | `admin/include/db_handler.php` | 21× `var` → `public` | Mittel |
| 5 | `admin/include/db_handler.php` | 7× PHPMailer 5.x → 6.x API | Kritisch |
