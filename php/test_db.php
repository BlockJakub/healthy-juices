<?php
// Diagnostic: test DB connection using project's db.php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
$out = ['ok' => false];
try {
    $pdo = getPDO();
    $st = $pdo->query('SELECT 1 AS v');
    $v = $st->fetch(PDO::FETCH_ASSOC);
    $out['ok'] = true;
    $out['server_ready'] = true;
    $out['db_test'] = $v;
} catch (Throwable $e) {
    $out['ok'] = false;
    $out['error'] = $e->getMessage();
}
echo json_encode($out, JSON_PRETTY_PRINT);
