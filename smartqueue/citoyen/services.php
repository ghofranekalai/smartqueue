<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('citoyen');

$user = currentUser();

// Get all active services
$stmt = $conn->query("SELECT * FROM services WHERE est_actif = 1 ORDER BY nom");
$services = $stmt->fetchAll();

// Get waiting count for each service
$waitingCounts = [];
$res = $conn->query("
    SELECT service_id, COUNT(*) as count 
    FROM tickets 
    WHERE statut = 'en_attente' 
    GROUP BY service_id
");
foreach ($res->fetchAll() as $row) {
    $waitingCounts[$row['service_id']] = $row['count'];
}
?>
<?php include '../layout/header.php'; ?>

<div class="container">
    <div class="mb-4">
        <h2 class="fw-bold">
            <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Services disponibles
        </h2>
        <p class="text-muted">Choisissez un service pour prendre un ticket en ligne</p>
    </div>

    <div class="row g-3">
        <?php foreach ($services as $service): 
            $waiting = $waitingCounts[$service['id']] ?? 0;
            $capacityPercent = $service['capacite_max'] > 0 ? min(100, round($waiting / $service['capacite_max'] * 100)) : 0;
            $barColor = $capacityPercent < 50 ? 'success' : ($capacityPercent < 80 ? 'warning' : 'danger');
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($service['nom']) ?></h5>
                            <span class="badge bg-success">Ouvert</span>
                        </div>
                        <p class="card-text text-muted small mb-3">
                            <?= htmlspecialchars($service['description']) ?>
                        </p>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>File d'attente</span>
                                <span><?= $waiting ?> personne(s) en attente</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-<?= $barColor ?>" style="width: <?= $capacityPercent ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span><i class="bi bi-clock me-1"></i>~<?= $service['duree_moy'] ?> min</span>
                            <span><i class="bi bi-people me-1"></i>Capacité max: <?= $service['capacite_max'] ?></span>
                        </div>
                        
                        <a href="prendre_ticket.php?service_id=<?= $service['id'] ?>" class="btn btn-primary w-100">
                            <i class="bi bi-ticket-perforated me-2"></i>Prendre un ticket
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../layout/footer.php'; ?>