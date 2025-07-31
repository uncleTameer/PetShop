<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();
use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_GET['id'])) exit('Order ID missing.');

$orderId = new ObjectId($_GET['id']);
$order = $db->orders->findOne(['_id' => $orderId]);

if (!$order) exit('<p class="text-danger">Order not found.</p>');

$user = isset($order['userId']) ? $db->users->findOne(['_id' => $order['userId']]) : null;
?>

<h6>👤 Customer: <?= htmlspecialchars($user['fullName'] ?? 'Unknown') ?></h6>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
<?php if (!empty($user['suspended'])): ?>
  <p class="text-danger">⚠️ This user is currently <strong>suspended</strong>.</p>
<?php endif; ?>
<p><strong>Date:</strong> <?= $order['createdAt'] ? $order['createdAt']->toDateTime()->format('d/m/Y H:i') : 'Unknown' ?></p>

<hr>

<h6>🧾 Items:</h6>
<table class="table table-sm table-bordered">
  <thead><tr><th>Product</th><th>Qty</th><th>Price</th></tr></thead>
  <tbody>
    <?php foreach ($order['items'] as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['name']) ?></td>
        <td><?= $item['quantity'] ?></td>
        <td>₪<?= number_format($item['price'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<hr>
<h6 class="text-end">💰 <strong>Total:</strong> ₪<?= number_format($order['total'], 2) ?></h6>

<?php if ($_SESSION['user']['role'] === 'admin'): ?>
<hr>
<form method="POST" action="updateOrderStatus.php" class="text-end">
  <input type="hidden" name="orderId" value="<?= $order['_id'] ?>">
  <label class="me-2 fw-bold">Status:</label>
  <select name="status" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
    <?php foreach (['Pending', 'Shipped', 'Cancelled'] as $status): ?>
      <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
        <?= $status ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>
<?php else: ?>
  <p class="text-end"><strong>Status:</strong> <?= htmlspecialchars($order['status'] ?? 'Pending') ?></p>
<?php endif; ?>
