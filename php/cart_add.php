<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/php/products.php");
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity   = filter_input(INPUT_POST, 'quantity',   FILTER_VALIDATE_INT);
$user_id    = (int)$_SESSION['user_id'];

if (!$product_id || !$quantity || $quantity < 1) {
    header("Location: " . BASE_URL . "/php/products.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, stock, seller_id FROM products WHERE id = ? AND stock > 0");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product || $product['seller_id'] === $user_id) {
    header("Location: " . BASE_URL . "/php/product.php?id=" . $product_id);
    exit;
}

$quantity = min($quantity, $product['stock']);

$stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $new_qty = min($existing['quantity'] + $quantity, $product['stock']);
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_qty, $existing['id']);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
    $stmt->execute();
    $stmt->close();
}

$count_stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$row = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();
$_SESSION['cart_count'] = (int)($row['total'] ?? 0);

header("Location: " . BASE_URL . "/php/cart.php");
exit;