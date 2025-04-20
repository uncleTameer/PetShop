<?php
session_start();
require 'php/dbConnect.php';

use MongoDB\BSON\ObjectId;

$recentOrders = [];

if (isset($_SESSION['user'])) {
    $userId = new ObjectId($_SESSION['user']['id']);
    $recentOrders = $db->orders->find(
        ['userId' => $userId],
        ['sort' => ['createdAt' => -1], 'limit' => 3]
    )->toArray();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pet Shop</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<?php if (isset($_SESSION['logout_message'])): ?>
  <div class="alert alert-info text-center m-3 flash-message">
    <?= $_SESSION['logout_message'] ?>
  </div>
  <?php unset($_SESSION['logout_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success text-center m-3 flash-message">
    <?= $_SESSION['success_message'] ?>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="#">Horse & Camel</a>
  <div class="ms-auto">
    <?php if (isset($_SESSION['user'])): ?>
      <span class="navbar-text text-white me-3">
        Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>
      </span>
      <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a>
      <a href="php/logout.php" class="btn btn-outline-light">Logout</a>
    <?php else: ?>
      <a href="php/register.php" class="btn btn-outline-light me-2">Register</a>
      <a href="php/login.php" class="btn btn-outline-light">Login</a>
    <?php endif; ?>
    <a href="cart.php" class="btn btn-outline-warning ms-2">🛒 Cart</a>
  </div>
</nav>

<div class="container mt-5 text-center">
  <h1 class="display-4">Welcome to the Horse & Camel!</h1>
  <p class="lead">Find the best products for your pets 🐶🐱🐦</p>

  <?php if (!empty($recentOrders)): ?>
    <div class="container mt-5">
      <h4 class="mb-3">🧾 Your Recent Orders</h4>
      <div class="list-group">
        <?php foreach ($recentOrders as $order): ?>
          <div class="list-group-item text-start">
            <strong>Order #<?= substr((string)$order['_id'], -5) ?></strong> |
            <?= count($order['items']) ?> items |
            Total: ₪<?= number_format($order['total'], 2) ?>
            <span class="float-end text-muted">
              <?= date('d/m/Y', $order['createdAt']->toDateTime()->getTimestamp()) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php
    $shopTarget = 'shop.php';
    $buttonText = '🛒 Browse Products';

    if (isset($_SESSION['user'])) {
      if ($_SESSION['user']['isAdmin']) {
        $shopTarget = 'admin/dashboard.php';
        $buttonText = '🧠 Go to Admin Dashboard';
      } else {
        $buttonText = '🛒 Start Shopping, ' . explode(' ', $_SESSION['user']['name'])[0] . '!';
      }
    }
  ?>

  <a href="<?= $shopTarget ?>" class="btn btn-primary btn-lg mt-4 shop-btn">
    <?= $buttonText ?>
  </a>
</div>

<script>
  document.querySelectorAll('.flash-message').forEach(msg => {
    setTimeout(() => {
      msg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      msg.style.opacity = '0';
      msg.style.transform = 'translateY(-10px)';
      setTimeout(() => msg.remove(), 500);
    }, 3000);
  });
</script>

</body>
</html>
