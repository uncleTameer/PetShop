<?php
require_once '../php/dbConnect.php';
session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

// Query totals
$productCount = $db->products->countDocuments();
$userCount = $db->users->countDocuments();
$orderCount = $db->orders->countDocuments(); // Future-proof
$lowStockCount = $db->products->countDocuments(['stock' => ['$lt' => 5]]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="#">🏠 Admin Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">Welcome, Admin 👋</h2>

  <div class="row g-4">

<!-- Total Products -->
<div class="col-md-3">
  <a href="manageProducts.php" class="text-decoration-none">
    <div class="card text-white bg-primary shadow-sm">
      <div class="card-body text-center">
        <h4><?= $productCount ?></h4>
        <p class="card-text">Total Products</p>
      </div>
    </div>
  </a>
</div>

<!-- Registered Users -->
<div class="col-md-3">
  <a href="manageUsers.php" class="text-decoration-none">
    <div class="card text-white bg-success shadow-sm">
      <div class="card-body text-center">
        <h4><?= $userCount ?></h4>
        <p class="card-text">Registered Users</p>
      </div>
    </div>
  </a>
</div>

<!-- Total Orders -->
<div class="col-md-3">
  <a href="manageOrders.php" class="text-decoration-none">
    <div class="card text-white bg-info shadow-sm">
      <div class="card-body text-center">
        <h4><?= $orderCount ?></h4>
        <p class="card-text">Total Orders</p>
      </div>
    </div>
  </a>
</div>

<!-- Low Stock -->
<div class="col-md-3">
  <a href="manageProducts.php?lowStock=1" class="text-decoration-none">
    <div class="card text-white bg-danger shadow-sm">
      <div class="card-body text-center">
        <h4><?= $lowStockCount ?></h4>
        <p class="card-text">Low Stock Items</p>
      </div>
    </div>
  </a>
</div>

</div>


</body>
</html>
