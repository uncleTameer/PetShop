<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Get order statistics for most ordered products
$pipeline = [
    ['$unwind' => '$items'],
    ['$group' => ['_id' => '$items.name', 'count' => ['$sum' => '$items.quantity']]],
    ['$sort' => ['count' => -1]]
];
$orderStats = $db->orders->aggregate($pipeline)->toArray();
$allProducts = $db->products->find()->toArray();

$orderedNames = array_map(fn($item) => $item->_id, $orderStats);
$neverOrderedProducts = array_filter($allProducts, fn($product) => !in_array($product['name'], $orderedNames));

// Get weekly profit data for current month
$currentMonth = date('Y-m');
$weeklyProfitPipeline = [
    ['$match' => [
        'createdAt' => [
            '$gte' => new MongoDB\BSON\UTCDateTime(strtotime($currentMonth . '-01') * 1000),
            '$lt' => new MongoDB\BSON\UTCDateTime(strtotime($currentMonth . '-01 +1 month') * 1000)
        ]
    ]],
    ['$addFields' => [
        'week' => ['$week' => '$createdAt'],
        'totalWithVAT' => ['$multiply' => ['$total', 1.17]] // Adding 17% VAT
    ]],
    ['$group' => [
        '_id' => '$week',
        'weeklyProfit' => ['$sum' => '$totalWithVAT'],
        'orderCount' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id' => 1]]
];
$weeklyProfits = $db->orders->aggregate($weeklyProfitPipeline)->toArray();

// Prepare data for charts
$weeklyLabels = [];
$weeklyData = [];
$weeklyOrders = [];

foreach ($weeklyProfits as $week) {
    $weeklyLabels[] = 'Week ' . $week->_id;
    $weeklyData[] = round($week->weeklyProfit, 2);
    $weeklyOrders[] = $week->orderCount;
}

// Get most and least ordered products for lists
$mostOrdered = array_slice($orderStats, 0, 5);
$leastOrdered = array_slice(array_reverse($orderStats), 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Report - Admin</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success text-center m-3"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger text-center m-3"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Back to Dashboard</a>
  <div class="d-flex align-items-center ms-auto text-white">
    <?php
      $profilePath = '../uploads/' . ($_SESSION['user']['profilePicture'] ?? 'default.png');
      $img = file_exists($profilePath) ? $profilePath : '../uploads/default.png';
    ?>
    <img src="<?= $img ?>" alt="Profile" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">📊 Full Order Report</h2>

  <!-- Charts Section -->
  <div class="row mb-5">
    <!-- Most Ordered Products Chart -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">🏆 Most Ordered Products</div>
        <div class="card-body">
          <canvas id="mostOrderedChart" height="300"></canvas>
        </div>
      </div>
    </div>

    <!-- Weekly Profits Chart -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-success text-white">💰 Weekly Profits (Current Month)</div>
        <div class="card-body">
          <canvas id="weeklyProfitsChart" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Ordered Products Table -->
  <div class="card shadow mb-4">
    <div class="card-header bg-info text-white">🛒 Products Ordered At Least Once</div>
    <div class="card-body">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
          <tr><th>Product Name</th><th>Times Ordered</th></tr>
        </thead>
        <tbody>
        <?php foreach ($orderStats as $item): ?>
          <tr><td><?= htmlspecialchars($item->_id) ?></td><td><?= $item->count ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<!-- Products Never Ordered Table -->
<div class="card shadow mt-4">
  <div class="card-header bg-danger text-white">🚫 Products Never Ordered</div>
  <div class="card-body">
    <?php if (empty($neverOrderedProducts)): ?>
      <div class="alert alert-success text-center mb-0">
        🎉 No unused products! All products have been ordered.
      </div>
    <?php else: ?>
      <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
          <tr><th>Product Name</th><th>Price</th><th>Stock</th></tr>
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

<!-- Most and Least Ordered Products Lists -->
<div class="row g-4 mt-4">
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

</div>

<script>
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-10px)';
    setTimeout(() => el.remove(), 500);
  }, 3000);
});

document.addEventListener('DOMContentLoaded', () => {
  // Most Ordered Products Bar Chart
  const mostOrderedCtx = document.getElementById('mostOrderedChart').getContext('2d');
  new Chart(mostOrderedCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column(array_slice($orderStats, 0, 8), '_id')) ?>,
      datasets: [{
        label: 'Times Ordered',
        data: <?= json_encode(array_column(array_slice($orderStats, 0, 8), 'count')) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Number of Orders'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Products'
          }
        }
      },
      plugins: {
        legend: { display: false },
        title: { 
          display: true,
          text: 'Most Popular Products'
        }
      }
    }
  });

  // Weekly Profits Line Chart
  const weeklyProfitsCtx = document.getElementById('weeklyProfitsChart').getContext('2d');
  new Chart(weeklyProfitsCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($weeklyLabels) ?>,
      datasets: [{
        label: 'Weekly Profit (₪)',
        data: <?= json_encode($weeklyData) ?>,
        borderColor: 'rgba(75, 192, 192, 1)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderWidth: 3,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Profit (₪)'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Week of Month'
          }
        }
      },
      plugins: {
        legend: { display: false },
        title: { 
          display: true,
          text: 'Weekly Profits (Including VAT)'
        }
      }
    }
  });
});
</script>

</body>
</html>
