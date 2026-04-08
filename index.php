<?php
require_once __DIR__ . '/db/db.php';

// Fetch 8 most recent products for featured section
$featured = [];
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image_url, p.category, p.`condition`,
           u.name AS seller_name
    FROM   products p
    JOIN   users u ON u.id = p.seller_id
    WHERE  p.stock > 0
    ORDER  BY p.created_at DESC
    LIMIT  8
");
$stmt->execute();
$featured = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<!-- ── HERO ─────────────────────────────────────────────────────── -->
<section class="py-5 rounded-3 mb-4 text-white"
         style="background: linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0f3460 100%);">
  <div class="container py-3 text-center">
    <h1 class="fw-bold mb-2" style="font-size:clamp(1.8rem,4vw,3rem);">
      Find Great Deals on Second-Hand Goods
    </h1>
    <p class="text-white-50 mb-4" style="font-size:1.1rem;">
      Electronics, books, vinyl, clothing, collectibles and more —
      all from trusted sellers.
    </p>

    <!-- Search bar -->
    <form method="GET" action="<?= BASE_URL ?>/php/products.php"
          class="d-flex justify-content-center gap-2 flex-wrap">
      <input
        type="text"
        name="search"
        class="form-control w-auto flex-grow-1"
        style="max-width:420px;"
        placeholder="Search for anything..."
        aria-label="Search products">
      <button type="submit" class="btn btn-success px-4 fw-semibold">
        Search
      </button>
    </form>
  </div>
</section>

<!-- ── CATEGORIES ────────────────────────────────────────────────── -->
<section class="mb-5">
  <h2 class="h5 fw-bold mb-3">Browse by Category</h2>
  <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-2">
    <?php
    $categories = [
      ['label' => 'Electronics',   'icon' => '💻', 'slug' => 'Electronics'],
      ['label' => 'Books',         'icon' => '📚', 'slug' => 'Books'],
      ['label' => 'Vinyl & CDs',   'icon' => '🎵', 'slug' => 'Vinyl & CDs'],
      ['label' => 'Clothing',      'icon' => '👗', 'slug' => 'Clothing'],
      ['label' => 'Collectibles',  'icon' => '🏆', 'slug' => 'Collectibles'],
      ['label' => 'Other',         'icon' => '📦', 'slug' => 'Other'],
    ];
    foreach ($categories as $cat): ?>
      <div class="col">
        <a href="<?= BASE_URL ?>/php/products.php?category=<?= urlencode($cat['slug']) ?>"
           class="card h-100 text-center text-decoration-none border-0 shadow-sm
                  category-card py-3">
          <div class="fs-2"><?= $cat['icon'] ?></div>
          <div class="small fw-semibold text-dark mt-1"><?= $cat['label'] ?></div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── FEATURED LISTINGS ─────────────────────────────────────────── -->
<section class="mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 fw-bold mb-0">Recent Listings</h2>
    <a href="<?= BASE_URL ?>/php/products.php"
       class="btn btn-outline-success btn-sm">
      View All &rarr;
    </a>
  </div>

  <?php if (empty($featured)): ?>
    <div class="text-center py-5 text-muted">
      <div class="fs-1">🛍️</div>
      <p class="mt-2">No listings yet — be the first to sell something!</p>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= BASE_URL ?>/seller/dashboard.php"
           class="btn btn-success">List an Item</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/php/register.php"
           class="btn btn-success">Get Started</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
      <?php foreach ($featured as $p): ?>
        <div class="col">
          <?php include BASE_PATH . '/includes/product_card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ── SELL CTA ──────────────────────────────────────────────────── -->
<?php if (!isset($_SESSION['user_id'])): ?>
<section class="bg-success bg-opacity-10 border border-success
                rounded-3 p-4 text-center mb-4">
  <h3 class="h5 fw-bold text-success mb-2">Have something to sell?</h3>
  <p class="text-muted mb-3">
    List your items for free and reach buyers looking for exactly what you have.
  </p>
  <a href="<?= BASE_URL ?>/php/register.php"
     class="btn btn-success px-4">
    Start Selling Today
  </a>
</section>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>