'use strict';

// Die Ränge – von "extrem dünn" bis "beleidigend dick" 😄
// minVisits = ab wie vielen abgehakten McDonald's dieser Rang gilt.
const RANKS = [
  { minVisits: 0,   name: 'Windhauch',                icon: '🌬️', subtitle: 'Noch nie zugebissen. Verdächtig gesund.' },
  { minVisits: 1,   name: 'Grashalm',                 icon: '🌱', subtitle: 'So dünn, dich weht der Föhn um.' },
  { minVisits: 6,   name: 'Zahnstocher',              icon: '🦷', subtitle: 'Pass auf, dass dich niemand verschluckt.' },
  { minVisits: 11,  name: 'Spaghetti (ungekocht)',    icon: '🍝', subtitle: 'Knackt beim Umfallen.' },
  { minVisits: 18,  name: 'Besenstiel',               icon: '🧹', subtitle: 'Seitlich gedreht bist du unsichtbar.' },
  { minVisits: 26,  name: 'Bohnenstange',             icon: '🫛', subtitle: 'Wächst langsam… in die Höhe.' },
  { minVisits: 35,  name: 'Pommes-Lehrling',          icon: '🍟', subtitle: 'Erste Anzeichen von Geschmack.' },
  { minVisits: 44,  name: 'Stammgast',                icon: '🪑', subtitle: 'Der Stuhl kennt dich beim Vornamen.' },
  { minVisits: 50,  name: 'Mobile Panzersperre',      icon: '🚧', subtitle: 'Strassen werden für dich gesperrt.' },
  { minVisits: 61,  name: 'Wandelnder Big Mac',       icon: '🍔', subtitle: 'Zwei Etagen, Spezialsauce inklusive.' },
  { minVisits: 74,  name: 'Schwergewicht',            icon: '🏋️', subtitle: 'Die Waage bittet um eine Pause.' },
  { minVisits: 87,  name: 'Türrahmen-Optimierer',     icon: '🚪', subtitle: 'Türen werden für dich nachgemessen.' },
  { minVisits: 101, name: 'Eigene Postleitzahl',      icon: '📮', subtitle: 'Gross genug für einen eigenen Bezirk.' },
  { minVisits: 116, name: 'Wandelnde Litfasssäule',   icon: '🛢️', subtitle: 'Rundum bedruckbar.' },
  { minVisits: 131, name: 'Eigenes Gravitationsfeld', icon: '🪐', subtitle: 'Kleine Objekte umkreisen dich bereits.' },
  { minVisits: 150, name: 'Personifizierter Bypass',  icon: '🫀', subtitle: 'Der Kardiologe hat ein Foto von dir am Kühlschrank.' },
  { minVisits: 170, name: 'McLegende – Endgegner',    icon: '👑', subtitle: 'Du HAST den Bogen raus. Und alles andere auch.' },
];

function getRankInfo(visits) {
  let index = 0;
  for (let i = 0; i < RANKS.length; i++) {
    if (visits >= RANKS[i].minVisits) index = i; else break;
  }
  const rank = RANKS[index];
  const next = RANKS[index + 1] || null;
  return {
    index, rank, next, visits,
    toNext: next ? next.minVisits - visits : 0,
    current: rank.minVisits,
  };
}
