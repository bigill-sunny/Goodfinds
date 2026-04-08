<?php
// Expects $p array with: id, name, price, image_url, category, condition, seller_name
$condition_colors = [
    'New'       => 'success',
    'Like New'  => 'primary',
    'Good'      => 'secondary',
    'Fair'      => 'warning',
    'Poor'      => 'danger',
];
$badge = $condition_colors[$p['condition']] ?? 'secondary';
$img   = !empty($p['image_url'])
       ? BASE_URL . '/uploads/' . htmlspecialchars($p['image_url'])
       : BASE_URL . '/img/placeholder.webp';
?>
<div class="card h-100 border-0 shadow-sm product-card">
  <a href="<?= BASE_URL ?>/php/product.php?id=<?= (int)$p['id'] ?>"
     class="text-decoration-none">
    <img
      src="<?= $img ?>"
      alt="<?= htmlspecialchars($p['name']) ?>"
      class="card-img-top object-fit-cover"
      style="height:180px;"
      loading="lazy"
      width="300" height="180">
  </a>

  <div class="card-body d-flex flex-column p-3">
    <!-- Category + Condition -->
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="text-muted" style="font-size:0.75rem;">
        <?= htmlspecialchars($p['category']) ?>
      </span>
      <span class="badge bg-<?= $badge ?> bg-opacity-75"
            style="font-size:0.7rem;">
        <?= htmlspecialchars($p['condition']) ?>
      </span>
    </div>

    <!-- Title -->
    <h3 class="card-title fw-semibold mb-1"
        style="font-size:0.95rem; line-height:1.3;">
      <a href="<?= BASE_URL ?>/php/product.php?id=<?= (int)$p['id'] ?>"
         class="text-dark text-decoration-none stretched-link">
        <?= htmlspecialchars($p['name']) ?>
      </a>
    </h3>

    <!-- Seller -->
    <p class="text-muted mb-2" style="font-size:0.78rem;">
      by <?= htmlspecialchars($p['seller_name']) ?>
    </p>

    <!-- Price -->
    <p class="fw-bold text-success mt-auto mb-0" style="font-size:1.1rem;">
      $<?= number_format($p['price'], 2) ?>
    </p>
  </div>
</div>