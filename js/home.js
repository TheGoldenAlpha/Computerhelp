'use strict';

let ALL_GROUPS = [];
let TOTAL = 0;

const $groups = document.getElementById('groups');
const $meta = document.getElementById('meta');
const $search = document.getElementById('search');

async function load() {
  if (!requireConfigOrWarn()) {
    $groups.innerHTML = `<div class="empty">Bitte zuerst <code>config.js</code> ausfüllen (siehe README).</div>`;
    return;
  }
  try {
    const [groups, total] = await Promise.all([API.listGroups(''), mcdCountQuick()]);
    // nach Besuchszahl sortiert (Leaderboard)
    ALL_GROUPS = groups.slice().sort((a, b) => b.visit_count - a.visit_count);
    TOTAL = total;
    $meta.textContent = `${ALL_GROUPS.length} Gruppen · ${TOTAL} McDonald's`;
    render();
  } catch (err) {
    $groups.innerHTML = `<div class="empty">Konnte nicht laden: ${escapeHtml(err.message)}</div>`;
  }
}

function render() {
  const q = $search.value.trim().toLowerCase();
  const list = q ? ALL_GROUPS.filter((g) => g.name.toLowerCase().includes(q)) : ALL_GROUPS;

  if (list.length === 0) {
    $groups.innerHTML = `<div class="empty">${q ? 'Keine Gruppe gefunden 🤷' : 'Noch keine Gruppe – erstelle die erste! 🎉'}</div>`;
    return;
  }

  const medals = ['🥇', '🥈', '🥉'];
  $groups.innerHTML = list.map((g) => {
    const info = getRankInfo(g.visit_count);
    const place = ALL_GROUPS.indexOf(g);
    const medal = place < 3 ? medals[place] : `<span class="place">${place + 1}.</span>`;
    const pct = TOTAL ? Math.round((g.visit_count / TOTAL) * 100) : 0;
    return `
      <button class="group-card" data-id="${g.id}" data-name="${escapeHtml(g.name)}">
        <div class="gc-rank">${medal}</div>
        <div class="gc-icon" title="${escapeHtml(info.rank.name)}">${info.rank.icon}</div>
        <div class="gc-main">
          <div class="gc-name">${escapeHtml(g.name)}</div>
          <div class="gc-rankname">${escapeHtml(info.rank.name)}</div>
          <div class="progress slim"><div class="progress-bar" style="width:${pct}%"></div></div>
        </div>
        <div class="gc-count"><strong>${g.visit_count}</strong><span class="muted">/ ${TOTAL}</span></div>
      </button>`;
  }).join('');

  $groups.querySelectorAll('.group-card').forEach((card) => {
    card.addEventListener('click', () => askPassword(Number(card.dataset.id), card.dataset.name));
  });
}

$search.addEventListener('input', render);

// --- Login per Passwort ---------------------------------------------------
function askPassword(id, name) {
  openModal({
    title: `Einloggen als „${name}"`,
    html: `
      <p class="muted">Gib das Passwort dieser Gruppe ein, um ihre Karte zu öffnen.</p>
      <form id="login-form">
        <input id="pw" class="input" type="password" placeholder="Passwort" autocomplete="current-password" />
        <div class="form-err" id="login-err"></div>
        <button class="btn btn-primary btn-block" type="submit">Karte öffnen 🗺️</button>
      </form>`,
    onMount(body) {
      const form = body.querySelector('#login-form');
      const pw = body.querySelector('#pw');
      const err = body.querySelector('#login-err');
      pw.focus();
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        err.textContent = '';
        try {
          const data = await API.login(id, pw.value);
          Session.set({ id: data.id, name: data.name, token: data.token });
          FX.sound.pop();
          location.href = 'map.html';
        } catch (ex) {
          err.textContent = ex.message;
          pw.select();
        }
      });
    },
  });
}

// --- Neue Gruppe ----------------------------------------------------------
document.getElementById('btn-new').addEventListener('click', () => {
  if (!requireConfigOrWarn()) return;
  openModal({
    title: 'Neue Gruppe erstellen',
    html: `
      <form id="new-form">
        <label class="field"><span>Gruppenname</span>
          <input id="ng-name" class="input" type="text" maxlength="40" placeholder="z.B. Die Pommes-Piraten" /></label>
        <label class="field"><span>Passwort</span>
          <input id="ng-pw" class="input" type="password" placeholder="mind. 3 Zeichen" /></label>
        <div class="form-err" id="ng-err"></div>
        <button class="btn btn-primary btn-block" type="submit">Los geht's! 🚀</button>
      </form>`,
    onMount(body) {
      const form = body.querySelector('#new-form');
      const name = body.querySelector('#ng-name');
      const pw = body.querySelector('#ng-pw');
      const err = body.querySelector('#ng-err');
      name.focus();
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        err.textContent = '';
        try {
          const data = await API.createGroup(name.value, pw.value);
          Session.set({ id: data.id, name: data.name, token: data.token });
          FX.confetti(120);
          FX.sound.fanfare();
          setTimeout(() => { location.href = 'map.html'; }, 600);
        } catch (ex) {
          err.textContent = ex.message;
        }
      });
    },
  });
});

// --- "Weiter als …" Schnellzugriff ---------------------------------------
function renderContinue() {
  const s = Session.get();
  const box = document.getElementById('continue-box');
  if (!s) { box.innerHTML = ''; return; }
  box.innerHTML = `
    <div class="continue">
      <span>Eingeloggt als <strong>${escapeHtml(s.name)}</strong></span>
      <div>
        <button class="btn btn-ghost" id="logout">Abmelden</button>
        <button class="btn btn-primary" id="goto-map">Zur Karte 🗺️</button>
      </div>
    </div>`;
  box.querySelector('#goto-map').addEventListener('click', () => location.href = 'map.html');
  box.querySelector('#logout').addEventListener('click', () => {
    if (s.token) API.logout(s.token);
    Session.clear(); renderContinue(); toast('Abgemeldet.');
  });
}

renderContinue();
load();
