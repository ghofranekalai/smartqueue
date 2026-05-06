<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

$message = '';

// Handle role change
if (isset($_GET['role_change'])) {
    $id = $_GET['role_change'];
    $new_role = $_GET['role'] ?? '';
    if (in_array($new_role, ['citoyen', 'agent', 'admin'])) {
        $stmt = $conn->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $id]);
        $message = '<div class="alert alert-success">Rôle utilisateur modifié.</div>';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id = ? AND role != 'admin'");
    if ($stmt->execute([$id])) {
        $message = '<div class="alert alert-success">Utilisateur supprimé.</div>';
    } else {
        $message = '<div class="alert alert-danger">Impossible de supprimer un admin.</div>';
    }
}

// Get all users
$users = $conn->query("SELECT * FROM utilisateurs ORDER BY created_at DESC")->fetchAll();
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
                    <i class="bi bi-people text-primary me-2"></i>Gestion des utilisateurs
                </h2>
                <a href="../auth/register.php" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>Ajouter un agent
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
                                    <th>Nom complet</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Rôle</th>
                                    <th>Date inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['telephone'] ?? '-') ?></td>
                                        <td>
                                            <select class="form-select form-select-sm" 
                                                    onchange="window.location.href='?role_change=<?= $user['id'] ?>&role='+this.value">
                                                <option value="citoyen" <?= $user['role'] === 'citoyen' ? 'selected' : '' ?>>Citoyen</option>
                                                <option value="agent" <?= $user['role'] === 'agent' ? 'selected' : '' ?>>Agent</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        data-sq-confirm="Supprimer cet utilisateur ?" 
                                                        data-sq-action="?delete=<?= $user['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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