'use strict';

// Holt alle McDonald's der Schweiz von der Overpass-API (OpenStreetMap).
// Wird vom Seed-Script benutzt. Node >= 18 hat global fetch.

const ENDPOINTS = [
  'https://overpass-api.de/api/interpreter',
  'https://overpass.kumi.systems/api/interpreter',
  'https://overpass.osm.ch/api/interpreter',
  'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
];

// Sucht in der Schweiz nach Fast-Food/Restaurants, deren Marke oder Name
// "McDonald" enthält (case-insensitive).
const QUERY = `
[out:json][timeout:120];
area["ISO3166-1"="CH"][admin_level=2]->.ch;
(
  nwr["amenity"~"fast_food|restaurant"]["brand"~"McDonald",i](area.ch);
  nwr["amenity"~"fast_food|restaurant"]["name"~"McDonald",i](area.ch);
);
out center tags;
`;

function parseElements(elements) {
  const list = [];
  for (const el of elements) {
    const lat = el.lat ?? el.center?.lat;
    const lon = el.lon ?? el.center?.lon;
    if (typeof lat !== 'number' || typeof lon !== 'number') continue;

    const t = el.tags || {};
    const extId = `${el.type[0]}${el.id}`; // n123 / w456 / r789
    list.push({
      ext_id: extId,
      name: t.name || t.brand || "McDonald's",
      lat,
      lon,
      street: t['addr:street'] || null,
      housenumber: t['addr:housenumber'] || null,
      postcode: t['addr:postcode'] || null,
      city: t['addr:city'] || null,
    });
  }
  return list;
}

async function fetchFromOverpass({ timeoutMs = 130000 } = {}) {
  let lastErr;
  for (const url of ENDPOINTS) {
    try {
      const ctrl = new AbortController();
      const timer = setTimeout(() => ctrl.abort(), timeoutMs);
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'User-Agent': 'McSammler/1.0 (Hobby-Projekt)',
        },
        body: 'data=' + encodeURIComponent(QUERY),
        signal: ctrl.signal,
      });
      clearTimeout(timer);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      const list = parseElements(json.elements || []);
      if (list.length === 0) throw new Error('0 Resultate');
      return { list, endpoint: url };
    } catch (err) {
      lastErr = err;
      console.warn(`  ⚠️  Overpass ${url} fehlgeschlagen: ${err.message}`);
    }
  }
  throw lastErr || new Error('Alle Overpass-Endpunkte fehlgeschlagen');
}

module.exports = { fetchFromOverpass, parseElements, QUERY, ENDPOINTS };
