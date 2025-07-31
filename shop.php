<?php
require 'php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Search functionality
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $products = $db->products->find(['name' => ['$regex' => $search, '$options' => 'i']]);
} else {
    $products = $db->products->find();
}
?>

<script>
  // Auto-hide alerts after 3 seconds
  setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    });
  }, 3000);
</script>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop - Pet Shop</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="index.php">🏠 Pet Shop</a>
  <div class="ms-auto text-white">
    <?php if (isset($_SESSION['user'])): ?>
      <div class="d-flex align-items-center text-white me-2">
        <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
          <img src="uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
               alt="Profile" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php else: ?>
          <img src="uploads/default.png" 
               alt="Default" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php endif; ?>
        <span>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
      </div>
                  <a href="editProfile.php" class="btn btn-outline-info btn-sm me-2">👤 Edit Profile</a>
            <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a> 
            <a href="wishlist.php" class="btn btn-outline-danger btn-sm ms-2">❤️ Wishlist</a>
            <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
    <?php else: ?>
      <a href="php/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="php/register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
    <a href="cart.php" class="btn btn-warning btn-sm ms-3">🛒 Cart</a>
  </div>
</nav>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger alert-dismissible fade show text-center mx-4" role="alert">
    <?= $_SESSION['error_message'] ?>
  </div>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show text-center mx-4" role="alert">
    <?= $_SESSION['success_message'] ?>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

  <div class="container py-4">
    <h2 class="text-center mb-4">All Products</h2>
    
    <!-- Search Bar -->
    <div class="row justify-content-center mb-4">
      <div class="col-md-6">
        <form method="GET" action="shop.php" class="d-flex">
          <input type="text" name="search" class="form-control me-2" 
                 placeholder="Search products by name..." 
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn btn-primary">🔍 Search</button>
        </form>
      </div>
    </div>
    
    <div class="row">

      <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <a href="product.php?id=<?= $product['_id'] ?>" class="text-decoration-none">
              <img src="<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>" style="height: 200px; object-fit: cover;">
            </a>
            <div class="card-body">
              <h5 class="card-title">
                <a href="product.php?id=<?= $product['_id'] ?>" class="text-decoration-none text-dark">
                  <?= $product['name'] ?>
                </a>
              </h5>
              <p class="card-text">₪<?= number_format($product['price'], 2) ?></p>
              <p class="card-text"><small class="text-muted">Stock: <?= $product['stock'] ?></small></p>
              <div class="d-flex gap-2">
                <a href="product.php?id=<?= $product['_id'] ?>" class="btn btn-outline-primary flex-fill">👁️ View Details</a>
                <?php if ($product['stock'] > 0): ?>
                  <form method="POST" action="php/addToCart.php" class="flex-fill">
                   <input type="hidden" name="productId" value="<?= $product['_id'] ?>">
                   <input type="hidden" name="name" value="<?= $product['name'] ?>">
                   <input type="hidden" name="price" value="<?= $product['price'] ?>">
                   <button type="submit" class="btn btn-primary w-100">🛒 Add to Cart</button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-secondary w-100" disabled>❌ Out of Stock</button>
                <?php endif; ?>
                <?php if (isset($_SESSION['user'])): ?>
                  <form method="POST" action="wishlist.php" class="flex-fill">
                    <input type="hidden" name="productId" value="<?= $product['_id'] ?>">
                    <button type="submit" name="add_to_wishlist" class="btn btn-outline-danger w-100">❤️ Wishlist</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
