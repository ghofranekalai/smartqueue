<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

$id = $_GET['id'] ?? 0;
$isEdit = $id > 0;
$error = '';
$success = '';

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    if (!$service) {
        header("Location: services.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacite_max = intval($_POST['capacite_max'] ?? 50);
    $duree_moy = intval($_POST['duree_moy'] ?? 10);
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    
    if (empty($nom)) {
        $error = "Le nom du service est requis.";
    } elseif ($capacite_max <= 0) {
        $error = "La capacité maximale doit être positive.";
    } elseif ($duree_moy <= 0) {
        $error = "La durée moyenne doit être positive.";
    } else {
        if ($isEdit) {
            $stmt = $conn->prepare("
                UPDATE services 
                SET nom = ?, description = ?, capacite_max = ?, duree_moy = ?, est_actif = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $description, $capacite_max, $duree_moy, $est_actif, $id]);
            $success = "Service mis à jour avec succès.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO services (nom, description, capacite_max, duree_moy, est_actif)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nom, $description, $capacite_max, $duree_moy, $est_actif]);
            $success = "Service créé avec succès.";
            header("Location: services.php");
            exit();
        }
    }
}
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="fw-bold mb-0">
                        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-primary me-2"></i>
                        <?= $isEdit ? 'Modifier le service' : 'Nouveau service' ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nom" class="form-label fw-bold">Nom du service *</label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?= htmlspecialchars($service['nom'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="capacite_max" class="form-label fw-bold">Capacité maximale</label>
                                <input type="number" class="form-control" id="capacite_max" name="capacite_max" 
                                       value="<?= $service['capacite_max'] ?? 50 ?>" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="duree_moy" class="form-label fw-bold">Durée moyenne (minutes)</label>
                                <input type="number" class="form-control" id="duree_moy" name="duree_moy" 
                                       value="<?= $service['duree_moy'] ?? 10 ?>" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="est_actif" name="est_actif" 
                                   <?= isset($service['est_actif']) && $service['est_actif'] ? 'checked' : 'checked' ?>>
                            <label class="form-check-label" for="est_actif">Service actif</label>
                        </div>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i><?= $isEdit ? 'Mettre à jour' : 'Créer' ?>
                        </button>
                        <a href="services.php" class="btn btn-secondary px-4">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>