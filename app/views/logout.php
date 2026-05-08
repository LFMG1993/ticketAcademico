<?php
session_start();

require '../Core/Database.php';
require '../Core/Redis.php';

// Registrar salida en Redis
if ($redis && isset($_SESSION['admin'])) {
    $redis->del('admin:sesion:' . session_id());
    $redis->lPush('tickets:log', 'Admin ' . $_SESSION['admin'] . ' cerró sesión - ' . date('H:i:s'));
    $redis->lTrim('tickets:log', 0, 19);
}

session_destroy();
header('Location: login.php');
exit;