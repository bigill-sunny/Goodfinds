<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$platform = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM users)                            AS total_users,
        (SELECT COUNT(*) FROM users WHERE is_admin = 1)        AS total_admins,
        (SELECT COUNT(*) FROM products)                        AS total_listings,
        (SELECT COUNT(*) FROM products WHERE stock = 0)        AS sold_out,
        (SELECT COUNT(*) FROM orders)                          AS total_orders,
        (SELECT COALESCE(SUM(total_price),0) FROM orders)      AS total_revenue,
        (SELECT COUNT(*) FROM orders WHERE status='Pending')   AS pending_orders,
        (SELECT COUNT(*) FROM orders WHERE status='Shipped')   AS shipped_orders
")->fetch_assoc();

$recent_orders_stmt = $conn->prepare("
    SELECT o.id, o.total_price, o.status, o.order_date,
           o.shipping_name, o.shipping_city,
           u.name AS buyer_name, u.email AS buyer_email,
           COUNT(oi.id) AS item_count
    FROM   orders o
    JOIN   users u  ON u.id  = o.user_id
    LEFT   JOIN order_items oi ON oi.order_id = o.id
    GROUP  BY o.id
    ORDER  BY o.order_date DESC
    LIMIT  6
");
$recent_orders_stmt->execute();
$recent_orders = $recent_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_orders_stmt->close();

$recent_users_stmt = $conn->prepare("
    SELECT id, name, email, is_admin, created_at
    FROM   users
    ORDER  BY created_at DESC
    LIMIT  5
");
$recent_users_stmt->execute();
$recent_users = $recent_users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_users_stmt->close();

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
  <h1 class="h4 fw-bold mb-0">Admin Dashboard</h1>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline-primary btn-sm">
      Manage Orders
    </a>
    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary btn-sm">
      Manage Users
    </a>
  </div>
</div>

<div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-primary"><?= (int)$platform['total_users'] ?></div>
      <div class="small text-muted">Registered Users</div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-success"><?= (int)$platform['total_listings'] ?></div>
      <div class="small text-muted">Active Listings</div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-warning"><?= (int)$platform['total_orders'] ?></div>
      <div class="small text-muted">Total Orders</div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-success">
        $<?= number_format($platform['total_revenue'], 2) ?>
      </div>
      <div class="small text-muted">Platform Revenue</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-start border-warning border-3 shadow-sm p-3">
      <div class="fw-bold fs-4"><?= (int)$platform['pending_orders'] ?></div>
      <div class="small text-muted">Pending Orders</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-start border-primary border-3 shadow-sm p-3">
      <div class="fw-bold fs-4"><?= (int)$platform['shipped_orders'] ?></div>
      <div class="small text-muted">Shipped Orders</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-start border-danger border-3 shadow-sm p-3">
      <div class="fw-bold fs-4"><?= (int)$platform['sold_out'] ?></div>
      <div class="small text-muted">Sold Out Items</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-start border-secondary border-3 shadow-sm p-3">
      <div class="fw-bold fs-4"><?= (int)$platform['total_admins'] ?></div>
      <div class="small text-muted">Admin Accounts</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">Recent Orders</h2>
      <a href="<?= BASE_URL ?>/admin/orders.php"
         class="btn btn-outline-primary btn-sm">View All</a>
    </div>
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Buyer</th>
              <th>Items</th>
              <th class="text-end">Total</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_orders as $o):
              $badge = $status_colors[$o['status']] ?? 'secondary'; ?>
              <tr>
                <td class="small text-muted">#<?= (int)$o['id'] ?></td>
                <td>
                  <span class="fw-semibold small"><?= htmlspecialchars($o['buyer_name']) ?></span>
                  <br>
                  <span class="text-muted" style="font-size:.75rem;">
                    <?= htmlspecialchars($o['shipping_city']) ?>
                  </span>
                </td>
                <td class="small"><?= (int)$o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                <td class="text-end fw-semibold text-success small">
                  $<?= number_format($o['total_price'], 2) ?>
                </td>
                <td>
                  <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($o['status']) ?></span>
                </td>
                <td class="small text-muted">
                  <?= date('M j', strtotime($o['order_date'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">New Users</h2>
      <a href="<?= BASE_URL ?>/admin/users.php"
         class="btn btn-outline-secondary btn-sm">View All</a>
    </div>
    <div class="card border-0 shadow-sm">
      <ul class="list-group list-group-flush">
        <?php foreach ($recent_users as $u): ?>
          <li class="list-group-item px-3 py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="mb-0 fw-semibold small"><?= htmlspecialchars($u['name']) ?></p>
                <p class="mb-0 text-muted" style="font-size:.75rem;">
                  <?= htmlspecialchars($u['email']) ?>
                </p>
              </div>
              <?php if ($u['is_admin']): ?>
                <span class="badge bg-danger">Admin</span>
              <?php else: ?>
                <span class="badge bg-light text-dark border">User</span>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>