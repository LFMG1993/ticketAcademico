<?php
require __DIR__ . '/../Core/Database.php';
require __DIR__ . '/../Core/Redis.php';

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'] ?? 'pendiente';

    $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    if ($redis) {
        $redis->del('tickets:recientes');
    }

    header('Location: /app/views/dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: /app/views/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar ticket</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<h1>Editar estado del ticket</h1>

<form method="POST">
    <p><strong>Ticket:</strong> <?= htmlspecialchars($ticket['descripcion']) ?></p>

    <select name="estado">
        <?php foreach (['pendiente' => 'Pendiente', 'en proceso' => 'En proceso', 'resuelto' => 'Resuelto'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $ticket['estado'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Guardar</button>
</form>

<a href="../views/index.php">Volver</a>
</body>
</html>