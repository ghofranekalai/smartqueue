<?php
require_once './config/session.php';
require_once './config/connexion.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin')   header("Location: /smartqueue/admin/dashboard.php");
    elseif ($role === 'agent') header("Location: /smartqueue/guichet/dashboard.php");
    else header("Location: /smartqueue/citoyen/dashboard.php");
    exit();
}

// Services actifs pour affichage public
$stmt = $conn->query("SELECT * FROM services WHERE est_actif=1 ORDER BY nom");
$services = $stmt->fetchAll();

// Nombre de tickets en attente par service
$counts = [];
$res = $conn->query(
    "SELECT service_id, COUNT(*) AS nb FROM tickets
     WHERE statut='en_attente' GROUP BY service_id"
);
foreach ($res->fetchAll() as $row) {
    $counts[$row['service_id']] = $row['nb'];
}

// Statistiques globales (compteurs animés)
$totalTickets  = $conn->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$totalServices = $conn->query("SELECT COUNT(*) FROM services WHERE est_actif=1")->fetchColumn();
$totalTraites  = $conn->query("SELECT COUNT(*) FROM tickets WHERE statut='termine'")->fetchColumn();
?>
<?php include './layout/header.php'; ?>

<!-- ═══════════════════════════ HERO ═══════════════════════════ -->
<div class="sq-hero mb-5">
  <div class="container text-center">
    <i class="bi bi-ticket-perforated-fill" style="font-size:3.5rem;opacity:.9"></i>
    <h1 class="display-4 fw-bold mt-2">SmartQueue</h1>
    <p class="lead mb-4">
      Gérez votre file d'attente en ligne — sans vous déplacer, sans attendre.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="/smartqueue/auth/register.php" class="btn btn-light btn-lg px-4 fw-semibold">
        <i class="bi bi-person-plus me-2"></i>Créer un compte
      </a>
      <a href="/smartqueue/auth/login.php" class="btn btn-outline-light btn-lg px-4">
        <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
      </a>
    </div>
  </div>
</div>

<div class="container">

  <!-- ═══════════ STATISTIQUES GLOBALES (compteurs animés JS) ═══════════ -->
  <div class="row g-3 mb-5 text-center">
    <?php foreach ([
      [$totalTickets,  'bi-ticket-fill',     'Tickets émis',       'primary'],
      [$totalTraites,  'bi-check2-circle',   'Tickets traités',    'success'],
      [$totalServices, 'bi-grid-fill',       'Services actifs',    'info'],
      [array_sum($counts), 'bi-hourglass-split', 'En attente maintenant', 'warning'],
    ] as [$val, $icon, $label, $color]): ?>
    <div class="col-6 col-md-3 sq-fade-in">
      <div class="card stat-card p-3 h-100">
        <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:2rem"></i>
        <div class="stat-number sq-counter mt-1" data-target="<?= (int)$val ?>">0</div>
        <div class="stat-label"><?= $label ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ═══════════ COMMENT ÇA MARCHE ═══════════ -->
  <h2 class="mb-4 fw-bold text-primary text-center sq-fade-in">
    <i class="bi bi-question-circle me-2"></i>Comment ça marche ?
  </h2>
  <div class="row text-center mb-5">
    <?php
    $steps = [
      ['bi-person-check', 'Inscrivez-vous',      'Créez votre compte en 30 secondes.',     'primary'],
      ['bi-grid-1x2',     'Choisissez un service','Sélectionnez le service souhaité.',       'info'],
      ['bi-ticket',       'Prenez un ticket',     'Obtenez votre numéro dans la file.',      'success'],
      ['bi-bell',         'Soyez notifié',        'Recevez une alerte quand c\'est votre tour.','warning'],
    ];
    foreach ($steps as $i => [$icon, $title, $desc, $color]):
    ?>
    <div class="col-md-3 col-6 mb-3 sq-fade-in" style="transition-delay:<?= $i * 100 ?>ms">
      <div class="card p-3 h-100 stat-card">
        <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 d-inline-flex
                    align-items-center justify-content-center mx-auto mb-2"
             style="width:56px;height:56px">
          <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:1.5rem"></i>
        </div>
        <span class="badge bg-<?= $color ?> mb-2">Étape <?= $i+1 ?></span>
        <h6 class="fw-bold"><?= $title ?></h6>
        <small class="text-muted"><?= $desc ?></small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ═══════════ SERVICES PUBLICS ═══════════ -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold text-primary mb-0">
      <i class="bi bi-grid me-2"></i>Services disponibles
    </h2>
    <!-- Recherche en temps réel via JS -->
    <div class="input-group" style="max-width:260px">
      <span class="input-group-text bg-white border-end-0">
        <i class="bi bi-search text-muted"></i>
      </span>
      <input type="search" id="serviceSearch" class="form-control border-start-0"
             placeholder="Rechercher un service…">
    </div>
  </div>

  <div class="row g-3 mb-5" id="serviceGrid">
    <?php foreach ($services as $s): ?>
    <div class="col-md-4 col-sm-6 sq-fade-in service-item"
         data-name="<?= htmlspecialchars(strtolower($s['nom'])) ?>">
      <div class="card h-100 p-3">
        <div class="d-flex justify-content-between align-items-start">
          <h6 class="fw-bold mb-1"><?= htmlspecialchars($s['nom']) ?></h6>
          <span class="badge bg-success">Ouvert</span>
        </div>
        <p class="text-muted small mb-2"><?= htmlspecialchars($s['description']) ?></p>

        <!-- Barre de capacité (JS progressive) -->
        <?php
          $enAttente = $counts[$s['id']] ?? 0;
          $pct = $s['capacite_max'] > 0
              ? min(100, round($enAttente / $s['capacite_max'] * 100))
              : 0;
          $barColor = $pct < 50 ? 'success' : ($pct < 80 ? 'warning' : 'danger');
        ?>
        <div class="progress mb-2" style="height:6px"
             data-bs-toggle="tooltip" title="Taux de remplissage : <?= $pct ?>%">
          <div class="progress-bar bg-<?= $barColor ?> sq-progress-bar"
               data-value="<?= $pct ?>" role="progressbar"
               aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="mt-auto d-flex justify-content-between align-items-center">
          <small class="text-muted">
            <i class="bi bi-people me-1"></i>
            <span data-sq-live-count="<?= $s['id'] ?>"><?= $enAttente ?></span>
            en attente
          </small>
          <small class="text-muted">
            <i class="bi bi-clock me-1"></i>~<?= $s['duree_moy'] ?> min
          </small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div><!-- /#serviceGrid -->

  <!-- Message si aucun résultat de recherche -->
  <p class="text-muted text-center sq-no-service-result" style="display:none">
    <i class="bi bi-emoji-frown me-2"></i>Aucun service ne correspond à votre recherche.
  </p>

</div><!-- /.container -->

<!-- ── Script de recherche de services (sans librairie externe) ── -->
<script>
(function () {
  const input  = document.getElementById('serviceSearch');
  const items  = document.querySelectorAll('.service-item');
  const noRes  = document.querySelector('.sq-no-service-result');

  input?.addEventListener('input', () => {
    const q = input.value.toLowerCase().trim();
    let visible = 0;
    items.forEach(item => {
      const match = item.dataset.name.includes(q);
      item.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (noRes) noRes.style.display = visible ? 'none' : '';
  });
})();
</script>

<?php include './layout/footer.php'; ?>
