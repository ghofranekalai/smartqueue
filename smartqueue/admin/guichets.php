<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

$message = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM guichets WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = '<div class="alert alert-success">Guichet supprimé avec succès.</div>';
    }
}

// Get all guichets with service and agent info
$stmt = $conn->query("
    SELECT g.*, s.nom as service_nom, 
           CONCAT(u.prenom, ' ', u.nom) as agent_nom
    FROM guichets g
    JOIN services s ON g.service_id = s.id
    LEFT JOIN utilisateurs u ON g.agent_id = u.id
    ORDER BY g.numero
");
$guichets = $stmt->fetchAll();
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-door-open text-primary me-2"></i>Gestion des guichets
                </h2>
                <a href="guichet_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nouveau guichet
                </a>
            </div>

            <?= $message ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Numéro</th>
                                    <th>Service</th>
                                    <th>Agent assigné</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guichets as $guichet): ?>
                                    <tr>
                                        <td><?= $guichet['id'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($guichet['numero']) ?></td>
                                        <td><?= htmlspecialchars($guichet['service_nom']) ?></td>
                                        <td><?= htmlspecialchars($guichet['agent_nom'] ?? 'Non assigné') ?></td>
                                        <td>
                                            <span class="badge <?= $guichet['statut'] === 'ouvert' ? 'bg-success' : ($guichet['statut'] === 'pause' ? 'bg-warning' : 'bg-danger') ?>">
                                                <?= ucfirst($guichet['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="guichet_edit.php?id=<?= $guichet['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-sq-confirm="Supprimer ce guichet ?" 
                                                    data-sq-action="?delete=<?= $guichet['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
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