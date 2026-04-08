<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/php/cart.php");
    exit;
}

$cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
$user_id = (int)$_SESSION['user_id'];

if (!$cart_id) {
    header("Location: " . BASE_URL . "/php/cart.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$stmt->close();

$count_stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$row = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();
$_SESSION['cart_count'] = (int)($row['total'] ?? 0);

header("Location: " . BASE_URL . "/php/cart.php");
exit;