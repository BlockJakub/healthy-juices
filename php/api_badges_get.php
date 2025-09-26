<?php
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');

function respond($c, $arr)
{
    http_response_code($c);
    echo json_encode($arr);
    exit;
}
if (empty($_SESSION['user_id'])) respond(401, ['ok' => false, 'error' => 'unauth']);

function ensure_badges_table($pdo)
{
    try {
        $q = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='user_badges'");
        if ($q && $q->fetchColumn()) return true;
    } catch (Throwable $e) {
    }
    $ddl = "CREATE TABLE user_badges (
        user_id INT NOT NULL,
        badge_key VARCHAR(64) NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id,badge_key),
        CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $pdo->exec($ddl);
        return true;
    } catch (Throwable $e) {
        error_log('BADGES_TABLE_FAIL: ' . $e->getMessage());
        // fallback without FK
        try {
            $pdo->exec("CREATE TABLE user_badges (
                user_id INT NOT NULL,
                badge_key VARCHAR(64) NOT NULL,
                earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(user_id,badge_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            return true;
        } catch (Throwable $e2) {
            return false;
        }
    }
}

try {
    $pdo = getPDO();
    ensure_badges_table($pdo); // ignore failure; empty fallback
    $uid = (int)$_SESSION['user_id'];
    $st = $pdo->prepare('SELECT badge_key, earned_at FROM user_badges WHERE user_id=? ORDER BY earned_at ASC');
    $st->execute([$uid]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    respond(200, ['ok' => true, 'badges' => $rows]);
} catch (Throwable $e) {
    error_log('BADGES_GET_FAIL: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'server']);
}
