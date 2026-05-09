<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('admin');

$id = $_GET['id'] ?? 0;
$isEdit = $id > 0;
$error = '';
$success = '';

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM guichets WHERE id = ?");
    $stmt->execute([$id]);
    $guichet = $stmt->fetch();
    if (!$guichet) {
        header("Location: guichets.php");
        exit();
    }
}

// Get services for select
$services = $conn->query("SELECT id, nom FROM services WHERE est_actif = 1 ORDER BY nom")->fetchAll();

// Get agents for select
$agents = $conn->query("SELECT id, prenom, nom FROM utilisateurs WHERE role = 'agent' ORDER BY prenom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $agent_id = !empty($_POST['agent_id']) ? intval($_POST['agent_id']) : null;
    $statut = $_POST['statut'] ?? 'ferme';
    
    if (empty($numero)) {
        $error = "Le numéro du guichet est requis.";
    } elseif ($service_id <= 0) {
        $error = "Veuillez sélectionner un service.";
    } else {
        if ($isEdit) {
            $stmt = $conn->prepare("
                UPDATE guichets 
                SET numero = ?, service_id = ?, agent_id = ?, statut = ?
                WHERE id = ?
            ");
            $stmt->execute([$numero, $service_id, $agent_id, $statut, $id]);
            $success = "Guichet mis à jour avec succès.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO guichets (numero, service_id, agent_id, statut)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$numero, $service_id, $agent_id, $statut]);
            $success = "Guichet créé avec succès.";
            header("Location: guichets.php");
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
                        <?= $isEdit ? 'Modifier le guichet' : 'Nouveau guichet' ?>
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
                            <label for="numero" class="form-label fw-bold">Numéro du guichet *</label>
                            <input type="text" class="form-control" id="numero" name="numero" 
                                   value="<?= htmlspecialchars($guichet['numero'] ?? '') ?>" required>
                            <small class="text-muted">Ex: G-01, G-02, etc.</small>
                        </div>
                        <div class="mb-3">
                            <label for="service_id" class="form-label fw-bold">Service *</label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="">Sélectionner un service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= $service['id'] ?>" 
                                        <?= isset($guichet['service_id']) && $guichet['service_id'] == $service['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($service['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="agent_id" class="form-label fw-bold">Agent assigné</label>
                            <select class="form-select" id="agent_id" name="agent_id">
                                <option value="">Non assigné</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?= $agent['id'] ?>" 
                                        <?= isset($guichet['agent_id']) && $guichet['agent_id'] == $agent['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statut" class="form-label fw-bold">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="ouvert" <?= isset($guichet['statut']) && $guichet['statut'] === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                                <option value="pause" <?= isset($guichet['statut']) && $guichet['statut'] === 'pause' ? 'selected' : '' ?>>Pause</option>
                                <option value="ferme" <?= isset($guichet['statut']) && $guichet['statut'] === 'ferme' ? 'selected' : '' ?>>Fermé</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i><?= $isEdit ? 'Mettre à jour' : 'Créer' ?>
                        </button>
                        <a href="guichets.php" class="btn btn-secondary px-4">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>