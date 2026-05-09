<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

$message = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = '<div class="alert alert-success">Service supprimé avec succès.</div>';
    } else {
        $message = '<div class="alert alert-danger">Erreur lors de la suppression.</div>';
    }
}

// Toggle active status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $conn->prepare("UPDATE services SET est_actif = NOT est_actif WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: services.php");
    exit();
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY nom")->fetchAll();
?>
<?php include '../layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="sq-sidebar p-3">
                <?php include 'sidebar.php'; ?>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-grid text-primary me-2"></i>Gestion des services
                </h2>
                <a href="service_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nouveau service
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
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Capacité max</th>
                                    <th>Durée moyenne</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?= $service['id'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($service['nom']) ?></td>
                                        <td><?= htmlspecialchars(substr($service['description'], 0, 50)) ?>...</td>
                                        <td><?= $service['capacite_max'] ?></td>
                                        <td><?= $service['duree_moy'] ?> min</td>
                                        <td>
                                            <span class="badge <?= $service['est_actif'] ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $service['est_actif'] ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="service_edit.php?id=<?= $service['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?toggle=<?= $service['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-toggle-<?= $service['est_actif'] ? 'off' : 'on' ?>"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-sq-confirm="Supprimer ce service ?" 
                                                    data-sq-action="?delete=<?= $service['id'] ?>">
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