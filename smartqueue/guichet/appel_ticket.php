<?php
/**
 * SmartQueue – Appel de ticket (agent / guichet)
 * Gère les actions : call, complete, no_show, call_next
 * Supporte les réponses JSON (si ?format=json) et HTML (redirect).
 */
require_once '../config/session.php';
require_once '../config/connexion.php';
requireLogin('agent');

$user      = currentUser();
$action    = $_GET['action']    ?? '';
$ticket_id = (int)($_GET['id']  ?? 0);
$format    = $_GET['format']    ?? 'html'; // 'json' pour AJAX

/* ── Récupérer le guichet de l'agent ─────────────────────────── */
$stmt = $conn->prepare("
    SELECT g.*, s.nom AS service_nom
    FROM guichets g
    JOIN services s ON g.service_id = s.id
    WHERE g.agent_id = ?
");
$stmt->execute([$user['id']]);
$guichet = $stmt->fetch();

if (!$guichet) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Aucun guichet assigné.']);
        exit();
    }
    header("Location: dashboard.php");
    exit();
}

/* ── Fonction de réponse ─────────────────────────────────────── */
function respond(bool $ok, string $msg, array $extra = [], string $format = 'html'): void {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
        exit();
    }
    $key = $ok ? 'success' : 'danger';
    if (!$ok && str_starts_with($msg, 'warn:')) {
        $key = 'warning';
        $msg = substr($msg, 5);
    }
    $_SESSION['flash'][$key] = $msg;
    header("Location: dashboard.php");
    exit();
}

/* ── ACTION : Appeler un ticket ──────────────────────────────── */
if ($action === 'call' && $ticket_id) {

    $stmt = $conn->prepare("
        UPDATE tickets
        SET statut     = 'appele',
            guichet_id = ?,
            appele_at  = NOW()
        WHERE id = ?
          AND service_id = ?
          AND statut = 'en_attente'
    ");
    $stmt->execute([$guichet['id'], $ticket_id, $guichet['service_id']]);

    if ($stmt->rowCount() > 0) {
        $stmt = $conn->prepare("
            SELECT t.numero, t.citoyen_id, t.appele_at, t.priorite,
                   s.nom AS service_nom
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();

        $message = "Votre ticket #{$ticket['numero']} pour le service {$ticket['service_nom']} "
                 . "a ete appele. Rendez-vous au guichet {$guichet['numero']}.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'warning')");
        $stmt->execute([$ticket['citoyen_id'], $message]);

        respond(true, "Ticket #{$ticket['numero']} appele avec succes !", [
            'ticket' => [
                'id'             => $ticket_id,
                'numero'         => $ticket['numero'],
                'guichet_numero' => $guichet['numero'],
                'service_nom'    => $ticket['service_nom'],
                'appele_at'      => $ticket['appele_at'],
                'priorite'       => $ticket['priorite'],
            ]
        ], $format);

    } else {
        respond(false, "Ce ticket n'est plus disponible.", [], $format);
    }

/* ── ACTION : Terminer ───────────────────────────────────────── */
} elseif ($action === 'complete' && $ticket_id) {

    $stmt = $conn->prepare("
        UPDATE tickets
        SET statut = 'termine', termine_at = NOW()
        WHERE id = ? AND guichet_id = ? AND statut IN ('appele', 'en_service')
    ");
    $stmt->execute([$ticket_id, $guichet['id']]);

    if ($stmt->rowCount() > 0) {
        $stmt = $conn->prepare("
            SELECT t.numero, t.citoyen_id, t.appele_at, t.termine_at, s.nom AS service_nom
            FROM tickets t JOIN services s ON t.service_id = s.id WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();

        $notif = "Votre ticket #{$ticket['numero']} a ete traite avec succes. Merci de votre visite.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
        $stmt->execute([$ticket['citoyen_id'], $notif]);

        $duree = '';
        if ($ticket['appele_at'] && $ticket['termine_at']) {
            $diff  = strtotime($ticket['termine_at']) - strtotime($ticket['appele_at']);
            $duree = floor($diff / 60) . ' min ' . ($diff % 60) . ' s';
        }

        respond(true, "Ticket #{$ticket['numero']} termine. Duree : {$duree}", [
            'ticket' => ['numero' => $ticket['numero'], 'duree' => $duree]
        ], $format);

    } else {
        respond(false, "Impossible de terminer ce ticket.", [], $format);
    }

/* ── ACTION : Absent ─────────────────────────────────────────── */
} elseif ($action === 'no_show' && $ticket_id) {

    $stmt = $conn->prepare("
        UPDATE tickets SET statut = 'no_show'
        WHERE id = ? AND guichet_id = ? AND statut IN ('appele', 'en_service')
    ");
    $stmt->execute([$ticket_id, $guichet['id']]);

    if ($stmt->rowCount() > 0) {
        respond(true, 'warn:Citoyen marque comme absent.', [], $format);
    } else {
        respond(false, "Impossible de marquer ce ticket.", [], $format);
    }

/* ── ACTION : Appel automatique du suivant ───────────────────── */
} elseif ($action === 'call_next') {

    $stmt = $conn->prepare("
        SELECT id FROM tickets WHERE guichet_id = ? AND statut IN ('appele','en_service') LIMIT 1
    ");
    $stmt->execute([$guichet['id']]);
    if ($stmt->fetch()) {
        respond(false, "Un ticket est deja en cours.", [], $format);
    }

    $stmt = $conn->prepare("
        SELECT id FROM tickets
        WHERE service_id = ? AND statut = 'en_attente'
        ORDER BY priorite = 'urgent' DESC, created_at ASC
        LIMIT 1
    ");
    $stmt->execute([$guichet['service_id']]);
    $next = $stmt->fetch();

    if (!$next) {
        respond(false, "Aucun ticket en attente.", [], $format);
    }

    header("Location: appel_ticket.php?action=call&id={$next['id']}" . ($format === 'json' ? '&format=json' : ''));
    exit();

} else {
    respond(false, "Action invalide.", [], $format);
}
