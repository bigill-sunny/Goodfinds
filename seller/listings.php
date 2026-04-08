<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_GET['action'] ?? '';
$edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editing = null;

// ★ NEW: also fetch existing gallery images when editing
$existing_images = [];
if ($edit_id) {
    $e_stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $e_stmt->bind_param("ii", $edit_id, $user_id);
    $e_stmt->execute();
    $editing = $e_stmt->get_result()->fetch_assoc();
    $e_stmt->close();
    if (!$editing) {
        header("Location: " . BASE_URL . "/seller/listings.php");
        exit;
    }

    // ★ NEW: load all saved images for this product
    $gi_stmt = $conn->prepare("
        SELECT image_path, is_primary
        FROM   product_images
        WHERE  product_id = ?
        ORDER  BY is_primary DESC, sort_order ASC
    ");
    $gi_stmt->bind_param("i", $edit_id);
    $gi_stmt->execute();
    $existing_images = $gi_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $gi_stmt->close();
}

$list_stmt = $conn->prepare("
    SELECT id, name, price, stock, category, `condition`, image_url, created_at
    FROM   products
    WHERE  seller_id = ?
    ORDER  BY created_at DESC
");
$list_stmt->bind_param("i", $user_id);
$list_stmt->execute();
$listings = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$list_stmt->close();

$categories = ['Electronics','Books','Vinyl & CDs','Clothing','Collectibles','Other'];
$conditions = ['New','Like New','Good','Fair','Poor'];
$condition_colors = [
    'New'      => 'success',
    'Like New' => 'primary',
    'Good'     => 'secondary',
    'Fair'     => 'warning',
    'Poor'     => 'danger',
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

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash_error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="row g-4">

  <div class="col-12 col-lg-<?= ($editing || $action === 'add') ? '7' : '12' ?>">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h1 class="h4 fw-bold mb-0">My Listings</h1>
      <a href="<?= BASE_URL ?>/seller/listings.php?action=add"
         class="btn btn-success btn-sm fw-semibold">+ New Listing</a>
    </div>

    <?php if (empty($listings)): ?>
      <div class="text-center py-5 text-muted">
        <div class="fs-1">📋</div>
        <p class="mt-2">No listings yet. Add your first item!</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th>Condition</th>
              <th class="text-end">Price</th>
              <th class="text-center">Stock</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listings as $item):
              $badge = $condition_colors[$item['condition']] ?? 'secondary';
            ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <img
                      src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($item['image_url'] ?? 'placeholder.webp') ?>"
                      alt="<?= htmlspecialchars($item['name']) ?>"
                      class="rounded object-fit-cover flex-shrink-0"
                      style="width:44px;height:44px;"
                      width="44" height="44" loading="lazy">
                    <span class="fw-semibold small">
                      <?= htmlspecialchars($item['name']) ?>
                    </span>
                  </div>
                </td>
                <td><span class="small"><?= htmlspecialchars($item['category']) ?></span></td>
                <td>
                  <span class="badge bg-<?= $badge ?> bg-opacity-75 small">
                    <?= htmlspecialchars($item['condition']) ?>
                  </span>
                </td>
                <td class="text-end fw-semibold text-success">
                  $<?= number_format($item['price'], 2) ?>
                </td>
                <td class="text-center">
                  <?php if ($item['stock'] == 0): ?>
                    <span class="badge bg-danger">Sold Out</span>
                  <?php else: ?>
                    <span class="badge bg-light text-dark border">
                      <?= (int)$item['stock'] ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a href="<?= BASE_URL ?>/seller/listings.php?edit=<?= (int)$item['id'] ?>"
                     class="btn btn-outline-primary btn-sm me-1">Edit</a>
                  <form method="POST"
                        action="<?= BASE_URL ?>/php/product_delete.php"
                        class="d-inline"
                        onsubmit="return confirm('Delete this listing?')">
                    <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($editing || $action === 'add'): ?>
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm p-4 sticky-top" style="top:80px;">
      <h2 class="h5 fw-bold mb-4">
        <?= $editing ? 'Edit Listing' : 'New Listing' ?>
      </h2>

      <form method="POST"
            action="<?= BASE_URL ?>/php/product_save.php"
            enctype="multipart/form-data"
            id="productForm" novalidate>

        <?php if ($editing): ?>
          <input type="hidden" name="product_id" value="<?= (int)$editing['id'] ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label for="name" class="form-label fw-semibold">Title</label>
          <input type="text" id="name" name="name" class="form-control"
                 value="<?= htmlspecialchars($editing['name'] ?? '') ?>"
                 required minlength="3" maxlength="200"
                 placeholder="e.g. Sony WH-1000XM4 Headphones">
          <div class="invalid-feedback">Title must be at least 3 characters.</div>
        </div>

        <div class="mb-3">
          <label for="description" class="form-label fw-semibold">Description</label>
          <textarea id="description" name="description" class="form-control"
                    rows="3" required minlength="10"
                    placeholder="Describe the item's features, any wear, included accessories..."
                    ><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
          <div class="invalid-feedback">Please provide a description.</div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label for="price" class="form-label fw-semibold">Price ($)</label>
            <input type="number" id="price" name="price" class="form-control"
                   value="<?= htmlspecialchars($editing['price'] ?? '') ?>"
                   required min="0.01" step="0.01" placeholder="0.00">
            <div class="invalid-feedback">Enter a valid price.</div>
          </div>
          <div class="col-6">
            <label for="stock" class="form-label fw-semibold">Quantity</label>
            <input type="number" id="stock" name="stock" class="form-control"
                   value="<?= htmlspecialchars($editing['stock'] ?? 1) ?>"
                   required min="0" step="1">
            <div class="invalid-feedback">Enter a valid quantity.</div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label for="category" class="form-label fw-semibold">Category</label>
            <select id="category" name="category" class="form-select" required>
              <option value="">Select...</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                  <?= ($editing['category'] ?? '') === $cat ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select a category.</div>
          </div>
          <div class="col-6">
            <label for="condition" class="form-label fw-semibold">Condition</label>
            <select id="condition" name="condition" class="form-select" required>
              <option value="">Select...</option>
              <?php foreach ($conditions as $cond): ?>
                <option value="<?= htmlspecialchars($cond) ?>"
                  <?= ($editing['condition'] ?? '') === $cond ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cond) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select a condition.</div>
          </div>
        </div>

        <!-- ★ CHANGED: Image section now supports multiple uploads -->
        <div class="mb-4">
          <label for="product_images" class="form-label fw-semibold">
            Product Images
            <?php if ($editing): ?>
              <span class="text-muted fw-normal small">(upload more to add to gallery)</span>
            <?php endif; ?>
          </label>

          <?php if (!empty($existing_images)): ?>
            <!-- ★ NEW: Show saved images as thumbnails when editing -->
            <div class="d-flex flex-wrap gap-2 mb-2">
              <?php foreach ($existing_images as $eimg): ?>
                <div class="text-center">
                  <img
                    src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($eimg['image_path']) ?>"
                    alt="Saved image"
                    class="rounded border <?= $eimg['is_primary'] ? 'border-success border-2' : '' ?>"
                    style="width:64px;height:64px;object-fit:cover;"
                    width="64" height="64" loading="lazy">
                  <?php if ($eimg['is_primary']): ?>
                    <div><span class="badge bg-success mt-1" style="font-size:.6rem;">Primary</span></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="form-text mb-2">Current images above. Upload below to add more.</p>

          <?php elseif (!empty($editing['image_url'])): ?>
            <!-- Fallback: show the single image_url if no product_images rows yet -->
            <div class="mb-2">
              <img
                src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($editing['image_url']) ?>"
                alt="Current image"
                class="rounded border"
                style="width:72px;height:72px;object-fit:cover;"
                width="72" height="72">
            </div>
          <?php endif; ?>

          <!-- ★ CHANGED: name="product_images[]" + multiple attribute -->
          <input type="file"
                 id="product_images"
                 name="product_images[]"
                 class="form-control"
                 accept="image/jpeg,image/png,image/webp,image/gif"
                 multiple>
          <div class="form-text">JPG, PNG, WebP or GIF · Max 5 MB each · You can select several files at once.</div>

          <!-- ★ NEW: Live preview box — JS fills this in -->
          <div id="newImagesPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-success fw-semibold">
            <?= $editing ? 'Save Changes' : 'Publish Listing' ?>
          </button>
          <a href="<?= BASE_URL ?>/seller/listings.php"
             class="btn btn-outline-secondary">Cancel</a>
        </div>

      </form>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- ★ NEW: JavaScript for live thumbnail preview -->
<script>
document.getElementById('product_images')?.addEventListener('change', function () {
    var preview = document.getElementById('newImagesPreview');
    preview.innerHTML = '';  // clear old previews

    Array.from(this.files).forEach(function (file) {
        if (!file.type.startsWith('image/')) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.createElement('img');
            img.src    = e.target.result;
            img.title  = file.name;
            img.alt    = file.name;
            img.className = 'rounded border';
            img.style  = 'width:64px;height:64px;object-fit:cover;';
            img.width  = 64;
            img.height = 64;
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
