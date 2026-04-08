<?php
require_once __DIR__ . '/../db/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/php/login.php"); exit;
}

$user_id    = (int)$_SESSION['user_id'];
$with_id    = filter_input(INPUT_GET, 'with',    FILTER_VALIDATE_INT);
$product_id = filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT);

// Mark thread as read when opened
if ($with_id) {
    if ($product_id) {
        $m = $conn->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=? AND product_id=?");
        $m->bind_param("iii", $user_id, $with_id, $product_id);
    } else {
        $m = $conn->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=? AND product_id IS NULL");
        $m->bind_param("ii", $user_id, $with_id);
    }
    $m->execute(); $m->close();
}

// All conversations (one row per unique person+product combo)
$cv = $conn->prepare("
    SELECT
        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id,
        product_id,
        MAX(created_at) AS last_at,
        SUM(CASE WHEN receiver_id = ? AND is_read = 0 THEN 1 ELSE 0 END) AS unread
    FROM messages
    WHERE sender_id = ? OR receiver_id = ?
    GROUP BY other_id, product_id
    ORDER BY last_at DESC
");
$cv->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$cv->execute();
$conversations = $cv->get_result()->fetch_all(MYSQLI_ASSOC);
$cv->close();

// Add names to conversations
foreach ($conversations as &$conv) {
    $nu = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $nu->bind_param("i", $conv['other_id']);
    $nu->execute();
    $conv['other_name'] = $nu->get_result()->fetch_assoc()['name'] ?? 'Unknown';
    $nu->close();
    if ($conv['product_id']) {
        $np = $conn->prepare("SELECT name FROM products WHERE id = ?");
        $np->bind_param("i", $conv['product_id']);
        $np->execute();
        $conv['product_name'] = $np->get_result()->fetch_assoc()['name'] ?? '';
        $np->close();
    }
}
unset($conv);

// Thread messages
$thread = [];
$other_user = null;
$thread_product = null;

if ($with_id) {
    $nu = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    $nu->bind_param("i", $with_id);
    $nu->execute();
    $other_user = $nu->get_result()->fetch_assoc();
    $nu->close();

    if ($product_id) {
        $np = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
        $np->bind_param("i", $product_id);
        $np->execute();
        $thread_product = $np->get_result()->fetch_assoc();
        $np->close();

        $tm = $conn->prepare("
            SELECT m.*, u.name AS sender_name FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
              AND m.product_id=?
            ORDER BY m.created_at ASC
        ");
        $tm->bind_param("iiiii", $user_id, $with_id, $with_id, $user_id, $product_id);
    } else {
        $tm = $conn->prepare("
            SELECT m.*, u.name AS sender_name FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
              AND m.product_id IS NULL
            ORDER BY m.created_at ASC
        ");
        $tm->bind_param("iiii", $user_id, $with_id, $with_id, $user_id);
    }
    $tm->execute();
    $thread = $tm->get_result()->fetch_all(MYSQLI_ASSOC);
    $tm->close();
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once BASE_PATH . '/includes/header.php';
?>

<?php if ($flash_success): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= htmlspecialchars($flash_success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <?= htmlspecialchars($flash_error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h4 fw-bold mb-3">Messages</h1>

<div class="row g-3" style="min-height:500px;">

  <!-- Sidebar: conversation list -->
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-dark text-white small fw-semibold py-2">Conversations</div>
      <?php if (empty($conversations)): ?>
        <div class="p-4 text-center text-muted small">
          <div class="fs-2 mb-2">💬</div>
          No messages yet.<br>Go to a product and click <strong>Message Seller</strong>.
        </div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($conversations as $conv):
              $active = ($with_id == $conv['other_id'] && $product_id == $conv['product_id']);
              $url    = BASE_URL . '/php/messages.php?with=' . (int)$conv['other_id']
                      . ($conv['product_id'] ? '&product=' . (int)$conv['product_id'] : '');
          ?>
            <li class="list-group-item list-group-item-action px-3 py-2 <?= $active ? 'active' : '' ?>">
              <a href="<?= $url ?>" class="text-decoration-none <?= $active ? 'text-white' : 'text-dark' ?>">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold small"><?= htmlspecialchars($conv['other_name']) ?></div>
                    <?php if (!empty($conv['product_name'])): ?>
                      <div class="text-<?= $active ? 'white-50' : 'muted' ?>" style="font-size:.75rem;">
                        re: <?= htmlspecialchars(mb_strimwidth($conv['product_name'], 0, 25, '…')) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="text-end">
                    <div class="text-<?= $active ? 'white-50' : 'muted' ?>" style="font-size:.7rem;">
                      <?= date('M j', strtotime($conv['last_at'])) ?>
                    </div>
                    <?php if ($conv['unread'] > 0): ?>
                      <span class="badge bg-danger"><?= (int)$conv['unread'] ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Thread panel -->
  <div class="col-12 col-md-8">
    <div class="card border-0 shadow-sm h-100 d-flex flex-column">

      <?php if ($with_id && $other_user): ?>
        <div class="card-header bg-dark text-white py-2">
          <div class="fw-semibold"><?= htmlspecialchars($other_user['name']) ?></div>
          <?php if ($thread_product): ?>
            <div class="small text-white-50">
              Re: <a href="<?= BASE_URL ?>/php/product.php?id=<?= (int)$thread_product['id'] ?>"
                     class="text-white-50"><?= htmlspecialchars($thread_product['name']) ?></a>
            </div>
          <?php endif; ?>
        </div>

        <!-- Chat bubbles -->
        <div class="flex-grow-1 overflow-auto p-3" id="threadMessages" style="max-height:350px;">
          <?php if (empty($thread)): ?>
            <div class="text-center text-muted py-4 small">No messages yet — say hello!</div>
          <?php endif; ?>
          <?php foreach ($thread as $msg):
              $mine = ((int)$msg['sender_id'] === $user_id);
          ?>
            <div class="d-flex <?= $mine ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
              <div class="rounded-3 px-3 py-2"
                   style="max-width:75%;
                          background:<?= $mine ? '#198754' : '#f0f0f0' ?>;
                          color:<?= $mine ? '#fff' : '#212529' ?>;">
                <div style="font-size:.9rem;"><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
                <div class="mt-1 text-end" style="font-size:.7rem;opacity:.7;">
                  <?= date('M j, g:i a', strtotime($msg['created_at'])) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Reply box -->
        <div class="card-footer p-3">
          <form method="POST" action="<?= BASE_URL ?>/php/message_send.php">
            <input type="hidden" name="receiver_id" value="<?= (int)$with_id ?>">
            <?php if ($product_id): ?>
              <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
            <?php endif; ?>
            <div class="input-group">
              <textarea name="body" class="form-control" rows="2"
                        placeholder="Type a message..." required
                        style="resize:none;" maxlength="1000"></textarea>
              <button type="submit" class="btn btn-success px-4 fw-semibold">Send</button>
            </div>
          </form>
        </div>

      <?php else: ?>
        <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted">
          <div class="text-center">
            <div class="fs-1 mb-2">📩</div>
            <p>Select a conversation on the left,</p>
            <p class="small">or go to a product and click <strong>Message Seller</strong>.</p>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
// Auto-scroll chat to bottom
var t = document.getElementById('threadMessages');
if (t) t.scrollTop = t.scrollHeight;
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>