<?php
session_start();
require 'php/dbConnect.php';

use MongoDB\BSON\ObjectId;

// Check login
if (isset($_SESSION['user']['id'])) {
    $userId = new ObjectId($_SESSION['user']['id']);
    $recentOrders = $db->orders->find(
        ['userId' => $userId],
        ['sort' => ['createdAt' => -1], 'limit' => 3]
    )->toArray();
} else {
    echo "<script>
         alert('Session expired. Please login again.');
         window.location.href = 'php/login.php'; 
    </script>";
    exit;
}

// Fun Facts
$funFacts = [
    "A horse’s heart weighs about 9 kilograms!",
    "Camels can survive without water for weeks.",
    "Horses can sleep both lying down and standing up!",
    "A camel’s hump stores fat, not water!",
    "Horses have bigger eyes than any other land mammal!"
];
$randomFact = $funFacts[array_rand($funFacts)];

// Fetch 3 random featured products
$featuredProducts = $db->products->aggregate([
  ['$sample' => ['size' => 3]]
])->toArray();

// Personalized recommendation
$recommendation = "🎯 Check out our latest horse saddles!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Horse & Camel</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>

<!-- Flash Messages -->
<?php foreach (['logout_message', 'success_message'] as $msgType): ?>
  <?php if (isset($_SESSION[$msgType])): ?>
    <div class="alert alert-<?= $msgType === 'success_message' ? 'success' : 'info' ?> text-center m-3 flash-message">
      <?= $_SESSION[$msgType] ?>
    </div>
    <?php unset($_SESSION[$msgType]); ?>
  <?php endif; ?>
<?php endforeach; ?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="#">Horse & Camel</a>
  <div class="ms-auto">
    <?php if (isset($_SESSION['user'])): ?>
      <span class="navbar-text text-white me-3">
        Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>
      </span>
      <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
    <?php else: ?>
      <a href="php/register.php" class="btn btn-outline-light btn-sm me-2">Register</a>
      <a href="php/login.php" class="btn btn-outline-light btn-sm">Login</a>
    <?php endif; ?>
    <a href="cart.php" class="btn btn-outline-warning btn-sm ms-2">🛒 Cart</a>
  </div>
</nav>

<!-- Main Container -->
<div class="container mt-5 text-center">

  <!-- Welcome Animation -->
  <div class="alert alert-primary animate__animated animate__fadeInDown">
    👋 Welcome back, <?= htmlspecialchars($_SESSION['user']['name']) ?>!
  </div>

  <h1 class="display-4 mb-3">Horse & Camel Shop</h1>
  <p class="lead mb-5">Find the best products for your pets 🐴🐫🐶🐱</p>

  <!-- Fun Fact -->
  <div class="alert alert-warning mx-auto" style="max-width: 600px;">
    🐾 Fun Fact: <?= $randomFact ?>
  </div>

  <!-- Featured Products Today (Advanced) -->
  <div class="mt-5">
    <h3 class="mb-4">🌟 Featured Products Today</h3>
    <div class="row justify-content-center g-4">

      <?php foreach ($featuredProducts as $product): ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100 hover-zoom">
            <img src="<?= htmlspecialchars($product['image']) ?>" class="card-img-top rounded" style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($product['name']) ?>">
            <div class="card-body text-center">
              <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
              <p class="badge bg-success fs-6">₪<?= number_format($product['price'], 2) ?></p>
              <div class="mt-3">
                <a href="shop.php" class="btn btn-outline-primary shop-btn btn-sm">🔎 View Product</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

  <!-- Quick Access Buttons -->
  <div class="d-flex flex-wrap justify-content-center gap-3 my-5">
    <a href="shop.php" class="btn btn-outline-primary shop-btn btn-lg">🛒 Shop Now</a>
    <a href="myOrders.php" class="btn btn-outline-success shop-btn btn-lg">📦 My Orders</a>
    <a href="contact.php" class="btn btn-outline-info shop-btn btn-lg">📬 Contact Us</a>
    <a href="shop.php#newArrivals" class="btn btn-outline-warning shop-btn btn-lg">🆕 New Arrivals</a>
  </div>

  <!-- Personalized Recommendation -->
  <div class="alert alert-success mx-auto" style="max-width: 600px;">
    <?= $recommendation ?>
  </div>

</div>

<!-- Flash Messages Fade Out -->
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
