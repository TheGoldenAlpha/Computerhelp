'use strict';

// ============================================================================
//  Gemeinsame Helfer: API-Aufrufe, Session, Modal, Toasts.
// ============================================================================

const SESSION_KEY = 'mcsammler_session';

const Session = {
  get() {
    try { return JSON.parse(localStorage.getItem(SESSION_KEY)); }
    catch { return null; }
  },
  set(s) { localStorage.setItem(SESSION_KEY, JSON.stringify(s)); },
  clear() { localStorage.removeItem(SESSION_KEY); },
};

async function api(path, { method = 'GET', body, token } = {}) {
  const headers = {};
  if (body) headers['Content-Type'] = 'application/json';
  if (token) headers['Authorization'] = 'Bearer ' + token;
  const res = await fetch(path, { method, headers, body: body ? JSON.stringify(body) : undefined });
  let data = null;
  try { data = await res.json(); } catch { /* leer */ }
  if (!res.ok) {
    const err = new Error((data && data.error) || `Fehler ${res.status}`);
    err.status = res.status;
    throw err;
  }
  return data;
}

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// --- Toast ----------------------------------------------------------------
function toast(msg, type = 'info') {
  let host = document.getElementById('toast-host');
  if (!host) {
    host = document.createElement('div');
    host.id = 'toast-host';
    document.body.appendChild(host);
  }
  const el = document.createElement('div');
  el.className = 'toast toast-' + type;
  el.textContent = msg;
  host.appendChild(el);
  setTimeout(() => { el.classList.add('show'); }, 10);
  setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 2800);
}

// --- Modal ----------------------------------------------------------------
// openModal({ title, html, onMount }) -> liefert {close, root}
function openModal({ title, html, onMount }) {
  const back = document.createElement('div');
  back.className = 'modal-back';
  back.innerHTML = `
    <div class="modal">
      <div class="modal-head">
        <h2>${escapeHtml(title)}</h2>
        <button class="modal-x" aria-label="Schliessen">✕</button>
      </div>
      <div class="modal-body">${html}</div>
    </div>`;
  document.body.appendChild(back);
  const close = () => { back.classList.add('closing'); setTimeout(() => back.remove(), 200); };
  back.querySelector('.modal-x').addEventListener('click', close);
  back.addEventListener('click', (e) => { if (e.target === back) close(); });
  document.addEventListener('keydown', function esc(e) {
    if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); }
  });
  requestAnimationFrame(() => back.classList.add('open'));
  if (onMount) onMount(back.querySelector('.modal-body'), close);
  return { close, root: back };
}

// Fortschrittsbalken-HTML für eine Rang-Info
function progressHtml(visitCount, rank) {
  const from = rank.current ?? 0;
  const to = rank.next ? rank.next.minVisits : visitCount;
  const span = Math.max(1, to - from);
  const done = Math.min(span, visitCount - from);
  const pct = rank.next ? Math.round((done / span) * 100) : 100;
  const label = rank.next
    ? `Noch ${rank.toNext} bis „${escapeHtml(rank.next.name)}“ ${rank.next.icon}`
    : 'Maximaler Rang erreicht! 👑';
  return `
    <div class="progress" title="${pct}%">
      <div class="progress-bar" style="width:${pct}%"></div>
    </div>
    <div class="progress-label">${label}</div>`;
}
