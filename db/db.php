<?php
// ─── Path Constants (used by header.php for links + require_once) ───
define('BASE_URL',  '/Goodfinds');
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/Goodfinds');

// ─── Database Credentials ────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'goodfinds_db');

// ─── Database Connection ─────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>