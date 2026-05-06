<?php
header('Content-Type: application/json');
require_once '../config/connexion.php';

$service_id = $_GET['service_id'] ?? 0;

if ($service_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tickets WHERE service_id = ? AND statut = 'en_attente'");
    $stmt->execute([$service_id]);
    $result = $stmt->fetch();
    echo json_encode(['count' => (int)$result['count']]);
} else {
    echo json_encode(['error' => 'service_id required']);
}
?>