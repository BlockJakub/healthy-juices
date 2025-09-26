<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$csrf = $data['csrf'] ?? '';
if (!validate_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$email = $email ? mb_strtolower($email) : $email;
$password = trim($data['password'] ?? '');
// minimal password strength: at least 6 chars; recommend stronger policies in production
if (!$email || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid']);
    exit;
}

try {
    try {
        $pdo = getPDO();
    } catch (Throwable $e) {
        error_log('Register DB connect error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'db']);
        exit;
    }
    // Use transaction to avoid race conditions
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ? FOR UPDATE');
    $st->execute([$email]);
    if ($st->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'exists']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO users (email, password_hash, created_at) VALUES (?, ?, NOW())');
    $st->execute([$email, $hash]);
    $userId = (int)$pdo->lastInsertId();
    $pdo->commit();
    // Log user in
    $_SESSION['user_id'] = $userId;
    session_regenerate_id(true);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if (!empty($pdo) && $pdo instanceof PDO) {
        try {
            if ($pdo->inTransaction()) $pdo->rollBack();
        } catch (Throwable $_) {
        }
    }
    error_log('Register error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server']);
}
