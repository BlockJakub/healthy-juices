<?php
// Development-only helper: shows current session id, session CSRF token, and cookies
// WARNING: This file leaks session information and must NOT be used in production.
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');

$out = [
    'ok' => true,
    'session_id' => session_id(),
    'session_csrf' => ($_SESSION['csrf_token'] ?? null),
    'cookies' => $_COOKIE,
    'server_time' => date('c')
];

echo json_encode($out, JSON_PRETTY_PRINT);
