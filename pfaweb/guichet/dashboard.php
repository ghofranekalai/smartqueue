<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('agent');

$user = currentUser();

// Get agent's guichet
$stmt = $conn->prepare("
    SELECT g.*, s.nom as service_nom, s.duree_moy
    FROM guichets g
    JOIN services s ON g.service_id = s.id
    WHERE g.agent_id = ?
");
$stmt->execute([$user['id']]);
$guichet = $stmt->fetch();

if (!$guichet) {
    // No guichet assigned
    ?>
    <?php include '../layout/header.php'; ?>
    <div class="container">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Aucun guichet ne vous a été assigné. Veuillez contacter l'administrateur.
        </div>
    </div>
    <?php include '../layout/footer.php'; ?>
    <?php
    exit();
}

// Get waiting tickets
$stmt = $conn->prepare("
    SELECT t.*, u.nom, u.prenom, u.telephone
    FROM tickets t
    JOIN utilisateurs u ON t.citoyen_id = u.id
    WHERE t.service_id = ? AND t.statut = 'en_attente'
    ORDER BY t.priorite = 'urgent' DESC, t.created_at ASC
");
$stmt->execute([$guichet['service_id']]);
$waitingTickets = $stmt->fetchAll();

// Get current ticket being served
$stmt = $conn->prepare("
    SELECT t.*, u.nom, u.prenom
    FROM tickets t
    JOIN utilisateurs u ON t.citoyen_id = u.id
    WHERE t.guichet_id = ? AND t.statut IN ('appele', 'en_service')
    ORDER BY t.appele_at DESC
    LIMIT 1
");
$stmt->execute([$guichet['id']]);
$currentTicket = $stmt->fetch();

// Handle guichet status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_status') {
        $new_status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE guichets SET statut = ? WHERE id = ?");
        $stmt->execute([$new_status, $guichet['id']]);
        $guichet['statut'] = $new_status;
        $_SESSION['flash']['success'] = "Statut du guichet mis à jour.";
        header("Refresh:0");
        exit();
    }
}
?>
<?php include '../layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="sq-sidebar p-3">
                <div class="text-center mb-4">
                    <i class="bi bi-door-open fs-1"></i>
                    <h5 class="mt-2">Guichet <?= htmlspecialchars($guichet['numero']) ?></h5>
                    <span class="badge <?= $guichet['statut'] === 'ouvert' ? 'bg-success' : ($guichet['statut'] === 'pause' ? 'bg-warning' : 'bg-danger') ?>">
                        <?= ucfirst($guichet['statut']) ?>
                    </span>
                </div>
                <hr class="bg-light">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                    </a>
                    <a class="nav-link" href="tickets.php">
                        <i class="bi bi-list-check me-2"></i>Tickets
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-speedometer2 text-primary me-2"></i>Tableau de bord - Guichet
                </h2>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="change_status">
                    <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                        <option value="ouvert" <?= $guichet['statut'] === 'ouvert' ? 'selected' : '' ?>>🟢 Ouvert</option>
                        <option value="pause" <?= $guichet['statut'] === 'pause' ? 'selected' : '' ?>>🟡 Pause</option>
                        <option value="ferme" <?= $guichet['statut'] === 'ferme' ? 'selected' : '' ?>>🔴 Fermé</option>
                    </select>
                </form>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 fw-bold text-primary">
                                <?= count($waitingTickets) ?>
                            </div>
                            <p class="text-muted mb-0">En attente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 fw-bold text-success">
                                <?= $guichet['duree_moy'] ?> min
                            </div>
                            <p class="text-muted mb-0">Durée moyenne</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Ticket -->
            <?php if ($currentTicket): ?>
                <div class="card border-0 shadow-sm mb-4 queue-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-bell-fill me-2"></i>Ticket en cours
                        </h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <div class="ticket-number display-1 fw-bold mb-2">
                            <?= htmlspecialchars($currentTicket['numero']) ?>
                        </div>
                        <p class="mb-2">
                            <strong><?= htmlspecialchars($currentTicket['prenom'] . ' ' . $currentTicket['nom']) ?></strong>
                        </p>
                        <div class="mb-3">
                            <span class="badge <?= $currentTicket['statut'] === 'appele' ? 'badge-appele' : 'badge-en_service' ?> fs-6">
                                <?= $currentTicket['statut'] === 'appele' ? 'Appelé' : 'En service' ?>
                            </span>
                            <?php if ($currentTicket['appele_at']): ?>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    <span class="sq-live-timer" data-since="<?= $currentTicket['appele_at'] ?>">0:00</span>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="btn-group">
                            <a href="appel_ticket.php?action=complete&id=<?= $currentTicket['id'] ?>" 
                               class="btn btn-success"
                               onclick="return confirm('Terminer ce ticket ?')">
                                <i class="bi bi-check-circle me-2"></i>Terminer
                            </a>
                            <a href="appel_ticket.php?action=no_show&id=<?= $currentTicket['id'] ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Marquer comme absent ?')">
                                <i class="bi bi-person-x me-2"></i>Absent
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Next Ticket -->
            <?php if ($guichet['statut'] === 'ouvert' && !$currentTicket && !empty($waitingTickets)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-arrow-right-circle me-2"></i>Prochain ticket
                        </h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <div class="ticket-number display-1 fw-bold mb-3">
                            <?= htmlspecialchars($waitingTickets[0]['numero']) ?>
                        </div>
                        <p class="mb-3">
                            <strong><?= htmlspecialchars($waitingTickets[0]['prenom'] . ' ' . $waitingTickets[0]['nom']) ?></strong>
                        </p>
                        <a href="appel_ticket.php?action=call&id=<?= $waitingTickets[0]['id'] ?>" 
                           class="btn btn-success btn-lg px-5">
                            <i class="bi bi-megaphone me-2"></i>Appeler le ticket
                        </a>
                    </div>
                </div>
            <?php elseif ($guichet['statut'] === 'ouvert' && !$currentTicket && empty($waitingTickets)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Aucun ticket en attente. La file est vide.
                </div>
            <?php elseif ($guichet['statut'] !== 'ouvert'): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Votre guichet est actuellement <strong><?= $guichet['statut'] ?></strong>. Ouvrez-le pour commencer à servir.
                </div>
            <?php endif; ?>

            <!-- Waiting List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-people me-2"></i>File d'attente
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($waitingTickets)): ?>
                        <div class="list-group-item text-center text-muted">
                            Aucun ticket en attente
                        </div>
                    <?php else: ?>
                        <?php foreach ($waitingTickets as $index => $ticket): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold fs-5"><?= htmlspecialchars($ticket['numero']) ?></span>
                                        <?php if ($ticket['priorite'] === 'urgent'): ?>
                                            <span class="badge bg-danger ms-2">Urgent</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary">Position <?= $index + 1 ?></div>
                                        <small class="text-muted">
                                            <?= date('H:i', strtotime($ticket['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>