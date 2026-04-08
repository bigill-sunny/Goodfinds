</main><!-- /.container -->

<footer class="bg-dark text-white mt-5 py-4">
  <div class="container">
    <div class="row align-items-center">

      <div class="col-md-4 mb-3 mb-md-0">
        <div class="d-flex align-items-center gap-2">
          <svg width="20" height="20" viewBox="0 0 32 32" fill="none"
               xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="16" cy="16" r="14" stroke="#20c997" stroke-width="2.5"/>
            <path d="M10 16 L14 20 L22 12" stroke="#20c997" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="fw-bold">GoodFinds</span>
        </div>
        <p class="text-white-50 small mt-1 mb-0">
          Buy &amp; sell second-hand goods — electronics, books, vinyl, clothing, and more.
        </p>
      </div>

      <div class="col-md-4 mb-3 mb-md-0 text-md-center">
        <ul class="list-unstyled mb-0 small">
          <li><a href="<?= BASE_URL ?>/php/products.php"
                 class="text-white-50 text-decoration-none">Browse Listings</a></li>
          <li><a href="<?= BASE_URL ?>/php/register.php"
                 class="text-white-50 text-decoration-none">Create Account</a></li>
          <li><a href="<?= BASE_URL ?>/seller/dashboard.php"
                 class="text-white-50 text-decoration-none">Start Selling</a></li>
        </ul>
      </div>

      <div class="col-md-4 text-md-end">
        <p class="text-white-50 small mb-0">
          &copy; <?= date('Y') ?> GoodFinds. All rights reserved.
        </p>
      </div>

    </div>
  </div>
</footer>

<!-- Bootstrap JS (local from Lab9) -->
<script src="<?= BASE_URL ?>/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>/js/validation.js"></script>

</body>
</html>