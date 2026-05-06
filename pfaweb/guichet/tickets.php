<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('agent');

$user = currentUser();

// Get agent's guichet
$stmt = $conn->prepare("
    SELECT g.*, s.nom as service_nom
    FROM guichets g
    JOIN services s ON g.service_id = s.id
    WHERE g.agent_id = ?
");
$stmt->execute([$user['id']]);
$guichet = $stmt->fetch();

if (!$guichet) {
    header("Location: dashboard.php");
    exit();
}

// Get all tickets for this service with pagination
$page = $_GET['page'] ?? 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("
    SELECT t.*, u.nom, u.prenom, u.telephone,
           g.numero as guichet_num
    FROM tickets t
    JOIN utilisateurs u ON t.citoyen_id = u.id
    LEFT JOIN guichets g ON t.guichet_id = g.id
    WHERE t.service_id = ?
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$guichet['service_id'], $per_page, $offset]);
$tickets = $stmt->fetchAll();

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE service_id = ?");
$stmt->execute([$guichet['service_id']]);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);
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
                    <span class="badge bg-secondary"><?= htmlspecialchars($guichet['service_nom']) ?></span>
                </div>
                <hr class="bg-light">
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                    </a>
                    <a class="nav-link active" href="#">
                        <i class="bi bi-list-check me-2"></i>Tickets
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 py-4">
            <h2 class="fw-bold mb-4">
                <i class="bi bi-list-check text-primary me-2"></i>Historique des tickets
            </h2>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>N° Ticket</th>
                                    <th>Citoyen</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Priorité</th>
                                    <th>Guichet</th>
                                    <th>Durée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ticket['numero']) ?></td>
                                        <td><?= htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                                        <td>
                                            <span class="badge <?= 
                                                $ticket['statut'] === 'en_attente' ? 'badge-en_attente' : 
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
                                        <td><?= $ticket['guichet_num'] ?? '-' ?></td>
                                        <td>
                                            <?php if ($ticket['termine_at'] && $ticket['appele_at']): ?>
                                                <?php 
                                                    $diff = strtotime($ticket['termine_at']) - strtotime($ticket['appele_at']);
                                                    echo floor($diff / 60) . ' min';
                                                ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>