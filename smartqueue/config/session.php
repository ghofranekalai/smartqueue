<?php
// ============================================================
// SmartQueue – Gestion des sessions et sécurité
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $role = ''): void {
    if (!isLoggedIn()) {
        header("Location: /smartqueue/index.php");
        exit();
    }
    if ($role && $_SESSION['user_role'] !== $role) {
        header("Location: /smartqueue/index.php?error=access_denied");
        exit();
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'nom'   => $_SESSION['user_nom']   ?? '',
        'prenom'=> $_SESSION['user_prenom']?? '',
        'role'  => $_SESSION['user_role']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}
?>
