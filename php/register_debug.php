<?php
// Development-only diagnostic: checks DB connectivity and users table/schema
// WARNING: Do NOT enable in production. This endpoint returns schema info for debugging.
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
$out = ['ok' => false, 'checks' => []];
try {
    $pdo = getPDO();
    $out['checks']['db_connected'] = true;
    // check users table existence
    $st = $pdo->query("SHOW TABLES LIKE 'users'");
    $tbl = $st->fetchAll(PDO::FETCH_NUM);
    $out['checks']['users_table_exists'] = !empty($tbl);
    if (!empty($tbl)) {
        // describe columns
        $cols = [];
        $st2 = $pdo->query('DESCRIBE users');
        $cols = $st2->fetchAll(PDO::FETCH_ASSOC);
        $out['checks']['users_columns'] = $cols;
    }
    $out['ok'] = true;
} catch (Throwable $e) {
    $out['checks']['db_connected'] = false;
    $out['error'] = $e->getMessage();
}
echo json_encode($out, JSON_PRETTY_PRINT);
