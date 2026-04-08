<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: " . BASE_URL . "/php/products.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT p.*, u.name AS seller_name, u.id AS seller_id,
           u.created_at AS seller_since
    FROM   products p
    JOIN   users u ON u.id = p.seller_id
    WHERE  p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: " . BASE_URL . "/php/products.php");
    exit;
}

$img_stmt = $conn->prepare("
    SELECT image_path, is_primary
    FROM   product_images
    WHERE  product_id = ?
    ORDER  BY is_primary DESC, sort_order ASC
");
$img_stmt->bind_param("i", $id);
$img_stmt->execute();
$images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$img_stmt->close();

if (empty($images) && !empty($product['image_url'])) {
    $images = [['image_path' => $product['image_url'], 'is_primary' => 1]];
}

$rel_stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image_url, p.category,
           p.`condition`, u.name AS seller_name
    FROM   products p
    JOIN   users u ON u.id = p.seller_id
    WHERE  p.category = ? AND p.id != ? AND p.stock > 0
    ORDER  BY p.created_at DESC
    LIMIT  4
");
$rel_stmt->bind_param("si", $product['category'], $id);
$rel_stmt->execute();
$related = $rel_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rel_stmt->close();

// ── BONUS C: fetch reviews + average rating ──────────────────────
$rev_stmt = $conn->prepare("
    SELECT r.*, u.name AS reviewer_name
    FROM   reviews r
    JOIN   users u ON u.id = r.user_id
    WHERE  r.product_id = ?
    ORDER  BY r.created_at DESC
");
$rev_stmt->bind_param("i", $id);
$rev_stmt->execute();
$reviews = $rev_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rev_stmt->close();

$review_count = count($reviews);
$avg_rating   = $review_count
    ? round(array_sum(array_column($reviews, 'rating')) / $review_count, 1)
    : null;

// Check if the logged-in user already reviewed this product
$user_review = null;
$is_seller   = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$product['seller_id'];
if (isset($_SESSION['user_id'])) {
    foreach ($reviews as $rv) {
        if ((int)$rv['user_id'] === (int)$_SESSION['user_id']) {
            $user_review = $rv;
            break;
        }
    }
}

// Flash messages (from review_save.php redirect)
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$condition_colors = [
    'New'      => 'success',
    'Like New' => 'primary',
    'Good'     => 'secondary',
    'Fair'     => 'warning',
    'Poor'     => 'danger',
];
$badge = $condition_colors[$product['condition']] ?? 'secondary';

require_once BASE_PATH . '/includes/header.php';
?>

<?php if ($flash_success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash_success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash_error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">
      <a href="<?= BASE_URL ?>/index.php" class="text-success">Home</a>
    </li>
    <li class="breadcrumb-item">
      <a href="<?= BASE_URL ?>/php/products.php?category=<?= urlencode($product['category']) ?>"
         class="text-success">
        <?= htmlspecialchars($product['category']) ?>
      </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <?= htmlspecialchars($product['name']) ?>
    </li>
  </ol>
</nav>

<div class="row g-4 mb-5">

  <div class="col-12 col-md-6">
    <img
      src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($images[0]['image_path'] ?? 'placeholder.webp') ?>"
      alt="<?= htmlspecialchars($product['name']) ?>"
      class="img-fluid rounded-3 shadow-sm w-100 object-fit-cover mb-2"
      id="mainImage"
      style="height:380px;"
      width="600" height="380" loading="lazy">

    <?php if (count($images) > 1): ?>
      <div class="d-flex gap-2 flex-wrap mt-2">
        <?php foreach ($images as $img): ?>
          <img
            src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($img['image_path']) ?>"
            alt="<?= htmlspecialchars($product['name']) ?>"
            class="rounded border thumbnail-img"
            style="width:72px;height:72px;object-fit:cover;cursor:pointer;
                   opacity:<?= $img['is_primary'] ? '1' : '0.6' ?>;"
            width="72" height="72" loading="lazy">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-12 col-md-6">
    <span class="badge bg-<?= $badge ?> mb-2">
      <?= htmlspecialchars($product['condition']) ?>
    </span>
    <span class="badge bg-light text-dark border ms-1 mb-2">
      <?= htmlspecialchars($product['category']) ?>
    </span>

    <h1 class="h3 fw-bold mt-1 mb-1">
      <?= htmlspecialchars($product['name']) ?>
    </h1>

    <!-- ── BONUS C: average star rating near price ──────────────── -->
    <?php if ($avg_rating): ?>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="text-warning fw-bold"><?= $avg_rating ?> ★</span>
        <span class="text-muted small">(<?= $review_count ?> review<?= $review_count !== 1 ? 's' : '' ?>)</span>
      </div>
    <?php endif; ?>

    <p class="text-success fw-bold mb-3" style="font-size:1.8rem;">
      $<?= number_format($product['price'], 2) ?>
    </p>

    <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

    <div class="mb-3 small text-muted">
      <span class="me-3">
        Seller: <strong><?= htmlspecialchars($product['seller_name']) ?></strong>
      </span>
      <span>
        Listed: <strong><?= date('M j, Y', strtotime($product['created_at'])) ?></strong>
      </span>
    </div>

    <?php if ($product['stock'] > 0): ?>
      <p class="text-success small mb-3">
        ✓ In stock (<?= (int)$product['stock'] ?> available)
      </p>

      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $product['seller_id']): ?>
        <form method="POST" action="<?= BASE_URL ?>/php/cart_add.php">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <div class="d-flex gap-2 align-items-center mb-3">
            <label for="quantity" class="form-label fw-semibold mb-0">Qty:</label>
            <input type="number" id="quantity" name="quantity"
                   class="form-control form-control-sm"
                   style="width:80px;"
                   value="1" min="1"
                   max="<?= (int)$product['stock'] ?>">
          </div>
          <button type="submit" class="btn btn-success px-4 fw-semibold w-100">
            Add to Cart
          </button>
        </form>

        <!-- ── BONUS B: Message Seller button ─────────────────── -->
        <a href="<?= BASE_URL ?>/php/messages.php?with=<?= (int)$product['seller_id'] ?>&product=<?= (int)$product['id'] ?>"
           class="btn btn-outline-primary w-100 mt-2 fw-semibold">
          ✉ Message Seller
        </a>

      <?php elseif (!isset($_SESSION['user_id'])): ?>
        <a href="<?= BASE_URL ?>/php/login.php"
           class="btn btn-success w-100 fw-semibold">
          Log in to Add to Cart
        </a>

      <?php else: ?>
        <p class="text-muted fst-italic">This is your own listing.</p>
      <?php endif; ?>

    <?php else: ?>
      <button class="btn btn-secondary w-100" disabled>Out of Stock</button>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/php/products.php"
       class="btn btn-outline-secondary w-100 mt-2">
      &larr; Back to Listings
    </a>
  </div>
</div>

<?php if (!empty($related)): ?>
  <section class="mb-4">
    <h2 class="h5 fw-bold mb-3">More in <?= htmlspecialchars($product['category']) ?></h2>
    <div class="row row-cols-2 row-cols-md-4 g-3">
      <?php foreach ($related as $p): ?>
        <div class="col">
          <?php include BASE_PATH . '/includes/product_card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<!-- ══ BONUS C: Reviews Section ════════════════════════════════ -->
<section id="reviews" class="border-top pt-4 mb-5">
  <h2 class="h5 fw-bold mb-3">
    Customer Reviews
    <?php if ($avg_rating): ?>
      <span class="text-muted fw-normal fs-6">· <?= $avg_rating ?> / 5</span>
    <?php endif; ?>
  </h2>

  <!-- Review form: shown to logged-in users who are NOT the seller -->
  <?php if (isset($_SESSION['user_id']) && !$is_seller): ?>
    <div class="card border-0 bg-light p-4 mb-4">
      <h3 class="h6 fw-semibold mb-3">
        <?= $user_review ? 'Update Your Review' : 'Leave a Review' ?>
      </h3>
      <form method="POST" action="<?= BASE_URL ?>/php/review_save.php" id="reviewForm">
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

        <!-- Star picker -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
          <div class="d-flex gap-1" id="starPicker">
            <?php for ($i = 1; $i <= 5; $i++):
                $selected = $user_review && (int)$user_review['rating'] === $i;
            ?>
              <span class="star-btn fs-4"
                    data-value="<?= $i ?>"
                    style="cursor:pointer; color:<?= $selected ? '#ffc107' : '#ccc' ?>;">
                ★
              </span>
            <?php endfor; ?>
          </div>
          <!-- Hidden input that actually gets submitted -->
          <input type="hidden" name="rating" id="ratingInput"
                 value="<?= htmlspecialchars($user_review['rating'] ?? '') ?>">
          <div class="text-danger small mt-1" id="ratingError" style="display:none;">
            Please click a star to rate this product.
          </div>
        </div>

        <div class="mb-3">
          <label for="reviewBody" class="form-label fw-semibold">
            Comment <span class="text-muted fw-normal">(optional)</span>
          </label>
          <textarea id="reviewBody" name="body" class="form-control" rows="3"
                    placeholder="What did you think of this item?"
                    maxlength="1000"><?= htmlspecialchars($user_review['body'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-success fw-semibold">
          <?= $user_review ? 'Update Review' : 'Submit Review' ?>
        </button>
      </form>
    </div>

  <?php elseif (!isset($_SESSION['user_id'])): ?>
    <div class="alert alert-light border mb-4">
      <a href="<?= BASE_URL ?>/php/login.php" class="text-success fw-semibold">Log in</a>
      to leave a review.
    </div>
  <?php endif; ?>

  <!-- Existing reviews list -->
  <?php if (empty($reviews)): ?>
    <div class="text-center text-muted py-4">
      <div class="fs-2 mb-2">⭐</div>
      <p class="mb-0">No reviews yet — be the first!</p>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($reviews as $rv): ?>
        <div class="col-12 col-md-6">
          <div class="card border-0 shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <strong class="small"><?= htmlspecialchars($rv['reviewer_name']) ?></strong>
              <span class="text-muted" style="font-size:.75rem;">
                <?= date('M j, Y', strtotime($rv['created_at'])) ?>
              </span>
            </div>
            <!-- Render filled/empty stars -->
            <div class="mb-2">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <span style="color:<?= $s <= (int)$rv['rating'] ? '#ffc107' : '#ccc' ?>;">★</span>
              <?php endfor; ?>
            </div>
            <?php if (!empty($rv['body'])): ?>
              <p class="small mb-0 text-secondary">
                <?= nl2br(htmlspecialchars($rv['body'])) ?>
              </p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
// ── Thumbnail switcher (your original code) ──────────────────────
document.querySelectorAll('.thumbnail-img').forEach(function(thumb) {
    thumb.addEventListener('click', function() {
        document.getElementById('mainImage').src = this.src;
        document.querySelectorAll('.thumbnail-img').forEach(t => t.style.opacity = '0.6');
        this.style.opacity = '1';
    });
});

// ── BONUS C: Star picker logic ───────────────────────────────────
(function () {
    var stars      = document.querySelectorAll('.star-btn');
    var ratingInput = document.getElementById('ratingInput');

    stars.forEach(function (star) {
        // Hover: highlight up to hovered star
        star.addEventListener('mouseover', function () {
            var val = parseInt(this.dataset.value);
            stars.forEach(function (s) {
                s.style.color = parseInt(s.dataset.value) <= val ? '#ffc107' : '#ccc';
            });
        });
        // Mouse out: revert to selected value
        star.addEventListener('mouseout', function () {
            var selected = parseInt(ratingInput.value) || 0;
            stars.forEach(function (s) {
                s.style.color = parseInt(s.dataset.value) <= selected ? '#ffc107' : '#ccc';
            });
        });
        // Click: lock in the rating
        star.addEventListener('click', function () {
            ratingInput.value = this.dataset.value;
            document.getElementById('ratingError').style.display = 'none';
            var selected = parseInt(ratingInput.value);
            stars.forEach(function (s) {
                s.style.color = parseInt(s.dataset.value) <= selected ? '#ffc107' : '#ccc';
            });
        });
    });

    // Block submit if no star chosen
    document.getElementById('reviewForm')?.addEventListener('submit', function (e) {
        if (!ratingInput.value) {
            e.preventDefault();
            document.getElementById('ratingError').style.display = 'block';
        }
    });
})();
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>