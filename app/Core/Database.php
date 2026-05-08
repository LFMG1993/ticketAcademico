<?php

$dbPath = '/var/www/data/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA busy_timeout=5000');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            correo TEXT NOT NULL,
            categoria TEXT NOT NULL,
            descripcion TEXT NOT NULL,
            estado TEXT NOT NULL DEFAULT 'pendiente',
            respuesta TEXT,
            atendido_por TEXT,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tabla de usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            nombre TEXT NOT NULL
        )
    ");

    // Insertar un usuario admin de prueba (password: admin123)
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT OR IGNORE INTO usuarios (usuario, password, nombre) VALUES ('admin', '$pass', 'Administrador Académico')");

    // Migración
    $cols = array_column($pdo->query("PRAGMA table_info(tickets)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('respuesta',    $cols)) $pdo->exec("ALTER TABLE tickets ADD COLUMN respuesta TEXT");
    if (!in_array('atendido_por', $cols)) $pdo->exec("ALTER TABLE tickets ADD COLUMN atendido_por TEXT");

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}