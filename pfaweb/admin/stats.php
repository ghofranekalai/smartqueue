<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

// Daily tickets for last 7 days
$stmt = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM tickets
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$dailyStats = $stmt->fetchAll();

// Tickets by status
$stmt = $conn->query("
    SELECT statut, COUNT(*) as count
    FROM tickets
    GROUP BY statut
");
$statusStats = $stmt->fetchAll();

// Top services
$stmt = $conn->query("
    SELECT s.nom, COUNT(t.id) as count
    FROM services s
    LEFT JOIN tickets t ON s.id = t.service_id
    GROUP BY s.id
    ORDER BY count DESC
    LIMIT 5
");
$topServices = $stmt->fetchAll();

// Average duration by service
$stmt = $conn->query("
    SELECT s.nom, 
           AVG(TIMESTAMPDIFF(MINUTE, t.appele_at, t.termine_at)) as avg_duration
    FROM services s
    JOIN tickets t ON s.id = t.service_id
    WHERE t.appele_at IS NOT NULL AND t.termine_at IS NOT NULL
    GROUP BY s.id
");
$avgDurations = $stmt->fetchAll();
?>
<?php include '../layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2">
            <div class="sq-sidebar p-3">
                <?php include 'sidebar.php'; ?>
            </div>
        </div>
        <div class="col-md-9 col-lg-10 py-4">
            <h2 class="fw-bold mb-4">
                <i class="bi bi-bar-chart-steps text-primary me-2"></i>Statistiques avancées
            </h2>

            <div class="row">
                <!-- Daily tickets chart -->
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-calendar-week me-2"></i>Tickets par jour (7 derniers jours)
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-pie-chart me-2"></i>Répartition par statut
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-trophy me-2"></i>Top 5 services les plus demandés
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="topServicesChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average duration table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-hourglass-split me-2"></i>Temps de traitement moyen par service
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th>Temps moyen (minutes)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avgDurations as $duration): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($duration['nom']) ?></td>
                                        <td><?= round($duration['avg_duration'], 1) ?> min</td>
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
    // Daily chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyData = <?= json_encode($dailyStats) ?>;
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Tickets',
                data: dailyData.map(d => d.count),
                borderColor: '#1E3A5F',
                backgroundColor: 'rgba(30,58,95,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // Status chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = <?= json_encode($statusStats) ?>;
    const statusLabels = {
        'en_attente': 'En attente',
        'appele': 'Appelé',
        'en_service': 'En service',
        'termine': 'Terminé',
        'annule': 'Annulé',
        'no_show': 'Absent'
    };
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(s => statusLabels[s.statut] || s.statut),
            datasets: [{
                data: statusData.map(s => s.count),
                backgroundColor: ['#ffc107', '#2E86AB', '#198754', '#6c757d', '#dc3545', '#343a40']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Top services chart
    const topCtx = document.getElementById('topServicesChart').getContext('2d');
    const topData = <?= json_encode($topServices) ?>;
    new Chart(topCtx, {
        type: 'bar',
        data: {
            labels: topData.map(s => s.nom),
            datasets: [{
                label: 'Nombre de tickets',
                data: topData.map(s => s.count),
                backgroundColor: '#2E86AB'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

<?php include '../layout/footer.php'; ?>