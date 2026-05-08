<?php
session_start();
require '../Core/Database.php';
require '../Core/Redis.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin'] = $user['nombre'];
        
        // REGISTRO EN REDIS: Marcar admin como activo
        if ($redis) {
            $redis->setex("admin:sesion:" . session_id(), 3600, $user['nombre']);
            $redis->lPush('tickets:log', "Admin " . $user['nombre'] . " inició sesión - " . date('H:i:s'));
        }
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenciales inválidas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - TicketFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="container">
    <div class="login-container card">
        <h2 style="text-align:center">🔐 Área Administrativa</h2>
        <?php if ($error): ?> <p style="color:red"><?= $error ?></p> <?php endif; ?>
        <form method="POST">
            <label>Usuario</label>
            <input type="text" name="usuario" required>
            <label>Contraseña</label>
            <input type="password" name="password" required>
            <button type="submit" style="width:100%">Ingresar</button>
        </form>
        <p style="text-align:center; margin-top:1rem"><a href="index.php">← Volver al inicio</a></p>
    </div>
</body>
</html>