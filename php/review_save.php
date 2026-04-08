<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/php/products.php"); exit;
}

$user_id    = (int)$_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$rating     = filter_input(INPUT_POST, 'rating',     FILTER_VALIDATE_INT);
$body       = trim($_POST['body'] ?? '');
$redirect   = BASE_URL . '/php/product.php?id=' . (int)$product_id . '#reviews';

if (!$product_id || !$rating || $rating < 1 || $rating > 5) {
    $_SESSION['flash_error'] = 'Please select a star rating.';
    header("Location: " . $redirect); exit;
}

// Prevent seller from reviewing their own product
$sel = $conn->prepare("SELECT seller_id FROM products WHERE id = ?");
$sel->bind_param("i", $product_id);
$sel->execute();
$row = $sel->get_result()->fetch_assoc();
$sel->close();

if (!$row || (int)$row['seller_id'] === $user_id) {
    $_SESSION['flash_error'] = 'You cannot review your own listing.';
    header("Location: " . $redirect); exit;
}

// Insert or update (so users can edit their own review)
$ins = $conn->prepare("
    INSERT INTO reviews (product_id, user_id, rating, body)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating),
                            body = VALUES(body),
                            created_at = CURRENT_TIMESTAMP
");
$ins->bind_param("iiis", $product_id, $user_id, $rating, $body);
$ins->execute();
$ins->close();

$_SESSION['flash_success'] = 'Your review has been saved!';
header("Location: " . $redirect);
exit;