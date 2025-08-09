<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../php/index.php");
    exit;
}


// Query totals
$productCount = $db->products->countDocuments();
$userCount = $db->users->countDocuments();
$orderCount = $db->orders->countDocuments();
$lowStockCount = $db->products->countDocuments(['stock' => ['$lt' => 5]]);
$unreadNotifications = $db->notifications->countDocuments(['read' => ['$ne' => true]]);

// Query product order stats
$pipeline = [
    ['$unwind' => '$items'],
    ['$group' => [
        '_id' => '$items.name',
        'count' => ['$sum' => '$items.quantity']
    ]],
    ['$sort' => ['count' => -1]]
];

$productStats = $db->orders->aggregate($pipeline)->toArray();
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
  <div class="d-flex align-items-center ms-auto text-white">
    <?php
      $imgPath = '../uploads/' . ($_SESSION['user']['profilePicture'] ?? 'default.png');
      if (!file_exists($imgPath)) $imgPath = '../uploads/default.png';
    ?>
    <img src="<?= $imgPath ?>" alt="Profile" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">Welcome, Admin 👋</h2>

  <div class="row g-4 mb-5">
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

    <!-- Unread Notifications -->
    <div class="col-md-3">
      <a href="notifications.php" class="text-decoration-none">
        <div class="card text-white bg-warning shadow-sm">
          <div class="card-body text-center">
            <h4><?= $unreadNotifications ?></h4>
            <p class="card-text">Unread Notifications</p>
          </div>
        </div>
      </a>
    </div>
  </div>

  <div class="text-center mt-5">
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="orderReport.php" class="btn btn-outline-primary btn-lg">📊 View Full Order Report</a>
      <a href="notifications.php" class="btn btn-outline-warning btn-lg">
        🔔 System Notifications
        <?php if ($unreadNotifications > 0): ?>
          <span class="badge bg-danger ms-2"><?= $unreadNotifications ?></span>
        <?php endif; ?>
      </a>
      <a href="manageCategories.php" class="btn btn-outline-success btn-lg">📂 Manage Categories</a>
      <a href="createAccount.php" class="btn btn-outline-info btn-lg">👤 Create Account</a>
    </div>
  </div>


</div>

</body>
</html>
