#!/usr/bin/env python3
"""Prueft eine NPK-Devis-Datei (.chd) vor dem Import auf typische Layout-/Importfehler.

Aufruf:
    python3 -I tools/chd_check.py DATEI.chd [ORIGINAL.pdf]

Mit PDF werden zusaetzlich alle Mengen/Einheiten/Positionsnummern gegen das PDF
verglichen und zu jedem Fund ein Suchtext fuer Ctrl+F vorgeschlagen, der im PDF
und in der .chd genau einmal auf einer Zeile vorkommt.
Fuer den PDF-Teil wird `pdftotext` (poppler-utils) benoetigt.
"""
import re
import subprocess
import sys

FOOTER = re.compile(r'Zwischentotal|^Total$|^Objekt:|^NPK:|^BKP-Nr\.:|Seite\s+\d+')
TEXT_POS = re.compile(r'^\.\d{3}\b')


def load_chd(path):
    rows = []
    with open(path, encoding='latin-1') as f:
        for n, line in enumerate(f, 1):
            c = line.rstrip('\r\n').split('\t')
            if len(c) < 29:
                continue
            rows.append({'n': n, 'qty': c[1], 'unit': c[2], 'label': c[6].strip(),
                         'text': c[9], 'key': c[27]})
    return rows


def pdf_lines(path):
    out = subprocess.run(['pdftotext', '-layout', path, '-'], capture_output=True,
                         text=True, check=True).stdout
    return out


def pdf_quantities(layout):
    """Liefert [(kapitel.pos.unterpos, menge, einheit)] in PDF-Reihenfolge."""
    ch = main = sub = None
    res = []
    for l in layout.split('\n'):
        m = re.match(r'NPK:\s+(\d{3})', l)
        if m:
            ch = m.group(1)
            continue
        if re.match(r'\s*(Objekt|BKP-Nr|Position|Zwischentotal|Total)', l):
            continue
        m = re.match(r'^\s*(?:R\s+)?(\d{3})(?:\.(\d{3}))?\s', l)
        if m:
            main, sub = m.group(1), m.group(2) or '000'
        else:
            m = re.match(r'^\s*(?:R\s+)?\.(\d{3})\s', l)
            if m:
                sub = m.group(1)
        q = re.search(r"\s(per|[\d']+(?:\.\d+)?)\s+(\S+)\s+\.{5,}", l)
        if q and ch:
            res.append((f'{ch}.{main}.{sub}', q.group(1).replace("'", ''), q.group(2)))
    return res


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    rows = load_chd(sys.argv[1])
    texts = [r['text'] for r in rows]
    pl = []
    if len(sys.argv) > 2:
        layout = pdf_lines(sys.argv[2])
        pl = [re.sub(r'\s+', ' ', l).strip() for l in layout.split('\n')]

    def unique(s):
        return (sum(t.count(s) for t in texts) == 1 and
                (not pl or sum(l.count(s) for l in pl) == 1))

    def search_text(i):
        """Eindeutiger Suchtext aus der Naehe von Zeile i (fuer Ctrl+F)."""
        for d in range(0, 8):
            for j in (i - d, i + d):
                if not 0 <= j < len(rows):
                    continue
                t = rows[j]['text'].strip()
                if not t or FOOTER.search(t):
                    continue
                w = t.split()
                for n in range(len(w), 1, -1):
                    for k in range(len(w) - n + 1):
                        s = ' '.join(w[k:k + n])
                        if len(s) >= 10 and unique(s):
                            return f'Ctrl+F "{s}"'
        # Fortsetzungsnummer oben auf der naechsten PDF-Seite, z.B. "176.300"
        for j in range(i, min(i + 4, len(rows))):
            if re.fullmatch(r'\d{3}\.\d{3}', rows[j]['label']):
                return f'Ctrl+F "{rows[j]["label"]}" (nur im PDF, oben auf der Folgeseite)'
        return 'kein eindeutiger Text - Positionsnummer im PDF suchen'

    findings = []
    for i, r in enumerate(rows):
        t = r['text'].strip()
        if FOOTER.search(t):
            findings.append((i, 'Seitenfuss/-kopf als Positionstext', t))
        if '\xff' in r['text']:
            findings.append((i, 'Kaputtes Zeichen (ÿ statt Anfuehrungszeichen)', t))
        if TEXT_POS.match(t):
            findings.append((i, 'Unterpositionsnummer steht im Text (Position verschluckt)', t))
        if r['qty'] not in ('0.00', '') and not t:
            findings.append((i, 'Menge auf leerer Textzeile', f"{r['qty']} {r['unit']}"))
        if FOOTER.search(t) and 0 < i < len(rows) - 2:
            before, after = rows[i - 1]['text'].strip(), rows[i + 2]['text'].strip()
            if before and (before == after or before == rows[i + 1]['text'].strip()):
                findings.append((i, 'Doppelte Textzeile ueber Seitenumbruch', before))

    keys = {}
    for r in rows:
        if r['qty'] not in ('0.00', ''):
            keys.setdefault(r['key'], []).append(r)
    for k, rs in keys.items():
        if len(rs) > 1:
            findings.append((rows.index(rs[0]), f'Position {k} hat {len(rs)} Mengen',
                             ', '.join(f"{x['qty']} {x['unit']}" for x in rs)))

    findings.sort()
    for i, kind, detail in findings:
        r = rows[i]
        print(f"[{kind}] chd-Zeile {r['n']}, Pos {r['key']}: {detail}\n    -> {search_text(i)}")

    if len(sys.argv) > 2:
        chd_q = [(r['key'], r['qty'] if r['qty'] == 'per' else str(int(float(r['qty']))), r['unit'])
                 for r in rows if r['qty'] not in ('0.00', '') and r['key']]
        pdf_q = [(k, q if q == 'per' else str(int(float(q))), u) for k, q, u in pdf_quantities(layout)]
        print(f'\nMengen: chd={len(chd_q)} PDF={len(pdf_q)}')
        for a, b in zip(chd_q, pdf_q):
            if a != b:
                print(f'  Abweichung: chd {a} <> PDF {b}')
    if not findings:
        print('Keine Auffaelligkeiten gefunden.')


if __name__ == '__main__':
    main()
