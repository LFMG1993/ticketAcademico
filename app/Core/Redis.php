<?php
$redis = null;

/**
 * Variables de entorno (producción)
 * Si no hay variables, intentar Docker Compose local
 */
$host = getenv('REDIS_HOST') ?: 'redis';
$port = (int)(getenv('REDIS_PORT') ?: 6379);
$password = getenv('REDIS_PASSWORD') ?: null;

try {
    $r = new Redis();
    // @ suprime el warning si falla la conexión
    if (@$r->connect($host, $port, 1.5)) {
        if ($password) {
            $r->auth($password);
        }
        // Ping para confirmar que realmente responde
        if ($r->ping()) {
            $redis = $r;
            if (!$redis->exists('tickets:contador')) {
                $count = isset($pdo)
                    ? (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn()
                    : 0;
                $redis->set('tickets:contador', $count);
            }
        }
    }
} catch (Exception $e) {
    $redis = null;
}