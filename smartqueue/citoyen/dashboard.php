<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('citoyen');

$user = currentUser();

// Get active tickets (en_attente, appele, en_service)
$stmt = $conn->prepare("
    SELECT t.*, s.nom as service_nom, s.duree_moy,
           (SELECT COUNT(*) FROM tickets t2 
            WHERE t2.service_id = t.service_id 
            AND t2.statut = 'en_attente' 
            AND t2.created_at < t.created_at) + 1 as position
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    WHERE t.citoyen_id = ? AND t.statut IN ('en_attente', 'appele', 'en_service')
    ORDER BY t.created_at DESC
");
$stmt->execute([$user['id']]);
$activeTickets = $stmt->fetchAll();

// Get completed tickets
$stmt = $conn->prepare("
    SELECT t.*, s.nom as service_nom, e.note, e.commentaire
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    LEFT JOIN evaluations e ON t.id = e.ticket_id
    WHERE t.citoyen_id = ? AND t.statut IN ('termine', 'annule', 'no_show')
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt->execute([$user['id']]);
$completedTickets = $stmt->fetchAll();

// Get available services
$stmt = $conn->query("SELECT * FROM services WHERE est_actif = 1 ORDER BY nom");
$services = $stmt->fetchAll();
?>
<?php include '../layout/header.php'; ?>

<div class="container">
    <!-- Welcome Banner -->
    <div class="sq-hero mb-4" style="padding: 2rem 0;">
        <div class="container text-center">
            <h2 class="fw-bold">Bienvenue, <?= htmlspecialchars($user['prenom']) ?> !</h2>
            <p class="lead mb-0">Gérez vos tickets et suivez votre position dans la file d'attente.</p>
        </div>
    </div>

    <!-- Active Tickets -->
    <div class="mb-5">
        <h3 class="fw-bold mb-3">
            <i class="bi bi-clock-history text-primary me-2"></i>Tickets actifs
        </h3>
        
        <?php if (empty($activeTickets)): ?>
            <div class="card bg-light border-0 text-center p-5">
                <i class="bi bi-ticket-slash fs-1 text-muted mb-3"></i>
                <p class="mb-3">Vous n'avez aucun ticket actif.</p>
                <div>
                    <a href="services.php" class="btn btn-primary">
                        <i class="bi bi-ticket-perforated me-2"></i>Prendre un ticket
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($activeTickets as $ticket): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge <?= $ticket['priorite'] === 'urgent' ? 'bg-danger' : 'bg-secondary' ?> mb-2">
                                            <?= ucfirst($ticket['priorite']) ?>
                                        </span>
                                        <h5 class="card-title fw-bold">Ticket #<?= htmlspecialchars($ticket['numero']) ?></h5>
                                    </div>
                                    <span class="badge <?= $ticket['statut'] === 'en_attente' ? 'badge-en_attente' : ($ticket['statut'] === 'appele' ? 'badge-appele' : 'badge-en_service') ?>">
                                        <?= $ticket['statut'] === 'en_attente' ? 'En attente' : ($ticket['statut'] === 'appele' ? 'Appelé' : 'En service') ?>
                                    </span>
                                </div>
                                <p class="card-text">
                                    <strong>Service :</strong> <?= htmlspecialchars($ticket['service_nom']) ?><br>
                                    <strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                                </p>
                                
                                <?php if ($ticket['statut'] === 'en_attente'): ?>
                                    <div class="mt-3">
                                        <div class="queue-position text-center mb-2">
                                            <?= $ticket['position'] ?>
                                        </div>
                                        <div class="progress mb-2" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?= min(100, ($ticket['position'] / 20) * 100) ?>%"></div>
                                        </div>
                                        <small class="text-muted">Position dans la file</small>
                                    </div>
                                    
                                    <?php if ($ticket['duree_moy'] && $ticket['position']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-hourglass-split me-1"></i>
                                                Temps estimé : ~<?= $ticket['duree_moy'] * $ticket['position'] ?> minutes
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($ticket['statut'] === 'appele' || $ticket['statut'] === 'en_service'): ?>
                                    <div class="alert alert-warning mt-2 mb-0">
                                        <i class="bi bi-bell-fill me-2"></i>
                                        <strong>Votre tour est arrivé !</strong><br>
                                        Rendez-vous au guichet indiqué.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-5">
        <div class="col-md-6">
            <a href="services.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100 border-0 shadow-sm hover-shadow">
                    <i class="bi bi-ticket-perforated fs-1 text-primary mb-2"></i>
                    <h5 class="fw-bold">Prendre un ticket</h5>
                    <p class="text-muted mb-0">Choisissez un service et obtenez votre numéro</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="mes_tickets.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100 border-0 shadow-sm hover-shadow">
                    <i class="bi bi-list-ul fs-1 text-primary mb-2"></i>
                    <h5 class="fw-bold">Mes tickets</h5>
                    <p class="text-muted mb-0">Historique complet de vos tickets</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Tickets -->
    <?php if (!empty($completedTickets)): ?>
    <div class="mb-4">
        <h3 class="fw-bold mb-3">
            <i class="bi bi-hourglass-split text-primary me-2"></i>Tickets récents
        </h3>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedTickets as $ticket): ?>
                        <tr>
                            <td class="fw-bold">#<?= htmlspecialchars($ticket['numero']) ?></td>
                            <td><?= htmlspecialchars($ticket['service_nom']) ?></td>
                            <td><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $ticket['statut'] === 'termine' ? 'badge-termine' : ($ticket['statut'] === 'annule' ? 'badge-annule' : 'badge-no_show') ?>">
                                    <?= $ticket['statut'] === 'termine' ? 'Terminé' : ($ticket['statut'] === 'annule' ? 'Annulé' : 'Absent') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($ticket['note']): ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star-fill text-warning <?= $i <= $ticket['note'] ? '' : 'opacity-25' ?>"></i>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    <span class="text-muted">Non évalué</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ticket['statut'] === 'termine' && !$ticket['note']): ?>
                                    <a href="evaluer.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-star me-1"></i>Évaluer
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow:hover {
    transform: translateY(-5px);
    transition: all 0.3s ease;
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important;
}
.queue-position {
    font-size: 2rem;
    font-weight: 800;
    color: var(--sq-primary);
}
</style>

<?php include '../layout/footer.php'; ?>