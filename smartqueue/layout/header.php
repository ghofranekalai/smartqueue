<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartQueue – Gestion de File d'Attente</title>

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- SmartQueue styles -->
  <link rel="stylesheet" href="/smartqueue/styles/style.css">
</head>
<body>

<?php
require_once __DIR__ . '/../config/session.php';
$user = currentUser();
$role = $user['role'] ?? '';

// Determine dashboard link based on role
$dashLink = '/smartqueue/index.php';
if ($role === 'admin')   $dashLink = '/smartqueue/admin/dashboard.php';
if ($role === 'agent')   $dashLink = '/smartqueue/guichet/dashboard.php';
if ($role === 'citoyen') $dashLink = '/smartqueue/citoyen/dashboard.php';

// Unread notifications count (for badge)
$notifCount = 0;
if (isLoggedIn() && isset($conn)) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0");
    $stmt->execute([$user['id']]);
    $notifCount = (int)$stmt->fetchColumn();
}
?>

<!-- ── NAVBAR ────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark sq-navbar sticky-top">
  <div class="container">

    <!-- Brand -->
    <a class="navbar-brand fw-bold" href="<?= $dashLink ?>">
      <i class="bi bi-ticket-perforated-fill me-2"></i>SmartQueue
    </a>

    <!-- Toggler (mobile) -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-center gap-2">

        <!-- ── VISITEUR (non connecté) ─────────────────── -->
        <?php if (!isLoggedIn()): ?>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/index.php">
              <i class="bi bi-house me-1"></i>Accueil
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm" href="/smartqueue/auth/login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-light btn-sm text-primary" href="/smartqueue/auth/register.php">
              <i class="bi bi-person-plus me-1"></i>Inscription
            </a>
          </li>

        <!-- ── ADMIN ────────────────────────────────────── -->
        <?php elseif ($role === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/admin/dashboard.php"
               data-bs-toggle="tooltip" title="Tableau de bord">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/admin/services.php">
              <i class="bi bi-grid me-1"></i>Services
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/admin/guichets.php">
              <i class="bi bi-door-open me-1"></i>Guichets
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/admin/utilisateurs.php">
              <i class="bi bi-people me-1"></i>Utilisateurs
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/admin/tickets.php">
              <i class="bi bi-ticket me-1"></i>Tickets
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/admin/stats.php">
              <i class="bi bi-bar-chart me-1"></i>Statistiques
            </a>
          </li>

        <!-- ── AGENT ─────────────────────────────────────── -->
        <?php elseif ($role === 'agent'): ?>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/guichet/dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i>Guichet
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/guichet/tickets.php">
              <i class="bi bi-list-check me-1"></i>Tickets
            </a>
          </li>

        <!-- ── CITOYEN ───────────────────────────────────── -->
        <?php elseif ($role === 'citoyen'): ?>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/citoyen/dashboard.php">
              <i class="bi bi-house me-1"></i>Accueil
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/citoyen/services.php">
              <i class="bi bi-grid me-1"></i>Services
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/smartqueue/citoyen/mes_tickets.php">
              <i class="bi bi-ticket me-1"></i>Mes Tickets
            </a>
          </li>
        <?php endif; ?>

        <!-- ── NOTIFICATIONS (si connecté) ───────────────── -->
        <?php if (isLoggedIn()): ?>
          <li class="nav-item">
            <a class="nav-link position-relative" href="/smartqueue/notifications.php"
               data-bs-toggle="tooltip" title="Notifications">
              <i class="bi bi-bell-fill"></i>
              <?php if ($notifCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge
                             rounded-pill bg-danger" style="font-size:.65rem">
                  <?= $notifCount ?>
                  <span class="visually-hidden">notifications non lues</span>
                </span>
              <?php endif; ?>
            </a>
          </li>

          <!-- Dropdown profil -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-1"
               href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i>
              <?= htmlspecialchars($user['prenom']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
              <li>
                <h6 class="dropdown-header text-muted">
                  <?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?>
                </h6>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="/smartqueue/auth/profile.php">
                  <i class="bi bi-person me-2 text-primary"></i>Mon profil
                </a>
              </li>
              <li>
                <a class="dropdown-item text-danger" href="/smartqueue/auth/logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                </a>
              </li>
            </ul>
          </li>
        <?php endif; ?>

      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container -->
</nav>

<!-- ── FLASH MESSAGES (session PHP → alert Bootstrap) ─────── -->
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="container mt-3">
    <?php foreach ($_SESSION['flash'] as $type => $msg): ?>
      <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
    <?php $_SESSION['flash'] = []; ?>
  </div>
<?php endif; ?>

<main class="py-4">
