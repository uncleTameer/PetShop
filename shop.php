<?php
require 'php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Search and filter functionality
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Build query
$query = [];
if (!empty($search)) {
    $query['name'] = ['$regex' => $search, '$options' => 'i'];
}
if (!empty($categoryFilter)) {
    $query['categoryId'] = new MongoDB\BSON\ObjectId($categoryFilter);
}

$products = $db->products->find($query);

// Get all categories for filter dropdown
$categories = $db->categories->find([], ['sort' => ['name' => 1]]);
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
    
    <!-- Search and Filter Bar -->
    <div class="row justify-content-center mb-4">
      <div class="col-md-8">
        <form method="GET" action="shop.php" class="row g-3">
          <div class="col-md-5">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search products by name..." 
                   value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-4">
            <select name="category" class="form-control">
              <option value="">All Categories</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= $category['_id'] ?>" 
                        <?= $categoryFilter == $category['_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($category['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">🔍 Search</button>
          </div>
        </form>
        <?php if (!empty($search) || !empty($categoryFilter)): ?>
          <div class="text-center mt-2">
            <a href="shop.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
          </div>
        <?php endif; ?>
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
              <?php 
              // Get category name for this product
              $category = null;
              if (isset($product['categoryId'])) {
                  $category = $db->categories->findOne(['_id' => $product['categoryId']]);
              }
              ?>
              <?php if ($category): ?>
                <span class="badge bg-info mb-2"><?= htmlspecialchars($category['name']) ?></span>
              <?php endif; ?>
              <p class="card-text">₪<?= number_format($product['price'], 2) ?></p>
              <p class="card-text"><small class="text-muted">Stock: <?= $product['stock'] ?></small></p>
              <div class="d-flex gap-2">
                <a href="product.php?id=<?= $product['_id'] ?>" class="btn btn-outline-primary flex-fill">👁️ View Details</a>
                <?php if ($product['stock'] > 0): ?>
                  <form method="POST" action="php/addToCart.php" class="flex-fill">
                    <input type="hidden" name="name" value="<?= $product['name'] ?>">
                                                             <input type="hidden" name="redirect" value="../shop.php">
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
