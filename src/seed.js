'use strict';

// Befüllt die mcdonalds-Tabelle.
//   node src/seed.js          -> holt alle CH-McDonald's von Overpass (OSM),
//                                bei Fehler: gebündelter Fallback-Datensatz.
//   node src/seed.js --fresh  -> zusätzlich ALLE Gruppen & Besuche löschen
//                                (kompletter Neustart der Nutzerdaten).
//
// Die Standortliste wird immer komplett neu aufgebaut. Abgehakte Besuche
// bleiben erhalten (separate Tabelle), ausser bei --fresh.

const fs = require('fs');
const path = require('path');
const { db } = require('./db');
const { fetchFromOverpass } = require('./overpass');

const FRESH = process.argv.includes('--fresh');
const FALLBACK_PATH = path.join(__dirname, '..', 'data', 'mcdonalds.fallback.json');

async function getData() {
  console.log('🌍 Hole McDonald\'s-Standorte der Schweiz von OpenStreetMap (Overpass) …');
  try {
    const { list, endpoint } = await fetchFromOverpass();
    console.log(`✅ ${list.length} Standorte von ${endpoint} geladen.`);
    return list;
  } catch (err) {
    console.warn(`\n⚠️  Konnte Overpass nicht erreichen (${err.message}).`);
    console.warn('   Verwende stattdessen den gebündelten Fallback-Datensatz.');
    console.warn('   (Tipp: später nochmals "npm run seed" ausführen für die vollständige Liste.)\n');
    const raw = fs.readFileSync(FALLBACK_PATH, 'utf8');
    const list = JSON.parse(raw);
    console.log(`✅ ${list.length} Standorte aus dem Fallback geladen.`);
    return list;
  }
}

async function main() {
  const list = await getData();

  const insert = db.prepare(`
    INSERT INTO mcdonalds (ext_id, name, lat, lon, street, housenumber, postcode, city)
    VALUES (@ext_id, @name, @lat, @lon, @street, @housenumber, @postcode, @city)
  `);

  const rebuild = db.transaction((rows) => {
    if (FRESH) {
      db.prepare('DELETE FROM visits').run();
      db.prepare('DELETE FROM groups').run();
      console.log('🧹 --fresh: Alle Gruppen & Besuche gelöscht.');
    }
    db.prepare('DELETE FROM mcdonalds').run();
    for (const r of rows) {
      insert.run({
        ext_id: r.ext_id,
        name: r.name || "McDonald's",
        lat: r.lat,
        lon: r.lon,
        street: r.street ?? null,
        housenumber: r.housenumber ?? null,
        postcode: r.postcode ?? null,
        city: r.city ?? null,
      });
    }
  });

  rebuild(list);

  const total = db.prepare('SELECT COUNT(*) AS c FROM mcdonalds').get().c;
  const visits = db.prepare('SELECT COUNT(*) AS c FROM visits').get().c;
  console.log(`\n🍔 Fertig! ${total} McDonald's in der Datenbank.`);
  console.log(`   ${visits} bestehende Besuche erhalten.`);
  process.exit(0);
}

main().catch((err) => {
  console.error('❌ Seed fehlgeschlagen:', err);
  process.exit(1);
});
