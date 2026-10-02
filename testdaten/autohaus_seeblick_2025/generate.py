#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Testmandant "Autohaus Seeblick AG" - Geschaeftsjahr 2025 (01.01.2025 - 31.12.2025)

Erzeugt fuer die FIBU (PowerBuilder, PostgreSQL-Schema aus Files/db_syntax/postgres/setup.txt):
  - Mandant, Geschaeftsjahr, KMU-Kontenplan (Vorlage "Kontoplan kmu Juristische Person AG")
    mit Autohaus-Erweiterungen (Abteilungen Neuwagen / Occasionen / Werkstatt / Carrosserie /
    Ersatzteile / Shop)
  - MWST-Codes wie of_insert_estv_rates (V81/M81/I81, V26/M26/I26, V38/M38/I38, E0)
  - Eroeffnungsbilanz (visible = '3', gegen EB-Konto 9100) wie of_book_saldovortrag
  - Einzelbuchungen wie of_book_journal und Sammelbuchungen wie nuo_new_sammelbuchung_vfibu
    (Bruttobetrag, MWST herausgerechnet: round(betrag * satz / (100 + satz), 2),
     Netto-Zeile visible = '1', MWST-Zeile visible = '2' mit Text "MWST - <text>")

Ausgabe:
  autohaus_seeblick_2025.sql            -> in die DB einspielen (idempotent, loescht zuerst den Mandanten)
  Kontoplan KMU Autohaus Seeblick.txt   -> Kontenplan im Import-Format der App (ANSI, Tab-getrennt)
  SOLLWERTE.md                          -> alle erwarteten Resultate (Eroeffnungsbilanz, Saldenliste,
                                           Bilanz, Erfolgsrechnung, MWST pro Quartal, Kontrollsummen)

Alles deterministisch (fester Seed, uuid5) -> jeder Lauf erzeugt exakt dieselben Daten.
"""
import random
import uuid
from collections import defaultdict
from datetime import date, timedelta
from decimal import Decimal, ROUND_HALF_UP
from pathlib import Path

HERE = Path(__file__).resolve().parent
YEAR = 2025
GJ_VON = date(YEAR, 1, 1)
GJ_BIS = date(YEAR, 12, 31)
USERNAME = "testdaten"
NS = uuid.UUID("6b2f0c1e-7a51-4c55-9a3e-5e1b0a2d2025")
MID = str(uuid.uuid5(NS, "mandant-autohaus-seeblick"))
EB_KONTO = "9100"

rnd = random.Random(20250101)
_uuid_counter = 0


def new_uuid():
    global _uuid_counter
    _uuid_counter += 1
    return str(uuid.uuid5(NS, f"obj-{_uuid_counter}"))


def D(x):
    return Decimal(str(x))


def r2(x):
    return D(x).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def r05(x):
    """Rappenrundung auf 5 Rappen (Lohnabzuege, Barbetraege)."""
    return (D(x) * 20).quantize(Decimal("1"), rounding=ROUND_HALF_UP) / 20


def rbetrag(lo, hi, step=D("0.05")):
    """Zufallsbetrag zwischen lo und hi, auf step gerundet."""
    v = D(rnd.uniform(float(lo), float(hi)))
    return (v / step).quantize(Decimal("1"), rounding=ROUND_HALF_UP) * step


def chf(x):
    x = D(x).quantize(Decimal("0.01"))
    neg = x < 0
    s = f"{abs(x):,.2f}".replace(",", "'")
    return ("-" if neg else "") + s


# ============================================================================
# 1) KONTENPLAN  (Vorlage KMU AG + Autohaus-Erweiterungen)
# ============================================================================
RENAME = {
    "1020": "Bankguthaben ZKB Kontokorrent",
    "1200": "Handelswaren Neuwagen",
    "3200": "Handelserlöse Neuwagen",
    "3400": "Dienstleistungserlöse Werkstatt",
    "4200": "Handelswarenaufwand Neuwagen",
    "4000": "Materialaufwand Werkstatt / Lack",
    "5000": "Lohnaufwand Geschäftsleitung",
    "7500": "Ertrag Vermietung Monteurzimmer",
}
# mwst_typ-Anpassungen bestehender Konten
SET_MWST = {
    "7500": "VS",  # Beherbergung -> Standardcode V38
}
# neue Konten: kontonr -> (name, seite, uebertrag, kontotype, mwst_typ)
ADD = {
    "1010": ("PostFinance", "S", "1", "AK", ""),
    "1201": ("Handelswaren Occasionen", "S", "1", "AK", ""),
    "1202": ("Ersatzteile und Zubehör (Lager)", "S", "1", "AK", ""),
    "3210": ("Handelserlöse Occasionen", "H", "0", "ER", "VN"),
    "3220": ("Handelserlöse Ersatzteile und Zubehör", "H", "0", "ER", "VN"),
    "3230": ("Erlöse Shop / Kiosk (Lebensmittel)", "H", "0", "ER", "VR"),
    "3410": ("Dienstleistungserlöse Carrosserie / Spritzwerk", "H", "0", "ER", "VN"),
    "3420": ("Erlöse Ersatzwagen / Vermietung", "H", "0", "ER", "VN"),
    "4210": ("Handelswarenaufwand Occasionen", "S", "0", "AU", "MN"),
    "4220": ("Aufwand Ersatzteile und Zubehör", "S", "0", "AU", "MN"),
    "4230": ("Warenaufwand Shop / Kiosk", "S", "0", "AU", "MR"),
    "5010": ("Lohnaufwand Verkauf", "S", "0", "AU", ""),
    "5020": ("Lohnaufwand Werkstatt", "S", "0", "AU", ""),
    "5030": ("Lohnaufwand Carrosserie", "S", "0", "AU", ""),
    "5040": ("Lohnaufwand Ersatzteillager", "S", "0", "AU", ""),
    "5050": ("Lohnaufwand Administration", "S", "0", "AU", ""),
    "9100": ("Eröffnungsbilanz", "H", "1", "PA", ""),
}


def load_kontoplan():
    rows = []
    for line in (HERE / "vorlage_kontoplan_kmu_ag.txt").read_text(encoding="utf-8").splitlines():
        if not line.strip():
            continue
        p = line.split("\t")
        while len(p) < 6:
            p.append("")
        nr, name, seite, ueb, typ, mwst = [x.strip() for x in p[:6]]
        name = RENAME.get(nr, name)
        mwst = SET_MWST.get(nr, mwst)
        rows.append([nr, name, seite, ueb, typ, mwst])
    for nr in sorted(ADD):
        name, seite, ueb, typ, mwst = ADD[nr]
        # vor dem ersten 4-stelligen Konto mit groesserer Nummer einfuegen, das
        # hinter dem groessten kleineren 4-stelligen Konto liegt (bleibt in der Gruppe)
        idx_prev = max(i for i, r in enumerate(rows) if len(r[0]) == 4 and r[0] < nr or r[0] == nr[0])
        pos = len(rows)
        for i in range(idx_prev + 1, len(rows)):
            if len(rows[i][0]) <= 4 and (len(rows[i][0]) < 4 or rows[i][0] > nr):
                pos = i
                break
        rows.insert(pos, [nr, name, seite, ueb, typ, mwst])
    return rows


KONTOPLAN = load_kontoplan()
KONTO = {r[0]: {"name": r[1], "seite": r[2], "ueb": r[3] == "1", "typ": r[4], "mwst": r[5]} for r in KONTOPLAN}


def build_kontenrahmen():
    """Nachbau von nvo_datamodel_vfibu.of_parse_file_vfibu (hierarchie = Laenge Kontonr,
    parent = naechste offene Ebene darueber, sort_columns = laufende Nummer)."""
    guid_arr = {}
    out = []
    sort = 0
    for nr, name, seite, ueb, typ, mwst in KONTOPLAN:
        sort += 1
        gid = str(uuid.uuid5(NS, f"konto-{YEAR}-{nr}"))
        hier = len(nr)
        parent = None
        for lvl in range(hier - 1, 0, -1):
            if guid_arr.get(lvl):
                parent = guid_arr[lvl]
                break
        guid_arr[hier] = gid
        for c in range(hier + 1, 5):
            guid_arr[c] = ""
        out.append(dict(id=gid, kontonr=nr, name=name, hierarchie=hier, parent=parent, sort=sort,
                        seite=seite or "S", ueb=(ueb == "1"), typ=typ, mwst=mwst or None))
    return out


# ============================================================================
# 2) MWST-CODES  (wie of_insert_estv_rates 8.1 / 2.6 / 3.8 + E0)
# ============================================================================
STEUERKONTO = {"V": "2200", "M": "1170", "I": "1171"}
PRAEFIX = [("V", "Verkauf"), ("M", "Material/Dienstleistungen"), ("I", "Investitionen")]
SAETZE = [(D("8.1"), "Normalsatz", "N"), (D("2.6"), "reduzierter Satz", "R"), (D("3.8"), "Sondersatz Beherbergung", "S")]
STEUERCODES = {}
for satz, satzname, kat in SAETZE:
    suffix = str(int((satz * 10).quantize(Decimal("1"))))
    for p, pname in PRAEFIX:
        code = p + suffix
        STEUERCODES[code] = dict(name=f"{pname} {satzname} {satz}%"[:50], satz=satz, mwst_typ=p + kat,
                                 konto=STEUERKONTO[p])
# E0: die App legt E0 ohne Steuerkonto an -> of_get_steuercode_info verlangt aber ein gueltiges
# Steuerkonto. Damit E0 buchbar ist, wird hier 2200 hinterlegt (Steuer = 0, es entsteht keine MWST-Zeile).
STEUERCODES["E0"] = dict(name="Export steuerbefreit 0%", satz=D("0"), mwst_typ="E0", konto="2200")


# ============================================================================
# 3) BUCHUNGSMODELL  (exakt wie die App)
# ============================================================================
class Beleg:
    def __init__(self, datum, art, zeilen, opening=False):
        self.datum = datum
        self.art = art  # 'EB', 'JOURNAL', 'SAMMEL'
        self.zeilen = zeilen  # list of dict(konto, betrag, sh, text, code, visible)
        self.opening = opening
        self.nr = None
        self.bid = None


BELEGE = []


def _check_konto(k):
    assert k in KONTO and len(k) == 4, f"Konto {k} nicht buchbar"


def _split(konto, brutto, sh, text, code):
    """Eine Buchungsseite -> Netto-Zeile (+ MWST-Zeile), wie of_book_journal / Sammelbuchung."""
    _check_konto(konto)
    assert brutto > 0, (konto, brutto, text)
    assert len(text) <= 93, f"Text zu lang ({len(text)}): {text}"
    z = []
    steuer = D("0")
    if code:
        sc = STEUERCODES[code]
        satz = sc["satz"]
        steuer = r2(brutto * satz / (100 + satz))
    z.append(dict(konto=konto, betrag=brutto - steuer, sh=sh, text=text, code=code, visible="1"))
    if steuer > 0:
        z.append(dict(konto=STEUERCODES[code]["konto"], betrag=steuer, sh=sh, text="MWST - " + text,
                      code=code, visible="2"))
    return z


def journal(datum, soll, haben, text, betrag, code_soll=None, code_haben=None):
    betrag = r2(betrag)
    assert GJ_VON <= datum <= GJ_BIS
    z = _split(soll, betrag, "S", text, code_soll) + _split(haben, betrag, "H", text, code_haben)
    BELEGE.append(Beleg(datum, "JOURNAL", z))


def sammel(datum, zeilen):
    """zeilen: (konto, 'S'|'H', bruttobetrag, text, code|None)"""
    assert GJ_VON <= datum <= GJ_BIS
    s = sum(r2(b) for k, sh, b, t, c in zeilen if sh == "S")
    h = sum(r2(b) for k, sh, b, t, c in zeilen if sh == "H")
    assert s == h and s > 0, f"Sammelbuchung nicht ausgeglichen {datum}: S {s} H {h} {zeilen}"
    z = []
    for k, sh, b, t, c in zeilen:
        z += _split(k, r2(b), sh, t, c)
    BELEGE.append(Beleg(datum, "SAMMEL", z))


def eroeffnung(konto, saldo, text):
    """wie of_book_saldovortrag: saldo bezogen auf konto_seite, Gegenkonto = EB-Konto."""
    _check_konto(konto)
    saldo = r2(saldo)
    if saldo == 0:
        return
    seite = KONTO[konto]["seite"]
    if seite == "S":
        soll, haben = (konto, EB_KONTO) if saldo > 0 else (EB_KONTO, konto)
    else:
        haben, soll = (konto, EB_KONTO) if saldo > 0 else (EB_KONTO, konto)
    b = abs(saldo)
    BELEGE.append(Beleg(GJ_VON, "EB", [
        dict(konto=soll, betrag=b, sh="S", text=text, code=None, visible="3"),
        dict(konto=haben, betrag=b, sh="H", text=text, code=None, visible="3"),
    ], opening=True))


def saldo_bis(konto, bis, nur_bis_inkl=True):
    """Saldo eines Kontos (bezogen auf konto_seite) inkl. aller Belege mit datum <= bis."""
    seite = KONTO[konto]["seite"]
    s = D("0")
    for b in BELEGE:
        if b.datum > bis:
            continue
        for z in b.zeilen:
            if z["konto"] == konto:
                s += z["betrag"] if z["sh"] == seite else -z["betrag"]
    return s


def soll_minus_haben(konto, von, bis):
    s = D("0")
    for b in BELEGE:
        if von <= b.datum <= bis:
            for z in b.zeilen:
                if z["konto"] == konto:
                    s += z["betrag"] if z["sh"] == "S" else -z["betrag"]
    return s


def werktag(d):
    while d.weekday() >= 5:
        d += timedelta(days=1)
    return d


def clamp(d):
    return min(max(d, GJ_VON), GJ_BIS)


# ============================================================================
# 4) STAMMDATEN AUTOHAUS
# ============================================================================
MANDANT = dict(id=MID, firstname="Reto", lastname="Keller", company_name="Autohaus Seeblick AG",
               str="Churerstrasse 120", ort="Pfäffikon SZ", plz="8808", mail="info@autohaus-seeblick.ch",
               tel="055 410 25 25", gj_start="01.01", gj_first=YEAR)

# Mitarbeitende: (Name, Lohnkonto, Monatslohn brutto, BVG-AN, QST-Satz %, Lernende/r)
MITARBEITER = [
    ("Reto Keller (GF)", "5000", D("12500.00"), D("780.00"), None, False),
    ("Sabine Gerber", "5050", D("6800.00"), D("365.00"), None, False),
    ("Nadine Steiner (80%)", "5050", D("4640.00"), D("210.00"), None, False),
    ("Marco Bianchi", "5010", D("7200.00"), D("395.00"), None, False),
    ("Luca Meier", "5010", D("6900.00"), D("370.00"), None, False),
    ("Daniel Frei", "5020", D("7400.00"), D("410.00"), None, False),
    ("Patrick Huber", "5020", D("5900.00"), D("295.00"), None, False),
    ("Jonas Schmid", "5020", D("5600.00"), D("270.00"), None, False),
    ("Tobias Weber (Grenzgänger)", "5020", D("5700.00"), D("280.00"), D("4.5"), False),
    ("Kevin Baumann (Lernender)", "5020", D("1150.00"), D("0.00"), None, True),
    ("Stefan Brunner", "5030", D("6300.00"), D("320.00"), None, False),
    ("Ivan Kovacevic", "5030", D("5800.00"), D("285.00"), None, False),
    ("Thomas Wyss", "5040", D("5500.00"), D("265.00"), None, False),
]
VERKAEUFER = {"Marco Bianchi": D("0.0125"), "Luca Meier": D("0.0100")}  # Provision auf Neuwagenumsatz netto
AN_AHV, AN_ALV, AN_NBU, AN_KTG = D("0.053"), D("0.011"), D("0.0095"), D("0.005")
AG_AHV, AG_ALV, AG_FAK, AG_VK, AG_BU, AG_KTG = D("0.053"), D("0.011"), D("0.016"), D("0.003"), D("0.007"), D("0.005")

KUNDEN = ["Müller Beat", "Schneider Andrea", "Huber Thomas", "Weber Claudia", "Fischer Martin", "Meier Sandra",
          "Keller Urs", "Brunner Ruth", "Gerber Philipp", "Frei Monika", "Baumann Stefan", "Zimmermann Petra",
          "Moser Daniel", "Steiner Karin", "Graf Roland", "Wyss Lea", "Bachmann Pascal", "Suter Nicole",
          "Kälin Josef", "Ochsner Rita", "Bürgi Simon", "Lienert Franziska", "Marty Reto", "Schuler Anita",
          "Ammann Christian", "Hess Barbara", "Pfister Lukas", "Rüegg Silvia", "Kistler Markus",
          "Bucher Elektro AG", "Gartenbau Höfe GmbH", "Bäckerei Feusi", "Treuhand Etzel AG", "Malerei Kryenbühl",
          "Spitex Höfe", "Zürichsee Immobilien AG", "Schreinerei Mächler", "Gemeinde Freienbach"]
NEUWAGEN = ["VW ID.4 Pro", "VW Golf 1.5 eTSI", "VW Tiguan 2.0 TDI 4Motion", "Skoda Octavia Combi RS",
            "Skoda Enyaq iV 80", "Audi A4 Avant 40 TDI", "Audi Q5 Sportback", "Cupra Formentor VZ",
            "VW T-Roc R-Line", "Skoda Kodiaq 4x4", "Audi e-tron GT", "VW Multivan eHybrid"]
OCCASIONEN = ["VW Polo 1.0 TSI (2019)", "Skoda Fabia Combi (2018)", "Audi A3 Sportback (2020)",
              "VW Passat Variant (2017)", "Seat Leon ST (2019)", "Toyota Yaris Hybrid (2020)",
              "BMW 320d Touring (2018)", "Mercedes C200 (2017)", "VW Golf GTI (2016)", "Skoda Superb (2019)",
              "Ford Kuga 2.0 (2018)", "Renault Clio (2021)", "Opel Astra K (2019)", "Volvo XC60 (2017)"]

# ============================================================================
# 5) EROEFFNUNGSBILANZ 01.01.2025
# ============================================================================
EROEFFNUNG = [
    ("1000", "6850.40"), ("1010", "18240.15"), ("1020", "462486.75"), ("1060", "38500.00"),
    ("1100", "168420.30"), ("1109", "8400.00"), ("1140", "5000.00"), ("1176", "1225.00"),
    ("1200", "845000.00"), ("1201", "412500.00"), ("1202", "186300.00"), ("1300", "9600.00"),
    ("1500", "185000.00"), ("1509", "92500.00"), ("1510", "78000.00"), ("1519", "31200.00"),
    ("1520", "42000.00"), ("1529", "25200.00"), ("1530", "96000.00"), ("1539", "38400.00"),
    ("1540", "64000.00"), ("1549", "28800.00"),
    ("2000", "386540.20"), ("2030", "45000.00"), ("2100", "650000.00"), ("2201", "28764.35"),
    ("2208", "24500.00"), ("2270", "18640.50"), ("2279", "1240.00"), ("2300", "12800.00"),
    ("2330", "35000.00"), ("2450", "250000.00"), ("2800", "500000.00"), ("2950", "100000.00"),
    ("2960", "150000.00"),
]
# offene Posten per 01.01. (muessen den Eroeffnungssalden entsprechen)
OP_DEBI_EB = [("Zürichsee Immobilien AG", "RE 24-1187", "52480.00", 9), ("Graf Roland", "RE 24-1192", "38960.00", 14),
              ("Bucher Elektro AG", "RE 24-1201", "41275.30", 21), ("Spitex Höfe", "RE 24-1205", "18630.00", 17),
              ("Gemeinde Freienbach", "RE 24-1210", "17075.00", 31)]
OP_KREDI_EB = [("Swiss Auto Import AG", "RG 98821", "248760.00", 10), ("Teilehandel Ost AG", "RG 55102", "61240.20", 8),
               ("Auto Auktion Schweiz AG", "RG A-7741", "52380.00", 15), ("Lackwerk Zürich AG", "RG 3390", "14830.00", 13),
               ("Treuhand Etzel AG", "RG 2024-88", "9330.00", 24)]
assert sum(D(x[2]) for x in OP_DEBI_EB) == D("168420.30")
assert sum(D(x[2]) for x in OP_KREDI_EB) == D("386540.20")


def eb_plug():
    ak = D("0")
    pa = D("0")
    for k, v in EROEFFNUNG:
        v = D(v)
        sign = 1 if KONTO[k]["seite"] == ("S" if KONTO[k]["typ"] == "AK" else "H") else -1
        if KONTO[k]["typ"] == "AK":
            ak += sign * v
        else:
            pa += sign * v
    return ak - pa  # Gewinnvortrag als Ausgleich


GEWINNVORTRAG = eb_plug()
assert GEWINNVORTRAG > 0
for k, v in EROEFFNUNG:
    eroeffnung(k, D(v), "Eröffnungsbilanz 01.01.2025")
eroeffnung("2970", GEWINNVORTRAG, "Eröffnungsbilanz 01.01.2025")

# ============================================================================
# 6) GESCHAEFTSFAELLE 2025
# ============================================================================
offene_debi = []   # dict(datum_zahlung, kunde, re, betrag)
offene_kredi = []  # dict(datum_zahlung, lieferant, re, betrag, skonto)

for kunde, re, betrag, tage in OP_DEBI_EB:
    offene_debi.append(dict(zahlung=werktag(date(YEAR, 1, 1) + timedelta(days=tage)), kunde=kunde, re=re, betrag=D(betrag)))
for lief, re, betrag, tage in OP_KREDI_EB:
    offene_kredi.append(dict(zahlung=werktag(date(YEAR, 1, 1) + timedelta(days=tage)), lieferant=lief, re=re,
                             betrag=D(betrag), skonto=False))

re_nr = [1300]


def next_re():
    re_nr[0] += 1
    return f"RE 25-{re_nr[0]}"


def debitor(datum, kunde, re, betrag, ziel_von=10, ziel_bis=40, nie=False):
    if nie:
        return
    offene_debi.append(dict(zahlung=werktag(datum + timedelta(days=rnd.randint(ziel_von, ziel_bis))), kunde=kunde,
                            re=re, betrag=r2(betrag)))


def kreditor(datum, lieferant, re, betrag, ziel=30, skonto=False):
    offene_kredi.append(dict(zahlung=werktag(datum + timedelta(days=ziel)), lieferant=lieferant, re=re,
                             betrag=r2(betrag), skonto=skonto))


neuwagen_netto_monat = defaultdict(lambda: defaultdict(Decimal))  # monat -> verkaeufer -> netto
anzahlungen_offen = [("Ochsner Rita", D("20000.00")), ("Treuhand Etzel AG", D("25000.00"))]  # EB 2030 = 45'000
konkurs_rechnung = None

# --- Transitorische Aufloesung Vorjahr -------------------------------------
journal(date(YEAR, 1, 3), "6300", "1300", "Auflösung TA Versicherungsprämie 2025 (Vorjahr)", D("9600.00"))
journal(date(YEAR, 1, 3), "2300", "6500", "Auflösung TP Revisionshonorar 2024", D("9800.00"))
journal(date(YEAR, 1, 3), "2300", "6400", "Auflösung TP Energie Q4/2024", D("3000.00"))

monate = range(1, 13)
for m in monate:
    m_start = date(YEAR, m, 1)
    m_end = (date(YEAR, m + 1, 1) - timedelta(days=1)) if m < 12 else date(YEAR, 12, 31)

    # ---------------- Neuwagen-Verkaeufe -----------------------------------
    n_neu = rnd.randint(6, 10) if m not in (1, 8) else rnd.randint(4, 6)
    for i in range(n_neu):
        d = werktag(m_start + timedelta(days=rnd.randint(0, 26)))
        if d > m_end:
            d = werktag(m_end - timedelta(days=3))
        kunde = rnd.choice(KUNDEN)
        modell = rnd.choice(NEUWAGEN)
        preis = rbetrag(29800, 98500, D("50"))
        re = next_re()
        vk = rnd.choice(list(VERKAEUFER))
        netto = preis - r2(preis * D("8.1") / D("108.1"))
        neuwagen_netto_monat[m][vk] += netto
        txt = f"{re} {kunde} {modell}"
        # Einkauf beim Importeur ca. 84 % des Verkaufspreises, Rechnung 5-15 Tage vorher
        ek = r2(preis * D(str(rnd.uniform(0.87, 0.91))))
        d_ek = clamp(werktag(d - timedelta(days=rnd.randint(5, 15))))
        rg = f"RG {rnd.randint(100000, 999999)}"
        journal(d_ek, "4200", "2000", f"{rg} Swiss Auto Import AG {modell}", ek, code_soll="M81")
        kreditor(d_ek, "Swiss Auto Import AG", rg, ek, ziel=30)

        if anzahlungen_offen and m <= 2:
            # Auslieferung mit Anzahlung aus Vorjahr (Konto 2030)
            kunde, anz = anzahlungen_offen.pop(0)
            txt = f"{re} {kunde} {modell}"
            sammel(d, [("2030", "S", anz, f"Verrechnung Anzahlung {kunde}", None),
                       ("1100", "S", preis - anz, f"{re} {kunde} Restbetrag", None),
                       ("3200", "H", preis, txt, "V81")])
            debitor(d, kunde, re, preis - anz)
        elif rnd.random() < 0.40:
            # Neuwagen mit Eintausch (Occasion vom Privatkunden, ohne MWST) -> Sammelbuchung
            occ = rnd.choice(OCCASIONEN)
            eintausch = rbetrag(4500, min(26000, float(preis) * 0.45), D("100"))
            sammel(d, [("1100", "S", preis - eintausch, f"{re} {kunde} Restbetrag nach Eintausch", None),
                       ("4210", "S", eintausch, f"Eintausch {occ} von {kunde}", None),
                       ("3200", "H", preis, txt, "V81")])
            debitor(d, kunde, re, preis - eintausch)
        else:
            journal(d, "1100", "3200", txt, preis, code_haben="V81")
            debitor(d, kunde, re, preis)

    # ---------------- Neue Anzahlungen fuer Bestellungen -------------------
    if m in (3, 6, 9, 11):
        kunde = rnd.choice(KUNDEN)
        journal(werktag(date(YEAR, m, 12)), "1020", "2030", f"Anzahlung Neuwagenbestellung {kunde}", D("10000.00"))
        anzahlungen_offen.append((kunde, D("10000.00")))
    if m in (4, 7, 10) and anzahlungen_offen and m > 2:
        kunde, anz = anzahlungen_offen.pop(0)
        d = werktag(date(YEAR, m, 22))
        modell = rnd.choice(NEUWAGEN)
        preis = rbetrag(42000, 76000, D("50"))
        re = next_re()
        netto = preis - r2(preis * D("8.1") / D("108.1"))
        neuwagen_netto_monat[m]["Marco Bianchi"] += netto
        ek = r2(preis * D("0.89"))
        rg = f"RG {rnd.randint(100000, 999999)}"
        journal(werktag(d - timedelta(days=7)), "4200", "2000", f"{rg} Swiss Auto Import AG {modell}", ek, code_soll="M81")
        kreditor(werktag(d - timedelta(days=7)), "Swiss Auto Import AG", rg, ek)
        sammel(d, [("2030", "S", anz, f"Verrechnung Anzahlung {kunde}", None),
                   ("1100", "S", preis - anz, f"{re} {kunde} Restbetrag", None),
                   ("3200", "H", preis, f"{re} {kunde} {modell}", "V81")])
        debitor(d, kunde, re, preis - anz)

    # ---------------- Occasionen ------------------------------------------
    for i in range(rnd.randint(7, 12)):
        d = werktag(m_start + timedelta(days=rnd.randint(0, 26)))
        if d > m_end:
            d = werktag(m_end - timedelta(days=4))
        occ = rnd.choice(OCCASIONEN)
        kunde = rnd.choice(KUNDEN)
        preis = rbetrag(6900, 38900, D("100"))
        re = next_re()
        if rnd.random() < 0.3:
            # Barzahlung / Kartenzahlung direkt auf Bank
            journal(d, "1020", "3210", f"{re} {kunde} {occ} bar/Karte", preis, code_haben="V81")
        else:
            journal(d, "1100", "3210", f"{re} {kunde} {occ}", preis, code_haben="V81")
            debitor(d, kunde, re, preis, 5, 30)
    # Occasions-Einkauf ueber Auktion (mit MWST) und von Privaten (ohne MWST)
    for i in range(rnd.randint(5, 8)):
        d = werktag(m_start + timedelta(days=rnd.randint(0, 25)))
        occ = rnd.choice(OCCASIONEN)
        ek = rbetrag(6500, 26000, D("50"))
        rg = f"RG A-{rnd.randint(7800, 9999)}"
        journal(d, "4210", "2000", f"{rg} Auto Auktion Schweiz AG {occ}", ek, code_soll="M81")
        kreditor(d, "Auto Auktion Schweiz AG", rg, ek, ziel=20)
    for i in range(rnd.randint(1, 3)):
        d = werktag(m_start + timedelta(days=rnd.randint(0, 25)))
        occ = rnd.choice(OCCASIONEN)
        ek = rbetrag(3000, 15000, D("100"))
        journal(d, "4210", "1020", f"Ankauf {occ} von Privat {rnd.choice(KUNDEN)}", ek)
    # Export Occasion nach Deutschland (steuerbefreit E0)
    if m in (2, 5, 8, 11):
        d = werktag(date(YEAR, m, 18))
        occ = rnd.choice(OCCASIONEN)
        preis = rbetrag(9000, 21000, D("100"))
        re = next_re()
        journal(d, "1100", "3210", f"{re} Export DE Autohandel Lörrach GmbH {occ}", preis, code_haben="E0")
        debitor(d, "Autohandel Lörrach GmbH", re, preis, 10, 25)

    # ---------------- Werkstatt / Carrosserie: woechentliche Sammelbuchung ---
    d = m_start
    while d <= m_end:
        if d.weekday() == 4:  # Freitag
            kw = d.isocalendar()[1]
            arbeit = rbetrag(7500, 13500)
            teile = rbetrag(5500, 10500)
            carro = rbetrag(2000, 6500)
            mietw = rbetrag(300, 1800) if rnd.random() < 0.6 else D("0")
            bar = r05(D(rnd.uniform(0.10, 0.20)) * (arbeit + teile))
            total = arbeit + teile + carro + mietw
            zeilen = [("1100", "S", total - bar, f"Werkstattrechnungen KW {kw} auf Rechnung", None),
                      ("1000", "S", bar, f"Werkstattrechnungen KW {kw} bar", None),
                      ("3400", "H", arbeit, f"Werkstatt Arbeit KW {kw}", "V81"),
                      ("3220", "H", teile, f"Werkstatt Ersatzteile KW {kw}", "V81"),
                      ("3410", "H", carro, f"Carrosserie / Spritzwerk KW {kw}", "V81")]
            if mietw > 0:
                zeilen.append(("3420", "H", mietw, f"Ersatzwagen KW {kw}", "V81"))
            sammel(d, zeilen)
            # Debitoren-Sammelposten der Woche: Zahlung verteilt ueber 3 Tranchen
            rest = total - bar
            for t, anteil in enumerate((D("0.5"), D("0.3"))):
                teil = r2(rest * anteil)
                offene_debi.append(dict(zahlung=werktag(d + timedelta(days=12 + 9 * t)), kunde=f"Werkstattkunden KW {kw}",
                                        re=f"Tranche {t + 1}", betrag=teil))
                rest -= teil
            offene_debi.append(dict(zahlung=werktag(d + timedelta(days=35)), kunde=f"Werkstattkunden KW {kw}",
                                    re="Tranche 3", betrag=rest))
            # Theke / Shop (bar)
            theke = rbetrag(1800, 4200)
            shop = rbetrag(250, 650)
            sammel(d, [("1000", "S", theke + shop, f"Kassenumsatz Theke/Shop KW {kw}", None),
                       ("3220", "H", theke, f"Thekenverkauf Ersatzteile KW {kw}", "V81"),
                       ("3230", "H", shop, f"Shop Snacks/Getränke KW {kw}", "V26")])
            # Ersatzteil-Einkauf (2 Lieferanten), Teilehandel Ost mit 2% Skonto
            ek1 = r2(teile * D(str(rnd.uniform(0.64, 0.70))) + theke * D("0.66"))
            rg1 = f"RG {rnd.randint(55200, 59999)}"
            journal(werktag(d - timedelta(days=2)), "4220", "2000", f"{rg1} Teilehandel Ost AG Ersatzteile", ek1, code_soll="M81")
            kreditor(werktag(d - timedelta(days=2)), "Teilehandel Ost AG", rg1, ek1, ziel=10, skonto=True)
            if rnd.random() < 0.5:
                ek2 = rbetrag(800, 3500)
                rg2 = f"RG {rnd.randint(1000, 9999)}"
                journal(werktag(d - timedelta(days=1)), "4220", "2000", f"{rg2} Autoteile Müller GmbH Zubehör", ek2, code_soll="M81")
                kreditor(werktag(d - timedelta(days=1)), "Autoteile Müller GmbH", rg2, ek2, ziel=30)
            # Bankeinzahlung Kasse (Kasse nie unter ca. 3'000)
            einzahlung = (saldo_bis("1000", d) - D("3000")).quantize(Decimal("1")) // 1000 * 1000
            if einzahlung > 0:
                journal(werktag(d + timedelta(days=3)) if d + timedelta(days=3) <= m_end else d,
                        "1020", "1000", f"Bareinzahlung Kasse KW {kw}", einzahlung)
        d += timedelta(days=1)

    # ---------------- Material / Fremdleistungen / Shop-Einkauf -------------
    lack = rbetrag(5500, 11800)
    rg = f"RG {rnd.randint(3400, 3999)}"
    dd = werktag(date(YEAR, m, 8))
    journal(dd, "4000", "2000", f"{rg} Lackwerk Zürich AG Lack/Verbrauchsmaterial", lack, code_soll="M81")
    kreditor(dd, "Lackwerk Zürich AG", rg, lack, ziel=30)
    fremd = rbetrag(1200, 4800)
    rg = f"RG {rnd.randint(100, 999)}"
    dd = werktag(date(YEAR, m, 14))
    journal(dd, "4400", "2000", f"{rg} Scheiben-Service Höfe GmbH Fremdarbeiten", fremd, code_soll="M81")
    kreditor(dd, "Scheiben-Service Höfe GmbH", rg, fremd, ziel=30)
    shopek = rbetrag(700, 1500)
    dd = werktag(date(YEAR, m, 5))
    journal(dd, "4230", "2000", f"RG {rnd.randint(10000, 99999)} Prodega Snacks/Getränke Shop", shopek, code_soll="M26")
    kreditor(dd, "Prodega", "Shop", shopek, ziel=20)

    # ---------------- Monteurzimmer (Beherbergung 3.8 %) --------------------
    zimmer = rbetrag(1400, 2600, D("10"))
    journal(werktag(date(YEAR, m, 2)), "1020", "7500", f"Vermietung Monteurzimmer {m:02d}/{YEAR}", zimmer, code_haben="V38")

    # ---------------- Betriebsaufwand ---------------------------------------
    journal(werktag(date(YEAR, m, 1)), "6000", "1020", f"Miete Showroom/Werkstatt {m:02d}/{YEAR}", D("14500.00"))
    journal(werktag(date(YEAR, m, 1)), "6000", "1020", f"Nebenkosten Akonto {m:02d}/{YEAR}", D("1350.00"))
    journal(werktag(date(YEAR, m, 6)), "6570", "1020", f"DMS-Lizenz Garage Software {m:02d}/{YEAR}", D("890.40"), code_soll="I81")
    journal(werktag(date(YEAR, m, 7)), "6500", "1020", f"Swisscom Telefon/Internet {m:02d}/{YEAR}", rbetrag(380, 520), code_soll="I81")
    journal(werktag(date(YEAR, m, 10)), "6200", "1020", f"Treibstoff Vorführ-/Ersatzwagen {m:02d}/{YEAR}", rbetrag(1100, 2300), code_soll="I81")
    journal(werktag(date(YEAR, m, 15)), "6260", "1020", f"Leasing Abschleppwagen {m:02d}/{YEAR}", D("1185.00"), code_soll="I81")
    journal(werktag(date(YEAR, m, 15)), "6105", "1020", f"Leasing Reifenwuchtmaschine {m:02d}/{YEAR}", D("324.25"), code_soll="I81")
    journal(werktag(date(YEAR, m, 20)), "6700", "1020", f"Reinigung Showroom {m:02d}/{YEAR}", D("1620.00"), code_soll="I81")
    journal(werktag(date(YEAR, m, 20)), "6700", "1000", f"Kaffee/Gipfeli Kundenlounge {m:02d}/{YEAR}", rbetrag(180, 340), code_soll="I26")
    journal(werktag(date(YEAR, m, 25)), "6900", "1020", f"Bankspesen ZKB {m:02d}/{YEAR}", rbetrag(45, 120))
    journal(werktag(date(YEAR, m, 26)), "6900", "1010", f"Kontoführung PostFinance {m:02d}/{YEAR}", D("15.00"))
    if m % 2 == 0:
        journal(werktag(date(YEAR, m, 12)), "6600", "1020", f"Inserat Höfner Volksblatt {m:02d}/{YEAR}", rbetrag(900, 2400), code_soll="I81")
    if m in (3, 9):
        journal(werktag(date(YEAR, m, 18)), "6600", "1020", "Online-Werbung AutoScout24 Paket", D("4860.00"), code_soll="I81")
    if m in (3, 6, 9, 12):
        q = (m - 1) // 3 + 1
        journal(werktag(date(YEAR, m, 28)), "6400", "1020", f"EW Höfe Strom Q{q}/{YEAR}", rbetrag(6200, 9400), code_soll="I81")
        service = rbetrag(1800, 3900)
        journal(werktag(date(YEAR, m, 28)), "6100", "2000", f"RG S-{q}{m:02d} Maha Service AG Service Hebebühnen Q{q}", service, code_soll="I81")
        kreditor(werktag(date(YEAR, m, 28)), "Maha Service AG", f"RG S-{q}{m:02d}", service, ziel=30)
        # Zins Kontokorrentkredit 2100
        journal(werktag(date(YEAR, m, 30)), "6900", "1020", f"Zins Kontokorrentkredit Q{q}/{YEAR}", D("6093.75"))
    if m == 1:
        journal(werktag(date(YEAR, 1, 15)), "6300", "1020", "Betriebshaftpflicht/Sachversicherung 2025", D("19450.00"))
        journal(werktag(date(YEAR, 1, 20)), "6300", "1020", "Motorfahrzeugversicherung Händlerschilder 2025", D("8640.00"))
        journal(werktag(date(YEAR, 1, 31)), "6300", "1020", "Verkehrsabgaben Händlerschilder 2025", D("1860.00"))
    if m == 2:
        journal(werktag(date(YEAR, 2, 14)), "6500", "2000", "RG 2025-14 Treuhand Etzel AG Revision 2024", D("9800.00"), code_soll="I81")
        kreditor(werktag(date(YEAR, 2, 14)), "Treuhand Etzel AG", "RG 2025-14", D("9800.00"), ziel=20)
    if m == 3:
        journal(date(YEAR, 3, 7), "6700", "1020", "Hotel Genève Salon de l'Auto 3 Nächte", D("1494.00"), code_soll="I38")
        journal(date(YEAR, 3, 7), "6700", "1020", "Messespesen Salon de l'Auto Verpflegung", D("612.80"), code_soll="I81")
        journal(date(YEAR, 3, 20), "6500", "1020", "Fachzeitschriften AutoSchweiz Abo 2025", D("348.00"), code_soll="I26")
    if m == 9:
        journal(date(YEAR, 9, 12), "6700", "1020", "Hotel Werkschulung Wolfsburg-Partner Luzern", D("622.80"), code_soll="I38")
        journal(date(YEAR, 9, 15), "5800", "1020", "Weiterbildung Hochvolt-Kurs Werkstatt", D("2400.00"))
    if m == 11:
        journal(date(YEAR, 11, 28), "5800", "1020", "Personalanlass Jahresessen", D("2860.00"))
        journal(date(YEAR, 11, 28), "6600", "1020", "Kundenevent Winterreifen-Apéro", D("1850.50"), code_soll="I81")

    # ---------------- Investitionen -----------------------------------------
    if m == 2:
        journal(date(YEAR, 2, 20), "1500", "2000", "RG 4471 Maha Service AG neue 4-Säulen-Hebebühne", D("38500.00"), code_soll="I81")
        kreditor(date(YEAR, 2, 20), "Maha Service AG", "RG 4471", D("38500.00"), ziel=30)
    if m == 6:
        journal(date(YEAR, 6, 11), "1540", "2000", "RG 9087 Bosch Diagnosesystem KTS 590", D("14800.00"), code_soll="I81")
        kreditor(date(YEAR, 6, 11), "Robert Bosch AG", "RG 9087", D("14800.00"), ziel=30)
    if m == 9:
        journal(date(YEAR, 9, 4), "1520", "1020", "Server + 4 Arbeitsplatz-PCs Werkstatt", D("9850.00"), code_soll="I81")
    if m == 11:
        journal(date(YEAR, 11, 6), "1510", "2000", "RG 551 Möbel Pfister Business Showroom-Möblierung", D("22400.00"), code_soll="I81")
        kreditor(date(YEAR, 11, 6), "Möbel Pfister Business", "RG 551", D("22400.00"), ziel=30)

    # ---------------- Loehne: Sammelbuchung pro Monat -----------------------
    dl = werktag(date(YEAR, m, 25))
    brutto_konto = defaultdict(Decimal)
    zeilen_h = []
    an_ahv = an_alv = an_nbu_ktg = an_bvg = qst = D("0")
    lohnsumme = D("0")
    lohnsumme_bvg = D("0")
    for name, konto, lohn, bvg, qst_satz, lern in MITARBEITER:
        brutto = lohn
        if m == 12:
            brutto += lohn  # 13. Monatslohn
        if name in VERKAEUFER and m in (3, 6, 9, 12):
            q_monate = range(m - 2, m + 1)
            prov = r05(sum(neuwagen_netto_monat[x][name] for x in q_monate) * VERKAEUFER[name])
            brutto += prov
        brutto_konto[konto] += brutto
        lohnsumme += brutto
        a_ahv = r05(brutto * AN_AHV)
        a_alv = r05(brutto * AN_ALV)
        a_nk = r05(brutto * (AN_NBU + AN_KTG))
        a_qst = r05(brutto * qst_satz / 100) if qst_satz else D("0")
        an_ahv += a_ahv
        an_alv += a_alv
        an_nbu_ktg += a_nk
        an_bvg += bvg
        lohnsumme_bvg += bvg
        qst += a_qst
        netto = brutto - a_ahv - a_alv - a_nk - bvg - a_qst
        if name == "Patrick Huber" and m <= 10:
            netto -= D("500.00")
            zeilen_h.append(("1140", "H", D("500.00"), f"Rückzahlung Lohnvorschuss {name} {m:02d}/{YEAR}", None))
        zeilen_h.append(("1020", "H", netto, f"Nettolohn {name} {m:02d}/{YEAR}", None))
    zeilen = [(k, "S", v, f"Bruttolöhne {KONTO[k]['name'].replace('Lohnaufwand ', '')} {m:02d}/{YEAR}", None)
              for k, v in sorted(brutto_konto.items())]
    zeilen += [("2270", "H", an_ahv, f"AHV/IV/EO Arbeitnehmer {m:02d}/{YEAR}", None),
               ("2270", "H", an_alv, f"ALV Arbeitnehmer {m:02d}/{YEAR}", None),
               ("2270", "H", an_nbu_ktg, f"NBU/KTG Arbeitnehmer {m:02d}/{YEAR}", None),
               ("2270", "H", an_bvg, f"BVG Arbeitnehmer {m:02d}/{YEAR}", None)]
    if qst > 0:
        zeilen.append(("2279", "H", qst, f"Quellensteuer {m:02d}/{YEAR}", None))
    zeilen += zeilen_h
    sammel(dl, zeilen)
    # Arbeitgeberbeitraege
    ag = [("AHV/IV/EO/FAK/VK Arbeitgeber", r05(lohnsumme * (AG_AHV + AG_FAK + AG_VK))),
          ("ALV Arbeitgeber", r05(lohnsumme * AG_ALV)),
          ("BU/KTG Arbeitgeber", r05(lohnsumme * (AG_BU + AG_KTG))),
          ("BVG Arbeitgeber", lohnsumme_bvg)]
    total_ag = sum(v for t, v in ag)
    sammel(dl, [("5700", "S", total_ag, f"Sozialversicherungen Arbeitgeber {m:02d}/{YEAR}", None)] +
           [("2270", "H", v, f"{t} {m:02d}/{YEAR}", None) for t, v in ag])

    # ---------------- Zahlung Sozialversicherungen / QST ---------------------
    # QST des Vormonats am 15.
    dq = werktag(date(YEAR, m, 15))
    q_saldo = saldo_bis("2279", dq - timedelta(days=1))
    if q_saldo > 0:
        journal(dq, "2279", "1020", f"Abrechnung Quellensteuer KSTA SZ per {dq.strftime('%d.%m.%Y')}", q_saldo)
    if m in (1, 4, 7, 10):
        # Sozialversicherungen: Saldo per Quartalsende (Vorquartal) begleichen
        stichtag = date(YEAR, m, 1) - timedelta(days=1)
        sv = saldo_bis("2270", stichtag) if stichtag >= GJ_VON else saldo_bis("2270", GJ_VON)
        if sv > 0:
            journal(werktag(date(YEAR, m, 10)), "2270", "1020", f"Zahlung SVA/BVG/UVG Abrechnung per {stichtag.strftime('%d.%m.%Y') if stichtag >= GJ_VON else '31.12.2024'}", sv)

    # ---------------- Zahlungseingang Debitoren: woechentlich Sammelbuchung --
    # ---------------- Zahlungsausgang Kreditoren: woechentlich Sammelbuchung -
    d = m_start
    while d <= m_end:
        if d.weekday() == 1:  # Dienstag
            von = d - timedelta(days=6)
            fae = [o for o in offene_debi if o["zahlung"] <= d and o.get("bezahlt") is None]
            if fae:
                zeilen = []
                for o in fae:
                    o["bezahlt"] = d
                    zeilen.append(("1100", "H", o["betrag"], f"Zahlung {o['kunde']} {o['re']}", None))
                tot = sum(z[2] for z in zeilen)
                sammel(d, [("1020", "S", tot, f"Zahlungseingänge Debitoren {d.strftime('%d.%m.%Y')}", None)] + zeilen)
        if d.weekday() == 3:  # Donnerstag: Zahlungslauf
            fae = [o for o in offene_kredi if o["zahlung"] <= d and o.get("bezahlt") is None and o["betrag"] > 0]
            if fae:
                zeilen = []
                bank = D("0")
                skonto = D("0")
                for o in fae:
                    o["bezahlt"] = d
                    zeilen.append(("2000", "S", o["betrag"], f"Zahlung {o['lieferant']} {o['re']}", None))
                    if o["skonto"]:
                        sk = r2(o["betrag"] * D("0.02"))
                        skonto += sk
                        bank += o["betrag"] - sk
                    else:
                        bank += o["betrag"]
                zeilen.append(("1020", "H", bank, f"Zahlungslauf Kreditoren {d.strftime('%d.%m.%Y')}", None))
                if skonto > 0:
                    zeilen.append(("4900", "H", skonto, f"Skonto 2% Teilehandel Ost {d.strftime('%d.%m.%Y')}", "M81"))
                sammel(d, zeilen)
        d += timedelta(days=1)

    # ---------------- Spezialfaelle -----------------------------------------
    if m == 3:
        journal(date(YEAR, 3, 31), "2208", "1020", "Zahlung Staats-/Bundessteuer 2024 (Schlussrechnung)", D("24500.00"))
    if m == 4:
        # Dividende Wertschriften mit Verrechnungssteuer
        sammel(date(YEAR, 4, 16), [("1020", "S", D("910.00"), "Dividende Nestlé-Aktien netto", None),
                                   ("1176", "S", D("490.00"), "Verrechnungssteuer 35% Dividende Nestlé", None),
                                   ("6950", "H", D("1400.00"), "Dividendenertrag Nestlé-Aktien brutto", None)])
    if m == 5:
        # Generalversammlung: Gewinnverwendung + Dividendenausschuettung
        journal(date(YEAR, 5, 15), "2970", "2261", "GV-Beschluss Dividende 2024", D("120000.00"))
        sammel(date(YEAR, 5, 22), [("2261", "S", D("120000.00"), "Auszahlung Dividende 2024", None),
                                   ("1020", "H", D("78000.00"), "Dividende 2024 netto an Aktionäre", None),
                                   ("2206", "H", D("42000.00"), "Verrechnungssteuer 35% Dividende 2024", None)])
    if m == 5:
        # Hotel fuer Fremdmonteur (Garantieeinsatz), als bezogene Dienstleistung -> Beherbergung 3.8 %
        journal(date(YEAR, 5, 14), "4400", "2000", "RG 7781 Hotel Seedamm Plaza Unterkunft Fremdmonteur", D("456.00"), code_soll="M38")
        kreditor(date(YEAR, 5, 14), "Hotel Seedamm Plaza", "RG 7781", D("456.00"), ziel=20)
    if m == 6:
        journal(date(YEAR, 6, 2), "1020", "1176", "Rückerstattung Verrechnungssteuer 2024", D("1225.00"))
        journal(date(YEAR, 6, 20), "2206", "1020", "Zahlung Verrechnungssteuer Dividende ESTV", D("42000.00"))
        journal(date(YEAR, 6, 30), "2450", "1020", "Amortisation Darlehen Raiffeisen", D("50000.00"))
        journal(date(YEAR, 6, 30), "6900", "1020", "Darlehenszins Raiffeisen 1. Semester", D("3125.00"))
        # Werkstattkunde, der spaeter in Konkurs geht (Rechnung bleibt offen)
        konkurs_rechnung = (next_re(), D("6485.40"))
        journal(date(YEAR, 6, 19), "1100", "3400", f"{konkurs_rechnung[0]} Garage Kunz GmbH Motorrevision",
                konkurs_rechnung[1], code_haben="V81")
    if m == 7:
        # nachtraeglicher Rabatt (Gutschrift) -> Erloesminderung mit MWST-Korrektur
        journal(date(YEAR, 7, 9), "3800", "1100", "Gutschrift Kulanz Rabatt Spitex Höfe", D("1620.00"), code_soll="V81")
        journal(date(YEAR, 7, 15), "1100", "1020", "Rückvergütung Gutschrift Kulanz an Spitex Höfe", D("1620.00"))
    if m == 9:
        # Konkurs Garage Kunz -> Debitorenverlust inkl. MWST-Korrektur
        journal(date(YEAR, 9, 24), "3805", "1100", f"Debitorenverlust Konkurs Garage Kunz GmbH {konkurs_rechnung[0]}",
                konkurs_rechnung[1], code_soll="V81")
        journal(date(YEAR, 9, 25), "8900", "1020", "Akontozahlung Staats-/Bundessteuer 2025", D("20000.00"))
    if m == 10:
        journal(date(YEAR, 10, 31), "1020", "1060", "Verkauf Wertschriften (Teilverkauf zum Buchwert)", D("8500.00"))
    if m == 12:
        journal(date(YEAR, 12, 15), "2000", "4900", "Jahresbonus Swiss Auto Import AG 2025", D("18500.00"), code_haben="M81")
        journal(date(YEAR, 12, 31), "6900", "1020", "Darlehenszins Raiffeisen 2. Semester", D("2500.00"))
        journal(date(YEAR, 12, 31), "1020", "6950", "Zinsertrag Bankguthaben 2025", D("412.35"))

    # ---------------- MWST-Abrechnung (Quartal) -----------------------------
    if m in (3, 6, 9, 12):
        q = m // 3
        qe = m_end
        us = saldo_bis("2200", qe)
        vm = saldo_bis("1170", qe)
        vi = saldo_bis("1171", qe)
        zahl = us - vm - vi
        zeilen = []
        for konto, wert, txt in (("2200", -us, "Umsatzsteuer"), ("1170", vm, "Vorsteuer Material/DL"),
                                 ("1171", vi, "Vorsteuer Inv./Betriebsaufwand"), ("2201", zahl, "Zahllast ESTV")):
            # wert > 0 -> Haben-Zeile, wert < 0 -> Soll-Zeile
            if wert != 0:
                zeilen.append((konto, "H" if wert > 0 else "S", abs(wert), f"MWST-Abrechnung Q{q}/{YEAR} {txt}", None))
        sammel(qe, zeilen)
    if m in (2, 5, 8, 11):
        # Zahlung der Zahllast des Vorquartals (60 Tage)
        offen = saldo_bis("2201", date(YEAR, m, 1))
        if offen > 0:
            journal(werktag(date(YEAR, m, 26)), "2201", "1020",
                    f"Zahlung MWST ESTV {'Q4/2024' if m == 2 else f'Q{(m - 2) // 3}/{YEAR}'}", offen)

for o in offene_debi:
    assert o["betrag"] > 0

# ============================================================================
# 7) ABSCHLUSSBUCHUNGEN 31.12.2025
# ============================================================================
SE = GJ_BIS
# Abschreibungen (direkt vom Anschaffungswert, indirekt ueber WB-Konten)
for anl, wb, satz, text in [("1500", "1509", D("0.10"), "Maschinen/Hebebühnen 10%"),
                            ("1510", "1519", D("0.125"), "Mobiliar/Einrichtungen 12.5%"),
                            ("1520", "1529", D("0.25"), "Informatik 25%"),
                            ("1530", "1539", D("0.20"), "Fahrzeuge 20%"),
                            ("1540", "1549", D("0.15"), "Werkzeuge/Diagnose 15%")]:
    afa = (saldo_bis(anl, SE) * satz).quantize(Decimal("1"), rounding=ROUND_HALF_UP)
    journal(SE, "6800", wb, f"Abschreibung {text} auf {chf(saldo_bis(anl, SE))}", afa)
# Delkredere 5 % der Debitoren
debi_se = saldo_bis("1100", SE)
delk_soll = (debi_se * D("0.05") / 100).quantize(Decimal("1"), rounding=ROUND_HALF_UP) * 100
delk_diff = delk_soll - saldo_bis("1109", SE)
if delk_diff > 0:
    journal(SE, "3805", "1109", f"Erhöhung Delkredere auf 5% von {chf(debi_se)}", delk_diff)
elif delk_diff < 0:
    journal(SE, "1109", "3805", f"Auflösung Delkredere auf 5% von {chf(debi_se)}", -delk_diff)
# Warenvorraete (Inventur) -> Bestandesaenderung ueber Aufwandkonten
for lager, aufw, inventur, text in [("1200", "4200", D("912000.00"), "Neuwagen"),
                                    ("1201", "4210", D("398000.00"), "Occasionen"),
                                    ("1202", "4220", D("193450.00"), "Ersatzteile")]:
    diff = inventur - saldo_bis(lager, SE)
    if diff > 0:
        journal(SE, lager, aufw, f"Bestandeszunahme {text} lt. Inventur {chf(inventur)}", diff)
    elif diff < 0:
        journal(SE, aufw, lager, f"Bestandesabnahme {text} lt. Inventur {chf(inventur)}", -diff)
# Transitorische
journal(SE, "1300", "6300", "TA Vorauszahlung Versicherungsprämie 2026", D("10200.00"))
journal(SE, "6500", "2300", "TP Revisionshonorar 2025", D("10500.00"))
journal(SE, "6400", "2300", "TP Energie Q4/2025 (noch nicht fakturiert)", D("3400.00"))
journal(SE, "6700", "2330", "Erhöhung Rückstellung Garantiearbeiten", D("5000.00"))


# Steuern: Gewinn vor Steuern ermitteln, 14 % Steuern (gerundet auf 100), abzgl. Akonto
def erfolg():
    e = D("0")
    for k, v in KONTO.items():
        if len(k) != 4 or v["typ"] not in ("ER", "AU"):
            continue
        smh = soll_minus_haben(k, GJ_VON, GJ_BIS)
        e += -smh
    return e  # Ertrag - Aufwand


gewinn_vor = erfolg() + soll_minus_haben("8900", GJ_VON, GJ_BIS)
steuern_total = (gewinn_vor * D("0.14") / 100).quantize(Decimal("1"), rounding=ROUND_HALF_UP) * 100
rueckst = steuern_total - soll_minus_haben("8900", GJ_VON, GJ_BIS)
assert rueckst > 0
journal(SE, "8900", "2208", f"Rückstellung direkte Steuern 2025 (Total {chf(steuern_total)})", rueckst)

# ============================================================================
# 8) BELEGNUMMERN (wie of_get_next_belegnummer: erste Nummer 1001)
# ============================================================================
order = sorted(range(len(BELEGE)), key=lambda i: (BELEGE[i].datum, 0 if BELEGE[i].opening else 1, i))
BELEGE = [BELEGE[i] for i in order]
nr = 1000
for b in BELEGE:
    b.bid = new_uuid()
    if not b.opening:
        nr += 1
        b.nr = nr
    for z in b.zeilen:
        z["bbid"] = new_uuid()
    s = sum(z["betrag"] for z in b.zeilen if z["sh"] == "S")
    h = sum(z["betrag"] for z in b.zeilen if z["sh"] == "H")
    assert s == h, (b.datum, b.zeilen)


# ============================================================================
# 9) SQL
# ============================================================================
def q(v):
    if v is None:
        return "NULL"
    if isinstance(v, bool):
        return "TRUE" if v else "FALSE"
    if isinstance(v, (int, Decimal)):
        return str(v)
    if isinstance(v, date):
        return f"'{v.isoformat()}'"
    return "'" + str(v).replace("'", "''") + "'"


def write_sql():
    L = []
    w = L.append
    w("-- ============================================================")
    w("-- TESTMANDANT  Autohaus Seeblick AG  -  Geschäftsjahr 2025")
    w("-- generiert von testdaten/autohaus_seeblick_2025/generate.py (nicht von Hand ändern)")
    w("-- Schema: Files/db_syntax/postgres/setup.txt")
    w(f"-- Mandanten-ID: {MID}")
    w(f"-- Belege: {len(BELEGE)}  (davon Eröffnung: {sum(1 for b in BELEGE if b.opening)}, "
      f"Sammelbuchungen: {sum(1 for b in BELEGE if b.art == 'SAMMEL')}, "
      f"Einzelbuchungen: {sum(1 for b in BELEGE if b.art == 'JOURNAL')})")
    w(f"-- Buchungszeilen: {sum(len(b.zeilen) for b in BELEGE)}")
    w("-- Das Skript ist wiederholbar: bestehende Daten dieses Mandanten werden zuerst gelöscht.")
    w("-- ============================================================")
    w("SET client_encoding = 'UTF8';")
    w("BEGIN;")
    w("")
    w("-- Aufräumen (nur dieser Mandant)")
    w(f"DELETE FROM fibubuchung_body WHERE bidheader IN (SELECT bid FROM fibubuchung_header WHERE mid = '{MID}');")
    w(f"DELETE FROM fibubuchung_header WHERE mid = '{MID}';")
    w(f"DELETE FROM kontenrahmen_vfibu WHERE mandanten_id = '{MID}';")
    w(f"DELETE FROM steuercode WHERE mid = '{MID}';")
    w(f"DELETE FROM mandant_konten_vfibu WHERE mid = '{MID}';")
    w(f"DELETE FROM jahre WHERE mid = '{MID}';")
    w(f"DELETE FROM mandanten_vfibu WHERE id = '{MID}';")
    w("")
    w("-- Mandant")
    M = MANDANT
    w("INSERT INTO mandanten_vfibu (id, firstname, lastname, company_name, str, ort, plz, mail, tel, gj_start, gj_first, "
      "is_verlaengertes_gj, verlaengertes_gj, is_ch) VALUES ("
      + ", ".join(q(x) for x in [M["id"], M["firstname"], M["lastname"], M["company_name"], M["str"], M["ort"], M["plz"],
                                 M["mail"], M["tel"], M["gj_start"], M["gj_first"], False, None, True]) + ");")
    w("")
    w("-- Geschäftsjahr (versteuerungsart 1 = effektive Methode)")
    w(f"INSERT INTO jahre (year, mid, closed, gj_von, gj_bis, versteuerungsart) VALUES ({YEAR}, '{MID}', FALSE, "
      f"{q(GJ_VON)}, {q(GJ_BIS)}, 1);")
    w("")
    w("-- Kontenplan (KMU AG + Autohaus-Erweiterungen)")
    for k in build_kontenrahmen():
        w("INSERT INTO kontenrahmen_vfibu (kontonr, name, id, hierarchie, parent_konto, sort_columns, mandanten_id, year, "
          "konto_seite, uebertragsfeld, kontotype, mwst_typ) VALUES ("
          + ", ".join(q(x) for x in [k["kontonr"], k["name"], k["id"], k["hierarchie"], k["parent"], k["sort"], MID, YEAR,
                                     k["seite"], k["ueb"], k["typ"], k["mwst"]]) + ");")
    w("")
    w("-- MWST-Codes 2025 (8.1 / 2.6 / 3.8 / Export 0)")
    for code, sc in STEUERCODES.items():
        w("INSERT INTO steuercode (code, name, steuersatz, saldosteuersatz, year, mid, steuerkonto, aktiv, mwst_typ) VALUES ("
          + ", ".join(q(x) for x in [code, sc["name"], sc["satz"], False, YEAR, MID, sc["konto"], True, sc["mwst_typ"]]) + ");")
    w("")
    w("-- Eröffnungsbilanz-Konto (EB) und Übertragskonto (UE) für den Saldovortrag ins Folgejahr")
    w(f"INSERT INTO mandant_konten_vfibu (mid, year, kontoart, kontonr) VALUES ('{MID}', {YEAR}, 'EB', '{EB_KONTO}');")
    w(f"INSERT INTO mandant_konten_vfibu (mid, year, kontoart, kontonr) VALUES ('{MID}', {YEAR}, 'UE', '{EB_KONTO}');")
    w("")
    w("-- Buchungen")
    for b in BELEGE:
        w(f"INSERT INTO fibubuchung_header (bid, belegdatum, buchungsdatum, erfassungsdatum, username, belegnummer, buchungsjahr, mid) "
          f"VALUES ('{b.bid}', {q(b.datum)}, {q(b.datum)}, {q(b.datum.isoformat() + ' 08:00:00')}, '{USERNAME}', "
          f"{q(b.nr)}, {YEAR}, '{MID}');")
        for z in b.zeilen:
            w(f"INSERT INTO fibubuchung_body (bbid, bidheader, konto, betrag, text, sollhaben, visible, steuercode) VALUES ("
              + ", ".join(q(x) for x in [z["bbid"], b.bid, z["konto"], z["betrag"], z["text"], z["sh"], z["visible"], z["code"]])
              + ");")
    w("")
    w("COMMIT;")
    (HERE / "autohaus_seeblick_2025.sql").write_text("\n".join(L) + "\n", encoding="utf-8")


def write_kontoplan_txt():
    lines = []
    for nr, name, seite, ueb, typ, mwst in KONTOPLAN:
        parts = [nr, name, seite, ueb, typ] + ([mwst] if mwst else [])
        lines.append("\t".join(parts))
    (HERE / "Kontoplan KMU Autohaus Seeblick.txt").write_bytes(("\r\n".join(lines) + "\r\n").encode("cp1252"))


# ============================================================================
# 10) SOLLWERTE
# ============================================================================
def saldo_view(konto, nur_visible=None):
    """wie View kontosaldo_live_vfibu / kontostart_vfibu: bezogen auf konto_seite"""
    seite = KONTO[konto]["seite"]
    s = D("0")
    for b in BELEGE:
        for z in b.zeilen:
            if z["konto"] == konto and (nur_visible is None or z["visible"] == nur_visible):
                s += z["betrag"] if z["sh"] == seite else -z["betrag"]
    return s


def nat(konto, wert):
    """Saldo in 'natuerlicher' Bilanzrichtung: Aktivkonto + = Soll, Passivkonto + = Haben,
    Aufwand + = Soll, Ertrag + = Haben (Minuskonten werden negativ)."""
    typ = KONTO[konto]["typ"]
    normal = "S" if typ in ("AK", "AU") else "H"
    return wert if KONTO[konto]["seite"] == normal else -wert


def mwst_auswertung():
    """pro Quartal und Code: steuerbares Entgelt (Netto, Vorzeichen: Haben + / Soll - bei V/E;
    Soll + / Haben - bei M/I) und Steuer."""
    res = defaultdict(lambda: defaultdict(lambda: [D("0"), D("0")]))
    for b in BELEGE:
        qn = (b.datum.month - 1) // 3 + 1
        for z in b.zeilen:
            if not z["code"]:
                continue
            p = z["code"][0]
            sign = 1 if (z["sh"] == "H") == (p in ("V", "E")) else -1
            idx = 0 if z["visible"] == "1" else 1
            res[qn][z["code"]][idx] += sign * z["betrag"]
    return res


def write_sollwerte():
    L = []
    w = L.append
    kr = build_kontenrahmen()
    bebuchte = sorted({z["konto"] for b in BELEGE for z in b.zeilen})
    w("# Sollwerte Testmandant Autohaus Seeblick AG, Geschäftsjahr 2025")
    w("")
    w(f"Mandanten-ID: `{MID}`. Geschäftsjahr 01.01.2025 bis 31.12.2025, effektive MWST-Abrechnung (versteuerungsart 1).")
    w("Generiert von `generate.py`. Alle Werte stammen aus denselben Buchungen, die in `autohaus_seeblick_2025.sql` stehen.")
    w("")
    w("## Mengengerüst")
    w("")
    n_eb = sum(1 for b in BELEGE if b.opening)
    n_s = sum(1 for b in BELEGE if b.art == "SAMMEL")
    n_j = sum(1 for b in BELEGE if b.art == "JOURNAL")
    zeilen = [z for b in BELEGE for z in b.zeilen]
    w("| Was | Anzahl |")
    w("|---|---:|")
    w(f"| Belege total (fibubuchung_header) | {len(BELEGE)} |")
    w(f"| davon Eröffnungsbuchungen (Belegnummer leer, visible 3) | {n_eb} |")
    w(f"| davon Einzelbuchungen (Journal) | {n_j} |")
    w(f"| davon Sammelbuchungen | {n_s} |")
    w(f"| Belegnummern | 1001 bis {max(b.nr for b in BELEGE if b.nr)} |")
    w(f"| Buchungszeilen total (fibubuchung_body) | {len(zeilen)} |")
    for v, t in (("1", "Buchungszeilen visible 1"), ("2", "MWST-Zeilen visible 2"), ("3", "Eröffnungszeilen visible 3")):
        w(f"| {t} | {sum(1 for z in zeilen if z['visible'] == v)} |")
    w(f"| Konten im Kontenplan | {len(kr)} (davon buchbar, 4-stellig: {sum(1 for k in kr if k['hierarchie'] >= 4)}) |")
    w(f"| bebuchte Konten | {len(bebuchte)} |")
    tot_s = sum(z["betrag"] for z in zeilen if z["sh"] == "S")
    tot_h = sum(z["betrag"] for z in zeilen if z["sh"] == "H")
    w(f"| Summe aller Soll-Zeilen | {chf(tot_s)} |")
    w(f"| Summe aller Haben-Zeilen | {chf(tot_h)} |")
    w("")
    w("Benutzte MWST-Codes: " + ", ".join(sorted({z['code'] for z in zeilen if z['code']})) + ".")
    w("")

    # ---- Eroeffnungsbilanz
    w("## Eröffnungsbilanz per 01.01.2025")
    w("")
    w("Startsaldo wie in View `kontostart_vfibu` (bezogen auf die Kontoseite). Gegenkonto aller Eröffnungsbuchungen ist **9100 Eröffnungsbilanz**. Sein Saldo muss danach **0.00** sein.")
    w("")
    w("| Konto | Bezeichnung | Typ | Seite | startsaldo (View) | Bilanzwert |")
    w("|---|---|---|---|---:|---:|")
    ak = pa = ak_view = pa_view = D("0")
    for k in kr:
        nrk = k["kontonr"]
        if k["hierarchie"] < 4 or k["typ"] not in ("AK", "PA"):
            continue
        sv = saldo_view(nrk, "3")
        if sv == 0:
            continue
        bw = nat(nrk, sv)
        w(f"| {nrk} | {k['name']} | {k['typ']} | {k['seite']} | {chf(sv)} | {chf(bw)} |")
        if k["typ"] == "AK":
            ak += bw
            ak_view += sv
        else:
            pa += bw
            pa_view += sv
    w(f"| | **Total Aktiven (netto, nach Wertberichtigungen)** | | | | **{chf(ak)}** |")
    w(f"| | **Total Passiven** | | | | **{chf(pa)}** |")
    w(f"| | **Differenz** | | | | **{chf(ak - pa)}** |")
    w(f"| 9100 | Eröffnungsbilanz (Saldo) | | | {chf(saldo_view('9100', '3'))} | |")
    w("")
    wb_ak = sum(saldo_view(k["kontonr"], "3") for k in kr if k["typ"] == "AK" and k["seite"] == "H" and k["hierarchie"] >= 4)
    w("> **Achtung Minuskonten:** Die Wertberichtigungs-Konten (1109, 1509, 1519, 1529, 1539, 1549) sind Aktivkonten mit "
      "Kontoseite **H**. Ihr `startsaldo` ist positiv. Das Eröffnungsbilanz-Fenster rechnet `Sum(if(kontotype = 'AK', startsaldo, 0))`. "
      f"Damit zeigt es Total Aktiven **{chf(ak_view)}** statt **{chf(ak)}** und eine Differenz von **{chf(ak_view - pa_view)}** "
      f"(= 2 × {chf(wb_ak)} Wertberichtigungen). Richtig ist Differenz 0.00. "
      "Wenn deine App 0.00 zeigt, behandelt sie Minuskonten korrekt. Sonst ist das ein Fehler in der Summenformel "
      "(Abhilfe: `if(konto_seite = 'S', startsaldo, -startsaldo)` für AK und umgekehrt für PA).")
    w("")

    # ---- Saldenliste
    w("## Saldenliste per 31.12.2025 (alle bebuchten Konten)")
    w("")
    w("`saldo` = Wert der View `kontosaldo_live_vfibu` (bezogen auf die Kontoseite, inkl. Eröffnung). Soll/Haben = Verkehrszahlen ohne Eröffnung.")
    w("")
    w("| Konto | Bezeichnung | Seite | Eröffnung | Soll 2025 | Haben 2025 | saldo (View) |")
    w("|---|---|---|---:|---:|---:|---:|")
    for k in kr:
        nrk = k["kontonr"]
        if nrk not in bebuchte:
            continue
        s = sum(z["betrag"] for b in BELEGE for z in b.zeilen if z["konto"] == nrk and z["sh"] == "S" and z["visible"] != "3")
        h = sum(z["betrag"] for b in BELEGE for z in b.zeilen if z["konto"] == nrk and z["sh"] == "H" and z["visible"] != "3")
        w(f"| {nrk} | {k['name']} | {k['seite']} | {chf(saldo_view(nrk, '3'))} | {chf(s)} | {chf(h)} | {chf(saldo_view(nrk))} |")
    w("")

    # ---- Bilanz
    def block(typ):
        rows = []
        tot = D("0")
        for k in kr:
            if k["typ"] != typ or k["hierarchie"] < 4:
                continue
            sv = saldo_view(k["kontonr"])
            if sv == 0:
                continue
            bw = nat(k["kontonr"], sv)
            rows.append((k["kontonr"], k["name"], bw))
            tot += bw
        return rows, tot

    ak_rows, ak_tot = block("AK")
    pa_rows, pa_tot = block("PA")
    au_rows, au_tot = block("AU")
    er_rows, er_tot = block("ER")
    gewinn = er_tot - au_tot
    w("## Erfolgsrechnung 01.01. bis 31.12.2025")
    w("")
    w("| Konto | Bezeichnung | Betrag |")
    w("|---|---|---:|")
    for nrk, n, v in er_rows:
        w(f"| {nrk} | {n} | {chf(v)} |")
    w(f"| | **Total Ertrag** | **{chf(er_tot)}** |")
    for nrk, n, v in au_rows:
        w(f"| {nrk} | {n} | {chf(v)} |")
    w(f"| | **Total Aufwand** | **{chf(au_tot)}** |")
    w(f"| | **Jahresgewinn 2025** | **{chf(gewinn)}** |")
    w("")
    w("Typ ER mit Kontoseite S oder Typ AU mit Kontoseite H kommt in diesem Mandanten nicht vor "
      "(3800/3805 sind AU, 4900/6950 sind ER). Die Summen der App über `kontotype` müssen daher exakt diese Werte ergeben.")
    w("")
    w("## Bilanz per 31.12.2025 (vor Gewinnverbuchung)")
    w("")
    w("| Konto | Bezeichnung | Betrag |")
    w("|---|---|---:|")
    for nrk, n, v in ak_rows:
        w(f"| {nrk} | {n} | {chf(v)} |")
    w(f"| | **Total Aktiven (netto)** | **{chf(ak_tot)}** |")
    for nrk, n, v in pa_rows:
        w(f"| {nrk} | {n} | {chf(v)} |")
    w(f"| | **Total Passiven (ohne Jahresgewinn)** | **{chf(pa_tot)}** |")
    w(f"| | **Aktiven - Passiven = Jahresgewinn** | **{chf(ak_tot - pa_tot)}** |")
    w("")
    assert ak_tot - pa_tot == gewinn, (ak_tot - pa_tot, gewinn)
    wb_se = sum(saldo_view(k["kontonr"]) for k in kr if k["typ"] == "AK" and k["seite"] == "H" and k["hierarchie"] >= 4)
    w(f"Kontrolle: Gewinn laut Erfolgsrechnung = Gewinn laut Bilanz = **{chf(gewinn)}**.")
    w("")
    w(f"> Eine Bilanz, die AK-Salden roh addiert (`Sum(... kontosaldo_live_vfibu_saldo ...)` ohne Vorzeichen für Minuskonten), "
      f"zeigt Total Aktiven **{chf(ak_tot + 2 * wb_se)}**. Das ist um 2 × {chf(wb_se)} (Wertberichtigungen/Delkredere) zu hoch.")
    w("")

    # ---- MWST
    res = mwst_auswertung()
    w("## MWST-Auswertung (Belegdatum, effektive Methode)")
    w("")
    w("Pro Quartal und Steuercode: Netto = Summe der Zeilen mit visible 1, Steuer = Summe der MWST-Zeilen mit visible 2. "
      "Vorzeichen: Umsatz (V/E) im Haben positiv, Vorsteuer (M/I) im Soll positiv. "
      "Gegenbuchungen wie Gutschrift, Debitorenverlust und Skonto sind dadurch negativ.")
    w("")
    for qn in (1, 2, 3, 4):
        w(f"### Q{qn}/2025")
        w("")
        w("| Code | Bezeichnung | Satz | Netto | Steuer |")
        w("|---|---|---:|---:|---:|")
        us = vm = vi = D("0")
        for code in sorted(res[qn], key=lambda c: ("VEMI".index(c[0]), c)):
            n, s = res[qn][code]
            sc = STEUERCODES[code]
            w(f"| {code} | {sc['name']} | {sc['satz']}% | {chf(n)} | {chf(s)} |")
            if code[0] in "VE":
                us += s
            elif code[0] == "M":
                vm += s
            else:
                vi += s
        w(f"| | **Umsatzsteuer** | | | **{chf(us)}** |")
        w(f"| | **Vorsteuer Material/DL (1170)** | | | **{chf(vm)}** |")
        w(f"| | **Vorsteuer Inv./Betriebsaufwand (1171)** | | | **{chf(vi)}** |")
        w(f"| | **Zahllast ESTV** | | | **{chf(us - vm - vi)}** |")
        w("")
    w("Die Zahllast jedes Quartals wird am Quartalsende per Sammelbuchung auf **2201 Abrechnungskonto MWST** übertragen. "
      "Bezahlt wird sie im 2. Monat danach. Q4/2025 ist per 31.12. offen.")
    w(f"Per 31.12.2025: 2200 = {chf(saldo_view('2200'))}, 1170 = {chf(saldo_view('1170'))}, 1171 = {chf(saldo_view('1171'))}, "
      f"2201 = {chf(saldo_view('2201'))} (= Zahllast Q4).")
    w("")
    (HERE / "SOLLWERTE.md").write_text("\n".join(L) + "\n", encoding="utf-8")
    return dict(gewinn=gewinn, ak=ak_tot, pa=pa_tot)


if __name__ == "__main__":
    write_sql()
    write_kontoplan_txt()
    r = write_sollwerte()
    print(f"Mandant {MID}")
    print(f"Belege {len(BELEGE)}, Zeilen {sum(len(b.zeilen) for b in BELEGE)}")
    print(f"Gewinn {chf(r['gewinn'])}  Aktiven {chf(r['ak'])}  Passiven {chf(r['pa'])}")
    for k in ("1000", "1010", "1020", "1100", "2000", "2030", "2201", "2270", "2279", "1140", "2970"):
        print(k, KONTO[k]["name"], chf(saldo_view(k)))
    # Bank-Tiefststand
    lo = min((saldo_bis("1020", GJ_VON + timedelta(days=i)), GJ_VON + timedelta(days=i)) for i in range(0, 365, 7))
    print("Bank Tiefststand (wöchentlich)", chf(lo[0]), lo[1])
