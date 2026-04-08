<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$orders_stmt = $conn->prepare("
    SELECT o.id, o.total_price, o.status, o.order_date,
           o.shipping_name, o.shipping_city, o.shipping_province,
           COUNT(oi.id) AS item_count
    FROM   orders o
    LEFT   JOIN order_items oi ON oi.order_id = o.id
    WHERE  o.user_id = ?
    GROUP  BY o.id
    ORDER  BY o.order_date DESC
");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

$status_colors = [
    'Pending'    => 'warning',
    'Processing' => 'info',
    'Shipped'    => 'primary',
    'Delivered'  => 'success',
    'Cancelled'  => 'danger',
];

require_once BASE_PATH . '/includes/header.php';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="row g-4">

  <div class="col-12 col-md-3">
    <div class="card border-0 shadow-sm p-3 text-center">
      <div class="fs-1 mb-2">👤</div>
      <h2 class="h6 fw-bold mb-0">
        <?= htmlspecialchars($_SESSION['user_name']) ?>
      </h2>
      <p class="text-muted small mb-3">Member account</p>
      <hr>
      <div class="d-grid gap-2">
        <a href="<?= BASE_URL ?>/php/products.php"
           class="btn btn-outline-success btn-sm">Browse Listings</a>
        <a href="<?= BASE_URL ?>/seller/dashboard.php"
           class="btn btn-outline-secondary btn-sm">Sell an Item</a>
        <a href="<?= BASE_URL ?>/php/cart.php"
           class="btn btn-outline-secondary btn-sm">View Cart</a>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-9">
    <h1 class="h4 fw-bold mb-4">My Orders</h1>

    <?php if (empty($orders)): ?>
      <div class="text-center py-5 text-muted">
        <div class="fs-1">📦</div>
        <h2 class="h5 mt-3">No orders yet</h2>
        <p>Find something you love and place your first order.</p>
        <a href="<?= BASE_URL ?>/php/products.php"
           class="btn btn-success">Browse Listings</a>
      </div>

    <?php else: ?>
      <div class="accordion" id="ordersAccordion">
        <?php foreach ($orders as $i => $order):
          $items_stmt = $conn->prepare("
              SELECT oi.quantity, oi.price_at_purchase,
                     p.name, p.image_url, p.id AS product_id
              FROM   order_items oi
              JOIN   products p ON p.id = oi.product_id
              WHERE  oi.order_id = ?
          ");
          $items_stmt->bind_param("i", $order['id']);
          $items_stmt->execute();
          $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
          $items_stmt->close();
          $badge = $status_colors[$order['status']] ?? 'secondary';
        ?>
          <div class="accordion-item border-0 shadow-sm mb-2 rounded-3 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#order<?= $order['id'] ?>"
                      aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-2">
                  <span>
                    Order #<?= (int)$order['id'] ?>
                    <span class="text-muted fw-normal small ms-2">
                      <?= date('M j, Y', strtotime($order['order_date'])) ?>
                    </span>
                  </span>
                  <div class="d-flex align-items-center gap-3">
                    <span class="text-success fw-bold">
                      $<?= number_format($order['total_price'], 2) ?>
                    </span>
                    <span class="badge bg-<?= $badge ?>">
                      <?= htmlspecialchars($order['status']) ?>
                    </span>
                  </div>
                </div>
              </button>
            </h2>

            <div id="order<?= $order['id'] ?>"
                 class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                 data-bs-parent="#ordersAccordion">
              <div class="accordion-body pt-0">

                <p class="text-muted small mb-3">
                  Shipping to: <?= htmlspecialchars($order['shipping_name']) ?>,
                  <?= htmlspecialchars($order['shipping_city']) ?>,
                  <?= htmlspecialchars($order['shipping_province']) ?>
                </p>

                <div class="d-flex flex-column gap-2">
                  <?php foreach ($order_items as $oi): ?>
                    <div class="d-flex align-items-center gap-3">
                      <img
                        src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($oi['image_url'] ?? 'placeholder.webp') ?>"
                        alt="<?= htmlspecialchars($oi['name']) ?>"
                        class="rounded object-fit-cover flex-shrink-0"
                        style="width:52px;height:52px;"
                        width="52" height="52" loading="lazy">
                      <div class="flex-grow-1">
                        <a href="<?= BASE_URL ?>/php/product.php?id=<?= (int)$oi['product_id'] ?>"
                           class="fw-semibold small text-dark text-decoration-none">
                          <?= htmlspecialchars($oi['name']) ?>
                        </a>
                        <p class="text-muted mb-0" style="font-size:.78rem;">
                          Qty: <?= (int)$oi['quantity'] ?> &times;
                          $<?= number_format($oi['price_at_purchase'], 2) ?>
                        </p>
                      </div>
                      <span class="fw-semibold small">
                        $<?= number_format($oi['price_at_purchase'] * $oi['quantity'], 2) ?>
                      </span>
                    </div>
                  <?php endforeach; ?>
                </div>

                <hr class="my-3">
                <div class="d-flex justify-content-end">
                  <span class="fw-bold">
                    Total: $<?= number_format($order['total_price'], 2) ?>
                  </span>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>