<?php
require_once '../php/dbConnect.php';

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
    if (isset($week->_id)) {
        $weeklyLabels[] = 'Week ' . $week->_id;
        $weeklyData[] = round($week->weeklyProfit, 2);
        $weeklyOrders[] = $week->orderCount;
    }
}

// Customer Lifetime Value Analysis
$clvPipeline = [
    ['$match' => [
        'userId' => ['$exists' => true, '$ne' => null]
    ]],
    ['$group' => [
        '_id' => '$userId',
        'totalSpent' => ['$sum' => '$total'],
        'orderCount' => ['$sum' => 1],
        'firstOrder' => ['$min' => '$createdAt'],
        'lastOrder' => ['$max' => '$createdAt']
    ]],
    ['$addFields' => [
        'averageOrderValue' => ['$divide' => ['$totalSpent', '$orderCount']],
        'customerLifetime' => ['$divide' => [
            ['$subtract' => ['$lastOrder', '$firstOrder']], 
            1000 * 60 * 60 * 24 // Convert to days
        ]]
    ]],
    ['$sort' => ['totalSpent' => -1]],
    ['$limit' => 10]
];
$topCustomers = $db->orders->aggregate($clvPipeline)->toArray();

// Seasonal Order Trends (Last 12 months)
$seasonalPipeline = [
    ['$match' => [
        'createdAt' => [
            '$gte' => new MongoDB\BSON\UTCDateTime(strtotime('-12 months') * 1000)
        ]
    ]],
    ['$addFields' => [
        'month' => ['$month' => '$createdAt'],
        'year' => ['$year' => '$createdAt']
    ]],
    ['$group' => [
        '_id' => ['month' => '$month', 'year' => '$year'],
        'orderCount' => ['$sum' => 1],
        'totalRevenue' => ['$sum' => '$total'],
        'avgOrderValue' => ['$avg' => '$total']
    ]],
    ['$sort' => ['_id.year' => 1, '_id.month' => 1]]
];
$seasonalData = $db->orders->aggregate($seasonalPipeline)->toArray();

// Prepare seasonal data for chart
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$seasonalLabels = [];
$seasonalOrderCounts = [];
$seasonalRevenue = [];

foreach ($seasonalData as $data) {
    if (isset($data->_id) && isset($data->_id->month) && isset($data->_id->year)) {
        $monthName = $monthNames[$data->_id->month - 1] . ' ' . $data->_id->year;
        $seasonalLabels[] = $monthName;
        $seasonalOrderCounts[] = $data->orderCount;
        $seasonalRevenue[] = round($data->totalRevenue, 2);
    }
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
  <link rel="stylesheet" href="../css/western-theme.css">
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

  <!-- New Analytics Charts -->
  <div class="row mb-5">
    <!-- Customer Lifetime Value Chart -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-warning text-white">👑 Top Customers by Lifetime Value</div>
        <div class="card-body">
          <canvas id="clvChart" height="300"></canvas>
        </div>
      </div>
    </div>

    <!-- Seasonal Trends Chart -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-info text-white">📅 Seasonal Order Trends (12 Months)</div>
        <div class="card-body">
          <canvas id="seasonalChart" height="300"></canvas>
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

<!-- Top Customers Analysis -->
<div class="card shadow mt-4">
  <div class="card-header bg-warning text-white">👑 Top 10 Customers by Lifetime Value</div>
  <div class="card-body">
    <?php if (empty($topCustomers)): ?>
      <div class="alert alert-info text-center mb-0">
        📊 No customer data available yet.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Customer ID</th>
              <th>Total Spent (₪)</th>
              <th>Orders</th>
              <th>Avg Order (₪)</th>
              <th>Customer Lifetime (Days)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topCustomers as $customer): ?>
              <tr>
                <td>
                  <a href="userProfile.php?id=<?= $customer->_id ?>" class="text-decoration-none">
                    <?= isset($customer->_id) && is_string($customer->_id) ? substr($customer->_id, 0, 8) . '...' : (string)$customer->_id ?>
                  </a>
                </td>
                <td class="text-success fw-bold">₪<?= number_format($customer->totalSpent, 2) ?></td>
                <td><span class="badge bg-primary"><?= $customer->orderCount ?></span></td>
                <td>₪<?= number_format($customer->averageOrderValue, 2) ?></td>
                <td><?= round($customer->customerLifetime, 1) ?> days</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
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

  // Customer Lifetime Value Chart
  const clvCtx = document.getElementById('clvChart').getContext('2d');
  new Chart(clvCtx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_map(function($customer) { return '₪' . number_format($customer->totalSpent, 0); }, array_slice($topCustomers, 0, 8))) ?>,
      datasets: [{
        data: <?= json_encode(array_column(array_slice($topCustomers, 0, 8), 'totalSpent')) ?>,
        backgroundColor: [
          'rgba(255, 99, 132, 0.8)',
          'rgba(54, 162, 235, 0.8)',
          'rgba(255, 206, 86, 0.8)',
          'rgba(75, 192, 192, 0.8)',
          'rgba(153, 102, 255, 0.8)',
          'rgba(255, 159, 64, 0.8)',
          'rgba(199, 199, 199, 0.8)',
          'rgba(83, 102, 255, 0.8)'
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true
          }
        },
        title: {
          display: true,
          text: 'Top Customers by Total Spending'
        }
      }
    }
  });

  // Seasonal Trends Chart
  const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
  new Chart(seasonalCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($seasonalLabels) ?>,
      datasets: [{
        label: 'Order Count',
        data: <?= json_encode($seasonalOrderCounts) ?>,
        backgroundColor: 'rgba(255, 159, 64, 0.7)',
        borderColor: 'rgba(255, 159, 64, 1)',
        borderWidth: 1,
        yAxisID: 'y'
      }, {
        label: 'Revenue (₪)',
        data: <?= json_encode($seasonalRevenue) ?>,
        backgroundColor: 'rgba(75, 192, 192, 0.7)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 1,
        yAxisID: 'y1'
      }]
    },
    options: {
      responsive: true,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      scales: {
        x: {
          title: {
            display: true,
            text: 'Month'
          }
        },
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          title: {
            display: true,
            text: 'Order Count'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Revenue (₪)'
          },
          grid: {
            drawOnChartArea: false,
          },
        }
      },
      plugins: {
        title: {
          display: true,
          text: 'Monthly Order Trends & Revenue'
        }
      }
    }
  });
});
</script>

</body>
</html>
