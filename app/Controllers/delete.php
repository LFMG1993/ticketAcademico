<?php
require __DIR__ . '/../Core/Database.php';
require __DIR__ . '/../Core/Redis.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $id = $_POST['id'] ?? 0;

    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$id]);

    if ($redis) {
        $redis->del('tickets:recientes');
        $redis->lPush('tickets:log', date('H:i:s') . ' — Ticket #' . $id . ' eliminado');
        $redis->lTrim('tickets:log', 0, 19);
    }

    header('Location: /app/views/dashboard.php');
    exit;
}

$id = $_GET['id'] ?? 0;
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
    <title>Eliminar ticket</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<h1>Eliminar ticket</h1>

<p>¿Estás seguro de que deseas eliminar este ticket?</p>

<p><strong>ID:</strong> <?= $ticket['id'] ?><br>
    <strong>Nombre:</strong> <?= htmlspecialchars($ticket['nombre']) ?><br>
    <strong>Descripción:</strong> <?= htmlspecialchars($ticket['descripcion']) ?></p>

<form method="POST">
    <input type="hidden" name="id" value="<?= $ticket['id'] ?>">
    <button type="submit" name="confirmar" value="1">Eliminar definitivamente</button>
    <a href="../views/index.php" style="margin-left:10px">Cancelar</a>
</form>

</body>
</html>