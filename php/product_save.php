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
$name        = trim($_POST['name']        ?? '');
$description = trim($_POST['description'] ?? '');
$price       = trim($_POST['price']       ?? '');
$stock       = trim($_POST['stock']       ?? '');
$category    = trim($_POST['category']    ?? '');
$condition   = trim($_POST['condition']   ?? '');

$allowed_categories = ['Electronics','Books','Vinyl & CDs','Clothing','Collectibles','Other'];
$allowed_conditions = ['New','Like New','Good','Fair','Poor'];

$errors = [];
if (strlen($name) < 3)                               $errors[] = 'Title must be at least 3 characters.';
if (strlen($description) < 10)                       $errors[] = 'Description must be at least 10 characters.';
if (!is_numeric($price) || (float)$price <= 0)       $errors[] = 'Price must be a positive number.';
if (!is_numeric($stock)  || (int)$stock < 0)         $errors[] = 'Quantity must be zero or more.';
if (!in_array($category, $allowed_categories))       $errors[] = 'Please select a valid category.';
if (!in_array($condition, $allowed_conditions))      $errors[] = 'Please select a valid condition.';

// ★ CHANGED: process multiple uploaded images into an array
$uploaded_files = [];   // will hold filenames of successfully saved images
$allowed_mime   = ['image/jpeg','image/png','image/webp','image/gif'];
$max_size       = 5 * 1024 * 1024;   // 5 MB per file

if (!empty($_FILES['product_images']['name'][0])) {
    $total = count($_FILES['product_images']['name']);

    for ($i = 0; $i < $total; $i++) {
        // Skip slots where no file was chosen
        if ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $mime     = $_FILES['product_images']['type'][$i];
        $size     = $_FILES['product_images']['size'][$i];
        $tmp      = $_FILES['product_images']['tmp_name'][$i];
        $origname = $_FILES['product_images']['name'][$i];

        if (!in_array($mime, $allowed_mime)) {
            $errors[] = '"' . htmlspecialchars($origname) . '" must be JPG, PNG, WebP or GIF.';
            continue;
        }
        if ($size > $max_size) {
            $errors[] = '"' . htmlspecialchars($origname) . '" must be under 5 MB.';
            continue;
        }

        $ext      = strtolower(pathinfo($origname, PATHINFO_EXTENSION));
        $filename = uniqid('img_', true) . '.' . $ext;
        $dest     = BASE_PATH . '/uploads/' . $filename;

        if (move_uploaded_file($tmp, $dest)) {
            $uploaded_files[] = $filename;   // ★ collected here
        } else {
            $errors[] = 'Upload failed for "' . htmlspecialchars($origname) . '". Please try again.';
        }
    }
}

if (!empty($errors)) {
    // Clean up any files we already moved before the error
    foreach ($uploaded_files as $f) {
        @unlink(BASE_PATH . '/uploads/' . $f);
    }
    $_SESSION['flash_error'] = implode(' ', $errors);
    $redirect = $product_id
        ? BASE_URL . '/seller/listings.php?edit=' . $product_id
        : BASE_URL . '/seller/listings.php?action=add';
    header("Location: " . $redirect);
    exit;
}

$price_val = (float)$price;
$stock_val = (int)$stock;

// ── UPDATE existing product ──────────────────────────────────────
if ($product_id) {
    $existing_stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ? AND seller_id = ?");
    $existing_stmt->bind_param("ii", $product_id, $user_id);
    $existing_stmt->execute();
    $existing = $existing_stmt->get_result()->fetch_assoc();
    $existing_stmt->close();

    if (!$existing) {
        $_SESSION['flash_error'] = 'Product not found or permission denied.';
        header("Location: " . BASE_URL . "/seller/listings.php");
        exit;
    }

    // If new images were uploaded, use first as the new primary; otherwise keep old one
    $final_image = !empty($uploaded_files) ? $uploaded_files[0] : $existing['image_url'];

    $upd_stmt = $conn->prepare("
        UPDATE products
        SET    name = ?, description = ?, price = ?, stock = ?,
               category = ?, `condition` = ?, image_url = ?
        WHERE  id = ? AND seller_id = ?
    ");
    $upd_stmt->bind_param(
        "ssdiissii",
        $name, $description, $price_val, $stock_val,
        $category, $condition, $final_image,
        $product_id, $user_id
    );
    $upd_stmt->execute();
    $upd_stmt->close();

    // ★ NEW: append each new image into product_images table
    if (!empty($uploaded_files)) {
        // Find the highest sort_order already used for this product
        $ord_stmt = $conn->prepare("
            SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_ord
            FROM   product_images
            WHERE  product_id = ?
        ");
        $ord_stmt->bind_param("i", $product_id);
        $ord_stmt->execute();
        $next_ord = (int)$ord_stmt->get_result()->fetch_assoc()['next_ord'];
        $ord_stmt->close();

        foreach ($uploaded_files as $idx => $fname) {
            $is_primary = 0;               // new uploads don't replace the old primary
            $sort_order = $next_ord + $idx;
            $pi_stmt = $conn->prepare("
                INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            $pi_stmt->bind_param("isii", $product_id, $fname, $is_primary, $sort_order);
            $pi_stmt->execute();
            $pi_stmt->close();
        }
    }

    $_SESSION['flash_success'] = 'Listing updated successfully.';

// ── INSERT new product ───────────────────────────────────────────
} else {
    // First uploaded image becomes the primary (stored in products.image_url too)
    $final_image = !empty($uploaded_files) ? $uploaded_files[0] : 'placeholder.webp';

    $ins_stmt = $conn->prepare("
        INSERT INTO products (seller_id, name, description, price, stock, category, `condition`, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins_stmt->bind_param(
        "issdiiss",
        $user_id, $name, $description, $price_val, $stock_val,
        $category, $condition, $final_image
    );
    $ins_stmt->execute();
    $new_id = $conn->insert_id;
    $ins_stmt->close();

    // ★ NEW: save every uploaded image into product_images
    foreach ($uploaded_files as $idx => $fname) {
        $is_primary = ($idx === 0) ? 1 : 0;   // first image = primary
        $pi_stmt = $conn->prepare("
            INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $pi_stmt->bind_param("isii", $new_id, $fname, $is_primary, $idx);
        $pi_stmt->execute();
        $pi_stmt->close();
    }

    $_SESSION['flash_success'] = 'Listing published successfully!';
}

header("Location: " . BASE_URL . "/seller/listings.php");
exit;
