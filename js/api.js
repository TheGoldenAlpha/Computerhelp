'use strict';

// ============================================================================
//  Supabase-Anbindung (über die REST/RPC-Schnittstelle, ganz ohne Library)
//  + Session-Verwaltung im localStorage.
// ============================================================================

const SUPA = window.MCSAMMLER_CONFIG || {};

function isConfigured() {
  return !!(SUPA.SUPABASE_URL && SUPA.SUPABASE_ANON_KEY &&
           !SUPA.SUPABASE_URL.includes('DEIN-') && SUPA.SUPABASE_ANON_KEY.length > 20);
}

async function rpc(fn, params) {
  if (!isConfigured()) {
    const e = new Error('Supabase ist noch nicht konfiguriert (config.js).');
    e.notConfigured = true;
    throw e;
  }
  const res = await fetch(`${SUPA.SUPABASE_URL}/rest/v1/rpc/${fn}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      apikey: SUPA.SUPABASE_ANON_KEY,
      Authorization: 'Bearer ' + SUPA.SUPABASE_ANON_KEY,
    },
    body: JSON.stringify(params || {}),
  });
  let data = null;
  try { data = await res.json(); } catch { /* leer */ }
  if (!res.ok) {
    const e = new Error((data && (data.message || data.error || data.hint)) || `Fehler ${res.status}`);
    e.status = res.status;
    throw e;
  }
  // Unsere Funktionen geben ein Objekt mit ok-Flag zurück
  if (data && data.ok === false) {
    const e = new Error(data.error || 'Fehler');
    e.app = true;
    throw e;
  }
  return data;
}

const API = {
  isConfigured,
  createGroup: (name, password) => rpc('mc_create_group', { p_name: name, p_password: password }),
  login:       (groupId, password) => rpc('mc_login', { p_group_id: groupId, p_password: password }),
  listGroups:  (search = '') => rpc('mc_list_groups', { p_search: search }),
  getVisits:   (token) => rpc('mc_get_visits', { p_token: token }),
  addVisit:    (token, extId) => rpc('mc_add_visit', { p_token: token, p_ext_id: extId }),
  removeVisit: (token, extId) => rpc('mc_remove_visit', { p_token: token, p_ext_id: extId }),
  logout:      (token) => rpc('mc_logout', { p_token: token }).catch(() => {}),
};

// --- Session --------------------------------------------------------------
const SESSION_KEY = 'mcsammler_session';
const Session = {
  get() { try { return JSON.parse(localStorage.getItem(SESSION_KEY)); } catch { return null; } },
  set(s) { localStorage.setItem(SESSION_KEY, JSON.stringify(s)); },
  clear() { localStorage.removeItem(SESSION_KEY); },
};

// --- Hinweis-Overlay, falls config.js noch leer ist -----------------------
function requireConfigOrWarn() {
  if (isConfigured()) return true;
  if (document.querySelector('.setup-overlay')) return false;
  const ov = document.createElement('div');
  ov.className = 'setup-overlay';
  ov.innerHTML = `
    <div class="setup-card">
      <div class="setup-icon">🔌</div>
      <h2>Fast geschafft!</h2>
      <p>Die App ist noch nicht mit Supabase verbunden.</p>
      <p>Trage in der Datei <code>config.js</code> deine <strong>Supabase-URL</strong>
         und den <strong>anon&nbsp;public Key</strong> ein – die genaue Anleitung
         steht im <strong>README</strong> (Schritt 1–2).</p>
      <a class="btn btn-primary" href="https://supabase.com" target="_blank" rel="noopener">Zu Supabase</a>
    </div>`;
  document.body.appendChild(ov);
  return false;
}
