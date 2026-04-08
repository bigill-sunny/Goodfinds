<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/php/checkout.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$ship_name     = trim($_POST['shipping_name']     ?? '');
$ship_email    = trim($_POST['shipping_email']    ?? '');
$ship_address  = trim($_POST['shipping_address']  ?? '');
$ship_city     = trim($_POST['shipping_city']     ?? '');
$ship_province = trim($_POST['shipping_province'] ?? '');
$ship_postal   = trim($_POST['shipping_postal']   ?? '');

$valid_provinces = ['AB','BC','MB','NB','NL','NS','NT','NU','ON','PE','QC','SK','YT'];

if (
    strlen($ship_name)    < 2 ||
    !filter_var($ship_email, FILTER_VALIDATE_EMAIL) ||
    empty($ship_address)  ||
    empty($ship_city)     ||
    !in_array($ship_province, $valid_provinces) ||
    empty($ship_postal)
) {
    $_SESSION['flash_error'] = 'Please fill in all shipping fields correctly.';
    header("Location: " . BASE_URL . "/php/checkout.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT c.quantity,
           p.id AS product_id, p.name, p.price, p.stock
    FROM   cart c
    JOIN   products p ON p.id = c.product_id
    WHERE  c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) {
    header("Location: " . BASE_URL . "/php/cart.php");
    exit;
}

foreach ($items as $item) {
    if ($item['quantity'] > $item['stock']) {
        $_SESSION['flash_error'] = '"' . htmlspecialchars($item['name']) . '" no longer has enough stock.';
        header("Location: " . BASE_URL . "/php/cart.php");
        exit;
    }
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

$conn->begin_transaction();

try {
    $order_stmt = $conn->prepare("
        INSERT INTO orders
            (user_id, total_price, status,
             shipping_name, shipping_email, shipping_address,
             shipping_city, shipping_province, shipping_postal)
        VALUES (?, ?, 'Pending', ?, ?, ?, ?, ?, ?)
    ");
    $order_stmt->bind_param(
        "idssssss",
        $user_id, $total,
        $ship_name, $ship_email, $ship_address,
        $ship_city, $ship_province, $ship_postal
    );
    $order_stmt->execute();
    $order_id = $conn->insert_id;
    $order_stmt->close();

    $item_stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
        VALUES (?, ?, ?, ?)
    ");
    $stock_stmt = $conn->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ?
    ");

    foreach ($items as $item) {
        $item_stmt->bind_param(
            "iiid",
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        );
        $item_stmt->execute();

        $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stock_stmt->execute();
    }

    $item_stmt->close();
    $stock_stmt->close();

    $del_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $del_stmt->bind_param("i", $user_id);
    $del_stmt->execute();
    $del_stmt->close();

    $conn->commit();

    $_SESSION['cart_count'] = 0;
    $_SESSION['flash_success'] = 'Order #' . $order_id . ' placed successfully!';
    header("Location: " . BASE_URL . "/php/dashboard.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_error'] = 'Order failed. Please try again.';
    header("Location: " . BASE_URL . "/php/checkout.php");
    exit;
}