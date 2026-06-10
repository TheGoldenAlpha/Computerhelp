# 🍔 McSammler

**Sammle mit deinen Kollegen jeden McDonald's der Schweiz.**

Eine kleine, lustige Web-App: Auf einer Karte der Schweiz ist jeder McDonald's
eingezeichnet. Du legst eine **Gruppe** an (Name + Passwort) und hakst jeden
besuchten McDonald's ab. Je mehr du sammelst, desto höher (und beleidigender 😄)
dein **Rang** – vom „Grashalm" bis zur „McLegende". Mit Häkchen-Animation,
Konfetti, herabregnenden Burgern und Rang-Aufstiegs-Show.

Gebaut nur mit **Skriptsprachen**: Node.js + Vanilla JavaScript. Keine Build-Tools,
keine CDNs nötig – Karte (Leaflet) wird lokal ausgeliefert.

---

## ✨ Features

- 🗺️ **Karte der Schweiz** mit allen McDonald's als runde Marker (wie in der Mc-App).
- ✅ **Abhaken & Zurücksetzen** jedes Standorts – aus Versehen geklickt? Einfach wieder zurücksetzen.
- 👥 **Gruppen** mit Name + Passwort. Auf der Startseite siehst du alle Gruppen; um eine Karte zu öffnen, brauchst du das Passwort der Gruppe.
- 🔎 **Suche** nach Gruppen (Startseite) und nach McDonald's/Orten (Karte).
- 🏆 **Rangliste** aller Gruppen mit Medaillen 🥇🥈🥉.
- 🏅 **Lustige Ränge** mit Icons – am Anfang spindeldürr, am Ende… nun ja. Oben links neben dem Gruppennamen sichtbar.
- 🎉 **Animationen**: Häkchen-Pop, Konfetti, Burger-Regen 🍔 beim Abhaken und eine grosse Show beim Rang-Aufstieg.
- 🔊 **Mini-Sounds** (mit Stummschalt-Knopf).
- 📍 **„Mein Standort"** zum schnellen Zentrieren unterwegs.
- 📱 Funktioniert auf Handy & Desktop.

---

## 🚀 Schnellstart (lokal)

Voraussetzung: **Node.js ≥ 18** (getestet mit Node 22).

```bash
# 1. Abhängigkeiten installieren
npm install

# 2. Datenbank mit allen McDonald's der Schweiz füllen
npm run seed

# 3. Server starten
npm start
```

Dann im Browser öffnen: **http://localhost:3000**

Fertig. Eine SQLite-Datenbank wird automatisch unter `data/mcsammler.db` angelegt.

---

## 🗃️ Wie die Datenbank befüllt wird (`npm run seed`)

Der Seed-Befehl holt **alle McDonald's der Schweiz live von OpenStreetMap**
(über die kostenlose [Overpass-API](https://overpass-api.de/) – kein API-Key nötig).

- ✅ **Klappt der Online-Abruf**, landen ~150–170 echte Standorte mit Adresse in der DB.
- ⚠️ **Ist Overpass gerade nicht erreichbar** (Firewall, Rate-Limit), nutzt der Seed
  automatisch einen **mitgelieferten Fallback-Datensatz** (`data/mcdonalds.fallback.json`,
  ~74 bekannte Standorte). Dann später einfach nochmal `npm run seed` ausführen,
  um die vollständige Liste zu laden.

> 💡 **Tipp:** Führe `npm run seed` zuerst aus, **bevor** ihr Gruppen anlegt und
> abhakt – so habt ihr von Anfang an die vollständige, präzise Standortliste.

### Alles zurücksetzen

```bash
npm run seed:fresh
```

Das löscht **alle Gruppen und Besuche** und baut die Standortliste neu auf
(kompletter Neustart). Ein normales `npm run seed` lässt eure Gruppen & Häkchen
unangetastet und aktualisiert nur die Standortliste.

---

## 🏅 Die Ränge (Auszug)

| ab … McDonald's | Rang | Icon |
|---|---|---|
| 0 | Windhauch | 🌬️ |
| 1 | Grashalm | 🌱 |
| 11 | Spaghetti (ungekocht) | 🍝 |
| 50 | Mobile Panzersperre | 🚧 |
| 74 | Schwergewicht | 🏋️ |
| 101 | Eigene Postleitzahl | 📮 |
| 131 | Eigenes Gravitationsfeld | 🪐 |
| 170 | McLegende – Endgegner | 👑 |

…und einige mehr dazwischen. Alle Ränge siehst du in der App über den 🏅-Knopf.
Anpassen kannst du sie in [`src/ranks.js`](src/ranks.js).

---

## ⚙️ Konfiguration (optional)

Kopiere `.env.example` nach `.env`, um etwas zu ändern – nötig ist das nicht.

| Variable | Standard | Bedeutung |
|---|---|---|
| `PORT` | `3000` | Port des Servers |
| `APP_SECRET` | (auto) | Schlüssel für Login-Tokens. Wird sonst automatisch erzeugt und in der DB gespeichert. |
| `DB_PATH` | `./data/mcsammler.db` | Speicherort der SQLite-Datei |

---

## 🌐 Ins Web stellen (Deployment)

Die App ist ein ganz normaler Node-Server und läuft auf jedem Host, der Node
unterstützt (z.B. **Render**, **Railway**, **Fly.io**, ein eigener VPS, …).

Allgemeines Rezept:

1. Repo hochladen / verbinden.
2. Build-Befehl: `npm install`
3. Start-Befehl: `npm start`
4. Einmalig `npm run seed` ausführen (z.B. als Deploy-Hook oder per Shell), damit die McDonald's in der DB sind.
5. **Wichtig:** Der Ordner `data/` muss **dauerhaft** sein (persistent disk),
   sonst gehen Datenbank und Häkchen bei jedem Neustart verloren. Alternativ
   `DB_PATH` auf einen persistenten Pfad setzen.
6. Empfehlenswert: `APP_SECRET` fest setzen, damit Logins einen Server-Neustart überleben.

> Die Kartenkacheln kommen zur Laufzeit von OpenStreetMap (`tile.openstreetmap.org`).
> Der Browser deiner Nutzer braucht also Internetzugang – der Server selbst nicht.

---

## 🔐 Sicherheit – kurz & ehrlich

Das ist ein Spass-Projekt für Freunde, **keine Banking-Software**:

- Passwörter werden mit **bcrypt** gehasht (nicht im Klartext gespeichert).
- Login läuft über ein signiertes Token (HMAC) – kein Klartext-Passwort im Browser-Speicher.
- Es gibt einen einfachen Schutz gegen Passwort-Raten (Rate-Limit pro Gruppe).

Für den Einsatz „im echten Internet" empfiehlt sich HTTPS (übernimmt i.d.R. der Hoster).

---

## 🧩 Projektstruktur

```
.
├── server.js               # Express-Server + API
├── src/
│   ├── db.js               # SQLite-Setup & Schema
│   ├── ranks.js            # die lustigen Ränge  ← hier anpassen
│   ├── auth.js             # signierte Login-Tokens
│   ├── overpass.js         # McDonald's von OpenStreetMap holen
│   └── seed.js             # DB befüllen (Overpass + Fallback)
├── data/
│   └── mcdonalds.fallback.json   # Notfall-Standortliste
├── public/
│   ├── index.html          # Startseite (Gruppen, Suche, Rangliste)
│   ├── map.html            # Kartenseite
│   ├── css/style.css
│   └── js/
│       ├── fx.js           # Konfetti, Burger-Regen, Rang-Show, Sounds
│       ├── common.js       # API, Session, Modal, Toasts
│       ├── home.js         # Logik Startseite
│       └── map.js          # Logik Karte
└── README.md
```

---

## 🛠️ Troubleshooting

- **`npm run seed` sagt „Overpass fehlgeschlagen"** → kein Drama, der Fallback
  springt ein. Internetverbindung prüfen und später nochmal `npm run seed`.
- **Karte bleibt grau / keine Kacheln** → der Browser braucht Internet
  (OpenStreetMap-Kacheln). Im Firmennetz ggf. blockiert.
- **`better-sqlite3`-Installationsfehler** → Node-Version prüfen (≥ 18).
  In seltenen Fällen werden Build-Tools benötigt; meist gibt es aber fertige Binaries.
- **Keine Marker auf der Karte** → wurde `npm run seed` ausgeführt?

---

## 📜 Lizenz

MIT – mach damit, was du willst. McDonald's-Standortdaten © OpenStreetMap-Mitwirkende.

Viel Spass beim Sammeln! 🍟
