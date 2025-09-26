<?php
// Diagnostic page to show resolved links and file existence for debugging 404s
header('Content-Type: text/plain');
$base = __DIR__;
$files = [
    'login' => $base . '/login.html',
    'register' => $base . '/register.html',
    'whoami' => $base . '/whoami.php',
    'logout' => $base . '/logout.php',
];

echo "Project PHP dir: " . realpath($base) . "\n\n";
foreach ($files as $k => $p) {
    echo strtoupper($k) . ":\n";
    echo "  expected path: " . $p . "\n";
    echo "  exists: " . (file_exists($p) ? 'yes' : 'no') . "\n";
    if (file_exists($p)) echo "  size: " . filesize($p) . " bytes\n";
    echo "\n";
}

// Also print a couple of helpful URLs assuming this file is reachable under /php/test_links.php
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$baseUrl = "{$proto}://{$host}{$uri}";

echo "Resolved base URL: {$baseUrl}\n";
echo "Login URL: {$baseUrl}/login.html\n";
echo "Register URL: {$baseUrl}/register.html\n";
echo "Whoami URL: {$baseUrl}/whoami.php\n";
