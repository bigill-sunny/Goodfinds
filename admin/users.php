<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: " . BASE_URL . "/php/login.php");
    exit;
}

$current_admin_id = (int)$_SESSION['user_id'];

$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where  = 'WHERE u.name LIKE ? OR u.email LIKE ?';
    $params = ['%' . $search . '%', '%' . $search . '%'];
    $types  = 'ss';
}

$sql = "
    SELECT u.id, u.name, u.email, u.is_admin, u.created_at,
           COUNT(DISTINCT p.id)  AS listing_count,
           COUNT(DISTINCT o.id)  AS order_count
    FROM   users u
    LEFT   JOIN products p ON p.seller_id = u.id
    LEFT   JOIN orders   o ON o.user_id   = u.id
    $where
    GROUP  BY u.id
    ORDER  BY u.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h4 fw-bold mb-0">
    All Users
    <span class="badge bg-secondary ms-2 fs-6"><?= count($users) ?></span>
  </h1>
  <a href="<?= BASE_URL ?>/admin/dashboard.php"
     class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</div>

<div class="card border-0 shadow-sm mb-3 p-3">
  <form method="GET" action="" class="d-flex gap-2 align-items-end flex-wrap">
    <div class="flex-grow-1">
      <label class="form-label small fw-semibold mb-1">Search Users</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Name or email..."
             value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search): ?>
      <a href="<?= BASE_URL ?>/admin/users.php"
         class="btn btn-outline-secondary btn-sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th class="text-center">Role</th>
          <th class="text-center">Listings</th>
          <th class="text-center">Orders</th>
          <th>Joined</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="text-muted small"><?= (int)$u['id'] ?></td>
            <td class="fw-semibold small"><?= htmlspecialchars($u['name']) ?></td>
            <td>
              <a href="mailto:<?= htmlspecialchars($u['email']) ?>"
                 class="text-muted small text-decoration-none">
                <?= htmlspecialchars($u['email']) ?>
              </a>
            </td>
            <td class="text-center">
              <?php if ($u['is_admin']): ?>
                <span class="badge bg-danger">Admin</span>
              <?php else: ?>
                <span class="badge bg-light text-dark border">User</span>
              <?php endif; ?>
            </td>
            <td class="text-center small"><?= (int)$u['listing_count'] ?></td>
            <td class="text-center small"><?= (int)$u['order_count'] ?></td>
            <td class="small text-muted">
              <?= date('M j, Y', strtotime($u['created_at'])) ?>
            </td>
            <td class="text-end">
              <?php if ((int)$u['id'] !== $current_admin_id): ?>
                <form method="POST"
                      action="<?= BASE_URL ?>/admin/user_toggle_admin.php"
                      class="d-inline">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button type="submit"
                          class="btn btn-sm <?= $u['is_admin'] ? 'btn-outline-danger' : 'btn-outline-primary' ?>">
                    <?= $u['is_admin'] ? 'Revoke Admin' : 'Make Admin' ?>
                  </button>
                </form>
              <?php else: ?>
                <span class="text-muted small fst-italic">You</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>