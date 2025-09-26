<?php
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');
function respond($c, $a)
{
    http_response_code($c);
    echo json_encode($a);
    exit;
}
if (empty($_SESSION['user_id'])) respond(401, ['ok' => false, 'error' => 'unauth']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'error' => 'method']);
$raw = file_get_contents('php://input');
if (!$raw) respond(400, ['ok' => false, 'error' => 'empty']);
$data = json_decode($raw, true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'json']);
$csrf = $data['csrf'] ?? '';
if (!validate_csrf_token($csrf)) respond(403, ['ok' => false, 'error' => 'csrf']);
$badge = trim((string)($data['badge'] ?? ''));
if ($badge === '') respond(400, ['ok' => false, 'error' => 'badge']);

function ensure_badges_table($pdo)
{
    try {
        $q = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='user_badges'");
        if ($q && $q->fetchColumn()) return;
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("CREATE TABLE user_badges (
        user_id INT NOT NULL,
        badge_key VARCHAR(64) NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id,badge_key),
        CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        try {
            $pdo->exec("CREATE TABLE user_badges (
            user_id INT NOT NULL,
            badge_key VARCHAR(64) NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(user_id,badge_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e2) {
        }
    }
}
try {
    $pdo = getPDO();
    ensure_badges_table($pdo);
    $uid = (int)$_SESSION['user_id'];
    $st = $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_key) VALUES (?,?)');
    $st->execute([$uid, $badge]);
    $earned = $st->rowCount() === 1; // inserted new
    respond(200, ['ok' => true, 'earned' => $earned, 'badge' => $badge]);
} catch (Throwable $e) {
    error_log('BADGES_AWARD_FAIL: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'server']);
}
