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
    require __DIR__ . '/../../Core/Redis.php';

    $redisActivo = $redis !== null;

    $data = [
        'activo'   => $redisActivo,
        'contador' => $redisActivo ? (int)$redis->get('tickets:contador') : 0,
        'cacheTTL' => $redisActivo ? (int)$redis->ttl('tickets:recientes') : -2,
        'sesiones' => $redisActivo ? count($redis->keys('admin:sesion:*')) : 0,
        'log'      => $redisActivo ? $redis->lRange('tickets:log', 0, 9) : [],
        'claves'   => [],
    ];

    if ($redisActivo) {
        foreach (['tickets:contador', 'tickets:recientes', 'tickets:log'] as $key) {
            $tipo  = $redis->type($key);
            $tipos = [
                Redis::REDIS_NOT_FOUND => '—',
                Redis::REDIS_STRING    => 'string',
                Redis::REDIS_LIST      => 'list',
            ];
            $ttl = (int)$redis->ttl($key);
            if ($ttl === -1)      $ttlStr = '∞';
            elseif ($ttl === -2)  $ttlStr = 'no existe';
            else                  $ttlStr = $ttl . 's';

            $val = $tipo === Redis::REDIS_STRING ? (string)$redis->get($key)
                 : ($tipo === Redis::REDIS_LIST  ? $redis->lLen($key) . ' elementos' : '—');

            $data['claves'][] = [
                'key'   => $key,
                'tipo'  => $tipos[$tipo] ?? '?',
                'ttl'   => $ttlStr,
                'valor' => $val,
            ];
        }
    }

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}