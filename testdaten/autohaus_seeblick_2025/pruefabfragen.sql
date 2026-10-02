-- ============================================================
-- Prüfabfragen Testmandant Autohaus Seeblick AG, GJ 2025
-- Mit psql oder pgAdmin ausführen und mit SOLLWERTE.md vergleichen.
-- Mandanten-ID: 5c2df529-87e5-5cd5-a2b3-19d463057958
-- ============================================================

-- 1) Unausgeglichene Belege. Erwartet: 0 Zeilen
SELECT h.belegnummer, h.belegdatum,
       SUM(CASE WHEN b.sollhaben = 'S' THEN b.betrag ELSE 0 END) AS soll,
       SUM(CASE WHEN b.sollhaben = 'H' THEN b.betrag ELSE 0 END) AS haben
  FROM fibubuchung_header h JOIN fibubuchung_body b ON b.bidheader = h.bid
 WHERE h.mid = '5c2df529-87e5-5cd5-a2b3-19d463057958'
 GROUP BY h.bid, h.belegnummer, h.belegdatum
HAVING SUM(CASE WHEN b.sollhaben = 'S' THEN b.betrag ELSE -b.betrag END) <> 0;

-- 2) Buchungszeilen auf Konten, die es im Kontenplan nicht gibt oder die nicht buchbar sind. Erwartet: 0 Zeilen
SELECT b.konto, COUNT(*)
  FROM fibubuchung_body b JOIN fibubuchung_header h ON h.bid = b.bidheader
 WHERE h.mid = '5c2df529-87e5-5cd5-a2b3-19d463057958'
   AND NOT EXISTS (SELECT 1 FROM kontenrahmen_vfibu k
                    WHERE k.kontonr = b.konto AND k.year = h.buchungsjahr
                      AND k.mandanten_id = h.mid AND k.hierarchie >= 4)
 GROUP BY b.konto;

-- 3) Steuercodes gültig wie in of_get_steuercode_info. Erwartet: alle 10 Codes mit gueltig = true
SELECT s.code, s.name, s.steuersatz, s.steuerkonto, s.mwst_typ,
       EXISTS (SELECT 1 FROM kontenrahmen_vfibu k
                WHERE k.kontonr = s.steuerkonto AND k.year = s.year AND k.hierarchie >= 4 AND k.mandanten_id = s.mid
                  AND (COALESCE(TRIM(k.mwst_typ), '') = '' OR LEFT(COALESCE(s.mwst_typ, ''), 1) NOT IN ('V', 'M', 'I')
                       OR k.mwst_typ = 'S' || LEFT(s.mwst_typ, 1))) AS gueltig
  FROM steuercode s
 WHERE s.mid = '5c2df529-87e5-5cd5-a2b3-19d463057958' AND s.year = 2025
 ORDER BY s.code;

-- 4) Eröffnungsbilanz (View kontostart_vfibu)
--    total_ak_roh / total_pa_roh = wie das Fenster summiert (Minuskonten falsch herum)
--    total_ak / total_pa         = korrekt mit Vorzeichen nach Kontoseite
SELECT SUM(CASE WHEN k.kontotype = 'AK' THEN s.startsaldo ELSE 0 END) AS total_ak_roh,
       SUM(CASE WHEN k.kontotype = 'PA' THEN s.startsaldo ELSE 0 END) AS total_pa_roh,
       SUM(CASE WHEN k.kontotype = 'AK' THEN CASE WHEN k.konto_seite = 'S' THEN s.startsaldo ELSE -s.startsaldo END ELSE 0 END) AS total_ak,
       SUM(CASE WHEN k.kontotype = 'PA' THEN CASE WHEN k.konto_seite = 'H' THEN s.startsaldo ELSE -s.startsaldo END ELSE 0 END) AS total_pa
  FROM kontenrahmen_vfibu k
  JOIN kontostart_vfibu s ON s.kontonr = k.kontonr AND s.mandanten_id = k.mandanten_id AND s.year = k.year
 WHERE k.mandanten_id = '5c2df529-87e5-5cd5-a2b3-19d463057958' AND k.year = 2025
   AND k.kontotype IN ('AK', 'PA') AND k.hierarchie >= 4;

-- 5) Saldenliste per 31.12.2025 (View kontosaldo_live_vfibu), alle Konten mit Saldo <> 0
SELECT k.kontonr, k.name, k.kontotype, k.konto_seite, s.saldo
  FROM kontenrahmen_vfibu k
  JOIN kontosaldo_live_vfibu s ON s.kontonr = k.kontonr AND s.mandanten_id = k.mandanten_id AND s.year = k.year
 WHERE k.mandanten_id = '5c2df529-87e5-5cd5-a2b3-19d463057958' AND k.year = 2025
   AND k.hierarchie >= 4 AND s.saldo <> 0
 ORDER BY k.sort_columns;

-- 6) Bilanz- und ER-Totale (korrekt mit Vorzeichen nach Kontoseite)
SELECT k.kontotype,
       SUM(s.saldo) AS summe_roh,
       SUM(CASE WHEN k.konto_seite = (CASE WHEN k.kontotype IN ('AK', 'AU') THEN 'S' ELSE 'H' END)
                THEN s.saldo ELSE -s.saldo END) AS summe_korrekt
  FROM kontenrahmen_vfibu k
  JOIN kontosaldo_live_vfibu s ON s.kontonr = k.kontonr AND s.mandanten_id = k.mandanten_id AND s.year = k.year
 WHERE k.mandanten_id = '5c2df529-87e5-5cd5-a2b3-19d463057958' AND k.year = 2025 AND k.hierarchie >= 4
 GROUP BY k.kontotype ORDER BY k.kontotype;

-- 7) MWST pro Quartal und Code. Gleiche Zuordnung Netto- zu MWST-Zeile wie in of_get_mwst_zusammenfassung.
--    Vorzeichen: V/E im Haben +, M/I im Soll +
SELECT EXTRACT(QUARTER FROM h.belegdatum) AS quartal, b.steuercode, sc.steuersatz,
       SUM(CASE WHEN (b.sollhaben = 'H') = (LEFT(b.steuercode, 1) IN ('V', 'E')) THEN b.betrag ELSE -b.betrag END) AS netto,
       SUM(CASE WHEN (b.sollhaben = 'H') = (LEFT(b.steuercode, 1) IN ('V', 'E')) THEN COALESCE(t.betrag, 0) ELSE -COALESCE(t.betrag, 0) END) AS steuer
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
 WHERE h.mid = '5c2df529-87e5-5cd5-a2b3-19d463057958'
   AND b.steuercode IS NOT NULL AND b.steuercode <> ''
 GROUP BY 1, 2, 3
 ORDER BY 1, LEFT(b.steuercode, 1) DESC, 2;

-- 8) MWST-Zeilen ohne passende Netto-Zeile. Erwartet: 0 Zeilen
SELECT t.bidheader, t.text, t.betrag
  FROM fibubuchung_body t JOIN fibubuchung_header h ON h.bid = t.bidheader
 WHERE h.mid = '5c2df529-87e5-5cd5-a2b3-19d463057958' AND t.visible = '2'
   AND NOT EXISTS (SELECT 1 FROM fibubuchung_body b
                    WHERE b.bidheader = t.bidheader AND b.visible = '1' AND b.steuercode = t.steuercode
                      AND b.sollhaben = t.sollhaben AND LEFT('MWST - ' || b.text, 100) = t.text);
