<?php

$redis = new Redis();

try {
    $redis->connect('redis', 6379);
    if (!$redis->exists('tickets:contador')) {
        $count = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
        $redis->set('tickets:contador', (int)$count);
    }
} catch (Exception $e) {
    $redis = null;
}