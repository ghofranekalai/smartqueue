<?php
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('agent');

$user = currentUser();
$action = $_GET['action'] ?? '';
$ticket_id = $_GET['id'] ?? 0;

// Get agent's guichet
$stmt = $conn->prepare("
    SELECT g.* FROM guichets g
    WHERE g.agent_id = ?
");
$stmt->execute([$user['id']]);
$guichet = $stmt->fetch();

if (!$guichet) {
    header("Location: dashboard.php");
    exit();
}

if ($action === 'call' && $ticket_id) {
    // Call the next ticket
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET statut = 'appele', 
            guichet_id = ?, 
            appele_at = NOW()
        WHERE id = ? AND service_id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$guichet['id'], $ticket_id, $guichet['service_id']]);
    
    if ($stmt->rowCount() > 0) {
        // Get citizen info for notification
        $stmt = $conn->prepare("
            SELECT t.numero, t.citoyen_id, s.nom as service_nom
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
        
        // Create notification for citizen
        $message = "Votre ticket #{$ticket['numero']} pour le service {$ticket['service_nom']} a été appelé. 
                    Rendez-vous au guichet {$guichet['numero']}.";
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'warning')
        ");
        $stmt->execute([$ticket['citoyen_id'], $message]);
        
        $_SESSION['flash']['success'] = "Ticket #{$ticket['numero']} appelé avec succès !";
    } else {
        $_SESSION['flash']['danger'] = "Erreur lors de l'appel du ticket.";
    }
    
} elseif ($action === 'complete' && $ticket_id) {
    // Complete the ticket
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET statut = 'termine', termine_at = NOW()
        WHERE id = ? AND guichet_id = ? AND statut IN ('appele', 'en_service')
    ");
    $stmt->execute([$ticket_id, $guichet['id']]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['flash']['success'] = "Ticket marqué comme terminé.";
    } else {
        $_SESSION['flash']['danger'] = "Erreur lors de la finalisation.";
    }
    
} elseif ($action === 'no_show' && $ticket_id) {
    // Mark as no show
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET statut = 'no_show'
        WHERE id = ? AND guichet_id = ? AND statut IN ('appele', 'en_service')
    ");
    $stmt->execute([$ticket_id, $guichet['id']]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['flash']['warning'] = "Citoyen marqué comme absent.";
    } else {
        $_SESSION['flash']['danger'] = "Erreur lors du marquage.";
    }
}

header("Location: dashboard.php");
exit();