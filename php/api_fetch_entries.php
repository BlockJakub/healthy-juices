<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauth']);
    exit;
}
$userId = (int)$_SESSION['user_id'];
$pdo = getPDO();
$st = $pdo->prepare('SELECT id, entry_date, payload, created_at FROM user_entries WHERE user_id = ? ORDER BY entry_date DESC, id DESC');
$st->execute([$userId]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$r) {
    $r['payload'] = json_decode($r['payload'], true);
    // Provide formatted date/time for UI (dd/mm/yyyy HH:MM:SS)
    try {
        if (!empty($r['entry_date'])) {
            $dt = new DateTime($r['entry_date']);
            // entry_date is date only in schema; keep time as 00:00:00
            $r['entry_date_formatted'] = $dt->format('d/m/Y') . ' 00:00:00';
        }
        if (!empty($r['created_at'])) {
            $dtc = new DateTime($r['created_at']);
            $r['created_at_formatted'] = $dtc->format('d/m/Y H:i:s');
        }
    } catch (Throwable $e) {
        // ignore formatting errors
    }
}
echo json_encode(['ok' => true, 'entries' => $rows]);
