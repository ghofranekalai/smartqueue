<?php
/**
 * SmartQueue – API : statut des tickets appelés
 * Retourne la liste des tickets en cours d'appel (statut 'appele' ou 'en_service')
 * avec le guichet associé, pour l'écran public de la salle d'attente.
 * Aucune authentification requise (affichage public).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');
require_once '../config/connexion.php';

try {
    // Tickets actuellement appelés / en service
    $stmt = $conn->prepare("
        SELECT
            t.id,
            t.numero,
            t.statut,
            t.appele_at,
            t.priorite,
            g.numero  AS guichet_numero,
            s.nom     AS service_nom
        FROM tickets t
        JOIN guichets g ON t.guichet_id = g.id
        JOIN services s ON t.service_id = s.id
        WHERE t.statut IN ('appele', 'en_service')
        ORDER BY t.appele_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $appels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dernier ticket appelé (pour l'annonce sonore)
    $lastStmt = $conn->prepare("
        SELECT
            t.numero,
            t.appele_at,
            g.numero AS guichet_numero,
            s.nom    AS service_nom
        FROM tickets t
        JOIN guichets g ON t.guichet_id = g.id
        JOIN services s ON t.service_id = s.id
        WHERE t.statut IN ('appele', 'en_service')
        ORDER BY t.appele_at DESC
        LIMIT 1
    ");
    $lastStmt->execute();
    $dernierAppel = $lastStmt->fetch(PDO::FETCH_ASSOC);

    // Statistiques globales
    $statsStmt = $conn->query("
        SELECT
            SUM(statut = 'en_attente')  AS total_attente,
            SUM(statut = 'appele')      AS total_appele,
            SUM(statut = 'en_service')  AS total_en_service,
            SUM(statut = 'termine' AND DATE(termine_at) = CURDATE()) AS traites_auj
        FROM tickets
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Guichets ouverts
    $guichetsStmt = $conn->query("
        SELECT g.numero, s.nom as service_nom, g.statut
        FROM guichets g
        JOIN services s ON g.service_id = s.id
        WHERE g.statut = 'ouvert'
        ORDER BY g.numero
    ");
    $guichets = $guichetsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'      => true,
        'timestamp'    => date('Y-m-d H:i:s'),
        'appels'       => $appels,
        'dernier_appel'=> $dernierAppel ?: null,
        'stats'        => $stats,
        'guichets'     => $guichets,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}
