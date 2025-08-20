<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// ---- Low-stock threshold (default 5; accepts ?lowStock=N) ----
$lowStockThreshold = (isset($_GET['lowStock']) && is_numeric($_GET['lowStock']) && (int)$_GET['lowStock'] > 0)
  ? (int)$_GET['lowStock']
  : 5;

// ---- Totals ----
$productCount        = $db->products->countDocuments();
$userCount           = $db->users->countDocuments();
$orderCount          = $db->orders->countDocuments();
$lowStockCount       = $db->products->countDocuments(['stock' => ['$lt' => $lowStockThreshold]]);
$unreadNotifications = $db->notifications->countDocuments(['read' => ['$ne' => true]]);

// ---- Product order stats (kept if you use it elsewhere) ----
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

  <!-- Threshold control (preset + custom) -->
  <form id="lowStockForm" method="GET" action="dashboard.php" class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <?php
      $options  = [5, 10, 20, 30];
      $current  = (int)$lowStockThreshold;
      $inPreset = in_array($current, $options, true);
    ?>
    <label for="lowStockSelect" class="mb-0">Low stock threshold:</label>
    <select id="lowStockSelect" class="form-select form-select-sm" style="width: 140px">
      <option value="" <?= $inPreset ? '' : 'selected' ?>>(choose)</option>
      <?php foreach ($options as $opt): ?>
        <option value="<?= $opt ?>" <?= ($inPreset && $current === $opt) ? 'selected' : ''?>>&lt; <?= $opt ?></option>
      <?php endforeach; ?>
    </select>

    <label for="lowStockCustom" class="mb-0">or custom:</label>
    <input type="number" id="lowStockCustom" min="1"
           class="form-control form-control-sm" style="width: 90px"
           value="<?= (!$inPreset && $current > 0) ? htmlspecialchars((string)$current) : '' ?>"
           placeholder="Any">

    <button type="submit" class="btn btn-outline-danger btn-sm">Apply</button>

    <noscript>
      <input type="hidden" name="lowStock" value="<?= (int)$current ?>">
      <button type="submit" class="btn btn-outline-danger btn-sm">Apply</button>
    </noscript>
  </form>

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

    <!-- Low Stock (uses dynamic threshold) -->
    <div class="col-md-3">
      <a href="manageProducts.php?lowStock=<?= (int)$lowStockThreshold ?>" class="text-decoration-none">
        <div class="card text-white bg-danger shadow-sm">
          <div class="card-body text-center">
            <h4><?= $lowStockCount ?></h4>
            <p class="card-text">Low Stock Items (&lt; <?= (int)$lowStockThreshold ?>)</p>
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

  <div class="text-center mt-4">
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

<script>
// Keep dropdown and custom input mutually exclusive, submit one lowStock value
(function () {
  const form   = document.getElementById('lowStockForm');
  if (!form) return;

  const select = form.querySelector('#lowStockSelect');
  const input  = form.querySelector('#lowStockCustom');

  select.addEventListener('change', () => { input.value = ''; });
  input.addEventListener('input',  () => { if (input.value !== '') select.value = ''; });

  form.addEventListener('submit', () => {
    // Remove previously added hidden field(s)
    [...form.querySelectorAll('input[name="lowStock"]')].forEach(n => n.remove());

    let value = '';
    if (input.value.trim() !== '') {
      value = input.value.trim();
    } else if (select.value !== '') {
      value = select.value;
    }

    if (value !== '') {
      const hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = 'lowStock';
      hidden.value = value;
      form.appendChild(hidden);
    }
  });
})();
</script>

</body>
</html>
