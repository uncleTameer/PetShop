<?php
session_start();
require __DIR__ . '/dbConnect.php';

use MongoDB\BSON\ObjectId;

// Check login - Allow browsing for everyone
$recentOrders = [];
if (isset($_SESSION['user']['id'])) {
    $userId = new ObjectId($_SESSION['user']['id']);
    $recentOrders = $db->orders->find(
        ['userId' => $userId],
        ['sort' => ['createdAt' => -1], 'limit' => 3]
    )->toArray();
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
  <!-- Asset paths adjusted: this file lives in /php so go up one level -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="#">Horse & Camel</a>
  <div class="ms-auto d-flex align-items-center gap-2">

    <?php if (isset($_SESSION['user'])): ?>
      <div class="d-flex align-items-center text-white me-2">
        <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
          <img src="../uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
               alt="Profile" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php else: ?>
          <img src="../uploads/default.png" 
               alt="Default" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php endif; ?>
        <span>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
      </div>
      <a href="editProfile.php" class="btn btn-outline-info btn-sm">👤 Edit Profile</a>
      <a href="wishlist.php" class="btn btn-outline-danger btn-sm">❤️ Wishlist</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    <?php else: ?>
      <a href="register.php" class="btn btn-outline-light btn-sm me-2">Register</a>
<a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
    <?php endif; ?>
    <a href="cart.php" class="btn btn-outline-warning btn-sm ms-2">🛒 Cart</a>
  </div>
</nav>

<!-- Main Container -->
<div class="container mt-5 text-center">

  <!-- Welcome Animation -->
  <div class="alert alert-primary animate__animated animate__fadeInDown flash-message">
    👋 <?= isset($_SESSION['user']) ? 'Welcome back, ' . htmlspecialchars($_SESSION['user']['name']) . '!' : 'Welcome to Horse & Camel Shop!' ?>
  </div>

  <h1 class="display-4 mb-3">Horse & Camel Shop</h1>
  <p class="lead mb-5">Find the best products for your pets 🐴🐫🐶🐱</p>

  <!-- Video Section -->
  <div class="mt-5 mb-4">
    <h3 class="mb-4">🎬 Featured Video</h3>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="ratio ratio-16x9">
          <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                  title="Featured Pet Care Video" 
                  frameborder="0" 
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                  allowfullscreen
                  class="rounded shadow">
          </iframe>
        </div>
      </div>
    </div>
  </div>

  <!-- Fun Fact -->
  <div class="alert alert-warning mx-auto" style="max-width: 600px;">
    🐾 Fun Fact: <?= $randomFact ?>
  </div>

  <!-- Featured Products Today -->
  <div class="mt-5">
    <h3 class="mb-4">🌟 Featured Products Today</h3>
    <div class="row justify-content-center g-4">
      <?php foreach ($featuredProducts as $product): ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100 hover-zoom">
            <img src="<?= htmlspecialchars(strpos($product['image'], 'uploads/') === 0 ? '../' . $product['image'] : '../uploads/default.png') ?>" class="card-img-top rounded" style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($product['name']) ?>">
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
    <a href="wishlist.php" class="btn btn-outline-danger shop-btn btn-lg">❤️ My Wishlist</a>
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
