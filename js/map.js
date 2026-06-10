'use strict';

// ============================================================================
//  Karten-Seite: McDonald's anzeigen, abhaken, zurücksetzen + Animationen.
//  Daten: Standorte live von Overpass (data.js), Besuche via Supabase (api.js).
// ============================================================================

const session = Session.get();

let MAP, MARKERS = new Map();   // ext_id -> { marker, mcd }
let VISITED = new Set();
let MCD_LIST = [];
let CURRENT = { visitCount: 0, total: 0, info: getRankInfo(0) };
let FILTER = 'all';

// --- Lade-Overlay ----------------------------------------------------------
function showLoading(text) {
  let el = document.getElementById('map-loading');
  if (!el) {
    el = document.createElement('div');
    el.id = 'map-loading';
    el.innerHTML = `<div class="ml-box"><div class="ml-spin">🍔</div><div class="ml-text"></div></div>`;
    document.body.appendChild(el);
  }
  el.querySelector('.ml-text').textContent = text || 'Lädt…';
  el.style.display = 'flex';
}
function hideLoading() { const el = document.getElementById('map-loading'); if (el) el.style.display = 'none'; }

// --- Marker-Icon ----------------------------------------------------------
function makeIcon(visited) {
  return L.divIcon({
    className: 'mc-divicon',
    html: `<div class="mc-marker ${visited ? 'visited' : ''}"><span class="mc-m">M</span><span class="mc-check">✓</span></div>`,
    iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -18],
  });
}

function popupHtml(mcd) {
  const visited = VISITED.has(mcd.ext_id);
  const addr = [
    mcd.street && `${mcd.street} ${mcd.housenumber || ''}`.trim(),
    [mcd.postcode, mcd.city].filter(Boolean).join(' '),
  ].filter(Boolean).join(', ');
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
  const info = CURRENT.info;
  document.getElementById('group-name').textContent = session.name;
  document.getElementById('rank-icon').textContent = info.rank.icon;
  document.getElementById('rank-icon').title = info.rank.subtitle || '';
  document.getElementById('rank-name').textContent = info.rank.name;
  document.getElementById('stat-count').textContent = CURRENT.visitCount;
  document.getElementById('stat-total').textContent = '/ ' + CURRENT.total;
  const pct = CURRENT.total ? Math.round((CURRENT.visitCount / CURRENT.total) * 100) : 0;
  document.getElementById('stat-pct').textContent = `(${pct}%)`;
  document.getElementById('progress-host').innerHTML = progressHtml(CURRENT.visitCount, info);
}

function setCount(count) {
  CURRENT.visitCount = count;
  CURRENT.total = MCD_LIST.length;
  CURRENT.info = getRankInfo(count);
  renderStatus();
}

// --- Filter ---------------------------------------------------------------
function applyFilter() {
  for (const [extId, { marker }] of MARKERS) {
    const v = VISITED.has(extId);
    const show = FILTER === 'all' || (FILTER === 'visited' && v) || (FILTER === 'open' && !v);
    if (show) { if (!MAP.hasLayer(marker)) marker.addTo(MAP); }
    else if (MAP.hasLayer(marker)) MAP.removeLayer(marker);
  }
}

// --- Abhaken / Zurücksetzen ------------------------------------------------
async function toggleVisit(extId) {
  const entry = MARKERS.get(extId);
  if (!entry) return;
  const visited = VISITED.has(extId);
  const before = CURRENT.visitCount;
  try {
    if (!visited) {
      const res = await API.addVisit(session.token, extId);
      VISITED.add(extId);
      entry.marker.setIcon(makeIcon(true));
      const prevIdx = getRankInfo(before).index;
      setCount(res.visit_count);
      celebrate(entry.marker);
      if (CURRENT.info.index > prevIdx) setTimeout(() => FX.rankUp(CURRENT.info.rank), 400);
    } else {
      const res = await API.removeVisit(session.token, extId);
      VISITED.delete(extId);
      entry.marker.setIcon(makeIcon(false));
      const prevIdx = getRankInfo(before).index;
      setCount(res.visit_count);
      FX.sound.undo();
      const p = markerPoint(entry.marker);
      FX.floatLabel('−1', p.x, p.y, 'minus');
      if (CURRENT.info.index < prevIdx) toast(`Zurückgestuft auf „${CURRENT.info.rank.name}" ${CURRENT.info.rank.icon}`);
    }
    applyFilter();
    MAP.closePopup();
  } catch (ex) {
    if (ex.notConfigured) { toast('Supabase nicht konfiguriert.', 'error'); return; }
    toast('Fehler: ' + ex.message, 'error');
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
      const entry = MARKERS.get(it.dataset.ext);
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
document.addEventListener('click', (e) => { if (!e.target.closest('.map-search')) $results.classList.remove('show'); });

// --- Steuer-Buttons --------------------------------------------------------
document.getElementById('filter').addEventListener('click', (e) => {
  const btn = e.target.closest('button'); if (!btn) return;
  FILTER = btn.dataset.f;
  document.querySelectorAll('#filter button').forEach((b) => b.classList.toggle('active', b === btn));
  applyFilter();
});
document.getElementById('btn-locate').addEventListener('click', () => MAP.locate({ setView: true, maxZoom: 13 }));
document.getElementById('btn-logout').addEventListener('click', () => {
  if (session && session.token) API.logout(session.token);
  Session.clear(); location.href = 'index.html';
});
document.getElementById('btn-refresh').addEventListener('click', reloadLocations);
document.getElementById('btn-ranks').addEventListener('click', showRanks);

const $mute = document.getElementById('btn-mute');
function refreshMute() { $mute.textContent = FX.isMuted() ? '🔇' : '🔊'; }
$mute.addEventListener('click', () => { FX.toggleMute(); refreshMute(); });
refreshMute();

function showRanks() {
  const cur = CURRENT.info.index;
  const html = `<div class="rank-list">` + RANKS.map((r, i) => `
    <div class="rank-row ${i === cur ? 'current' : ''} ${i < cur ? 'done' : ''}">
      <span class="rr-icon">${r.icon}</span>
      <span class="rr-info"><span class="rr-name">${escapeHtml(r.name)}</span>
        <span class="rr-sub muted">${escapeHtml(r.subtitle)}</span></span>
      <span class="rr-min">ab ${r.minVisits}</span>
    </div>`).join('') + `</div>`;
  openModal({ title: '🏅 Alle Ränge', html });
}

// --- Marker bauen ----------------------------------------------------------
function buildMarkers() {
  for (const { marker } of MARKERS.values()) MAP.removeLayer(marker);
  MARKERS.clear();
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
  applyFilter();
}

async function reloadLocations() {
  showLoading('Aktualisiere Standorte von OpenStreetMap…');
  try {
    const { list, source } = await loadMcdonalds(showLoading, true);
    MCD_LIST = list;
    buildMarkers();
    setCount(CURRENT.visitCount);
    toast(source === 'overpass' ? `${list.length} McDonald's aktualisiert ✅` : `${list.length} Standorte (Fallback)`);
  } catch (e) {
    toast('Aktualisieren fehlgeschlagen: ' + e.message, 'error');
  } finally { hideLoading(); }
}

// --- Initialisierung -------------------------------------------------------
async function init() {
  if (!session) { location.href = 'index.html'; return; }
  if (!requireConfigOrWarn()) return;

  renderStatus();
  MAP = L.map('map', { zoomControl: true }).setView([46.8, 8.23], 8);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(MAP);
  MAP.on('locationerror', () => toast('Standort nicht verfügbar.', 'error'));
  MAP.on('locationfound', (e) => {
    L.circleMarker(e.latlng, { radius: 8, color: '#264653', fillColor: '#4895ef', fillOpacity: 0.9, weight: 2 })
      .addTo(MAP).bindPopup('Du bist hier 📍').openPopup();
  });

  showLoading("Lade alle McDonald's der Schweiz…");
  let mcd, visitData;
  try {
    [mcd, visitData] = await Promise.all([
      loadMcdonalds(showLoading),
      API.getVisits(session.token),
    ]);
  } catch (ex) {
    hideLoading();
    if (ex.app || ex.status === 400) { toast('Sitzung abgelaufen – bitte neu einloggen.'); Session.clear(); setTimeout(() => location.href = 'index.html', 900); return; }
    toast('Fehler beim Laden: ' + ex.message, 'error');
    return;
  }

  MCD_LIST = mcd.list;
  VISITED = new Set(visitData.visits || []);
  buildMarkers();
  setCount(visitData.visit_count || VISITED.size);
  hideLoading();

  if (mcd.source === 'bundled') toast('OpenStreetMap nicht erreichbar – gebündelte Liste geladen.', 'error');
  if (MCD_LIST.length === 0) toast('Keine McDonald\'s gefunden.', 'error');
}

init();
