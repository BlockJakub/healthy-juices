<?php
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');

function respond($code, $arr)
{
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

if (empty($_SESSION['user_id'])) {
    respond(401, ['ok' => false, 'error' => 'unauth']);
}

function ensure_bmi_table($pdo)
{
    try {
        $q = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='user_bmi'");
        if ($q && $q->fetchColumn()) return true;
    } catch (Throwable $e) {
    }
    // Do not auto-create here (only on save) to keep fetch fast.
    return false;
}

try {
    $userId = (int)$_SESSION['user_id'];
    $pdo = getPDO();
    if (!ensure_bmi_table($pdo)) {
        respond(200, ['ok' => true, 'entries' => []]);
    }
    $st = $pdo->prepare('SELECT entry_date, weight_kg, height_cm, bmi, created_at FROM user_bmi WHERE user_id = ? ORDER BY entry_date DESC');
    $st->execute([$userId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$r) {
        try {
            if (!empty($r['entry_date'])) {
                $dt = new DateTime($r['entry_date']);
                $r['entry_date_formatted'] = $dt->format('d/m/Y') . ' 00:00:00';
            }
            if (!empty($r['created_at'])) {
                $dtc = new DateTime($r['created_at']);
                $r['created_at_formatted'] = $dtc->format('d/m/Y H:i:s');
            }
        } catch (Throwable $e) {
        }
    }
    respond(200, ['ok' => true, 'entries' => $rows]);
} catch (Throwable $e) {
    error_log('BMI_FETCH_FAIL: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'server']);
}
