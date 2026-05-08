<?php
require '../Core/Database.php';
require '../Core/Redis.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="10">
    <title>Panel Redis — TicketFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .redis-panel { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 10px; text-align: center; }
        .card .numero { font-size: 2.5em; font-weight: bold; color: #1d4ed8; }
        .card .label { color: #666; font-size: 0.9em; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .log-item { padding: 8px 12px; border-left: 4px solid #1d4ed8; margin-bottom: 8px; background: white; border-radius: 0 6px 6px 0; }
        .ttl { color: #888; font-size: 0.85em; }
    </style>
</head>
<body>
<h1>📊 Panel Redis en Vivo</h1>
<p class="ttl">Auto-refresca cada 10 seg &nbsp;|&nbsp; <a href="index.php">← Volver a tickets</a></p>

<?php if (!$redis): ?>
    <p style="color:red">⚠️ Redis no disponible.</p>
<?php else:
    $total     = (int)$redis->get('tickets:contador');
    $cacheTTL  = $redis->ttl('tickets:recientes');
    $cacheHit  = $cacheTTL > 0;
    $logItems  = $redis->lRange('tickets:log', 0, -1);
    $dbTotal   = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
?>

<div class="redis-panel">
    <div class="card">
        <div class="numero"><?= $total ?></div>
        <div class="label">Tickets (contador Redis)</div>
    </div>
    <div class="card">
        <div class="numero"><?= $dbTotal ?></div>
        <div class="label">Tickets (SQLite real)</div>
    </div>
    <div class="card">
        <div class="numero" style="font-size:1.5em">
            <?php if ($cacheHit): ?>
                <span class="badge badge-green">✓ ACTIVA</span><br>
                <span class="ttl">Expira en <?= $cacheTTL ?>s</span>
            <?php else: ?>
                <span class="badge badge-red">✗ EXPIRADA</span>
            <?php endif; ?>
        </div>
        <div class="label">Caché <code>tickets:recientes</code></div>
    </div>
</div>

<section>
    <h2>🔔 Log de actividad (Lista Redis — tickets:log)</h2>
    <?php if (empty($logItems)): ?>
        <p style="color:#888">Sin actividad aún. Crea un ticket para ver entradas aquí.</p>
    <?php else: ?>
        <?php foreach ($logItems as $item): ?>
            <div class="log-item"><?= htmlspecialchars($item) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section style="margin-top:30px">
    <h2>🔑 Claves Redis activas</h2>
    <table>
        <thead><tr><th>Clave</th><th>Tipo</th><th>TTL</th><th>Valor / Longitud</th></tr></thead>
        <tbody>
        <?php
        $claves = ['tickets:contador', 'tickets:recientes', 'tickets:log'];
        foreach ($claves as $clave):
            $tipo = $redis->type($clave);
            $tipos = [Redis::REDIS_NOT_FOUND => '—', Redis::REDIS_STRING => 'string',
                      Redis::REDIS_LIST => 'list', Redis::REDIS_SET => 'set',
                      Redis::REDIS_ZSET => 'zset', Redis::REDIS_HASH => 'hash'];
            $tipoNombre = $tipos[$tipo] ?? '?';
            $ttl = $redis->ttl($clave);
            $ttlStr = $ttl === -1 ? '∞ (sin expiración)' : ($ttl === -2 ? 'no existe' : $ttl . 's');
            if ($tipo === Redis::REDIS_STRING) $val = $redis->get($clave);
            elseif ($tipo === Redis::REDIS_LIST) $val = $redis->lLen($clave) . ' elementos';
            else $val = '—';
        ?>
        <tr>
            <td><code><?= $clave ?></code></td>
            <td><?= $tipoNombre ?></td>
            <td><?= $ttlStr ?></td>
            <td><?= htmlspecialchars($val) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php endif; ?>
</body>
</html>