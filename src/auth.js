'use strict';

const crypto = require('crypto');
const { getSecret } = require('./db');

// Zustandslose, signierte Tokens: "<groupId>.<hmac>".
// Kein Server-seitiger Session-Speicher nötig -> überlebt Neustarts.
function sign(groupId) {
  const secret = getSecret();
  const mac = crypto.createHmac('sha256', secret).update(String(groupId)).digest('hex');
  return `${groupId}.${mac}`;
}

function verify(token) {
  if (!token || typeof token !== 'string') return null;
  const dot = token.indexOf('.');
  if (dot < 0) return null;
  const groupId = token.slice(0, dot);
  const mac = token.slice(dot + 1);
  const expected = sign(groupId).split('.')[1];
  // Timing-sichere Prüfung
  const a = Buffer.from(mac);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return null;
  const id = Number(groupId);
  return Number.isInteger(id) && id > 0 ? id : null;
}

// Express-Middleware: prüft Bearer-Token und dass er zur :id-Gruppe passt.
function requireGroupAuth(req, res, next) {
  const header = req.get('authorization') || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  const groupId = verify(token);
  const routeId = Number(req.params.id);
  if (!groupId || groupId !== routeId) {
    return res.status(401).json({ error: 'Nicht eingeloggt oder falsches Passwort.' });
  }
  req.groupId = groupId;
  next();
}

module.exports = { sign, verify, requireGroupAuth };
