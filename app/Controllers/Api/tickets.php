<?php
ob_start();
ini_set('display_errors', 0);
session_start();

if (!isset($_SESSION['admin'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

try {
    require __DIR__ . '/../../Core/Database.php';

    $stmt    = $pdo->query("SELECT * FROM tickets ORDER BY creado_en DESC");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($tickets);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}