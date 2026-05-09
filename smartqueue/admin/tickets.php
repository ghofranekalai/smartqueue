<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT t.*, s.nom as service_nom, 
           CONCAT(u.prenom, ' ', u.nom) as citoyen_nom,
           g.numero as guichet_num
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    JOIN utilisateurs u ON t.citoyen_id = u.id
    LEFT JOIN guichets g ON t.guichet_id = g.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (t.numero LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $query .= " AND t.statut = ?";
    $params[] = $status;
}

$query .= " ORDER BY t.created_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
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
                <i class="bi bi-ticket-fill text-primary me-2"></i>Gestion des tickets
            </h2>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Rechercher par numéro ou citoyen..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?= $status === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="appele" <?= $status === 'appele' ? 'selected' : '' ?>>Appelé</option>
                                <option value="en_service" <?= $status === 'en_service' ? 'selected' : '' ?>>En service</option>
                                <option value="termine" <?= $status === 'termine' ? 'selected' : '' ?>>Terminé</option>
                                <option value="annule" <?= $status === 'annule' ? 'selected' : '' ?>>Annulé</option>
                                <option value="no_show" <?= $status === 'no_show' ? 'selected' : '' ?>>Absent</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tickets table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>N° Ticket</th>
                                    <th>Citoyen</th>
                                    <th>Service</th>
                                    <th>Guichet</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Priorité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ticket['numero']) ?></td>
                                        <td><?= htmlspecialchars($ticket['citoyen_nom']) ?></td>
                                        <td><?= htmlspecialchars($ticket['service_nom']) ?></td>
                                        <td><?= $ticket['guichet_num'] ?? '-' ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                                        <td>
                                            <span class="badge <?= $ticket['statut'] === 'en_attente' ? 'badge-en_attente' : 
                                                ($ticket['statut'] === 'appele' ? 'badge-appele' : 
                                                ($ticket['statut'] === 'en_service' ? 'badge-en_service' : 
                                                ($ticket['statut'] === 'termine' ? 'badge-termine' : 'badge-annule'))) ?>">
                                                <?= $ticket['statut'] === 'en_attente' ? 'En attente' : 
                                                   ($ticket['statut'] === 'appele' ? 'Appelé' : 
                                                   ($ticket['statut'] === 'en_service' ? 'En service' : 
                                                   ($ticket['statut'] === 'termine' ? 'Terminé' : 'Annulé'))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($ticket['priorite'] === 'urgent'): ?>
                                                <span class="badge bg-danger">Urgent</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Normal</span>
                                            <?php endif; ?>
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

<?php include '../layout/footer.php'; ?>