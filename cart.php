<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="shop.php">⬅ Back to Shop</a>
  <div class="ms-auto text-white">
    <?php if (isset($_SESSION['user'])): ?>
      <?= htmlspecialchars($_SESSION['user']['name']) ?>
      <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a>
      <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    <?php else: ?>
      <a href="php/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="php/register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4">🛒 Your Cart</h2>

  <?php if (empty($cart)): ?>
    <div class="alert alert-info text-center">Your cart is empty.</div>
  <?php else: ?>
    <form method="POST" action="php/updateCart.php">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cart as $id => $item): 
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
          ?>
            <tr>
              <td><?= htmlspecialchars($item['name']) ?></td>
              <td>₪<?= number_format($item['price'], 2) ?></td>
              <td>
                <input type="number" name="quantities[<?= $id ?>]" value="<?= $item['quantity'] ?>" min="1" class="form-control form-control-sm w-50 mx-auto">
              </td>
              <td>₪<?= number_format($subtotal, 2) ?></td>
              <td>
                <a href="php/removeFromCart.php?id=<?= $id ?>" class="btn btn-sm btn-danger">❌ Remove</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="3" class="text-end">Total</th>
            <th>₪<?= number_format($total, 2) ?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
      <div class="text-end mb-4">
        <button type="submit" class="btn btn-warning">🔄 Update Cart</button>
      </div>
    </form>


    <?php if (!isset($_SESSION['user'])): ?>
  <div class="alert alert-warning text-center mt-4">
    ⚠️ You must <a href="php/login.php">log in</a> to place your order.
  </div>
<?php else: ?>
  <h4>🧾 Checkout</h4>
  <form method="POST" action="php/submitOrder.php" class="row g-3">
    <div class="col-12">
      <button type="submit" class="btn btn-success w-100">✅ Place Order as <?= htmlspecialchars($_SESSION['user']['name']) ?></button>
    </div>
  </form>
<?php endif; ?>

  <?php endif; ?>
</div>

</body>
</html>
