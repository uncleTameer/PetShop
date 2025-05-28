<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!in_array($_SESSION['user']['role'], ['admin', 'moderator'])) {
  header("Location: ../index.php");
  exit;
}


// Query totals
$productCount = $db->products->countDocuments();
$userCount = $db->users->countDocuments();
$orderCount = $db->orders->countDocuments();
$lowStockCount = $db->products->countDocuments(['stock' => ['$lt' => 5]]);

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
$mostOrdered = array_slice($productStats, 0, 5);
$leastOrdered = array_slice(array_reverse($productStats), 0, 5);
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
  </div>

  <!-- Most Ordered and Least Ordered Products -->
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-success text-white">
          📈 Most Ordered Products
        </div>
        <div class="card-body">
          <ul class="list-group">
            <?php foreach ($mostOrdered as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($item->_id) ?>
                <span class="badge bg-success"><?= $item->count ?> times</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-danger text-white">
          📉 Least Ordered Products
        </div>
        <div class="card-body">
          <ul class="list-group">
            <?php foreach ($leastOrdered as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($item->_id) ?>
                <span class="badge bg-danger"><?= $item->count ?> times</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-5">
    <a href="orderReport.php" class="btn btn-outline-primary btn-lg">📊 View Full Order Report</a>
  </div>


</div>

</body>
</html>
