<div class="text-center mb-4">
    <i class="bi bi-shield-lock fs-1"></i>
    <h5 class="mt-2">Administration</h5>
</div>
<hr class="bg-light">
<nav class="nav flex-column">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
       href="dashboard.php">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'services.php' || basename($_SERVER['PHP_SELF']) == 'service_edit.php' ? 'active' : '' ?>" 
       href="services.php">
        <i class="bi bi-grid me-2"></i>Services
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'guichets.php' || basename($_SERVER['PHP_SELF']) == 'guichet_edit.php' ? 'active' : '' ?>" 
       href="guichets.php">
        <i class="bi bi-door-open me-2"></i>Guichets
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'utilisateurs.php' ? 'active' : '' ?>" 
       href="utilisateurs.php">
        <i class="bi bi-people me-2"></i>Utilisateurs
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : '' ?>" 
       href="tickets.php">
        <i class="bi bi-ticket me-2"></i>Tickets
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'stats.php' ? 'active' : '' ?>" 
       href="stats.php">
        <i class="bi bi-bar-chart me-2"></i>Statistiques
    </a>
</nav>