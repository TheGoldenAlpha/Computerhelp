#!/usr/bin/env python3
"""
Gleicht die Sollwerte aus generate.py mit einer echten PostgreSQL-DB ab, in die
setup.txt und autohaus_seeblick_2025.sql eingespielt wurden.

Aufruf:  python3 verify_db.py "psql -h localhost -p 5431 -U postgres -d test_ch_migration"
"""
import runpy
import subprocess
import sys
from collections import defaultdict
from decimal import Decimal
from pathlib import Path

HERE = Path(__file__).resolve().parent
PSQL = sys.argv[1] if len(sys.argv) > 1 else "psql -h /tmp -p 5433 -d fibu"

g = runpy.run_path(str(HERE / "generate.py"), run_name="verify")
MID = g["MID"]


def sql(q):
    out = subprocess.run(PSQL.split() + ["-At", "-F", "\t", "-c", q], capture_output=True, text=True, check=True).stdout
    return [line.split("\t") for line in out.splitlines() if line]


fehler = 0


def check(name, ist, soll):
    global fehler
    ok = ist == soll
    if not ok:
        fehler += 1
    print(f"[{'OK ' if ok else 'ERR'}] {name}: ist={ist} soll={soll}")


# Mengen
check("Belege", int(sql(f"select count(*) from fibubuchung_header where mid='{MID}'")[0][0]), len(g["BELEGE"]))
check("Zeilen", int(sql(f"select count(*) from fibubuchung_body b join fibubuchung_header h on h.bid=b.bidheader where h.mid='{MID}'")[0][0]),
      sum(len(b.zeilen) for b in g["BELEGE"]))
check("unausgeglichene Belege", len(sql(f"""select h.bid from fibubuchung_header h join fibubuchung_body b on b.bidheader=h.bid
      where h.mid='{MID}' group by h.bid having sum(case when b.sollhaben='S' then b.betrag else -b.betrag end)<>0""")), 0)

# Saldo pro Konto (View kontosaldo_live_vfibu) und Startsaldo (kontostart_vfibu)
live = {r[0]: Decimal(r[1]) for r in sql(f"select kontonr, saldo from kontosaldo_live_vfibu where mandanten_id='{MID}' and year=2025")}
start = {r[0]: Decimal(r[1]) for r in sql(f"select kontonr, startsaldo from kontostart_vfibu where mandanten_id='{MID}' and year=2025")}
diff_live = [k for k in g["KONTO"] if live.get(k, Decimal(0)) != g["saldo_view"](k)]
diff_start = [k for k in g["KONTO"] if start.get(k, Decimal(0)) != g["saldo_view"](k, "3")]
check("Konten mit abweichendem saldo (kontosaldo_live_vfibu)", diff_live, [])
check("Konten mit abweichendem startsaldo (kontostart_vfibu)", diff_start, [])
check("Saldo 9100 Eröffnungsbilanz", live.get("9100"), Decimal("0.00"))

# Steuercodes gueltig (of_get_steuercode_info)
ung = sql(f"""select s.code from steuercode s where s.mid='{MID}' and s.year=2025 and s.aktiv and not exists (
  select 1 from kontenrahmen_vfibu k where k.kontonr=s.steuerkonto and k.year=s.year and k.hierarchie>=4 and k.mandanten_id=s.mid
  and (coalesce(trim(k.mwst_typ),'')='' or left(coalesce(s.mwst_typ,''),1) not in ('V','M','I') or k.mwst_typ='S'||left(s.mwst_typ,1)))""")
check("ungültige Steuercodes", ung, [])

# MWST pro Quartal/Code mit der Zuordnungslogik der App
rows = sql(f"""
SELECT extract(quarter from h.belegdatum)::int, b.steuercode,
       SUM(CASE WHEN (b.sollhaben='H') = (left(b.steuercode,1) in ('V','E')) THEN b.betrag ELSE -b.betrag END),
       SUM(CASE WHEN (b.sollhaben='H') = (left(b.steuercode,1) in ('V','E')) THEN coalesce(t.betrag,0) ELSE -coalesce(t.betrag,0) END)
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
 WHERE h.mid = '{MID}' AND b.steuercode IS NOT NULL AND b.steuercode <> ''
 GROUP BY 1, 2""")
ist = {(int(r[0]), r[1]): (Decimal(r[2]), Decimal(r[3])) for r in rows}
res = g["mwst_auswertung"]()
soll = {(qn, c): tuple(v) for qn in res for c, v in res[qn].items()}
check("MWST pro Quartal/Code (Netto, Steuer) wie App-Abfrage", {k: v for k, v in ist.items() if soll.get(k) != v}, {})
check("MWST-Kombinationen Quartal/Code", sorted(ist), sorted(soll))

# Totale
tot = {r[0]: Decimal(r[1]) for r in sql(f"""select k.kontotype, sum(case when k.konto_seite = (case when k.kontotype in ('AK','AU') then 'S' else 'H' end)
       then s.saldo else -s.saldo end) from kontenrahmen_vfibu k join kontosaldo_live_vfibu s on s.kontonr=k.kontonr and s.mandanten_id=k.mandanten_id
       and s.year=k.year where k.mandanten_id='{MID}' and k.year=2025 and k.hierarchie>=4 group by k.kontotype""")}
gewinn = tot["ER"] - tot["AU"]
check("Gewinn ER = Aktiven - Passiven", gewinn, tot["AK"] - tot["PA"])
print(f"      Aktiven {tot['AK']}  Passiven {tot['PA']}  Ertrag {tot['ER']}  Aufwand {tot['AU']}  Gewinn {gewinn}")

print()
print("ALLES OK" if fehler == 0 else f"{fehler} FEHLER")
sys.exit(1 if fehler else 0)
