<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('citoyen');

$user = currentUser();
$service_id = $_GET['service_id'] ?? 0;
$error = '';
$success = '';

// Get service details
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND est_actif = 1");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    header("Location: services.php");
    exit();
}

// Calculate next ticket number
$stmt = $conn->prepare("
    SELECT MAX(CAST(SUBSTRING_INDEX(numero, '-', -1) AS UNSIGNED)) as last_num 
    FROM tickets 
    WHERE service_id = ? AND DATE(created_at) = CURDATE()
");
$stmt->execute([$service_id]);
$last = $stmt->fetch();
$next_num = ($last['last_num'] ?? 0) + 1;

// Generate service prefix
$prefix = '';
switch($service_id) {
    case 1: $prefix = 'EC'; break;
    case 2: $prefix = 'PC'; break;
    case 3: $prefix = 'SF'; break;
    case 4: $prefix = 'SA'; break;
    case 5: $prefix = 'UR'; break;
    default: $prefix = 'TK';
}
$ticket_number = $prefix . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $priority = $_POST['priority'] ?? 'normal';
    
    $stmt = $conn->prepare("
        INSERT INTO tickets (numero, citoyen_id, service_id, statut, priorite)
        VALUES (?, ?, ?, 'en_attente', ?)
    ");
    
    if ($stmt->execute([$ticket_number, $user['id'], $service_id, $priority])) {
        $ticket_id = $conn->lastInsertId();
        
        // Create notification
        $message = "Votre ticket #{$ticket_number} a été créé avec succès. Vous êtes en position " . 
                   "dans la file d'attente du service {$service['nom']}.";
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')
        ");
        $stmt->execute([$user['id'], $message]);
        
        $_SESSION['flash']['success'] = "Ticket #{$ticket_number} créé avec succès !";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Erreur lors de la création du ticket.";
    }
}
?>
<?php include '../layout/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white border-0 rounded-top-4">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-ticket-perforated me-2"></i>Prendre un ticket
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Service :</strong> <?= htmlspecialchars($service['nom']) ?><br>
                        <strong>Durée moyenne :</strong> ~<?= $service['duree_moy'] ?> minutes par ticket
                    </div>
                    
                    <div class="text-center mb-4">
                        <div class="ticket-number display-4 fw-bold text-primary mb-2">
                            <?= $ticket_number ?>
                        </div>
                        <p class="text-muted">Votre numéro de ticket</p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Priorité</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="normal" value="normal" checked>
                                <label class="form-check-label" for="normal">
                                    Normal
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="urgent" value="urgent">
                                <label class="form-check-label" for="urgent">
                                    Urgent <span class="badge bg-danger ms-2">+ priorité</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="bi bi-check-circle me-2"></i>Confirmer la prise de ticket
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="services.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Retour aux services
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>