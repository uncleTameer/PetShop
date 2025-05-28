<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!in_array($_SESSION['user']['role'], ['admin', 'moderator'])) {
  header("Location: ../index.php");
  exit;
}

$adminName = htmlspecialchars($_SESSION['user']['name']);
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
  <span class="navbar-brand mb-0 h1">Admin Panel</span>
  <div class="ms-auto text-white">
    Welcome, <?= $adminName ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-5">
  <h2 class="mb-4 text-center">📊 Dashboard Overview</h2>

  <div class="row g-4 text-center">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">📦 Products</h5>
          <p class="card-text">Manage inventory, add or remove items</p>
          <a href="manageProducts.php" class="btn btn-primary">Go</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">🧾 Orders</h5>
          <p class="card-text">View and handle customer orders</p>
          <a href="manageOrders.php" class="btn btn-primary">Go</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title
