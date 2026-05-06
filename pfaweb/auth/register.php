<?php
require_once '../config/session.php';
require_once '../config/connexion.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') header("Location: /smartqueue/admin/dashboard.php");
    elseif ($role === 'agent') header("Location: /smartqueue/guichet/dashboard.php");
    else header("Location: /smartqueue/citoyen/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, telephone, mot_de_passe, role)
                VALUES (?, ?, ?, ?, ?, 'citoyen')
            ");
            if ($stmt->execute([$nom, $prenom, $email, $telephone, $hashed_password])) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                // Clear form
                $_POST = [];
            } else {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription - SmartQueue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/smartqueue/styles/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-ticket-perforated-fill fs-1 text-primary"></i>
                            <h2 class="fw-bold mt-2">Inscription</h2>
                            <p class="text-muted">Créez votre compte SmartQueue</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="sq-validate needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                                    <div class="invalid-feedback">Le nom est requis</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                                    <div class="invalid-feedback">Le prénom est requis</div>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    <div class="invalid-feedback">Email valide requis</div>
                                </div>
                                <div class="col-12">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <div class="invalid-feedback">6 caractères minimum</div>
                                </div>
                                <div class="col-12">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">Les mots de passe doivent correspondre</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mt-4 fw-semibold">
                                <i class="bi bi-person-plus me-2"></i>S'inscrire
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Déjà un compte ? <a href="login.php" class="text-decoration-none">Se connecter</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/smartqueue/scripts/smartqueue.js"></script>
</body>
</html>