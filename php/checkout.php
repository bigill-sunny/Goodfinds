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
           p.id AS product_id, p.name, p.price, p.image_url,
           p.stock, p.`condition`
    FROM   cart c
    JOIN   products p ON p.id = c.product_id
    WHERE  c.user_id = ?
    ORDER  BY c.id ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) {
    header("Location: " . BASE_URL . "/php/cart.php");
    exit;
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$provinces = [
    'AB' => 'Alberta',
    'BC' => 'British Columbia',
    'MB' => 'Manitoba',
    'NB' => 'New Brunswick',
    'NL' => 'Newfoundland and Labrador',
    'NS' => 'Nova Scotia',
    'NT' => 'Northwest Territories',
    'NU' => 'Nunavut',
    'ON' => 'Ontario',
    'PE' => 'Prince Edward Island',
    'QC' => 'Quebec',
    'SK' => 'Saskatchewan',
    'YT' => 'Yukon',
];

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row g-4">

  <div class="col-12 col-lg-7">
    <h1 class="h4 fw-bold mb-4">Checkout</h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <?php unset($_SESSION['flash_error']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/php/checkout_process.php"
          id="checkoutForm" novalidate>

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
          <h2 class="h6 fw-bold mb-3 text-uppercase text-muted"
              style="letter-spacing:.05em;">Shipping Information</h2>

          <div class="mb-3">
            <label for="shipping_name" class="form-label fw-semibold">Full Name</label>
            <input type="text" id="shipping_name" name="shipping_name"
                   class="form-control"
                   value="<?= htmlspecialchars($user['name']) ?>"
                   required minlength="2" autocomplete="name">
            <div class="invalid-feedback">Please enter your full name.</div>
          </div>

          <div class="mb-3">
            <label for="shipping_email" class="form-label fw-semibold">Email</label>
            <input type="email" id="shipping_email" name="shipping_email"
                   class="form-control"
                   value="<?= htmlspecialchars($user['email']) ?>"
                   required autocomplete="email">
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>

          <div class="mb-3">
            <label for="shipping_address" class="form-label fw-semibold">Street Address</label>
            <input type="text" id="shipping_address" name="shipping_address"
                   class="form-control"
                   placeholder="123 Main St"
                   required autocomplete="street-address">
            <div class="invalid-feedback">Please enter your street address.</div>
          </div>

          <div class="row g-3">
            <div class="col-12 col-sm-5">
              <label for="shipping_city" class="form-label fw-semibold">City</label>
              <input type="text" id="shipping_city" name="shipping_city"
                     class="form-control"
                     placeholder="Brampton"
                     required autocomplete="address-level2">
              <div class="invalid-feedback">Please enter your city.</div>
            </div>

            <div class="col-12 col-sm-4">
              <label for="shipping_province" class="form-label fw-semibold">Province</label>
              <select id="shipping_province" name="shipping_province"
                      class="form-select" required>
                <option value="">Select...</option>
                <?php foreach ($provinces as $code => $name): ?>
                  <option value="<?= $code ?>"
                    <?= $code === 'ON' ? 'selected' : '' ?>>
                    <?= htmlspecialchars($name) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Please select a province.</div>
            </div>

            <div class="col-12 col-sm-3">
              <label for="shipping_postal" class="form-label fw-semibold">Postal Code</label>
              <input type="text" id="shipping_postal" name="shipping_postal"
                     class="form-control"
                     placeholder="L6P 0A1"
                     required maxlength="7"
                     autocomplete="postal-code">
              <div class="invalid-feedback">Please enter your postal code.</div>
            </div>
          </div>

        </div>
      </div>

      <button type="submit" class="btn btn-success w-100 fw-semibold py-2">
        Place Order &rarr;
      </button>

    </form>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
      <div class="card-body p-4">
        <h2 class="h6 fw-bold mb-3 text-uppercase text-muted"
            style="letter-spacing:.05em;">Order Summary</h2>

        <div class="d-flex flex-column gap-2 mb-3">
          <?php foreach ($items as $item): ?>
            <div class="d-flex align-items-center gap-3">
              <img
                src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($item['image_url'] ?? 'placeholder.webp') ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"
                class="rounded object-fit-cover flex-shrink-0"
                style="width:48px;height:48px;"
                width="48" height="48" loading="lazy">
              <div class="flex-grow-1">
                <p class="mb-0 small fw-semibold">
                  <?= htmlspecialchars($item['name']) ?>
                </p>
                <p class="mb-0 text-muted" style="font-size:.78rem;">
                  Qty: <?= (int)$item['quantity'] ?>
                </p>
              </div>
              <span class="fw-semibold small">
                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <hr>

        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>Total</span>
          <span class="text-success">$<?= number_format($total, 2) ?></span>
        </div>

        <p class="text-muted small mt-2 mb-0">
          <?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in your order
        </p>
      </div>
    </div>
  </div>

</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>