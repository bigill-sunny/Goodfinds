<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/seller/listings.php");
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$product_id) {
    header("Location: " . BASE_URL . "/seller/listings.php");
    exit;
}

$img_stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ? AND seller_id = ?");
$img_stmt->bind_param("ii", $product_id, $user_id);
$img_stmt->execute();
$product = $img_stmt->get_result()->fetch_assoc();
$img_stmt->close();

if (!$product) {
    $_SESSION['flash_error'] = 'Product not found or permission denied.';
    header("Location: " . BASE_URL . "/seller/listings.php");
    exit;
}

$del_stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
$del_stmt->bind_param("ii", $product_id, $user_id);
$del_stmt->execute();
$del_stmt->close();

if (
    !empty($product['image_url']) &&
    $product['image_url'] !== 'placeholder.webp' &&
    file_exists(BASE_PATH . '/uploads/' . $product['image_url'])
) {
    unlink(BASE_PATH . '/uploads/' . $product['image_url']);
}

$_SESSION['flash_success'] = 'Listing deleted.';
header("Location: " . BASE_URL . "/seller/listings.php");
exit;