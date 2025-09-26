<?php
// Proper logout endpoint: require POST + valid CSRF, then destroy session and return JSON
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');

// Enforce method first (no prior output)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

// Parse JSON body safely
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$csrf = $data['csrf'] ?? '';
if (!validate_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

// Destroy session securely
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

echo json_encode(['ok' => true]);
