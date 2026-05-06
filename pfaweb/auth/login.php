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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: /smartqueue/admin/dashboard.php");
            } elseif ($user['role'] === 'agent') {
                header("Location: /smartqueue/guichet/dashboard.php");
            } else {
                header("Location: /smartqueue/citoyen/dashboard.php");
            }
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - SmartQueue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/smartqueue/styles/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-ticket-perforated-fill fs-1 text-primary"></i>
                            <h2 class="fw-bold mt-2">Connexion</h2>
                            <p class="text-muted">Connectez-vous à votre compte</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="sq-validate needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                <div class="invalid-feedback">Email requis</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Mot de passe requis</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Pas encore de compte ? <a href="register.php" class="text-decoration-none">S'inscrire</a></p>
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