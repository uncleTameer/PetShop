<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

// Fetch order stats
$pipeline = [
    ['$unwind' => '$items'],
    ['$group' => [
        '_id' => '$items.name',
        'count' => ['$sum' => '$items.quantity']
    ]],
    ['$sort' => ['count' => -1]]
];
$orderStats = $db->orders->aggregate($pipeline)->toArray();

// Fetch all products
$allProducts = $db->products->find()->toArray();

// Prepare ordered product names
$orderedNames = array_map(function($item) {
    return $item->_id;
}, $orderStats);

// Find products with 0 orders
$neverOrderedProducts = array_filter($allProducts, function($product) use ($orderedNames) {
    return !in_array($product['name'], $orderedNames);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Report - Admin</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Back to Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">📊 Full Order Report</h2>

  <!-- Ordered Products -->
  <div class="card mb-5 shadow">
    <div class="card-header bg-primary text-white">
      🛒 Products Ordered At Least Once
    </div>
    <div class="card-body">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
          <tr>
            <th>Product Name</th>
            <th>Times Ordered</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orderStats as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item->_id) ?></td>
              <td><?= $item->count ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Never Ordered Products -->
  <div class="card shadow">
    <div class="card-header bg-danger text-white">
      🚫 Products Never Ordered
    </div>
    <div class="card-body">
      <?php if (empty($neverOrderedProducts)): ?>
        <div class="alert alert-success text-center mb-0">
          🎉 No unused products! All products have been ordered.
        </div>
      <?php else: ?>
        <table class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Product Name</th>
              <th>Price</th>
              <th>Stock</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($neverOrderedProducts as $product): ?>
              <tr>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td>₪<?= number_format($product['price'], 2) ?></td>
                <td><?= $product['stock'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
   </div>
   <div class="container my-5">
       <h3 class="text-center mb-4">🏆 Top 5 Bestselling Products</h3>
       <canvas id="topProductsChart" height="120"></canvas>
      </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('topProductsChart').getContext('2d');

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column(array_slice($orderStats, 0, 5), '_id')) ?>,
            datasets: [{
                label: 'Orders',
                data: <?= json_encode(array_column(array_slice($orderStats, 0, 5), 'count')) ?>,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
          responsive: true,
          animation: {
            animateScale: true,    // ✅ POP Effect
            animateRotate: true,   // ✅ Rotate a little when showing
            duration: 1200,        // ✅ 1.2 seconds smooth
            easing: 'easeOutBounce' // ✅ Cool bounce at the end
          },
          plugins: {
            legend: {
              position: 'bottom',
            },
            title: {
              display: false
            }
          }
        }
    });
});
</script>

</body>
</html>
