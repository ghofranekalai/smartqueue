<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartQueue – Écran d'Appel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --sq-primary: #1a56db;
      --sq-dark:    #0f172a;
      --sq-accent:  #f59e0b;
    }

    * { box-sizing: border-box; }

    body {
      background: var(--sq-dark);
      color: #fff;
      font-family: 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ── HEADER ─────────────────────────────── */
    .sq-header {
      background: linear-gradient(135deg, var(--sq-primary), #1e3a8a);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 3px solid var(--sq-accent);
    }
    .sq-header .brand {
      font-size: 1.8rem;
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    .sq-header .brand i { color: var(--sq-accent); }
    .sq-clock {
      font-size: 2rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      color: var(--sq-accent);
    }

    /* ── GRAND TICKET EN COURS ──────────────── */
    .ticket-hero {
      background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
      border: 2px solid rgba(255,255,255,.15);
      border-radius: 20px;
      padding: 2rem;
      text-align: center;
      position: relative;
      overflow: hidden;
      transition: all .4s ease;
    }
    .ticket-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(245,158,11,.08) 0%, transparent 60%);
      animation: pulse-bg 3s ease-in-out infinite;
    }
    @keyframes pulse-bg {
      0%, 100% { transform: scale(1); opacity: .5; }
      50%       { transform: scale(1.1); opacity: 1; }
    }

    .ticket-hero.nouveau {
      animation: flash-ticket 1s ease-in-out 3;
      border-color: var(--sq-accent);
    }
    @keyframes flash-ticket {
      0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
      50%       { box-shadow: 0 0 40px 10px rgba(245,158,11,.5); }
    }

    .numero-principal {
      font-size: clamp(4rem, 12vw, 9rem);
      font-weight: 900;
      line-height: 1;
      color: var(--sq-accent);
      text-shadow: 0 0 40px rgba(245,158,11,.4);
      letter-spacing: -2px;
      position: relative;
      z-index: 1;
    }
    .guichet-principal {
      font-size: clamp(1.5rem, 4vw, 3rem);
      font-weight: 700;
      color: #fff;
      position: relative;
      z-index: 1;
    }
    .service-label {
      font-size: 1.1rem;
      color: rgba(255,255,255,.7);
      position: relative;
      z-index: 1;
    }
    .badge-urgent {
      background: #ef4444;
      color: #fff;
      padding: .4rem 1rem;
      border-radius: 999px;
      font-size: 1rem;
      font-weight: 700;
    }

    /* ── LISTE DES APPELS ──────────────────── */
    .appels-list {
      display: flex;
      flex-direction: column;
      gap: .75rem;
    }
    .appel-item {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 14px;
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all .3s ease;
    }
    .appel-item:hover {
      background: rgba(255,255,255,.1);
    }
    .appel-item .num {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--sq-accent);
    }
    .appel-item .info {
      font-size: .9rem;
      color: rgba(255,255,255,.6);
    }
    .appel-item .guichet-badge {
      background: var(--sq-primary);
      color: #fff;
      padding: .4rem 1rem;
      border-radius: 999px;
      font-weight: 700;
      font-size: 1.1rem;
    }
    .appel-item.en-service .guichet-badge {
      background: #059669;
    }

    /* ── STATS BAR ─────────────────────────── */
    .stats-bar {
      background: rgba(255,255,255,.05);
      border-top: 1px solid rgba(255,255,255,.1);
      padding: 1rem 2rem;
      display: flex;
      gap: 2rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    .stat-item {
      text-align: center;
    }
    .stat-value {
      font-size: 2rem;
      font-weight: 800;
      color: var(--sq-accent);
    }
    .stat-label {
      font-size: .75rem;
      color: rgba(255,255,255,.5);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* ── SECTION TITRES ────────────────────── */
    .section-title {
      font-size: .75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(255,255,255,.4);
      margin-bottom: .75rem;
    }

    /* ── GUICHETS OUVERTS ──────────────────── */
    .guichet-pill {
      background: rgba(5,150,105,.2);
      border: 1px solid rgba(5,150,105,.4);
      border-radius: 999px;
      padding: .35rem .9rem;
      font-size: .85rem;
      font-weight: 600;
      color: #6ee7b7;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
    }
    .guichet-pill i { color: #10b981; }

    /* ── VIDE ──────────────────────────────── */
    .empty-state {
      text-align: center;
      padding: 3rem;
      color: rgba(255,255,255,.3);
    }
    .empty-state i { font-size: 4rem; display: block; margin-bottom: 1rem; }

    /* ── INDICATEUR CONNEXION ──────────────── */
    .conn-dot {
      width: 10px; height: 10px;
      border-radius: 50%;
      background: #10b981;
      display: inline-block;
      animation: blink 2s ease-in-out infinite;
    }
    .conn-dot.offline { background: #ef4444; animation: none; }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: .3; }
    }

    /* ── TOAST ANNONCE ─────────────────────── */
    #toast-annonce {
      position: fixed;
      bottom: 2rem;
      left: 50%;
      transform: translateX(-50%) translateY(120px);
      background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
      border: 2px solid var(--sq-accent);
      border-radius: 16px;
      padding: 1.5rem 3rem;
      text-align: center;
      font-size: 1.5rem;
      font-weight: 700;
      box-shadow: 0 20px 60px rgba(0,0,0,.5);
      z-index: 9999;
      transition: transform .5s cubic-bezier(.34,1.56,.64,1);
      min-width: 350px;
    }
    #toast-annonce.show {
      transform: translateX(-50%) translateY(0);
    }
    #toast-annonce .toast-ticket { color: var(--sq-accent); font-size: 2.5rem; }
    #toast-annonce .toast-guichet { color: rgba(255,255,255,.8); font-size: 1.1rem; }

    /* ── RESPONSIVE ────────────────────────── */
    @media (max-width: 768px) {
      .sq-header { flex-direction: column; gap: .5rem; text-align: center; }
      .stats-bar { gap: 1rem; }
    }
  </style>
</head>
<body>

<!-- ── HEADER ──────────────────────────────────────── -->
<div class="sq-header">
  <div class="brand">
    <i class="bi bi-ticket-perforated-fill"></i> SmartQueue
    <small style="font-size:.8rem;font-weight:400;color:rgba(255,255,255,.6);margin-left:.5rem;">
      Écran d'Appel
    </small>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span class="conn-dot" id="connDot"></span>
    <span id="connLabel" style="font-size:.85rem;color:rgba(255,255,255,.5)">Connecté</span>
    <div class="sq-clock" id="clockDisplay">--:--</div>
  </div>
</div>

<!-- ── CONTENU PRINCIPAL ────────────────────────────── -->
<div class="container-fluid p-3" style="max-width:1400px;margin:0 auto;">
  <div class="row g-3 mt-1">

    <!-- COLONNE GAUCHE : Grand ticket en cours -->
    <div class="col-lg-5">
      <p class="section-title"><i class="bi bi-megaphone me-1"></i>Dernier appel</p>

      <div class="ticket-hero" id="heroCard">
        <div id="heroContent">
          <div class="empty-state">
            <i class="bi bi-hourglass"></i>
            <p>En attente d'appel…</p>
          </div>
        </div>
      </div>

      <!-- Guichets ouverts -->
      <div class="mt-4">
        <p class="section-title"><i class="bi bi-door-open me-1"></i>Guichets ouverts</p>
        <div id="guichetsList" class="d-flex flex-wrap gap-2">
          <span style="color:rgba(255,255,255,.3);font-size:.9rem">Chargement…</span>
        </div>
      </div>
    </div>

    <!-- COLONNE DROITE : Tous les appels en cours -->
    <div class="col-lg-7">
      <p class="section-title"><i class="bi bi-list-check me-1"></i>Tickets en cours de traitement</p>
      <div class="appels-list" id="appelsList">
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <p>Aucun ticket en cours</p>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ── BARRE DE STATS ───────────────────────────────── -->
<div class="stats-bar mt-4">
  <div class="stat-item">
    <div class="stat-value" id="statAttente">–</div>
    <div class="stat-label">En attente</div>
  </div>
  <div class="stat-item">
    <div class="stat-value" id="statAppele">–</div>
    <div class="stat-label">Appelés</div>
  </div>
  <div class="stat-item">
    <div class="stat-value" id="statService">–</div>
    <div class="stat-label">En service</div>
  </div>
  <div class="stat-item">
    <div class="stat-value" id="statTraites">–</div>
    <div class="stat-label">Traités aujourd'hui</div>
  </div>
</div>

<!-- ── TOAST ANNONCE ────────────────────────────────── -->
<div id="toast-annonce">
  <div class="toast-ticket" id="toastTicket">–</div>
  <div class="toast-guichet" id="toastGuichet">–</div>
</div>

<script>
'use strict';

// ── Horloge ────────────────────────────────────────────────────
function majHorloge() {
  const now = new Date();
  document.getElementById('clockDisplay').textContent =
    now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(majHorloge, 1000);
majHorloge();

// ── Synthèse vocale ─────────────────────────────────────────────
function annoncer(numero, guichet) {
  if (!('speechSynthesis' in window)) return;
  const utterance = new SpeechSynthesisUtterance(
    `Ticket ${numero.split('').join(' ')}. Guichet ${guichet}.`
  );
  utterance.lang = 'fr-FR';
  utterance.rate = 0.85;
  utterance.pitch = 1;
  window.speechSynthesis.cancel();
  setTimeout(() => window.speechSynthesis.speak(utterance), 200);
}

// ── Toast annonce ───────────────────────────────────────────────
function afficherToast(numero, guichet) {
  document.getElementById('toastTicket').textContent = numero;
  document.getElementById('toastGuichet').textContent = `→ Guichet ${guichet}`;
  const t = document.getElementById('toast-annonce');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 5000);
}

// ── Etat précédent ──────────────────────────────────────────────
let dernierNumero = null;

// ── Rendu hero ──────────────────────────────────────────────────
function renderHero(dernier) {
  const hero = document.getElementById('heroContent');
  const card = document.getElementById('heroCard');

  if (!dernier) {
    hero.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-hourglass"></i>
        <p>En attente d'appel…</p>
      </div>`;
    return;
  }

  const isNouveau = dernier.numero !== dernierNumero;

  hero.innerHTML = `
    <p class="service-label mb-2">
      <i class="bi bi-grid-3x3-gap me-1"></i>${escHTML(dernier.service_nom)}
    </p>
    <div class="numero-principal mb-2">${escHTML(dernier.numero)}</div>
    ${dernier.priorite === 'urgent' ? '<span class="badge-urgent mb-2 d-inline-block"><i class="bi bi-lightning-fill me-1"></i>URGENT</span>' : ''}
    <div class="guichet-principal mt-2">
      <i class="bi bi-door-open me-2"></i>Guichet ${escHTML(dernier.guichet_numero)}
    </div>
    <p class="service-label mt-3" style="font-size:.85rem">
      <i class="bi bi-clock me-1"></i>
      Appelé à ${dernier.appele_at ? new Date(dernier.appele_at).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '--:--'}
    </p>`;

  if (isNouveau) {
    card.classList.add('nouveau');
    setTimeout(() => card.classList.remove('nouveau'), 3200);
    afficherToast(dernier.numero, dernier.guichet_numero);
    annoncer(dernier.numero, dernier.guichet_numero);
    dernierNumero = dernier.numero;
  }
}

// ── Rendu liste ─────────────────────────────────────────────────
function renderListe(appels) {
  const container = document.getElementById('appelsList');
  if (!appels || appels.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>Aucun ticket en cours de traitement</p>
      </div>`;
    return;
  }

  container.innerHTML = appels.map(a => `
    <div class="appel-item ${a.statut === 'en_service' ? 'en-service' : ''}">
      <div>
        <div class="num">${escHTML(a.numero)}</div>
        <div class="info">
          <i class="bi bi-grid-3x3-gap me-1"></i>${escHTML(a.service_nom)}
          &nbsp;|&nbsp;
          <i class="bi bi-clock me-1"></i>
          ${a.appele_at ? new Date(a.appele_at).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '--'}
          ${a.priorite === 'urgent' ? '&nbsp;<span style="color:#f87171;font-size:.75rem"><i class="bi bi-lightning-fill"></i> Urgent</span>' : ''}
        </div>
      </div>
      <div class="text-end">
        <div class="guichet-badge">G-${escHTML(a.guichet_numero)}</div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:.25rem">
          ${a.statut === 'en_service' ? '<i class="bi bi-person-check me-1"></i>En service' : '<i class="bi bi-megaphone me-1"></i>Appelé'}
        </div>
      </div>
    </div>`
  ).join('');
}

// ── Rendu guichets ───────────────────────────────────────────────
function renderGuichets(guichets) {
  const container = document.getElementById('guichetsList');
  if (!guichets || guichets.length === 0) {
    container.innerHTML = `<span style="color:rgba(255,255,255,.3);font-size:.9rem">Aucun guichet ouvert</span>`;
    return;
  }
  container.innerHTML = guichets.map(g => `
    <span class="guichet-pill">
      <i class="bi bi-check-circle-fill"></i>
      G-${escHTML(g.numero)}
      <span style="color:rgba(255,255,255,.5);font-weight:400"> · ${escHTML(g.service_nom)}</span>
    </span>`
  ).join('');
}

// ── Rendu stats ──────────────────────────────────────────────────
function renderStats(stats) {
  if (!stats) return;
  document.getElementById('statAttente').textContent  = stats.total_attente  ?? '–';
  document.getElementById('statAppele').textContent   = stats.total_appele   ?? '–';
  document.getElementById('statService').textContent  = stats.total_en_service ?? '–';
  document.getElementById('statTraites').textContent  = stats.traites_auj    ?? '–';
}

// ── Connexion ────────────────────────────────────────────────────
function setConnexion(ok) {
  const dot   = document.getElementById('connDot');
  const label = document.getElementById('connLabel');
  if (ok) {
    dot.classList.remove('offline');
    label.textContent = 'Connecté';
  } else {
    dot.classList.add('offline');
    label.textContent = 'Hors ligne';
  }
}

// ── Echappement HTML ─────────────────────────────────────────────
function escHTML(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

// ── Rafraîchissement ─────────────────────────────────────────────
async function rafraichir() {
  try {
    const resp = await fetch('/smartqueue/api/appel_status.php?_=' + Date.now());
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const data = await resp.json();

    setConnexion(true);
    renderHero(data.dernier_appel);
    renderListe(data.appels);
    renderGuichets(data.guichets);
    renderStats(data.stats);

  } catch(e) {
    setConnexion(false);
    console.warn('Erreur rafraîchissement:', e.message);
  }
}

// Lancement
rafraichir();
setInterval(rafraichir, 5000); // Toutes les 5 secondes
</script>
</body>
</html>
