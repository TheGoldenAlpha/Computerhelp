# 🍔 McSammler

**Sammle mit deinen Kollegen jeden McDonald's der Schweiz.**

Eine kleine, lustige Web-App: Auf einer Karte der Schweiz ist jeder McDonald's
eingezeichnet. Du legst eine **Gruppe** an (Name + Passwort) und hakst jeden
besuchten McDonald's ab. Je mehr du sammelst, desto höher (und beleidigender 😄)
dein **Rang** – vom „Grashalm" bis zur „McLegende". Mit Häkchen-Animation,
Konfetti, herabregnenden Burgern und Rang-Aufstiegs-Show.

**Technik:** rein statische Seite (HTML/CSS/JS) → läuft auf **GitHub Pages**.
Die geteilten Daten (Gruppen, Passwörter, Häkchen) liegen in einer **Supabase**-
Datenbank (kostenlos). Die Karte nutzt **Leaflet** (lokal mitgeliefert, keine CDN).

---

## ✨ Features

- 🗺️ Karte der Schweiz mit **allen** McDonald's als runde Marker.
- ✅ **Abhaken & Zurücksetzen** jedes Standorts.
- 👥 **Gruppen** mit Name + Passwort. Alle sehen alle Gruppen; um eine Karte zu öffnen, braucht man das Passwort der Gruppe.
- 🔎 Suche nach Gruppen (Startseite) und nach McDonald's/Orten (Karte).
- 🏆 Geteilte **Rangliste** mit Medaillen 🥇🥈🥉 – du & dein Kollege seht euch gegenseitig.
- 🏅 Lustige **Ränge** mit Icons, oben links neben dem Gruppennamen.
- 🎉 Animationen: Häkchen-Pop, Konfetti, **Burger-Regen** 🍔, grosse Rang-Aufstiegs-Show.
- 🔊 Mini-Sounds (mit Stummschalt-Knopf), 📍 „Mein Standort", ↻ Standorte aktualisieren.
- 📱 Funktioniert auf Handy & Desktop.

---

## 🍟 Woher kommen „alle" McDonald's?

Die App holt die Standorte **live von OpenStreetMap (Overpass-API)** – direkt im
Browser. Damit hast du immer die **vollständige, aktuelle Liste** aller
Schweizer McDonald's (aktuell ~170–180, OSM ergänzt laufend neue). Das Ergebnis
wird im Browser zwischengespeichert (7 Tage) und kann mit dem ↻-Knopf auf der
Karte aktualisiert werden.

Als **Fallback** liegt eine Datei `data/mcdonalds.json` bei (falls OpenStreetMap
mal nicht antwortet). Du kannst diese Datei optional mit der **echten,
vollständigen Liste** füllen (siehe Schritt 4) – empfohlen, dann lädt die Karte
sofort und vollständig.

---

## 🚀 Einrichtung – Schritt für Schritt

### 1. Supabase-Projekt anlegen (kostenlos, ~5 Min)

1. Auf <https://supabase.com> registrieren → **New Project** (Name & DB-Passwort frei wählbar, Region z.B. *Frankfurt*).
2. Kurz warten, bis das Projekt bereit ist.
3. Links im Menü **SQL Editor** öffnen → **New query**.
4. Den **kompletten Inhalt** der Datei [`supabase/schema.sql`](supabase/schema.sql) hineinkopieren und auf **Run** klicken.
   → Damit werden Tabellen, Sicherheitsregeln und alle Funktionen erstellt.

### 2. Zugangsdaten in `config.js` eintragen

1. Im Supabase-Dashboard: **Project Settings → Data API** → die **Project URL** kopieren
   (z.B. `https://abcd1234.supabase.co`).
2. **Project Settings → API Keys** → den **`anon` `public`** Key kopieren.
3. Die Datei [`config.js`](config.js) im Projekt öffnen und beide Werte eintragen:

   ```js
   window.MCSAMMLER_CONFIG = {
     SUPABASE_URL: "https://abcd1234.supabase.co",
     SUPABASE_ANON_KEY: "eyJhbGciOiJ...dein-anon-key...",
   };
   ```

   > Der `anon`-Key ist **öffentlich** und darf im Code stehen – die Daten sind
   > durch Row Level Security und die SQL-Funktionen geschützt.

### 3. Auf GitHub Pages veröffentlichen

1. Dieses Repo zu GitHub pushen (Dateien einfach so lassen, alles liegt im Wurzelverzeichnis).
2. Im GitHub-Repo: **Settings → Pages**.
3. Unter **Source**: „Deploy from a branch", Branch auf deinen Branch (z.B. `main`) und Ordner `/ (root)` → **Save**.
4. Nach ~1 Minute ist die App erreichbar unter:
   **`https://<dein-github-name>.github.io/<repo-name>/`**

   Fertig! 🎉 Gruppe erstellen, Passwort setzen, losklicken.

### 4. (Optional, empfohlen) Vollständige Standortliste fest einbacken

Damit die Karte sofort & komplett lädt (statt erst live nachzuladen), kannst du
die echte Liste einmal lokal holen und mitcommitten. Dafür brauchst du **Node ≥ 18**
und normales Internet:

```bash
node tools/fetch-mcdonalds.mjs   # schreibt data/mcdonalds.json (~170–180 Standorte)
git add data/mcdonalds.json && git commit -m "Vollständige McDonald's-Liste" && git push
```

---

## 💻 Lokal testen

Da es eine statische Seite ist, reicht ein beliebiger Static-Server. Mit Python:

```bash
python3 -m http.server 8000
# dann http://localhost:8000 öffnen
```

(`config.js` muss ausgefüllt sein, sonst zeigt die App einen Setup-Hinweis.)

---

## 🌐 Eigene Domain?

GitHub Pages liefert die App unter `…github.io/<repo>/` aus. Eine eigene Domain
ist möglich (**Settings → Pages → Custom domain**), allerdings immer als ganze
(Sub-)Domain, z.B. `mcdonalds.florianhalter.ch` – **nicht** als Unterpfad wie
`florianhalter.ch/mcdonolds` (Unterpfade gehen nur, wenn die ganze Domain auf
GitHub Pages zeigt). Für einen Unterpfad bräuchtest du klassisches Hosting mit
Server (z.B. mit der PHP-Variante) – frag einfach nach.

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

…und einige mehr dazwischen. Anpassen in [`js/ranks.js`](js/ranks.js)
(und – falls du Server-Logik nutzt – identisch halten).

---

## 🔐 Sicherheit – kurz & ehrlich

Spass-Projekt für Freunde, **keine Banking-Software**:

- Passwörter werden in Supabase mit **bcrypt** (pgcrypto) gehasht, nie im Klartext.
- **Row Level Security** ist aktiv: Der öffentliche `anon`-Key kann die Tabellen
  **nicht** direkt lesen/schreiben – nur die geprüften SQL-Funktionen.
- Nach dem Login bekommst du ein zufälliges **Session-Token** (kein Passwort im Browser-Speicher).
- Öffentlich sichtbar sind nur **Gruppennamen + Anzahl** (für die Rangliste).
  *Welche* McDonald's eine Gruppe abgehakt hat, sieht nur, wer ihr Passwort kennt.

---

## 🧩 Projektstruktur

```
.
├── index.html              # Startseite (Gruppen, Suche, Rangliste)
├── map.html                # Kartenseite
├── config.js               # ← deine Supabase-Zugangsdaten
├── config.example.js       # Vorlage
├── css/style.css
├── js/
│   ├── api.js              # Supabase-Aufrufe + Session
│   ├── data.js             # McDonald's laden (Overpass live + Fallback + Cache)
│   ├── ranks.js            # die lustigen Ränge
│   ├── fx.js               # Konfetti, Burger-Regen, Rang-Show, Sounds
│   ├── common.js           # Toast, Modal, Helfer
│   ├── home.js             # Logik Startseite
│   └── map.js              # Logik Karte
├── data/mcdonalds.json     # Fallback-Standortliste
├── vendor/leaflet/         # Leaflet (lokal, keine CDN)
├── supabase/schema.sql     # ← einmal im Supabase SQL-Editor ausführen
├── tools/fetch-mcdonalds.mjs  # optional: volle Liste lokal holen
└── .nojekyll               # GitHub Pages: kein Jekyll-Build
```

---

## 🛠️ Troubleshooting

- **„Fast geschafft! …nicht mit Supabase verbunden"** → `config.js` ist noch leer. URL + anon-Key eintragen (Schritt 2).
- **Login/Erstellen schlägt fehl** → Wurde `supabase/schema.sql` im SQL-Editor ausgeführt? Stimmen URL & Key?
- **Karte bleibt grau** → Browser braucht Internet (OpenStreetMap-Kacheln).
- **Wenige Marker** → OpenStreetMap war evtl. kurz nicht erreichbar (Fallback aktiv). Auf der Karte ↻ drücken, oder Schritt 4 ausführen.
- **GitHub Pages zeigt 404** → 1–2 Minuten warten; prüfen, dass Pages auf den richtigen Branch + `/ (root)` zeigt.

---

## 📜 Lizenz

MIT. McDonald's-Standortdaten © OpenStreetMap-Mitwirkende.

Viel Spass beim Sammeln! 🍟
