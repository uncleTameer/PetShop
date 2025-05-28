<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

if (!in_array($_SESSION['user']['role'], ['admin', 'moderator'])) {
  header("Location: ../index.php");
  exit;
}

$pipeline = [
    ['$unwind' => '$items'],
    ['$group' => ['_id' => '$items.name', 'count' => ['$sum' => '$items.quantity']]],
    ['$sort' => ['count' => -1]]
];
$orderStats = $db->orders->aggregate($pipeline)->toArray();
$allProducts = $db->products->find()->toArray();

$orderedNames = array_map(fn($item) => $item->_id, $orderStats);
$neverOrderedProducts = array_filter($allProducts, fn($product) => !in_array($product['name'], $orderedNames));
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

  <div class="row mb-5">
  <!-- Ordered Products Table -->
  <div class="col-md-7">
    <div class="card shadow">
      <div class="card-header bg-primary text-white">🛒 Products Ordered At Least Once</div>
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
  </div>

  <!-- Chart Section -->
  <div class="col-md-5 d-flex align-items-center justify-content-center">
    <div class="chart-container">
      <h5 class="text-center mb-3">🏆 Top 5 Bestsellers</h5>
      <canvas id="topProductsChart" height="260"></canvas>
    </div>
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
  const ctx = document.getElementById('topProductsChart').getContext('2d');
  new Chart(ctx, {
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
        animateScale: true,
        animateRotate: true,
        duration: 1000,
        easing: 'easeOutBounce'
      },
      plugins: {
        legend: { position: 'bottom' },
        title: { display: false }
      }
    }
  });
});
</script>

</body>
</html>
