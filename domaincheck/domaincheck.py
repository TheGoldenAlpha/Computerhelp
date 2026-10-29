"""DomainCheck - sammelt alle oeffentlich verfuegbaren Infos zu einer Liste Domains.

Nur Python-Standardbibliothek. Startet lokalen Webserver + Browser.
Quellen: DNS-over-HTTPS (Google, Fallback Cloudflare), RDAP (rdap.org), ip-api.com,
direkter HTTP/TLS-Check.
"""
import csv, io, json, os, re, socket, ssl, sys, threading, time, webbrowser
import urllib.request, urllib.parse
from concurrent.futures import ThreadPoolExecutor
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

PORT = 8765
UA = "Mozilla/5.0 DomainCheck/1.0"
DKIM_SELECTORS = ["default", "selector1", "selector2", "google", "k1", "k2", "mail", "dkim", "s1", "s2", "smtp", "mx", "hs1", "zoho"]
SUBS = ["www", "mail", "webmail", "smtp", "imap", "pop", "pop3", "autodiscover", "autoconfig", "ftp", "cpanel", "plesk", "admin", "shop", "remote", "vpn", "owa"]
SRV = ["_autodiscover._tcp", "_imaps._tcp", "_submission._tcp", "_pop3s._tcp", "_sip._tls", "_sipfederationtls._tcp", "_caldavs._tcp", "_carddavs._tcp"]
MAIL_PROVIDERS = [
    ("protection.outlook.com", "Microsoft 365"), ("outlook.com", "Microsoft 365"),
    ("google.com", "Google Workspace"), ("googlemail.com", "Google Workspace"),
    ("pphosted.com", "Proofpoint"), ("mimecast", "Mimecast"), ("hornetsecurity", "Hornetsecurity"),
    ("barracudanetworks", "Barracuda"), ("sophos", "Sophos"), ("messagelabs", "Symantec"),
    ("infomaniak", "Infomaniak"), ("hostpoint", "Hostpoint"), ("cyon", "cyon"), ("metanet", "METANET"),
    ("ionos", "IONOS"), ("1and1", "IONOS"), ("swisscom", "Swisscom"), ("bluewin", "Swisscom"),
    ("ovh", "OVH"), ("zoho", "Zoho"), ("gmx", "GMX"), ("mailbox.org", "mailbox.org"), ("hostfactory", "Hostfactory"),
    ("green.ch", "green.ch"), ("sui-inter", "SUI"), ("seppmail", "SEPPmail"),
]

STATE = {"running": False, "total": 0, "done": 0, "phase": "", "results": {}, "keywords": ["einstein"]}
LOCK = threading.Lock()
OUTFILE = os.path.join(os.path.dirname(os.path.abspath(sys.executable if getattr(sys, "frozen", False) else __file__)), "domaincheck_results.json")

_ctx = ssl.create_default_context()


def http_get(url, timeout=10, data=None, headers=None, maxbytes=200000):
    h = {"User-Agent": UA}
    h.update(headers or {})
    req = urllib.request.Request(url, data=data, headers=h)
    with urllib.request.urlopen(req, timeout=timeout, context=_ctx) as r:
        return r.status, r.geturl(), dict(r.headers), r.read(maxbytes)


def doh(name, rtype):
    """DNS via DoH. Liefert (answers[list[str]], ad_flag, status)."""
    for base in ("https://dns.google/resolve", "https://cloudflare-dns.com/dns-query"):
        try:
            q = urllib.parse.urlencode({"name": name, "type": rtype})
            _, _, _, body = http_get(f"{base}?{q}", timeout=8, headers={"Accept": "application/dns-json"})
            j = json.loads(body)
            ans = [a["data"].strip('"') if rtype == "TXT" else a["data"] for a in j.get("Answer", []) if a.get("type") == TYPES.get(rtype, -1)]
            if rtype == "TXT":
                ans = [a.replace('" "', "") for a in ans]
            return ans, bool(j.get("AD")), j.get("Status")
        except Exception:
            continue
    return [], False, "ERR"


TYPES = {"A": 1, "NS": 2, "CNAME": 5, "SOA": 6, "PTR": 12, "MX": 15, "TXT": 16, "AAAA": 28, "SRV": 33, "DS": 43, "DNSKEY": 48, "CAA": 257}


def dns_link(name, rtype):
    return f"https://dns.google/query?name={urllib.parse.quote(name)}&rr_type={rtype}"


def rdap(domain):
    url = f"https://rdap.org/domain/{domain}"
    try:
        _, final, _, body = http_get(url, timeout=15)
        j = json.loads(body)
    except Exception as e:
        return {"error": str(e), "link": url}
    out = {"link": final, "status": j.get("status", []), "nameservers": [n.get("ldhName", "").lower() for n in j.get("nameservers", [])], "events": {}}
    for ev in j.get("events", []):
        out["events"][ev.get("eventAction")] = ev.get("eventDate")
    for ent in j.get("entities", []):
        roles = ent.get("roles", [])
        name = None
        for item in (ent.get("vcardArray") or [None, []])[1]:
            if item[0] in ("fn", "org"):
                name = item[3]
        if "registrar" in roles:
            out["registrar"] = name or ent.get("handle")
        elif "registrant" in roles:
            out["registrant"] = name or ent.get("handle")
    return out


def web(domain):
    res = {}
    for scheme in ("https", "http"):
        url = f"{scheme}://{domain}"
        try:
            st, final, hdr, body = http_get(url, timeout=10, maxbytes=65536)
            t = re.search(rb"<title[^>]*>(.*?)</title>", body, re.I | re.S)
            gen = re.search(rb'<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']+)', body, re.I)
            res[scheme] = {"status": st, "final_url": final, "server": hdr.get("Server"), "powered_by": hdr.get("X-Powered-By"),
                           "title": t.group(1).decode("utf-8", "replace").strip()[:150] if t else None,
                           "generator": gen.group(1).decode("utf-8", "replace") if gen else None}
        except Exception as e:
            res[scheme] = {"error": str(e)[:200]}
    try:
        with socket.create_connection((domain, 443), timeout=8) as s:
            with _ctx.wrap_socket(s, server_hostname=domain) as ss:
                c = ss.getpeercert()
                res["tls"] = {"issuer": dict(x[0] for x in c.get("issuer", [])).get("organizationName"),
                              "subject": dict(x[0] for x in c.get("subject", [])).get("commonName"),
                              "not_after": c.get("notAfter"), "san": [v for k, v in c.get("subjectAltName", [])][:20]}
    except Exception as e:
        res["tls"] = {"error": str(e)[:200]}
    return res


def check(domain):
    d = {"domain": domain, "dns": {}, "links": {}}
    q = lambda n, t: doh(n, t)[0]
    for t in ("NS", "SOA", "A", "AAAA", "MX", "TXT", "CAA", "DS"):
        d["dns"][t], ad, st = doh(domain, t)
        if t == "A":
            d["dnssec_validated"] = ad
            d["dns_status"] = {0: "NOERROR", 2: "SERVFAIL", 3: "NXDOMAIN"}.get(st, st)
    d["spf"] = [t for t in d["dns"]["TXT"] if t.lower().startswith("v=spf1")]
    d["dmarc"] = q(f"_dmarc.{domain}", "TXT")
    d["mta_sts"] = q(f"_mta-sts.{domain}", "TXT")
    d["tls_rpt"] = q(f"_smtp._tls.{domain}", "TXT")
    d["bimi"] = q(f"default._bimi.{domain}", "TXT")
    d["dkim"] = {}
    for sel in DKIM_SELECTORS:
        r = q(f"{sel}._domainkey.{domain}", "TXT") or q(f"{sel}._domainkey.{domain}", "CNAME")
        if r:
            d["dkim"][sel] = r
    d["mx_hosts"] = {}
    for mx in d["dns"]["MX"]:
        host = mx.split()[-1].rstrip(".")
        if host:
            d["mx_hosts"][host] = q(host, "A")
    d["subdomains"] = {}
    for s in SUBS:
        fq = f"{s}.{domain}"
        cn, a = q(fq, "CNAME"), q(fq, "A")
        if cn or a:
            d["subdomains"][s] = {"cname": cn, "a": a}
    d["srv"] = {s: r for s in SRV if (r := q(f"{s}.{domain}", "SRV"))}
    d["rdap"] = rdap(domain)
    d["web"] = web(domain) if d["dns"]["A"] or d["dns"]["AAAA"] else {"note": "kein A/AAAA"}
    ips = set(d["dns"]["A"]) | {ip for v in d["mx_hosts"].values() for ip in v} | {ip for v in d["subdomains"].values() for ip in v["a"]}
    d["ips"] = sorted(i for i in ips if re.match(r"^\d+\.\d+\.\d+\.\d+$", i))
    mxj = " ".join(d["dns"]["MX"]).lower()
    d["mail_provider"] = next((n for k, n in MAIL_PROVIDERS if k in mxj), "unbekannt" if mxj else "kein MX")
    d["links"] = {
        "DNS NS (Google DoH)": dns_link(domain, "NS"), "DNS MX": dns_link(domain, "MX"), "DNS TXT/SPF": dns_link(domain, "TXT"),
        "DMARC": dns_link(f"_dmarc.{domain}", "TXT"), "RDAP/Whois": d["rdap"].get("link"),
        "Whois (nic.ch/who.is)": f"https://www.nic.ch/whois/" if domain.endswith((".ch", ".li")) else f"https://who.is/whois/{domain}",
        "MXToolbox": f"https://mxtoolbox.com/SuperTool.aspx?action=mx%3a{domain}", "DNSViz (DNSSEC)": f"https://dnsviz.net/d/{domain}/dnssec/",
        "crt.sh (Zertifikate/Subdomains)": f"https://crt.sh/?q=%25.{domain}", "SecurityTrails (DNS-Historie)": f"https://securitytrails.com/domain/{domain}/dns",
        "Hardenize/Internet.nl Mail": f"https://internet.nl/mail/{domain}/", "Website": f"https://{domain}",
    }
    return d


def enrich_ips(results):
    ips = sorted({ip for r in results.values() for ip in r.get("ips", [])})
    info = {}
    for i in range(0, len(ips), 100):
        chunk = ips[i:i + 100]
        try:
            _, _, _, body = http_get("http://ip-api.com/batch?fields=status,query,country,city,isp,org,as,asname,reverse,hosting",
                                     timeout=20, data=json.dumps(chunk).encode(), headers={"Content-Type": "application/json"})
            for x in json.loads(body):
                info[x.get("query")] = x
        except Exception as e:
            for ip in chunk:
                info[ip] = {"error": str(e)}
        time.sleep(4.5)  # free tier: 15 batch requests/min
    for r in results.values():
        r["ip_info"] = {ip: info.get(ip, {}) for ip in r.get("ips", [])}
        r["links"].update({f"IP {ip} (ip-api)": f"https://ip-api.com/#{ip}" for ip in r.get("ips", [])[:4]})


def tag(r, keywords):
    def hit(text):
        t = text.lower()
        return any(k in t for k in keywords)
    ns = " ".join(r["dns"]["NS"] + r["rdap"].get("nameservers", []))
    mx = " ".join(r["dns"]["MX"] + list(r["mx_hosts"]))
    web_ips = r["dns"]["A"]
    ipblob = lambda ips: " ".join(f'{ip} {json.dumps(r.get("ip_info", {}).get(ip, {}))}' for ip in ips)
    tags = []
    if hit(ns):
        tags.append("NS_EINSTEIN")
    if hit(mx) or hit(ipblob([ip for v in r["mx_hosts"].values() for ip in v])):
        tags.append("MAIL_BEI_UNS")
    if hit(ipblob(web_ips)) or hit(" ".join(str(v) for v in r["subdomains"].get("www", {}).values())):
        tags.append("WEB_BEI_UNS")
    if hit(" ".join(r["spf"])):
        tags.append("SPF_BEI_UNS")
    if r.get("dns_status") == "NXDOMAIN":
        tags.append("NXDOMAIN")
    if not r["dns"]["NS"]:
        tags.append("KEIN_NS")
    if r["dns"]["NS"] and not hit(ns) and not any(t in tags for t in ("MAIL_BEI_UNS", "WEB_BEI_UNS")):
        tags.append("WEG")
    r["tags"] = tags


def run(domains, keywords):
    with LOCK:
        STATE.update(running=True, total=len(domains), done=0, phase="DNS/RDAP/Web", results={}, keywords=keywords)

    def one(dom):
        try:
            r = check(dom)
        except Exception as e:
            r = {"domain": dom, "error": str(e), "dns": {"NS": [], "MX": [], "A": [], "TXT": []}, "rdap": {}, "mx_hosts": {}, "subdomains": {}, "spf": [], "links": {}}
        with LOCK:
            STATE["results"][dom] = r
            STATE["done"] += 1

    with ThreadPoolExecutor(12) as ex:
        list(ex.map(one, domains))
    STATE["phase"] = "IP-Infos (ip-api.com)"
    enrich_ips(STATE["results"])
    for r in STATE["results"].values():
        tag(r, keywords)
    with open(OUTFILE, "w", encoding="utf-8") as f:
        json.dump(STATE["results"], f, indent=1, ensure_ascii=False)
    STATE.update(running=False, phase=f"fertig - gespeichert in {OUTFILE}")


def to_csv(results):
    buf = io.StringIO()
    w = csv.writer(buf, delimiter=";")
    w.writerow(["domain", "tags", "NS (DNS)", "NS (Registry)", "MX", "Mailprovider", "SPF", "DMARC", "DKIM", "A", "AAAA", "IP-Org/ASN", "PTR",
                "www", "autodiscover", "Registrar", "Registrant", "Erstellt", "Ablauf", "Web final URL", "Web Titel", "Server", "TLS Issuer", "TLS bis", "DNSSEC"])
    for r in results.values():
        ii = r.get("ip_info", {})
        rd, wb = r.get("rdap", {}), r.get("web", {})
        h = wb.get("https", {}) if isinstance(wb.get("https"), dict) else {}
        j = lambda x: " | ".join(x) if isinstance(x, list) else ("" if x is None else str(x))
        w.writerow([r["domain"], j(r.get("tags", [])), j(r["dns"]["NS"]), j(rd.get("nameservers", [])), j(r["dns"]["MX"]), r.get("mail_provider"),
                    j(r["spf"]), j(r.get("dmarc", [])), j(list(r.get("dkim", {}))), j(r["dns"]["A"]), j(r["dns"].get("AAAA", [])),
                    j([f'{ip}: {v.get("org") or v.get("isp")} {v.get("as", "")}' for ip, v in ii.items()]), j([f'{ip}: {v.get("reverse", "")}' for ip, v in ii.items()]),
                    json.dumps(r["subdomains"].get("www", {})), json.dumps(r["subdomains"].get("autodiscover", {})),
                    rd.get("registrar"), rd.get("registrant"), rd.get("events", {}).get("registration"), rd.get("events", {}).get("expiration"),
                    h.get("final_url"), h.get("title"), h.get("server"), wb.get("tls", {}).get("issuer"), wb.get("tls", {}).get("not_after"), r.get("dnssec_validated")])
    return "﻿" + buf.getvalue()


class H(BaseHTTPRequestHandler):
    def log_message(self, *a):
        pass

    def send(self, body, ctype="application/json", extra=None):
        b = body.encode() if isinstance(body, str) else body
        self.send_response(200)
        self.send_header("Content-Type", ctype + "; charset=utf-8")
        for k, v in (extra or {}).items():
            self.send_header(k, v)
        self.end_headers()
        self.wfile.write(b)

    def do_GET(self):
        if self.path.startswith("/api/status"):
            with LOCK:
                self.send(json.dumps({k: STATE[k] for k in ("running", "total", "done", "phase")} | {"results": list(STATE["results"].values())}, default=str))
        elif self.path.startswith("/api/csv"):
            self.send(to_csv(STATE["results"]), "text/csv", {"Content-Disposition": "attachment; filename=domaincheck.csv"})
        elif self.path.startswith("/api/json"):
            self.send(json.dumps(STATE["results"], indent=1, ensure_ascii=False), "application/json", {"Content-Disposition": "attachment; filename=domaincheck.json"})
        else:
            self.send(HTML, "text/html")

    def do_POST(self):
        body = json.loads(self.rfile.read(int(self.headers.get("Content-Length", 0))) or b"{}")
        if STATE["running"]:
            return self.send('{"error":"laeuft schon"}')
        doms = []
        for line in re.split(r"[\s,;]+", body.get("domains", "")):
            line = re.sub(r"^https?://", "", line.strip().lower()).split("/")[0].removeprefix("www.")
            if line and "." in line and line not in doms:
                doms.append(line)
        kws = [k.strip().lower() for k in body.get("keywords", "einstein").split(",") if k.strip()]
        threading.Thread(target=run, args=(doms, kws), daemon=True).start()
        self.send(json.dumps({"ok": True, "count": len(doms)}))


HTML = r"""<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>DomainCheck</title><style>
body{font:13px system-ui,sans-serif;margin:16px;color:#222;background:#fafafa}
textarea{width:100%;height:110px;font:12px monospace}input{width:420px}button{padding:5px 12px;margin-right:6px}
table{border-collapse:collapse;width:100%;margin-top:10px;background:#fff}td,th{border:1px solid #ddd;padding:4px 6px;vertical-align:top;text-align:left}
th{background:#eee;position:sticky;top:0;cursor:pointer}tr.row:hover{background:#f3f7ff;cursor:pointer}
.t{display:inline-block;padding:1px 6px;border-radius:3px;margin:1px;font-size:11px;color:#fff;background:#888}
.NS_EINSTEIN{background:#c0392b}.MAIL_BEI_UNS{background:#d35400}.WEB_BEI_UNS{background:#8e44ad}.SPF_BEI_UNS{background:#2c3e50}.WEG{background:#27ae60}.NXDOMAIN,.KEIN_NS{background:#000}
.det{background:#fcfcfc}.det pre{white-space:pre-wrap;font-size:11px;margin:0;max-height:500px;overflow:auto}.det a{display:inline-block;margin-right:12px}
details{margin:8px 0}small{color:#666}.m{font-family:monospace;font-size:11px}
</style></head><body>
<h3>DomainCheck</h3>
<textarea id="d" placeholder="Domains, eine pro Zeile (oder Komma getrennt)"></textarea><br>
Keywords fuer "bei uns" (NS/MX/IP/PTR/ISP/SPF), Komma: <input id="k" value="einstein"> <small>z.B. einstein, eure IPs, euer ISP-Name</small><br><br>
<button onclick="go()">Start</button><button onclick="location='/api/csv'">CSV (Excel)</button><button onclick="location='/api/json'">JSON</button>
Filter: <input id="f" style="width:200px" oninput="render()" placeholder="Text/Tag filtern"> <span id="s"></span>
<details><summary>Wie die Daten geholt werden (Quellen)</summary><ul>
<li><b>DNS</b> (NS, SOA, A, AAAA, MX, TXT, CAA, DS, CNAME, SRV): DNS-over-HTTPS <a href="https://dns.google/" target=_blank>dns.google/resolve</a>, Fallback <a href="https://developers.cloudflare.com/1.1.1.1/encryption/dns-over-https/" target=_blank>Cloudflare</a>. Live-Daten, nicht Plesk.</li>
<li><b>Mail</b>: MX + IPs der MX-Hosts, SPF (TXT v=spf1), DMARC (_dmarc), DKIM (Selectors: default, selector1/2 (M365), google, k1, mail, dkim, s1/s2...), MTA-STS, TLS-RPT, BIMI, autodiscover/autoconfig, SRV.</li>
<li><b>Subdomains</b>: www, mail, webmail, smtp, imap, pop, autodiscover, ftp, plesk, ... (A/CNAME). Mehr via <a href="https://crt.sh" target=_blank>crt.sh</a> Link je Domain.</li>
<li><b>Registry/Whois</b>: <a href="https://about.rdap.org/" target=_blank>RDAP</a> via rdap.org (leitet zur Registry, .ch/.li = SWITCH rdap.nic.ch). "NS (Registry)" = Delegation bei der Registry = die Wahrheit, egal was in Plesk steht.</li>
<li><b>IP-Infos</b> (ASN, ISP, Org, Land, PTR): <a href="https://ip-api.com/docs/api:batch" target=_blank>ip-api.com Batch</a>.</li>
<li><b>Web</b>: direkter HTTP/HTTPS Request (Status, Redirect-Ziel, Server, Titel, Generator) + TLS-Zertifikat (Issuer, Ablauf, SAN).</li>
<li><b>Tags</b>: NS_EINSTEIN = Nameserver (DNS oder Registry) enthalten Keyword. MAIL_/WEB_/SPF_BEI_UNS = MX/IP/PTR/ISP/SPF enthalten Keyword. WEG = hat NS, aber nichts matcht.</li>
<li>Klick auf eine Zeile = alle Rohdaten + Links zum Nachpruefen. Ergebnis wird auch als domaincheck_results.json neben der exe gespeichert.</li></ul></details>
<table><thead><tr><th>Domain</th><th>Tags</th><th>NS (DNS)</th><th>NS (Registry)</th><th>MX / Provider</th><th>A / Hoster</th><th>Registrar / Ablauf</th><th>Web</th></tr></thead><tbody id="b"></tbody></table>
<script>
let R=[],open={};
const e=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
async function go(){await fetch('/api/start',{method:'POST',body:JSON.stringify({domains:d.value,keywords:k.value})});poll()}
async function poll(){const j=await (await fetch('/api/status')).json();R=j.results;s.textContent=`${j.done}/${j.total} – ${j.phase}`;render();if(j.running)setTimeout(poll,1500)}
function render(){const f=document.getElementById('f').value.toLowerCase();let h='';
for(const r of R){if(f&&!JSON.stringify(r).toLowerCase().includes(f))continue;const ii=r.ip_info||{},rd=r.rdap||{},w=(r.web||{}).https||{};
const hoster=(r.dns.A||[]).map(i=>`${i} <small>${e((ii[i]||{}).org||(ii[i]||{}).isp||'')}</small>`).join('<br>');
h+=`<tr class=row onclick="open['${r.domain}']=!open['${r.domain}'];render()"><td><b>${e(r.domain)}</b>${r.error?'<br>ERR '+e(r.error):''}</td>
<td>${(r.tags||[]).map(t=>`<span class="t ${t}">${t}</span>`).join('')}</td><td class=m>${(r.dns.NS||[]).map(e).join('<br>')}</td>
<td class=m>${(rd.nameservers||[]).map(e).join('<br>')}</td><td class=m>${(r.dns.MX||[]).map(e).join('<br>')}<br><b>${e(r.mail_provider)}</b></td>
<td class=m>${hoster}</td><td>${e(rd.registrar)}<br><small>${e((rd.events||{}).expiration)}</small></td>
<td>${w.status?w.status+' '+e(w.final_url)+'<br><small>'+e(w.title)+'</small>':'<small>'+e(w.error||'')+'</small>'}</td></tr>`;
if(open[r.domain])h+=`<tr class=det><td colspan=8>${Object.entries(r.links||{}).map(([n,u])=>`<a href="${e(u)}" target=_blank>${e(n)}</a>`).join('')}<pre>${e(JSON.stringify(r,null,1))}</pre></td></tr>`}
b.innerHTML=h}
poll();
</script></body></html>"""


if __name__ == "__main__":
    srv = ThreadingHTTPServer(("127.0.0.1", PORT), H)
    url = f"http://127.0.0.1:{PORT}/"
    print(f"DomainCheck laeuft auf {url}  (Fenster offen lassen, Ctrl+C beendet)")
    threading.Timer(1, lambda: webbrowser.open(url)).start()
    srv.serve_forever()
