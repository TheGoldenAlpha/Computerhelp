'use strict';

// ============================================================================
//  FX – Konfetti, Burger-Regen, Häkchen-Pop, Rang-Aufstieg & kleine Sounds.
//  Alles ohne externe Libraries.
// ============================================================================
const FX = (() => {
  // --- Konfetti-Canvas ------------------------------------------------------
  let canvas, ctx, particles = [], rafId = null;

  function ensureCanvas() {
    if (canvas) return;
    canvas = document.createElement('canvas');
    canvas.id = 'fx-canvas';
    document.body.appendChild(canvas);
    ctx = canvas.getContext('2d');
    resize();
    window.addEventListener('resize', resize);
  }
  function resize() {
    if (!canvas) return;
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }

  const COLORS = ['#FFC72C', '#DA291C', '#FFFFFF', '#27AE60', '#264653'];

  function spawnConfetti(n, originX, originY) {
    ensureCanvas();
    const ox = originX ?? canvas.width / 2;
    const oy = originY ?? canvas.height / 3;
    for (let i = 0; i < n; i++) {
      const angle = Math.random() * Math.PI * 2;
      const speed = 4 + Math.random() * 8;
      particles.push({
        x: ox, y: oy,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed - 4,
        size: 6 + Math.random() * 7,
        color: COLORS[(Math.random() * COLORS.length) | 0],
        rot: Math.random() * Math.PI,
        vr: (Math.random() - 0.5) * 0.3,
        life: 1,
        decay: 0.006 + Math.random() * 0.01,
      });
    }
    if (!rafId) loop();
  }

  function loop() {
    rafId = requestAnimationFrame(loop);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (const p of particles) {
      p.vy += 0.25;           // Schwerkraft
      p.x += p.vx;
      p.y += p.vy;
      p.rot += p.vr;
      p.life -= p.decay;
      ctx.save();
      ctx.globalAlpha = Math.max(0, p.life);
      ctx.translate(p.x, p.y);
      ctx.rotate(p.rot);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
      ctx.restore();
    }
    particles = particles.filter((p) => p.life > 0 && p.y < canvas.height + 40);
    if (particles.length === 0) { cancelAnimationFrame(rafId); rafId = null; ctx.clearRect(0, 0, canvas.width, canvas.height); }
  }

  // --- Burger-Regen (Emoji fallen herab) -----------------------------------
  const RAIN_EMOJIS = ['🍔', '🍟', '🥤', '🍔', '🍔', '🧀'];
  function burgerRain(count = 26) {
    const layer = document.createElement('div');
    layer.className = 'fx-rain-layer';
    document.body.appendChild(layer);
    for (let i = 0; i < count; i++) {
      const span = document.createElement('span');
      span.className = 'fx-drop';
      span.textContent = RAIN_EMOJIS[(Math.random() * RAIN_EMOJIS.length) | 0];
      span.style.left = Math.random() * 100 + 'vw';
      span.style.fontSize = 18 + Math.random() * 26 + 'px';
      span.style.animationDuration = 1.6 + Math.random() * 1.8 + 's';
      span.style.animationDelay = Math.random() * 0.5 + 's';
      layer.appendChild(span);
    }
    setTimeout(() => layer.remove(), 4200);
  }

  // --- Schwebendes "+1" / "-1" ---------------------------------------------
  function floatLabel(text, x, y, cls = '') {
    const el = document.createElement('div');
    el.className = 'fx-float ' + cls;
    el.textContent = text;
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 1200);
  }

  // --- Rang-Aufstieg-Overlay -----------------------------------------------
  function rankUp(rank, onClose) {
    sound.fanfare();
    burgerRain(40);
    const center = window.innerWidth / 2;
    spawnConfetti(160, center, window.innerHeight / 2);
    setTimeout(() => spawnConfetti(120, center * 0.5, window.innerHeight / 3), 250);
    setTimeout(() => spawnConfetti(120, center * 1.5, window.innerHeight / 3), 450);

    const ov = document.createElement('div');
    ov.className = 'fx-rankup-overlay';
    ov.innerHTML = `
      <div class="fx-rankup-card">
        <div class="fx-rankup-banner">NEUER RANG!</div>
        <div class="fx-rankup-icon">${rank.icon}</div>
        <div class="fx-rankup-name">${escapeHtml(rank.name)}</div>
        <div class="fx-rankup-sub">${escapeHtml(rank.subtitle || '')}</div>
        <button class="fx-rankup-btn">Weiter so! 💪</button>
      </div>`;
    document.body.appendChild(ov);
    const close = () => { ov.classList.add('closing'); setTimeout(() => ov.remove(), 250); if (onClose) onClose(); };
    ov.querySelector('.fx-rankup-btn').addEventListener('click', close);
    ov.addEventListener('click', (e) => { if (e.target === ov) close(); });
  }

  // --- Sounds (WebAudio, mit Mute) -----------------------------------------
  let audioCtx = null;
  const MUTE_KEY = 'mcsammler_mute';
  function isMuted() { return localStorage.getItem(MUTE_KEY) === '1'; }
  function setMuted(v) { localStorage.setItem(MUTE_KEY, v ? '1' : '0'); }
  function toggleMute() { setMuted(!isMuted()); return isMuted(); }

  function tone(freq, start, dur, type = 'sine', gain = 0.15) {
    if (isMuted()) return;
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      const t0 = audioCtx.currentTime + start;
      const osc = audioCtx.createOscillator();
      const g = audioCtx.createGain();
      osc.type = type;
      osc.frequency.value = freq;
      g.gain.setValueAtTime(0, t0);
      g.gain.linearRampToValueAtTime(gain, t0 + 0.01);
      g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
      osc.connect(g).connect(audioCtx.destination);
      osc.start(t0);
      osc.stop(t0 + dur + 0.02);
    } catch (_) { /* Audio nicht verfügbar – egal */ }
  }

  const sound = {
    pop()  { tone(660, 0, 0.12, 'triangle', 0.18); tone(990, 0.05, 0.12, 'sine', 0.12); },
    undo() { tone(420, 0, 0.14, 'sine', 0.14); tone(260, 0.08, 0.16, 'sine', 0.12); },
    fanfare() {
      const notes = [523.25, 659.25, 783.99, 1046.5]; // C E G C
      notes.forEach((f, i) => tone(f, i * 0.12, 0.22, 'triangle', 0.16));
      tone(1318.5, 0.5, 0.4, 'sine', 0.12);
    },
  };

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  return {
    confetti: spawnConfetti,
    burgerRain,
    floatLabel,
    rankUp,
    sound,
    isMuted, setMuted, toggleMute,
  };
})();
