<?php
require_once __DIR__ . '/../db/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$errors = [];
$old    = []; // repopulate form fields on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password =      $_POST['password']         ?? '';
    $confirm  =      $_POST['confirm_password'] ?? '';

    $old = ['name' => $name, 'email' => $email];

    // ── Server-side validation ──────────────────────────────────────
    if (empty($name) || strlen($name) < 2)
        $errors[] = 'Full name must be at least 2 characters.';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email address is required.';

    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters.';

    if ($password !== $confirm)
        $errors[] = 'Passwords do not match.';

    // ── Check for duplicate email ───────────────────────────────────
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }
        $stmt->close();
    }

    // ── Insert new user ─────────────────────────────────────────────
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)"
        );
        $stmt->bind_param("sss", $name, $email, $hashed);

        if ($stmt->execute()) {
            $_SESSION['user_id']    = $conn->insert_id;
            $_SESSION['user_name']  = $name;
            $_SESSION['is_admin']   = 0;
            $_SESSION['cart_count'] = 0;
            $stmt->close();
            header("Location: " . BASE_URL . "/index.php");
            exit;
        } else {
            $errors[] = 'Registration failed. Please try again.';
            $stmt->close();
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-7 col-lg-5">

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body p-4">

        <h1 class="h4 fw-bold mb-1">Create an Account</h1>
        <p class="text-muted small mb-4">
          Join GoodFinds and start buying or selling today.
        </p>

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm" novalidate>

          <!-- Full Name -->
          <div class="mb-3">
            <label for="name" class="form-label fw-semibold">Full Name</label>
            <input
              type="text"
              id="name"
              name="name"
              class="form-control"
              placeholder="Jane Smith"
              value="<?= htmlspecialchars($old['name'] ?? '') ?>"
              required
              minlength="2"
              autocomplete="name">
            <div class="invalid-feedback">
              Please enter your full name (min. 2 characters).
            </div>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              placeholder="you@example.com"
              value="<?= htmlspecialchars($old['email'] ?? '') ?>"
              required
              autocomplete="email">
            <div class="invalid-feedback">
              Please enter a valid email address.
            </div>
          </div>

          <!-- Password -->
          <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Min. 8 characters"
              required
              minlength="8"
              autocomplete="new-password">
            <div class="form-text text-muted">
              Must be at least 8 characters.
            </div>
            <div class="invalid-feedback">
              Password must be at least 8 characters.
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="mb-4">
            <label for="confirm_password" class="form-label fw-semibold">
              Confirm Password
            </label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              class="form-control"
              placeholder="Repeat your password"
              required
              autocomplete="new-password">
            <div class="invalid-feedback" id="confirmError">
              Passwords do not match.
            </div>
          </div>

          <button type="submit" class="btn btn-success w-100 fw-semibold">
            Create Account
          </button>

        </form>

        <hr class="my-3">
        <p class="text-center small mb-0">
          Already have an account?
          <a href="<?= BASE_URL ?>/php/login.php" class="text-success fw-semibold">
            Log in
          </a>
        </p>

      </div>
    </div>

  </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>