<?php
session_start();
$sessionFileDir = __DIR__ . '/sessions';
$sessionFile = $sessionFileDir . '/' . session_id();
$timeoutSeconds = 600; // 10 minutes

// --- Cleanup old session files ---
foreach (glob($sessionFileDir . '/*') as $file) {
    if (is_file($file) && filemtime($file) < time() - $timeoutSeconds) {
        unlink($file);
    }
}

// --- Check if session is valid ---
if (( isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeoutSeconds) ||
    (!isset($_SESSION['2fa_passed']) || $_SESSION['2fa_passed'] !== true) ) {
    session_unset();
    session_destroy();
    header("Location: /2fa/index.php");
    exit;
}
$_SESSION['last_activity'] = time();

// If passed 2FA, redirect to original request
// Mark session as authenticated
if (!file_exists($sessionFile)) {
    if (!is_dir($sessionFileDir)) {
        mkdir($sessionFileDir, 0700, true);
    }
    file_put_contents($sessionFile, 'ok');
}
$target = $_GET['redirect'] ?? '/application';

header("Location: " . $target);
exit;
