<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stats_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_listings,
           SUM(stock) AS total_stock,
           SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS sold_out
    FROM   products
    WHERE  seller_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$earnings_stmt = $conn->prepare("
    SELECT COALESCE(SUM(oi.quantity * oi.price_at_purchase), 0) AS total_earned
    FROM   order_items oi
    JOIN   products p ON p.id = oi.product_id
    WHERE  p.seller_id = ?
");
$earnings_stmt->bind_param("i", $user_id);
$earnings_stmt->execute();
$earnings = $earnings_stmt->get_result()->fetch_assoc();
$earnings_stmt->close();

$recent_stmt = $conn->prepare("
    SELECT id, name, price, stock, category, `condition`, image_url
    FROM   products
    WHERE  seller_id = ?
    ORDER  BY created_at DESC
    LIMIT  4
");
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_stmt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash_error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h4 fw-bold mb-0">Seller Dashboard</h1>
  <a href="<?= BASE_URL ?>/seller/listings.php?action=add"
     class="btn btn-success fw-semibold">
    + List New Item
  </a>
</div>

<div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-success"><?= (int)$stats['total_listings'] ?></div>
      <div class="small text-muted">Total Listings</div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-primary"><?= (int)$stats['total_stock'] ?></div>
      <div class="small text-muted">Items in Stock</div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-warning"><?= (int)$stats['sold_out'] ?></div>
      <div class="small text-muted">Sold Out</div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm text-center p-3">
      <div class="fs-2 fw-bold text-success">
        $<?= number_format($earnings['total_earned'], 2) ?>
      </div>
      <div class="small text-muted">Total Earned</div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 fw-bold mb-0">Recent Listings</h2>
  <a href="<?= BASE_URL ?>/seller/listings.php" class="btn btn-outline-secondary btn-sm">
    Manage All &rarr;
  </a>
</div>

<?php if (empty($recent)): ?>
  <div class="text-center py-5 text-muted">
    <div class="fs-1">📦</div>
    <p class="mt-2">You have no listings yet.</p>
    <a href="<?= BASE_URL ?>/seller/listings.php?action=add"
       class="btn btn-success">Add Your First Item</a>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
    <?php foreach ($recent as $p): ?>
      <div class="col">
        <div class="card h-100 border-0 shadow-sm">
          <img
            src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($p['image_url'] ?? 'placeholder.webp') ?>"
            alt="<?= htmlspecialchars($p['name']) ?>"
            class="card-img-top object-fit-cover"
            style="height:140px;"
            width="300" height="140" loading="lazy">
          <div class="card-body p-3">
            <p class="fw-semibold mb-1 small"><?= htmlspecialchars($p['name']) ?></p>
            <p class="text-success fw-bold mb-1">$<?= number_format($p['price'], 2) ?></p>
            <p class="text-muted mb-2" style="font-size:.75rem;">
              Stock: <?= (int)$p['stock'] ?>
            </p>
            <a href="<?= BASE_URL ?>/seller/listings.php?edit=<?= (int)$p['id'] ?>"
               class="btn btn-outline-primary btn-sm w-100">Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>