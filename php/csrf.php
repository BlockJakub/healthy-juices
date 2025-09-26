<?php

/**
 * File: csrf.php
 * Author: Healthy Blog Team
 * Created: 2025-09-25
 * Description: Issues / returns CSRF token for the current session.
 * Notes: Token consumed by frontend and validated on all state-changing POST requests.
 */
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');

// Ensure CSRF token exists in session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

echo json_encode(['ok' => true, 'csrf_token' => $_SESSION['csrf_token']]);
