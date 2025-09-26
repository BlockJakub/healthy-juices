<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
try {
    $data = json_decode(file_get_contents('php://input'), true);
    $csrf = $data['csrf'] ?? '';
    if (!validate_csrf_token($csrf)) {
        http_response_code(403);
        echo json_encode(['error' => 'csrf']);
        exit;
    }
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = trim($data['password'] ?? '');
    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid']);
        exit;
    }
    $email = mb_strtolower($email);
    // Simple rate limiting per-session: allow 5 attempts per 5 minutes
    $now = time();
    if (empty($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
    // purge old
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function ($t) use ($now) {
        return ($now - $t) < 300;
    });
    if (count($_SESSION['login_attempts']) >= 5) {
        http_response_code(429);
        echo json_encode(['error' => 'too_many_attempts']);
        exit;
    }
    $pdo = getPDO();
    $st = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($password, $row['password_hash'])) {
        // lightweight logging for failed attempts
        error_log("Failed login for {$email}");
        $_SESSION['login_attempts'][] = $now;
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    $_SESSION['user_id'] = (int)$row['id'];
    session_regenerate_id(true);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server']);
}
