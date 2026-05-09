<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin();

$user = currentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($nom) || empty($prenom)) {
        $error = "Le nom et prénom sont requis.";
    } else {
        // Update basic info
        $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ? WHERE id = ?");
        $stmt->execute([$nom, $prenom, $telephone, $user['id']]);
        
        // Update password if provided
        if (!empty($current_password) && !empty($new_password)) {
            $stmt = $conn->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user['id']]);
            $db_user = $stmt->fetch();
            
            if (password_verify($current_password, $db_user['mot_de_passe'])) {
                if (strlen($new_password) >= 6) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                    $stmt->execute([$hashed, $user['id']]);
                    $success = "Profil mis à jour avec succès !";
                } else {
                    $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                }
            } else {
                $error = "Mot de passe actuel incorrect.";
            }
        } else {
            $success = "Profil mis à jour avec succès !";
        }
        
        // Refresh session data
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_prenom'] = $prenom;
    }
}

// Get fresh user data
$stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();
?>
<?php include '../layout/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-person-circle text-primary me-2"></i>Mon Profil
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="sq-validate">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($userData['nom']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                       value="<?= htmlspecialchars($userData['prenom']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?= htmlspecialchars($userData['email']) ?>" disabled>
                                <small class="text-muted">L'email ne peut pas être modifié</small>
                            </div>
                            <div class="col-12">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?= htmlspecialchars($userData['telephone']) ?>">
                            </div>
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="fw-bold">Changer le mot de passe</h6>
                            </div>
                            <div class="col-12">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-12">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-2"></i>Enregistrer les modifications
                            </button>
                            <a href="<?= $user['role'] === 'admin' ? '/smartqueue/admin/dashboard.php' : ($user['role'] === 'agent' ? '/smartqueue/guichet/dashboard.php' : '/smartqueue/citoyen/dashboard.php') ?>" 
                               class="btn btn-secondary px-4">
                                <i class="bi bi-arrow-left me-2"></i>Retour
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>