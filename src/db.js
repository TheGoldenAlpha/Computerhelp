'use strict';

const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const Database = require('better-sqlite3');

// Speicherort der Datenbank (per ENV überschreibbar, z.B. für Deployments)
const DB_PATH = process.env.DB_PATH || path.join(__dirname, '..', 'data', 'mcsammler.db');

// Sicherstellen, dass der data/-Ordner existiert
fs.mkdirSync(path.dirname(DB_PATH), { recursive: true });

const db = new Database(DB_PATH);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

// --- Schema ---------------------------------------------------------------
db.exec(`
  CREATE TABLE IF NOT EXISTS meta (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
  );

  CREATE TABLE IF NOT EXISTS groups (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL,
    name_lower    TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS mcdonalds (
    ext_id      TEXT PRIMARY KEY,   -- stabiler Schlüssel, z.B. "n123456" (OSM node/way)
    name        TEXT,
    lat         REAL NOT NULL,
    lon         REAL NOT NULL,
    street      TEXT,
    housenumber TEXT,
    postcode    TEXT,
    city        TEXT
  );

  -- Besuche referenzieren McDonald's bewusst per ext_id (TEXT) und NICHT per
  -- Fremdschlüssel. So kann die mcdonalds-Tabelle beim Re-Seed komplett neu
  -- aufgebaut werden, ohne dass abgehakte Besuche verloren gehen.
  CREATE TABLE IF NOT EXISTS visits (
    group_id   INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    ext_id     TEXT NOT NULL,
    visited_at TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (group_id, ext_id)
  );

  CREATE INDEX IF NOT EXISTS idx_visits_group ON visits(group_id);
`);

// --- App-Secret (für signierte Tokens) ------------------------------------
// Wird einmalig erzeugt und in der DB gespeichert, damit Logins auch nach
// einem Server-Neustart gültig bleiben. Per ENV APP_SECRET überschreibbar.
function getSecret() {
  if (process.env.APP_SECRET) return process.env.APP_SECRET;
  const row = db.prepare('SELECT value FROM meta WHERE key = ?').get('app_secret');
  if (row) return row.value;
  const secret = crypto.randomBytes(32).toString('hex');
  db.prepare('INSERT INTO meta (key, value) VALUES (?, ?)').run('app_secret', secret);
  return secret;
}

module.exports = { db, getSecret, DB_PATH };
