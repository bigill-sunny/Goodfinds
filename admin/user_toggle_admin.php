<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/users.php");
    exit;
}

$target_id        = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$current_admin_id = (int)$_SESSION['user_id'];

if (!$target_id || $target_id === $current_admin_id) {
    $_SESSION['flash_error'] = 'Invalid operation.';
    header("Location: " . BASE_URL . "/admin/users.php");
    exit;
}

$stmt = $conn->prepare("SELECT is_admin, name FROM users WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$target) {
    $_SESSION['flash_error'] = 'User not found.';
    header("Location: " . BASE_URL . "/admin/users.php");
    exit;
}

$new_role = $target['is_admin'] ? 0 : 1;

$upd_stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
$upd_stmt->bind_param("ii", $new_role, $target_id);
$upd_stmt->execute();
$upd_stmt->close();

$action = $new_role ? 'granted admin access' : 'revoked admin access';
$_SESSION['flash_success'] = htmlspecialchars($target['name']) . ' has been ' . $action . '.';
header("Location: " . BASE_URL . "/admin/users.php");
exit;