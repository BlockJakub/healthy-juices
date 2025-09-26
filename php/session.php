<?php

/**
 * File: session.php
 * Author: Healthy Blog Team
 * Created: 2025-09-25
 * Description: Secure session bootstrap + CSRF token generation & validation helpers.
 */
// Secure session start helper
function secure_session_start()
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Ensure a CSRF token exists for the session
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        } catch (Exception $e) {
            // fallback
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(24));
        }
    }
}

function validate_csrf_token($token)
{
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}
