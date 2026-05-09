<?php
session_start();
require '../Core/Database.php';
require '../Core/Redis.php';

// Cargar tickets: primero Redis, si no, SQLite
if ($redis && $redis->exists('tickets:recientes')) {
    $tickets = json_decode($redis->get('tickets:recientes'), true);
    $origen = '🟢 Redis (caché)';
} else {
    $stmt = $pdo->query("SELECT * FROM tickets ORDER BY creado_en DESC");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $origen = '🔵 SQLite';
    if ($redis) {
        $redis->setex('tickets:recientes', 30, json_encode($tickets));
    }
}
$totalRedis = $redis ? ((int)$redis->get('tickets:contador') ?: count($tickets)) : count($tickets);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Soporte Académico - TicketFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .origen-badge {
            font-size: .8rem;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 999px;
            color: #475569;
        }

        .ticket-row-resuelto {
            background: #f0fdf4;
        }

        .respuesta-box {
            background: #dcfce7;
            border-left: 3px solid #22c55e;
            padding: 8px 12px;
            border-radius: 0 6px 6px 0;
            margin-top: 6px;
            font-size: .85rem;
        }
    </style>
</head>
<body>

<nav>
    <div><strong>TicketFlow</strong> Académico</div>
    <a href="login.php">Acceso Admin 🔒</a>
</nav>

<div class="container">
    <h1>Enviar una solicitud de soporte</h1>
    <p>Completa el formulario y un asesor te responderá pronto.</p>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            ¡Ticket enviado con éxito! Tu número de seguimiento es
            <strong>#<?= htmlspecialchars($_GET['id'] ?? '0') ?></strong>.
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="../Controllers/create.php" method="POST">
            <label>Nombre Completo</label>
            <input type="text" name="nombre" placeholder="Ej. Juan Pérez" required>

            <label>Correo Institucional</label>
            <input type="email" name="correo" placeholder="usuario@universidad.edu" required>

            <label>Categoría</label>
            <select name="categoria" required>
                <option value="Plataforma">Plataforma</option>
                <option value="Matrícula">Matrícula</option>
                <option value="Pagos">Pagos</option>
                <option value="Biblioteca">Biblioteca</option>
                <option value="Soporte técnico">Soporte técnico</option>
            </select>

            <label>Descripción del Problema</label>
            <textarea name="descripcion" rows="5" placeholder="Explica detalladamente..." required></textarea>

            <button type="submit">Enviar Ticket</button>
        </form>
    </div>

    <!-- ===== LISTADO DE TICKETS ===== -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="margin:0">Solicitudes enviadas</h2>
        <span class="origen-badge">Datos desde: <?= $origen ?> &nbsp;|&nbsp; Total: <?= $totalRedis ?></span>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">Aún no hay tickets registrados.</div>
    <?php else: ?>
        <div class="card" style="padding:0;overflow:hidden">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Respuesta del asesor</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr class="<?= $t['estado'] === 'resuelto' ? 'ticket-row-resuelto' : '' ?>">
                        <td><strong>#<?= $t['id'] ?></strong></td>
                        <td><?= htmlspecialchars($t['nombre']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($t['categoria']) ?></span></td>
                        <td>
                            <span class="badge badge-<?= str_replace(' ', '-', $t['estado']) ?>"><?= $t['estado'] ?></span>
                        </td>
                        <td style="font-size:.8rem;color:#64748b"><?= substr($t['creado_en'], 0, 16) ?></td>
                        <td>
                            <?php if (!empty($t['respuesta'])): ?>
                                <div class="respuesta-box">
                                    <?= htmlspecialchars($t['respuesta']) ?>
                                    <div style="color:#15803d;font-size:.75rem;margin-top:4px">
                                        — <?= htmlspecialchars($t['atendido_por'] ?? 'Asesor') ?></div>
                                </div>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-size:.8rem">En espera de respuesta…</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>