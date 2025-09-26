<?php
// Persist a BMI calculation (one per date per user). Upsert by (user_id, entry_date).
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
if (!$raw || strlen($raw) > 2000) {
    respond(400, ['ok' => false, 'error' => 'bad_body']);
}
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, ['ok' => false, 'error' => 'json']);
}
$csrf = $data['csrf'] ?? '';
if (!validate_csrf_token($csrf)) {
    respond(403, ['ok' => false, 'error' => 'csrf']);
}

$userId = (int)$_SESSION['user_id'];
$today = date('Y-m-d');
// Allow optional 'date' if the user wants historical entry later; constrain to <= today.
$date = $today;
if (!empty($data['date'])) {
    $d = trim((string)$data['date']);
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    if ($dt && $dt->format('Y-m-d') === $d && $d <= $today) {
        $date = $d;
    }
}

$weight = isset($data['weight']) ? floatval($data['weight']) : 0.0; // kg
$heightCm = isset($data['height']) ? floatval($data['height']) : 0.0; // cm
if ($weight < 20) $weight = 20;
elseif ($weight > 500) $weight = 500; // sanity bounds
if ($heightCm < 50) $heightCm = 50;
elseif ($heightCm > 250) $heightCm = 250;
$hM = $heightCm / 100.0;
if ($hM <= 0) {
    respond(400, ['ok' => false, 'error' => 'height']);
}
$bmi = $weight / ($hM * $hM);
$bmi = round($bmi, 2);
if (!is_finite($bmi) || $bmi <= 0) {
    respond(400, ['ok' => false, 'error' => 'calc']);
}

function ensure_bmi_table($pdo)
{
    try {
        $q = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='user_bmi'");
        if ($q && $q->fetchColumn()) return ['exists' => true, 'fk' => null, 'created' => false];
    } catch (Throwable $e) {
    }
    $ddl = "CREATE TABLE user_bmi (
        user_id INT NOT NULL,
        entry_date DATE NOT NULL,
        weight_kg DECIMAL(6,2) NOT NULL,
        height_cm DECIMAL(6,2) NOT NULL,
        bmi DECIMAL(5,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id, entry_date),
        CONSTRAINT fk_user_bmi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $pdo->exec($ddl);
        return ['exists' => true, 'fk' => true, 'created' => true];
    } catch (Throwable $e) {
        error_log('BMI_TABLE_FK_FAIL: ' . $e->getMessage());
        $ddl2 = "CREATE TABLE user_bmi (
            user_id INT NOT NULL,
            entry_date DATE NOT NULL,
            weight_kg DECIMAL(6,2) NOT NULL,
            height_cm DECIMAL(6,2) NOT NULL,
            bmi DECIMAL(5,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(user_id, entry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($ddl2);
        return ['exists' => true, 'fk' => false, 'created' => true, 'warn' => 'fk_removed'];
    }
}

try {
    $pdo = getPDO();
    $meta = ensure_bmi_table($pdo);
    $st = $pdo->prepare('INSERT INTO user_bmi (user_id, entry_date, weight_kg, height_cm, bmi) VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg), height_cm=VALUES(height_cm), bmi=VALUES(bmi), created_at=NOW()');
    $st->execute([$userId, $date, $weight, $heightCm, $bmi]);
    respond(200, [
        'ok' => true,
        'date' => $date,
        'bmi' => $bmi,
        'weight' => $weight,
        'height_cm' => $heightCm,
        '_fk' => $meta['fk'] ?? null,
        'warning' => $meta['warn'] ?? null,
        'date_formatted' => (new DateTime($date))->format('d/m/Y') . ' 00:00:00'
    ]);
} catch (Throwable $e) {
    error_log('BMI_SAVE_FAIL: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'server']);
}
