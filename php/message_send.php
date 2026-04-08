<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/php/messages.php"); exit;
}

$sender_id   = (int)$_SESSION['user_id'];
$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$product_id  = filter_input(INPUT_POST, 'product_id',  FILTER_VALIDATE_INT) ?: null;
$body        = trim($_POST['body'] ?? '');

$redirect = BASE_URL . '/php/messages.php?with=' . (int)$receiver_id
          . ($product_id ? '&product=' . $product_id : '');

if (!$receiver_id || $receiver_id === $sender_id || strlen($body) < 1) {
    $_SESSION['flash_error'] = 'Could not send message. Please try again.';
    header("Location: " . $redirect); exit;
}

if ($product_id) {
    $ins = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, body) VALUES (?,?,?,?)");
    $ins->bind_param("iiis", $sender_id, $receiver_id, $product_id, $body);
} else {
    $ins = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, body) VALUES (?,?,?)");
    $ins->bind_param("iis", $sender_id, $receiver_id, $body);
}
$ins->execute();
$ins->close();

header("Location: " . $redirect);
exit;