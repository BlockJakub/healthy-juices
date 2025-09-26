<?php
require_once __DIR__ . '/config.php';

function getPDO()
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    // Use strict PDO options to avoid emulated prepares and enable exceptions
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $pdo;
    } catch (PDOException $e) {
        // Do not leak credentials or DSN in production errors
        error_log('Database connection error: ' . $e->getMessage());
        throw new RuntimeException('Database connection error');
    }
}
