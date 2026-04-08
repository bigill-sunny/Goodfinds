<?php
require_once __DIR__ . '/../db/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    // ── Server-side validation ──────────────────────────────────────
    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // ── Fetch user by email ─────────────────────────────────────
        $stmt = $conn->prepare(
            "SELECT id, name, password, is_admin FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        // ── Verify password ─────────────────────────────────────────
        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['is_admin']   = (int)$user['is_admin'];
            $_SESSION['cart_count'] = 0;

            // Redirect admin to admin panel, users to homepage
            if ((int)$user['is_admin'] === 1) {
                header("Location: " . BASE_URL . "/admin/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "/index.php");
            }
            exit;

        } else {
            // Deliberately vague — don't reveal if email exists
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-7 col-lg-5">

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body p-4">

        <h1 class="h4 fw-bold mb-1">Welcome Back</h1>
        <p class="text-muted small mb-4">Log in to your GoodFinds account.</p>

        <!-- Error message -->
        <?php if ($error): ?>
          <div class="alert alert-danger py-2">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" novalidate>

          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              placeholder="you@example.com"
              required
              autocomplete="email">
            <div class="invalid-feedback">
              Please enter a valid email address.
            </div>
          </div>

          <!-- Password -->
          <div class="mb-4">
            <label for="password" class="form-label fw-semibold">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Your password"
              required
              autocomplete="current-password">
            <div class="invalid-feedback">
              Password is required.
            </div>
          </div>

          <button type="submit" class="btn btn-success w-100 fw-semibold">
            Log In
          </button>

        </form>

        <hr class="my-3">
        <p class="text-center small mb-0">
          Don&rsquo;t have an account?
          <a href="<?= BASE_URL ?>/php/register.php" class="text-success fw-semibold">
            Register here
          </a>
        </p>

      </div>
    </div>

  </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>