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
function is_local_debug()
{
    $h = $_SERVER['HTTP_HOST'] ?? '';
    $r = $_SERVER['REMOTE_ADDR'] ?? '';
    return strpos($h, 'localhost') !== false || $r === '127.0.0.1' || $r === '::1';
}
function ensure_goals_table($pdo)
{
    // Check existence first
    try {
        $q = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='user_goals'");
        if ($q && $q->fetchColumn()) return ['exists' => true, 'fk' => null, 'created' => false];
    } catch (Throwable $e) { /* ignore */
    }
    $ddl = "CREATE TABLE user_goals (
        user_id INT NOT NULL PRIMARY KEY,
        water_goal DECIMAL(5,2) DEFAULT 0,
        sleep_goal DECIMAL(5,2) DEFAULT 0,
        steps_goal INT DEFAULT 0,
        health_score_goal INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_goals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $pdo->exec($ddl);
        return ['exists' => true, 'fk' => true, 'created' => true];
    } catch (Throwable $e) {
        error_log('GOALS_TABLE_FK_FAIL: ' . $e->getMessage());
        $ddl2 = "CREATE TABLE user_goals (
            user_id INT NOT NULL PRIMARY KEY,
            water_goal DECIMAL(5,2) DEFAULT 0,
            sleep_goal DECIMAL(5,2) DEFAULT 0,
            steps_goal INT DEFAULT 0,
            health_score_goal INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        try {
            $pdo->exec($ddl2);
            return ['exists' => true, 'fk' => false, 'created' => true, 'warn' => 'fk_removed'];
        } catch (Throwable $e2) {
            throw $e2;
        }
    }
}
try {
    $userId = (int)$_SESSION['user_id'];
    $pdo = getPDO();
    $meta = ensure_goals_table($pdo);
    $st = $pdo->prepare('SELECT water_goal, sleep_goal, steps_goal, health_score_goal FROM user_goals WHERE user_id = ?');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $resp = ['ok' => true, 'water_goal' => 0, 'sleep_goal' => 0, 'steps_goal' => 0, 'health_score_goal' => 0, '_fk' => $meta['fk']];
        if (isset($meta['warn'])) $resp['warning'] = $meta['warn'];
        respond(200, $resp);
    }
    $row = array_map(function ($v) {
        return is_null($v) ? 0 : $v;
    }, $row);
    $row['ok'] = true;
    $row['_fk'] = $meta['fk'];
    if (isset($meta['warn'])) $row['warning'] = $meta['warn'];
    respond(200, $row);
} catch (Throwable $e) {
    $dbg = is_local_debug() ? substr($e->getMessage(), 0, 160) : null;
    error_log('GOALS_GET_FAIL: ' . $e->getMessage());
    $resp = ['ok' => false, 'error' => 'server', 'reason' => 'goals_get_exception'];
    if ($dbg) $resp['debug'] = $dbg;
    respond(500, $resp);
}
