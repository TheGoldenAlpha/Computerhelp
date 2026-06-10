#!/usr/bin/env node
// ============================================================================
//  Holt ALLE McDonald's der Schweiz von OpenStreetMap (Overpass) und schreibt
//  sie nach data/mcdonalds.json. Optional – die App holt die Liste sonst auch
//  selbst im Browser. Nützlich, um eine vollständige, schnelle Fallback-Datei
//  fest ins Repo zu legen (mit echten OSM-IDs).
//
//  Ausführen (Node >= 18, normales Internet nötig):
//      node tools/fetch-mcdonalds.mjs
// ============================================================================

import { writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT = join(__dirname, '..', 'data', 'mcdonalds.json');

const ENDPOINTS = [
  'https://overpass-api.de/api/interpreter',
  'https://overpass.kumi.systems/api/interpreter',
  'https://overpass.osm.ch/api/interpreter',
];

const QUERY = `
[out:json][timeout:120];
area["ISO3166-1"="CH"][admin_level=2]->.a;
(
  nwr["amenity"~"fast_food|restaurant"]["brand"~"McDonald",i](area.a);
  nwr["amenity"~"fast_food|restaurant"]["name"~"McDonald",i](area.a);
);
out center tags;`;

function parse(elements) {
  const out = [];
  for (const el of elements || []) {
    const lat = el.lat ?? el.center?.lat;
    const lon = el.lon ?? el.center?.lon;
    if (typeof lat !== 'number' || typeof lon !== 'number') continue;
    const t = el.tags || {};
    out.push({
      ext_id: `${el.type[0]}${el.id}`,
      name: t.name || t.brand || "McDonald's",
      lat, lon,
      street: t['addr:street'] || null,
      housenumber: t['addr:housenumber'] || null,
      postcode: t['addr:postcode'] || null,
      city: t['addr:city'] || null,
    });
  }
  return out;
}

let list = null;
for (const url of ENDPOINTS) {
  try {
    console.log('→ ' + url);
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'data=' + encodeURIComponent(QUERY),
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    list = parse(json.elements);
    if (!list.length) throw new Error('0 Resultate');
    break;
  } catch (e) {
    console.warn('  ⚠️  ' + e.message);
  }
}

if (!list || !list.length) {
  console.error('❌ Konnte keine Daten holen. Internet/Overpass prüfen.');
  process.exit(1);
}

list.sort((a, b) => (a.city || '').localeCompare(b.city || '') || a.name.localeCompare(b.name));
writeFileSync(OUT, JSON.stringify(list, null, 0) + '\n');
console.log(`✅ ${list.length} McDonald's nach data/mcdonalds.json geschrieben.`);
