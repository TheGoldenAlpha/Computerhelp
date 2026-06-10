'use strict';

// ============================================================================
//  McDonald's-Standorte laden.
//  Reihenfolge: frischer Cache  ->  LIVE von Overpass (OpenStreetMap)
//               ->  gebündelte Datei data/mcdonalds.json (Fallback).
//  Der Browser darf Overpass direkt abfragen (CORS erlaubt), darum bekommen
//  wir hier IMMER die vollständige, aktuelle Liste aller CH-McDonald's.
// ============================================================================

const OVERPASS_ENDPOINTS = [
  'https://overpass-api.de/api/interpreter',
  'https://overpass.kumi.systems/api/interpreter',
  'https://overpass.osm.ch/api/interpreter',
];

const OVERPASS_QUERY = `
[out:json][timeout:90];
area["ISO3166-1"="CH"][admin_level=2]->.a;
(
  nwr["amenity"~"fast_food|restaurant"]["brand"~"McDonald",i](area.a);
  nwr["amenity"~"fast_food|restaurant"]["name"~"McDonald",i](area.a);
);
out center tags;`;

const CACHE_KEY = 'mcsammler_mcd_v1';
const CACHE_TTL = 7 * 24 * 60 * 60 * 1000; // 7 Tage

function parseOverpass(elements) {
  const out = [];
  for (const el of elements || []) {
    const lat = el.lat ?? (el.center && el.center.lat);
    const lon = el.lon ?? (el.center && el.center.lon);
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

async function fetchOverpass() {
  let lastErr;
  for (const url of OVERPASS_ENDPOINTS) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'data=' + encodeURIComponent(OVERPASS_QUERY),
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      const list = parseOverpass(json.elements);
      if (list.length === 0) throw new Error('0 Resultate');
      return list;
    } catch (e) { lastErr = e; }
  }
  throw lastErr || new Error('Overpass nicht erreichbar');
}

function readCache() {
  try {
    const raw = localStorage.getItem(CACHE_KEY);
    if (!raw) return null;
    const obj = JSON.parse(raw);
    if (!obj || !Array.isArray(obj.list) || !obj.list.length) return null;
    if (Date.now() - obj.ts > CACHE_TTL) return null;
    return obj.list;
  } catch { return null; }
}
function writeCache(list) {
  try { localStorage.setItem(CACHE_KEY, JSON.stringify({ ts: Date.now(), list })); } catch { /* voll */ }
}

async function fetchBundled() {
  const res = await fetch('data/mcdonalds.json', { cache: 'no-cache' });
  if (!res.ok) throw new Error('Fallback-Datei fehlt');
  return await res.json();
}

// onStatus(text) – optionale Callback-Funktion für Lade-Hinweise.
// force = true erzwingt frischen Overpass-Abruf (Cache ignorieren).
async function loadMcdonalds(onStatus, force = false) {
  if (!force) {
    const cached = readCache();
    if (cached) return { list: cached, source: 'cache' };
  }
  try {
    if (onStatus) onStatus("Lade alle McDonald's der Schweiz von OpenStreetMap…");
    const list = await fetchOverpass();
    writeCache(list);
    return { list, source: 'overpass' };
  } catch (e) {
    if (onStatus) onStatus('OpenStreetMap nicht erreichbar – nutze gebündelte Liste.');
    const list = await fetchBundled();
    return { list, source: 'bundled' };
  }
}

// Schnelle Gesamtzahl für die Startseite (ohne Overpass anzustossen).
async function mcdCountQuick() {
  const cached = readCache();
  if (cached) return cached.length;
  try { const b = await fetchBundled(); return b.length; } catch { return 0; }
}
