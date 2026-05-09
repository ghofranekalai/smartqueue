<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$totalTickets = $conn->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$totalServices = $conn->query("SELECT COUNT(*) FROM services WHERE est_actif = 1")->fetchColumn();
$waitingTickets = $conn->query("SELECT COUNT(*) FROM tickets WHERE statut = 'en_attente'")->fetchColumn();

// Tickets by service
$stmt = $conn->query("
    SELECT s.nom, COUNT(t.id) as count
    FROM services s
    LEFT JOIN tickets t ON s.id = t.service_id
    GROUP BY s.id
    LIMIT 5
");
$ticketsByService = $stmt->fetchAll();

// Recent tickets
$stmt = $conn->query("
    SELECT t.*, s.nom as service_nom, u.nom, u.prenom
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    JOIN utilisateurs u ON t.citoyen_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$recentTickets = $stmt->fetchAll();

// Average rating
$avgRating = $conn->query("SELECT AVG(note) FROM evaluations")->fetchColumn();
?>
<?php include '../layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="sq-sidebar p-3">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock fs-1"></i>
                    <h5 class="mt-2">Administration</h5>
                </div>
                <hr class="bg-light">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="services.php">
                        <i class="bi bi-grid me-2"></i>Services
                    </a>
                    <a class="nav-link" href="guichets.php">
                        <i class="bi bi-door-open me-2"></i>Guichets
                    </a>
                    <a class="nav-link" href="utilisateurs.php">
                        <i class="bi bi-people me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link" href="tickets.php">
                        <i class="bi bi-ticket me-2"></i>Tickets
                    </a>
                    <a class="nav-link" href="stats.php">
                        <i class="bi bi-bar-chart me-2"></i>Statistiques
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 py-4">
            <h2 class="fw-bold mb-4">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Tableau de bord
            </h2>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <i class="bi bi-people fs-2 text-primary"></i>
                            <div class="stat-number sq-counter" data-target="<?= $totalUsers ?>">0</div>
                            <div class="stat-label">Utilisateurs</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <i class="bi bi-ticket-fill fs-2 text-success"></i>
                            <div class="stat-number sq-counter" data-target="<?= $totalTickets ?>">0</div>
                            <div class="stat-label">Tickets émis</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <i class="bi bi-grid-fill fs-2 text-info"></i>
                            <div class="stat-number sq-counter" data-target="<?= $totalServices ?>">0</div>
                            <div class="stat-label">Services actifs</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                            <div class="stat-number sq-counter" data-target="<?= $waitingTickets ?>">0</div>
                            <div class="stat-label">En attente</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Tickets by service chart -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-pie-chart me-2"></i>Tickets par service
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="servicesChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Average rating -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-star-fill me-2"></i>Satisfaction globale
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-1 fw-bold text-warning">
                                <?= round($avgRating ?? 0, 1) ?>
                            </div>
                            <div class="mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill fs-3 <?= $i <= ($avgRating ?? 0) ? 'text-warning' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted">Note moyenne sur 5</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent tickets -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-clock-history me-2"></i>Derniers tickets
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>N° Ticket</th>
                                    <th>Service</th>
                                    <th>Citoyen</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ticket['numero']) ?></td>
                                        <td><?= htmlspecialchars($ticket['service_nom']) ?></td>
                                        <td><?= htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                                        <td>
                                            <span class="badge <?= $ticket['statut'] === 'en_attente' ? 'badge-en_attente' : 'badge-termine' ?>">
                                                <?= $ticket['statut'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Services chart
    const ctx = document.getElementById('servicesChart').getContext('2d');
    const servicesData = <?= json_encode($ticketsByService) ?>;
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: servicesData.map(s => s.nom),
            datasets: [{
                data: servicesData.map(s => s.count),
                backgroundColor: ['#1E3A5F', '#2E86AB', '#198754', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

<?php include '../layout/footer.php'; ?>