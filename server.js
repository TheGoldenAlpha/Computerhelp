'use strict';

const path = require('path');
const express = require('express');
const bcrypt = require('bcryptjs');

const { db } = require('./src/db');
const { RANKS, getRankInfo } = require('./src/ranks');
const { sign, requireGroupAuth } = require('./src/auth');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());

// Statische Dateien
app.use(express.static(path.join(__dirname, 'public')));
// Leaflet aus node_modules ausliefern (keine CDN-Abhängigkeit)
app.use('/vendor/leaflet', express.static(path.join(__dirname, 'node_modules', 'leaflet', 'dist')));

// --- Hilfsfunktionen ------------------------------------------------------
const TOTAL_MCD = () => db.prepare('SELECT COUNT(*) AS c FROM mcdonalds').get().c;
const VISIT_COUNT = db.prepare('SELECT COUNT(*) AS c FROM visits WHERE group_id = ?');

function groupPublic(g) {
  const count = VISIT_COUNT.get(g.id).c;
  const info = getRankInfo(count);
  return {
    id: g.id,
    name: g.name,
    visitCount: count,
    rank: { index: info.index, name: info.rank.name, icon: info.rank.icon, subtitle: info.rank.subtitle },
  };
}

// Sehr einfacher Login-Schutz gegen Brute-Force (in-memory)
const attempts = new Map(); // key: groupId -> { count, until }
function tooManyAttempts(groupId) {
  const a = attempts.get(groupId);
  if (a && a.until > Date.now() && a.count >= 10) return true;
  return false;
}
function noteFail(groupId) {
  const now = Date.now();
  const a = attempts.get(groupId) || { count: 0, until: now + 10 * 60 * 1000 };
  if (a.until < now) { a.count = 0; a.until = now + 10 * 60 * 1000; }
  a.count++;
  attempts.set(groupId, a);
}
function clearFails(groupId) { attempts.delete(groupId); }

// --- API: Ränge -----------------------------------------------------------
app.get('/api/ranks', (req, res) => {
  res.json(RANKS.map((r, i) => ({ index: i, ...r })));
});

// --- API: McDonald's-Standorte -------------------------------------------
app.get('/api/mcdonalds', (req, res) => {
  const rows = db.prepare('SELECT ext_id, name, lat, lon, street, housenumber, postcode, city FROM mcdonalds').all();
  res.json({ total: rows.length, mcdonalds: rows });
});

// --- API: Gruppen auflisten / suchen -------------------------------------
app.get('/api/groups', (req, res) => {
  const q = (req.query.q || '').toString().trim().toLowerCase();
  let rows;
  if (q) {
    rows = db.prepare('SELECT * FROM groups WHERE name_lower LIKE ? ORDER BY name COLLATE NOCASE')
             .all(`%${q}%`);
  } else {
    rows = db.prepare('SELECT * FROM groups ORDER BY name COLLATE NOCASE').all();
  }
  const groups = rows.map(groupPublic);
  // Für das Leaderboard zusätzlich nach Anzahl sortiert mitgeben
  const leaderboard = [...groups].sort((a, b) => b.visitCount - a.visitCount);
  res.json({ total: TOTAL_MCD(), groups, leaderboard });
});

// --- API: Gruppe erstellen ------------------------------------------------
app.post('/api/groups', (req, res) => {
  const name = (req.body?.name || '').toString().trim();
  const password = (req.body?.password || '').toString();

  if (name.length < 1 || name.length > 40) {
    return res.status(400).json({ error: 'Name muss 1–40 Zeichen lang sein.' });
  }
  if (password.length < 3 || password.length > 200) {
    return res.status(400).json({ error: 'Passwort muss mindestens 3 Zeichen haben.' });
  }

  const exists = db.prepare('SELECT 1 FROM groups WHERE name_lower = ?').get(name.toLowerCase());
  if (exists) return res.status(409).json({ error: 'Diese Gruppe gibt es schon.' });

  const hash = bcrypt.hashSync(password, 10);
  const info = db.prepare('INSERT INTO groups (name, name_lower, password_hash) VALUES (?, ?, ?)')
                 .run(name, name.toLowerCase(), hash);
  const id = info.lastInsertRowid;
  res.status(201).json({ id, name, token: sign(id) });
});

// --- API: Login -----------------------------------------------------------
app.post('/api/groups/:id/login', (req, res) => {
  const id = Number(req.params.id);
  const password = (req.body?.password || '').toString();
  const group = db.prepare('SELECT * FROM groups WHERE id = ?').get(id);
  if (!group) return res.status(404).json({ error: 'Gruppe nicht gefunden.' });

  if (tooManyAttempts(id)) {
    return res.status(429).json({ error: 'Zu viele Versuche. Bitte kurz warten.' });
  }
  if (!bcrypt.compareSync(password, group.password_hash)) {
    noteFail(id);
    return res.status(401).json({ error: 'Falsches Passwort.' });
  }
  clearFails(id);
  res.json({ token: sign(id), id, name: group.name });
});

// --- API: Besuche einer Gruppe lesen (geschützt) -------------------------
app.get('/api/groups/:id/visits', requireGroupAuth, (req, res) => {
  const rows = db.prepare('SELECT ext_id FROM visits WHERE group_id = ?').all(req.groupId);
  const count = rows.length;
  const info = getRankInfo(count);
  const group = db.prepare('SELECT name FROM groups WHERE id = ?').get(req.groupId);
  res.json({
    name: group.name,
    visits: rows.map((r) => r.ext_id),
    visitCount: count,
    total: TOTAL_MCD(),
    rank: rankPayload(info),
  });
});

// --- API: McDonald's abhaken (geschützt) ---------------------------------
app.post('/api/groups/:id/visits', requireGroupAuth, (req, res) => {
  const extId = (req.body?.ext_id || '').toString();
  const mcd = db.prepare('SELECT 1 FROM mcdonalds WHERE ext_id = ?').get(extId);
  if (!mcd) return res.status(404).json({ error: 'McDonald\'s nicht gefunden.' });

  const before = VISIT_COUNT.get(req.groupId).c;
  db.prepare('INSERT OR IGNORE INTO visits (group_id, ext_id) VALUES (?, ?)').run(req.groupId, extId);
  const after = VISIT_COUNT.get(req.groupId).c;

  const prev = getRankInfo(before);
  const now = getRankInfo(after);
  res.json({
    ok: true,
    visitCount: after,
    total: TOTAL_MCD(),
    rank: rankPayload(now),
    rankUp: now.index > prev.index,
    prevRank: rankPayload(prev),
  });
});

// --- API: McDonald's zurücksetzen (geschützt) ----------------------------
app.delete('/api/groups/:id/visits/:extId', requireGroupAuth, (req, res) => {
  const extId = req.params.extId;
  const before = VISIT_COUNT.get(req.groupId).c;
  db.prepare('DELETE FROM visits WHERE group_id = ? AND ext_id = ?').run(req.groupId, extId);
  const after = VISIT_COUNT.get(req.groupId).c;
  const prev = getRankInfo(before);
  const now = getRankInfo(after);
  res.json({
    ok: true,
    visitCount: after,
    total: TOTAL_MCD(),
    rank: rankPayload(now),
    rankDown: now.index < prev.index,
  });
});

function rankPayload(info) {
  return {
    index: info.index,
    name: info.rank.name,
    icon: info.rank.icon,
    subtitle: info.rank.subtitle,
    toNext: info.toNext,
    next: info.next ? { name: info.next.name, icon: info.next.icon, minVisits: info.next.minVisits } : null,
    current: info.rank.minVisits,
  };
}

// SPA-artige Fallbacks für die zwei Seiten
app.get('/map', (req, res) => res.sendFile(path.join(__dirname, 'public', 'map.html')));

app.listen(PORT, () => {
  console.log(`🍔 McSammler läuft auf http://localhost:${PORT}`);
  console.log(`   ${TOTAL_MCD()} McDonald's in der Datenbank.`);
});
