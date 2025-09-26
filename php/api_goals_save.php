<?php
// Ensure clean JSON (suppress direct HTML error output)
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'method']);
}
$raw = file_get_contents('php://input');
if (!$raw) {
    respond(400, ['ok' => false, 'error' => 'empty']);
}
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, ['ok' => false, 'error' => 'json']);
}
$csrf = $data['csrf'] ?? '';
if (!validate_csrf_token($csrf)) {
    respond(403, ['ok' => false, 'error' => 'csrf']);
}
function ensure_goals_table($pdo)
{
    try {
        $q = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='user_goals'");
        if ($q && $q->fetchColumn()) return ['exists' => true, 'fk' => null, 'created' => false];
    } catch (Throwable $e) {
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
    $water = isset($data['water_goal']) ? max(0, min(24, floatval($data['water_goal']))) : 0;
    $sleep = isset($data['sleep_goal']) ? max(0, min(24, floatval($data['sleep_goal']))) : 0;
    $steps = isset($data['steps_goal']) ? max(0, min(300000, intval($data['steps_goal']))) : 0;
    $hs = isset($data['health_score_goal']) ? max(0, min(100, intval($data['health_score_goal']))) : 0;
    $pdo = getPDO();
    $meta = ensure_goals_table($pdo);
    $st = $pdo->prepare('INSERT INTO user_goals (user_id, water_goal, sleep_goal, steps_goal, health_score_goal) VALUES (?,?,?,?,?)
      ON DUPLICATE KEY UPDATE water_goal=VALUES(water_goal), sleep_goal=VALUES(sleep_goal), steps_goal=VALUES(steps_goal), health_score_goal=VALUES(health_score_goal)');
    $st->execute([$userId, $water, $sleep, $steps, $hs]);
    $resp = ['ok' => true, '_fk' => $meta['fk']];
    if (isset($meta['warn'])) $resp['warning'] = $meta['warn'];
    respond(200, $resp);
} catch (Throwable $e) {
    error_log('GOALS_SAVE_FAIL: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'server']);
}
