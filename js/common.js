'use strict';

// ============================================================================
//  UI-Helfer: HTML-Escaping, Toasts, Modal, Fortschrittsbalken.
// ============================================================================

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
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
  setTimeout(() => el.classList.add('show'), 10);
  setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 2800);
}

// --- Modal ----------------------------------------------------------------
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

// --- Fortschrittsbalken zur nächsten Rang-Stufe ---------------------------
function progressHtml(visitCount, info) {
  const from = info.current ?? 0;
  const to = info.next ? info.next.minVisits : visitCount;
  const span = Math.max(1, to - from);
  const done = Math.min(span, visitCount - from);
  const pct = info.next ? Math.round((done / span) * 100) : 100;
  const label = info.next
    ? `Noch ${info.toNext} bis „${escapeHtml(info.next.name)}" ${info.next.icon}`
    : 'Maximaler Rang erreicht! 👑';
  return `
    <div class="progress" title="${pct}%"><div class="progress-bar" style="width:${pct}%"></div></div>
    <div class="progress-label">${label}</div>`;
}
