<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit;
}

$order_id        = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$status          = trim($_POST['status'] ?? '');
$allowed_statuses = ['Pending','Processing','Shipped','Delivered','Cancelled'];

if (!$order_id || !in_array($status, $allowed_statuses)) {
    $_SESSION['flash_error'] = 'Invalid order or status.';
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $order_id);
$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = 'Order #' . $order_id . ' updated to ' . $status . '.';
header("Location: " . BASE_URL . "/admin/orders.php");
exit;