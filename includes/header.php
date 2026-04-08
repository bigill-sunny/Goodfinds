<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GoodFinds – Buy &amp; Sell Second-Hand</title>

  <!-- Bootstrap (local from Lab9) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/bootstrap.css">

  <!-- Compiled from scss/style.scss via npm run sass:watch -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  

  <link rel="icon" href="<?= BASE_URL ?>/img/favicon.jpg" type="image/jpeg">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">

    <!-- Logo -->
     <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
      <img
      src="<?= BASE_URL ?>/img/favicon.jpg"
      alt="GoodFinds logo"
      width="32"
      height="32"
      style="border-radius: 8px; object-fit: cover;">
      <span class="fw-bold fs-5">GoodFinds</span>
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMenu"
            aria-controls="navMenu" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav Links -->
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-center gap-2">

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/php/products.php">Browse</a>
        </li>

        <?php if (isset($_SESSION['user_id'])): ?>

          <!-- Cart -->
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/php/cart.php">
              Cart
              <?php if (!empty($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                <span class="badge bg-warning text-dark">
                  <?= (int)$_SESSION['cart_count'] ?>
                </span>
              <?php endif; ?>
            </a>
          </li>

          <!-- My Orders -->
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/php/dashboard.php">My Orders</a>
          </li>

          <!-- Admin or Seller link -->
          <?php if (!empty($_SESSION['is_admin'])): ?>
            <li class="nav-item">
              <a class="nav-link text-warning fw-semibold"
                 href="<?= BASE_URL ?>/admin/dashboard.php">Admin Panel</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/seller/dashboard.php">Sell</a>
            </li>
          <?php endif; ?>

          <!-- Logged-in user name + logout -->
          <li class="nav-item d-flex align-items-center gap-2 ms-2">
            <span class="text-white-50 small">
              Hi, <?= htmlspecialchars($_SESSION['user_name']) ?>
            </span>
            <a class="btn btn-outline-light btn-sm"
               href="<?= BASE_URL ?>/php/logout.php">Logout</a>
          </li>

        <?php else: ?>

          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm"
               href="<?= BASE_URL ?>/php/login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-success btn-sm"
               href="<?= BASE_URL ?>/php/register.php">Register</a>
          </li>

        <?php endif; ?>
      </ul>
    </div>

  </div>
</nav>

<!-- Page content wrapper -->
<main class="container my-4">