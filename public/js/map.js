'use strict';

// ============================================================================
//  Karten-Seite: McDonald's anzeigen, abhaken, zurücksetzen + Animationen.
// ============================================================================

const session = Session.get();
if (!session) { location.href = '/'; }

let MAP, MARKERS = new Map();   // ext_id -> { marker, mcd }
let VISITED = new Set();
let MCD_LIST = [];
let CURRENT = { visitCount: 0, total: 0, rank: null };
let FILTER = 'all';

// --- Marker-Icon ----------------------------------------------------------
function makeIcon(visited) {
  return L.divIcon({
    className: 'mc-divicon',
    html: `<div class="mc-marker ${visited ? 'visited' : ''}">
             <span class="mc-m">M</span>
             <span class="mc-check">✓</span>
           </div>`,
    iconSize: [34, 34],
    iconAnchor: [17, 17],
    popupAnchor: [0, -18],
  });
}

function popupHtml(mcd) {
  const visited = VISITED.has(mcd.ext_id);
  const addr = [mcd.street && `${mcd.street} ${mcd.housenumber || ''}`.trim(), [mcd.postcode, mcd.city].filter(Boolean).join(' ')]
    .filter(Boolean).join(', ');
  return `
    <div class="mc-popup">
      <div class="mcp-title">${escapeHtml(mcd.name || "McDonald's")}</div>
      ${addr ? `<div class="mcp-addr">${escapeHtml(addr)}</div>` : (mcd.city ? `<div class="mcp-addr">${escapeHtml(mcd.city)}</div>` : '')}
      <button class="btn ${visited ? 'btn-ghost' : 'btn-primary'} btn-block mcp-action" data-ext="${escapeHtml(mcd.ext_id)}">
        ${visited ? '↩️ Zurücksetzen' : '✅ Abhaken!'}
      </button>
    </div>`;
}

// --- Status-Leiste oben ----------------------------------------------------
function renderStatus() {
  const r = CURRENT.rank;
  document.getElementById('group-name').textContent = session.name;
  if (r) {
    document.getElementById('rank-icon').textContent = r.icon;
    document.getElementById('rank-name').textContent = r.name;
    document.getElementById('rank-icon').title = r.subtitle || '';
  }
  document.getElementById('stat-count').textContent = CURRENT.visitCount;
  document.getElementById('stat-total').textContent = '/ ' + CURRENT.total;
  const pct = CURRENT.total ? Math.round((CURRENT.visitCount / CURRENT.total) * 100) : 0;
  document.getElementById('stat-pct').textContent = `(${pct}%)`;
  document.getElementById('progress-host').innerHTML = r ? progressHtml(CURRENT.visitCount, r) : '';
}

// --- Marker-Sichtbarkeit (Filter) -----------------------------------------
function applyFilter() {
  for (const [extId, { marker }] of MARKERS) {
    const v = VISITED.has(extId);
    const show = FILTER === 'all' || (FILTER === 'visited' && v) || (FILTER === 'open' && !v);
    if (show) { if (!MAP.hasLayer(marker)) marker.addTo(MAP); }
    else { if (MAP.hasLayer(marker)) MAP.removeLayer(marker); }
  }
}

// --- Abhaken / Zurücksetzen ------------------------------------------------
async function toggleVisit(extId) {
  const entry = MARKERS.get(extId);
  if (!entry) return;
  const visited = VISITED.has(extId);
  try {
    if (!visited) {
      const res = await api(`/api/groups/${session.id}/visits`, {
        method: 'POST', token: session.token, body: { ext_id: extId },
      });
      VISITED.add(extId);
      entry.marker.setIcon(makeIcon(true));
      CURRENT = { visitCount: res.visitCount, total: res.total, rank: res.rank };
      celebrate(entry.marker);
      if (res.rankUp) {
        setTimeout(() => FX.rankUp(res.rank), 400);
      }
    } else {
      const res = await api(`/api/groups/${session.id}/visits/${encodeURIComponent(extId)}`, {
        method: 'DELETE', token: session.token,
      });
      VISITED.delete(extId);
      entry.marker.setIcon(makeIcon(false));
      CURRENT = { visitCount: res.visitCount, total: res.total, rank: res.rank };
      FX.sound.undo();
      const p = markerPoint(entry.marker);
      FX.floatLabel('−1', p.x, p.y, 'minus');
      if (res.rankDown) toast(`Zurückgestuft auf „${res.rank.name}“ ${res.rank.icon}`);
    }
    renderStatus();
    applyFilter();
    MAP.closePopup();
  } catch (ex) {
    if (ex.status === 401) { toast('Sitzung abgelaufen – bitte neu einloggen.'); Session.clear(); setTimeout(() => location.href = '/', 800); }
    else toast('Fehler: ' + ex.message, 'error');
  }
}

function markerPoint(marker) {
  const pt = MAP.latLngToContainerPoint(marker.getLatLng());
  const rect = MAP.getContainer().getBoundingClientRect();
  return { x: rect.left + pt.x, y: rect.top + pt.y };
}

function celebrate(marker) {
  const p = markerPoint(marker);
  FX.sound.pop();
  FX.confetti(36, p.x, p.y);
  FX.burgerRain(18);
  FX.floatLabel('+1 🍔', p.x, p.y, 'plus');
}

// --- Suche ----------------------------------------------------------------
const $search = document.getElementById('mc-search');
const $results = document.getElementById('mc-results');

function runSearch() {
  const q = $search.value.trim().toLowerCase();
  if (!q) { $results.classList.remove('show'); $results.innerHTML = ''; return; }
  const hits = MCD_LIST.filter((m) =>
    (m.name || '').toLowerCase().includes(q) || (m.city || '').toLowerCase().includes(q)
  ).slice(0, 8);
  if (hits.length === 0) { $results.innerHTML = '<div class="mcr-empty">Nichts gefunden</div>'; $results.classList.add('show'); return; }
  $results.innerHTML = hits.map((m) => `
    <button class="mcr-item" data-ext="${escapeHtml(m.ext_id)}">
      <span class="mcr-dot ${VISITED.has(m.ext_id) ? 'on' : ''}"></span>
      <span class="mcr-name">${escapeHtml(m.name || "McDonald's")}</span>
      <span class="mcr-city muted">${escapeHtml(m.city || '')}</span>
    </button>`).join('');
  $results.classList.add('show');
  $results.querySelectorAll('.mcr-item').forEach((it) => {
    it.addEventListener('click', () => {
      const ext = it.dataset.ext;
      const entry = MARKERS.get(ext);
      if (entry) {
        if (!MAP.hasLayer(entry.marker)) entry.marker.addTo(MAP);
        MAP.flyTo(entry.marker.getLatLng(), 15, { duration: 0.8 });
        setTimeout(() => entry.marker.openPopup(), 850);
      }
      $results.classList.remove('show');
      $search.value = '';
    });
  });
}
$search.addEventListener('input', runSearch);
document.addEventListener('click', (e) => {
  if (!e.target.closest('.map-search')) $results.classList.remove('show');
});

// --- Steuer-Buttons --------------------------------------------------------
document.getElementById('filter').addEventListener('click', (e) => {
  const btn = e.target.closest('button'); if (!btn) return;
  FILTER = btn.dataset.f;
  document.querySelectorAll('#filter button').forEach((b) => b.classList.toggle('active', b === btn));
  applyFilter();
});

document.getElementById('btn-locate').addEventListener('click', () => {
  MAP.locate({ setView: true, maxZoom: 13 });
});

document.getElementById('btn-logout').addEventListener('click', () => {
  Session.clear(); location.href = '/';
});

const $mute = document.getElementById('btn-mute');
function refreshMute() { $mute.textContent = FX.isMuted() ? '🔇' : '🔊'; }
$mute.addEventListener('click', () => { FX.toggleMute(); refreshMute(); });
refreshMute();

document.getElementById('btn-ranks').addEventListener('click', showRanks);

async function showRanks() {
  let ranks = [];
  try { ranks = await api('/api/ranks'); } catch { /* egal */ }
  const cur = CURRENT.rank ? CURRENT.rank.index : 0;
  const html = `<div class="rank-list">` + ranks.map((r) => `
    <div class="rank-row ${r.index === cur ? 'current' : ''} ${r.index < cur ? 'done' : ''}">
      <span class="rr-icon">${r.icon}</span>
      <span class="rr-info">
        <span class="rr-name">${escapeHtml(r.name)}</span>
        <span class="rr-sub muted">${escapeHtml(r.subtitle)}</span>
      </span>
      <span class="rr-min">ab ${r.minVisits}</span>
    </div>`).join('') + `</div>`;
  openModal({ title: '🏅 Alle Ränge', html });
}

// --- Initialisierung -------------------------------------------------------
async function init() {
  renderStatus();
  MAP = L.map('map', { zoomControl: true, attributionControl: true }).setView([46.8, 8.23], 8);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
  }).addTo(MAP);

  MAP.on('locationerror', () => toast('Standort nicht verfügbar.', 'error'));
  MAP.on('locationfound', (e) => {
    L.circleMarker(e.latlng, { radius: 8, color: '#264653', fillColor: '#4895ef', fillOpacity: 0.9, weight: 2 })
      .addTo(MAP).bindPopup('Du bist hier 📍').openPopup();
  });

  // Daten parallel laden
  let mcdData, visitData;
  try {
    [mcdData, visitData] = await Promise.all([
      api('/api/mcdonalds'),
      api(`/api/groups/${session.id}/visits`, { token: session.token }),
    ]);
  } catch (ex) {
    if (ex.status === 401) { Session.clear(); location.href = '/'; return; }
    toast('Fehler beim Laden: ' + ex.message, 'error');
    return;
  }

  MCD_LIST = mcdData.mcdonalds;
  VISITED = new Set(visitData.visits);
  CURRENT = { visitCount: visitData.visitCount, total: visitData.total, rank: visitData.rank };
  renderStatus();

  for (const mcd of MCD_LIST) {
    const marker = L.marker([mcd.lat, mcd.lon], { icon: makeIcon(VISITED.has(mcd.ext_id)) });
    marker.bindPopup(() => popupHtml(mcd));
    marker.on('popupopen', (e) => {
      const btn = e.popup.getElement().querySelector('.mcp-action');
      if (btn) btn.addEventListener('click', () => toggleVisit(mcd.ext_id), { once: true });
    });
    marker.addTo(MAP);
    MARKERS.set(mcd.ext_id, { marker, mcd });
  }

  if (MCD_LIST.length === 0) {
    toast('Keine McDonald\'s in der DB – bitte "npm run seed" ausführen.', 'error');
  }
}

init();
