<?php
require_once __DIR__ . '/../db/db.php';

// ── Allowed values (whitelist to prevent injection on ORDER BY) ────
$allowed_sorts = ['newest', 'price_asc', 'price_desc'];
$allowed_conditions = ['New', 'Like New', 'Good', 'Fair', 'Poor'];
$allowed_categories = [
    'Electronics', 'Books', 'Vinyl & CDs',
    'Clothing', 'Collectibles', 'Other'
];

// ── Collect + sanitize GET filters ────────────────────────────────
$search    = trim($_GET['search']    ?? '');
$category  = trim($_GET['category']  ?? '');
$condition = trim($_GET['condition'] ?? '');
$min_price = trim($_GET['min_price'] ?? '');
$max_price = trim($_GET['max_price'] ?? '');
$sort      = in_array($_GET['sort'] ?? '', $allowed_sorts)
             ? $_GET['sort'] : 'newest';

// Validate whitelisted values
if (!in_array($category,  $allowed_categories))  $category  = '';
if (!in_array($condition, $allowed_conditions))  $condition = '';

// ── Build dynamic WHERE clause ────────────────────────────────────
$conditions = ['p.stock > 0'];
$params     = [];
$types      = '';

if ($search !== '') {
    $conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[]     = '%' . $search . '%';
    $params[]     = '%' . $search . '%';
    $types       .= 'ss';
}
if ($category !== '') {
    $conditions[] = 'p.category = ?';
    $params[]     = $category;
    $types       .= 's';
}
if ($condition !== '') {
    $conditions[] = 'p.`condition` = ?';
    $params[]     = $condition;
    $types       .= 's';
}
if (is_numeric($min_price)) {
    $conditions[] = 'p.price >= ?';
    $params[]     = (float)$min_price;
    $types       .= 'd';
}
if (is_numeric($max_price)) {
    $conditions[] = 'p.price <= ?';
    $params[]     = (float)$max_price;
    $types       .= 'd';
}

$where = 'WHERE ' . implode(' AND ', $conditions);

// ── Sort ──────────────────────────────────────────────────────────
$order = match($sort) {
    'price_asc'  => 'ORDER BY p.price ASC',
    'price_desc' => 'ORDER BY p.price DESC',
    default      => 'ORDER BY p.created_at DESC',
};

// ── Run query ─────────────────────────────────────────────────────
$sql = "
    SELECT p.id, p.name, p.price, p.image_url, p.category,
           p.`condition`, u.name AS seller_name
    FROM   products p
    JOIN   users u ON u.id = p.seller_id
    $where
    $order
";

$products = [];
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row g-4">

  <!-- ── FILTERS SIDEBAR ───────────────────────────────────────── -->
  <div class="col-12 col-md-3">
    <div class="card border-0 shadow-sm p-3 sticky-top" style="top:80px;">
      <h2 class="h6 fw-bold mb-3">Filter Listings</h2>

      <form method="GET" action="" id="filterForm">

        <!-- Search -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Search</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Keywords..."
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <!-- Category -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">All Categories</option>
            <?php foreach ($allowed_categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"
                <?= $category === $cat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Condition -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Condition</label>
          <select name="condition" class="form-select form-select-sm">
            <option value="">Any Condition</option>
            <?php foreach ($allowed_conditions as $cond): ?>
              <option value="<?= htmlspecialchars($cond) ?>"
                <?= $condition === $cond ? 'selected' : '' ?>>
                <?= htmlspecialchars($cond) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Price Range -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Price Range ($)</label>
          <div class="d-flex gap-2">
            <input type="number" name="min_price"
                   class="form-control form-control-sm"
                   placeholder="Min" min="0" step="0.01"
                   value="<?= htmlspecialchars($min_price) ?>">
            <input type="number" name="max_price"
                   class="form-control form-control-sm"
                   placeholder="Max" min="0" step="0.01"
                   value="<?= htmlspecialchars($max_price) ?>">
          </div>
        </div>

        <!-- Sort -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">Sort By</label>
          <select name="sort" class="form-select form-select-sm">
            <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>
              Newest First
            </option>
            <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>
              Price: Low to High
            </option>
            <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>
              Price: High to Low
            </option>
          </select>
        </div>

        <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">
          Apply Filters
        </button>
        <a href="<?= BASE_URL ?>/php/products.php"
           class="btn btn-outline-secondary btn-sm w-100 mt-2">
          Clear All
        </a>

      </form>
    </div>
  </div>

  <!-- ── PRODUCT GRID ───────────────────────────────────────────── -->
  <div class="col-12 col-md-9">

    <!-- Results count + active filters summary -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <p class="mb-0 text-muted small">
        <?= count($products) ?> listing<?= count($products) !== 1 ? 's' : '' ?> found
        <?= $category  ? " in <strong>" . htmlspecialchars($category)  . "</strong>" : '' ?>
        <?= $condition ? " · <strong>" . htmlspecialchars($condition) . "</strong>" : '' ?>
        <?= $search    ? " · matching <strong>\"" . htmlspecialchars($search) . "\"</strong>" : '' ?>
      </p>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= BASE_URL ?>/seller/dashboard.php"
           class="btn btn-success btn-sm">
          + List an Item
        </a>
      <?php endif; ?>
    </div>

    <!-- Empty state -->
    <?php if (empty($products)): ?>
      <div class="text-center py-5 text-muted">
        <div class="fs-1">🔍</div>
        <h3 class="h5 mt-3">No listings found</h3>
        <p>Try adjusting your filters or search term.</p>
        <a href="<?= BASE_URL ?>/php/products.php"
           class="btn btn-outline-success btn-sm">
          Clear Filters
        </a>
      </div>

    <?php else: ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
        <?php foreach ($products as $p): ?>
          <div class="col">
            <?php include BASE_PATH . '/includes/product_card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div><!-- /.row -->

<?php require_once BASE_PATH . '/includes/footer.php'; ?>