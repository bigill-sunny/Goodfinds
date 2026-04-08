<?php
require_once __DIR__ . '/../db/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Destroy session completely
$_SESSION = [];
session_destroy();

header("Location: " . BASE_URL . "/index.php");
exit;
?>