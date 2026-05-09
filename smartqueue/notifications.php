<?php
require_once 'config/session.php';
require_once 'config/connexion.php';
requireLogin();

$user = currentUser();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET lu = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    header("Location: notifications.php");
    exit();
}

// Get notifications
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
?>
<?php include 'layout/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="bi bi-bell-fill text-primary me-2"></i>Mes notifications
        </h2>
        <a href="?mark_read=1" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-envelope-open me-1"></i>Tout marquer comme lu
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            <?php if (empty($notifications)): ?>
                <div class="list-group-item text-center py-5">
                    <i class="bi bi-bell-slash fs-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-0">Aucune notification</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="list-group-item <?= !$notif['lu'] ? 'bg-light' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold <?= !$notif['lu'] ? 'text-primary' : 'text-dark' ?>">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                                </small>
                            </div>
                            <?php if (!$notif['lu']): ?>
                                <span class="badge bg-primary rounded-pill">Nouveau</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>