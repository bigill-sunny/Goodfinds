<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.id AS cart_id, c.quantity,
           p.id AS product_id, p.name, p.price,
           p.image_url, p.stock, p.`condition`
    FROM   cart c
    JOIN   products p ON p.id = c.product_id
    WHERE  c.user_id = ?
    ORDER  BY c.id ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row g-4">

  <div class="col-12 col-lg-8">
    <h1 class="h4 fw-bold mb-4">Your Cart</h1>

    <?php if (empty($items)): ?>
      <div class="text-center py-5 text-muted">
        <div class="fs-1">🛒</div>
        <h2 class="h5 mt-3">Your cart is empty</h2>
        <p>Browse listings and add items to get started.</p>
        <a href="<?= BASE_URL ?>/php/products.php"
           class="btn btn-success">Browse Listings</a>
      </div>

    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($items as $item): ?>
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="row align-items-center g-3">

                <div class="col-3 col-md-2">
                  <img
                    src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($item['image_url'] ?? 'placeholder.webp') ?>"
                    alt="<?= htmlspecialchars($item['name']) ?>"
                    class="img-fluid rounded object-fit-cover"
                    style="height:70px;width:100%;"
                    width="80" height="70" loading="lazy">
                </div>

                <div class="col-9 col-md-5">
                  <a href="<?= BASE_URL ?>/php/product.php?id=<?= (int)$item['product_id'] ?>"
                     class="fw-semibold text-dark text-decoration-none">
                    <?= htmlspecialchars($item['name']) ?>
                  </a>
                  <p class="text-muted small mb-0">
                    <?= htmlspecialchars($item['condition']) ?>
                  </p>
                </div>

                <div class="col-6 col-md-3 text-md-center">
                  <span class="text-success fw-bold">
                    $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                  </span>
                  <p class="text-muted small mb-0">
                    $<?= number_format($item['price'], 2) ?> &times; <?= (int)$item['quantity'] ?>
                  </p>
                </div>

                <div class="col-6 col-md-2 text-end">
                  <form method="POST" action="<?= BASE_URL ?>/php/cart_remove.php">
                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                    <button type="submit"
                            class="btn btn-outline-danger btn-sm"
                            aria-label="Remove <?= htmlspecialchars($item['name']) ?>">
                      Remove
                    </button>
                  </form>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($items)): ?>
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
      <div class="card-body p-4">
        <h2 class="h5 fw-bold mb-3">Order Summary</h2>

        <?php foreach ($items as $item): ?>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span><?= htmlspecialchars($item['name']) ?> &times; <?= (int)$item['quantity'] ?></span>
            <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
          </div>
        <?php endforeach; ?>

        <hr>

        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>Total</span>
          <span class="text-success">$<?= number_format($total, 2) ?></span>
        </div>

        <a href="<?= BASE_URL ?>/php/checkout.php"
           class="btn btn-success w-100 fw-semibold mt-3">
          Proceed to Checkout
        </a>
        <a href="<?= BASE_URL ?>/php/products.php"
           class="btn btn-outline-secondary w-100 mt-2">
          Continue Shopping
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>