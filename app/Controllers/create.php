<?php
require __DIR__ . '/../Core/Database.php';
require __DIR__ . '/../Core/Redis.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '' || $correo === '' || $categoria === '' || $descripcion === '') {
        header('Location: /app/views/index.php?error=campos_vacios');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tickets (nombre, correo, categoria, descripcion)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$nombre, $correo, $categoria, $descripcion]);
    $newId = $pdo->lastInsertId();

    if ($redis) {
        $redis->incr('tickets:contador');
        $redis->del('tickets:recientes');
        $redis->lPush('tickets:log', date('H:i:s') . ' — Ticket creado por ' . $nombre . ' [' . $categoria . ']');
        $redis->lTrim('tickets:log', 0, 19);
        $redis->publish('tickets:canal', json_encode(['nombre' => $nombre, 'categoria' => $categoria]));
    }

    header('Location: /app/views/index.php?success=1&id=' . $newId);
    exit;
}