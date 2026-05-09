<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('citoyen');

$user = currentUser();

// Get all tickets with pagination
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("
    SELECT t.*, s.nom as service_nom, s.duree_moy, g.numero as guichet_num,
           e.note, e.commentaire,
           (SELECT COUNT(*) FROM tickets t2 
            WHERE t2.service_id = t.service_id 
            AND t2.statut = 'en_attente' 
            AND t2.created_at < t.created_at) + 1 as position
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    LEFT JOIN guichets g ON t.guichet_id = g.id
    LEFT JOIN evaluations e ON t.id = e.ticket_id
    WHERE t.citoyen_id = ?
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
");

// FIX: Use bindValue with PDO::PARAM_INT instead of execute([...])
$stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();

$tickets = $stmt->fetchAll();

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE citoyen_id = ?");
$stmt->execute([$user['id']]);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);
?>
<?php include '../layout/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="bi bi-ticket-fill text-primary me-2"></i>Mes tickets
        </h2>
        <a href="services.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nouveau ticket
        </a>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="card bg-light border-0 text-center p-5">
            <i class="bi bi-ticket-slash fs-1 text-muted mb-3"></i>
            <p class="mb-0">Vous n'avez encore aucun ticket.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>N° Ticket</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Priorité</th>
                        <th>Position</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td class="fw-bold">#<?= htmlspecialchars($ticket['numero']) ?></td>
                            <td><?= htmlspecialchars($ticket['service_nom']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= 
                                    $ticket['statut'] === 'en_attente' ? 'badge-en_attente' : 
                                    ($ticket['statut'] === 'appele' ? 'badge-appele' : 
                                    ($ticket['statut'] === 'en_service' ? 'badge-en_service' : 
                                    ($ticket['statut'] === 'termine' ? 'badge-termine' : 
                                    'badge-annule'))) ?>">
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
                            <td>
                                <?php if ($ticket['statut'] === 'en_attente' && $ticket['position']): ?>
                                    <?= $ticket['position'] ?>
                                <?php elseif ($ticket['statut'] === 'appele' || $ticket['statut'] === 'en_service'): ?>
                                    <span class="text-success fw-bold">À votre tour</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ticket['note']): ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star-fill text-warning <?= $i <= $ticket['note'] ? '' : 'opacity-25' ?>"></i>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ticket['statut'] === 'termine' && !$ticket['note']): ?>
                                    <a href="evaluer.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-star me-1"></i>Évaluer
                                    </a>
                                <?php elseif ($ticket['statut'] === 'en_attente'): ?>
                                    <button class="btn btn-sm btn-outline-danger" disabled>
                                        <i class="bi bi-x-circle me-1"></i>Annuler
                                    </button>
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
    <?php endif; ?>
</div>

<?php include '../layout/footer.php'; ?>