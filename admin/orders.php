<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$filter_status = trim($_GET['status'] ?? '');
$allowed_statuses = ['Pending','Processing','Shipped','Delivered','Cancelled'];

$where  = '';
$params = [];
$types  = '';

if (in_array($filter_status, $allowed_statuses)) {
    $where  = 'WHERE o.status = ?';
    $params = [$filter_status];
    $types  = 's';
}

$sql = "
    SELECT o.id, o.total_price, o.status, o.order_date,
           o.shipping_name, o.shipping_email,
           o.shipping_city, o.shipping_province, o.shipping_postal,
           o.shipping_address,
           u.name AS buyer_name,
           COUNT(oi.id) AS item_count
    FROM   orders o
    JOIN   users u ON u.id = o.user_id
    LEFT   JOIN order_items oi ON oi.order_id = o.id
    $where
    GROUP  BY o.id
    ORDER  BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h4 fw-bold mb-0">All Orders</h1>
  <a href="<?= BASE_URL ?>/admin/dashboard.php"
     class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</div>

<div class="card border-0 shadow-sm mb-3 p-3">
  <form method="GET" action="" class="d-flex gap-2 align-items-end flex-wrap">
    <div>
      <label class="form-label small fw-semibold mb-1">Filter by Status</label>
      <select name="status" class="form-select form-select-sm" style="min-width:160px;">
        <option value="">All Statuses</option>
        <?php foreach ($allowed_statuses as $s): ?>
          <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>>
            <?= $s ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($filter_status): ?>
      <a href="<?= BASE_URL ?>/admin/orders.php"
         class="btn btn-outline-secondary btn-sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<p class="text-muted small mb-3">
  Showing <strong><?= count($orders) ?></strong> order<?= count($orders) != 1 ? 's' : '' ?>
  <?= $filter_status ? '— filtered by <strong>' . htmlspecialchars($filter_status) . '</strong>' : '' ?>
</p>

<?php if (empty($orders)): ?>
  <div class="text-center py-5 text-muted">
    <div class="fs-1">📋</div>
    <p class="mt-2">No orders found.</p>
  </div>
<?php else: ?>
  <div class="accordion" id="adminOrdersAccordion">
    <?php foreach ($orders as $i => $order):
      $badge = $status_colors[$order['status']] ?? 'secondary';

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
    ?>
      <div class="accordion-item border-0 shadow-sm mb-2 rounded-3 overflow-hidden">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#adminOrder<?= $order['id'] ?>"
                  aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
            <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-2">
              <span>
                #<?= (int)$order['id'] ?>
                <span class="text-muted fw-normal small ms-2">
                  <?= htmlspecialchars($order['buyer_name']) ?>
                </span>
              </span>
              <div class="d-flex align-items-center gap-3">
                <span class="text-success fw-bold">
                  $<?= number_format($order['total_price'], 2) ?>
                </span>
                <span class="badge bg-<?= $badge ?>">
                  <?= htmlspecialchars($order['status']) ?>
                </span>
                <span class="text-muted fw-normal small">
                  <?= date('M j, Y', strtotime($order['order_date'])) ?>
                </span>
              </div>
            </div>
          </button>
        </h2>

        <div id="adminOrder<?= $order['id'] ?>"
             class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
             data-bs-parent="#adminOrdersAccordion">
          <div class="accordion-body pt-0">

            <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                <p class="small mb-1 fw-semibold text-muted text-uppercase"
                   style="letter-spacing:.05em;">Shipping</p>
                <p class="small mb-0">
                  <?= htmlspecialchars($order['shipping_name']) ?><br>
                  <?= htmlspecialchars($order['shipping_address']) ?><br>
                  <?= htmlspecialchars($order['shipping_city']) ?>,
                  <?= htmlspecialchars($order['shipping_province']) ?>
                  <?= htmlspecialchars($order['shipping_postal']) ?><br>
                  <a href="mailto:<?= htmlspecialchars($order['shipping_email']) ?>"
                     class="text-muted">
                    <?= htmlspecialchars($order['shipping_email']) ?>
                  </a>
                </p>
              </div>

              <div class="col-12 col-md-6">
                <p class="small mb-1 fw-semibold text-muted text-uppercase"
                   style="letter-spacing:.05em;">Update Status</p>
                <form method="POST"
                      action="<?= BASE_URL ?>/admin/order_update.php"
                      class="d-flex gap-2">
                  <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                  <select name="status" class="form-select form-select-sm" style="max-width:160px;">
                    <?php foreach ($allowed_statuses as $s): ?>
                      <option value="<?= $s ?>"
                        <?= $order['status'] === $s ? 'selected' : '' ?>>
                        <?= $s ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </form>
              </div>
            </div>

            <div class="d-flex flex-column gap-2">
              <?php foreach ($order_items as $oi): ?>
                <div class="d-flex align-items-center gap-3">
                  <img
                    src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($oi['image_url'] ?? 'placeholder.webp') ?>"
                    alt="<?= htmlspecialchars($oi['name']) ?>"
                    class="rounded object-fit-cover flex-shrink-0"
                    style="width:48px;height:48px;"
                    width="48" height="48" loading="lazy">
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

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>