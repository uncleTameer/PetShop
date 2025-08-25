<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$orders = $db->orders->find([], ['sort' => ['createdAt' => -1]])->toArray();

function filterOrdersByStatus($orders, $status) {
    return array_filter($orders, fn($o) => ($o['status'] ?? 'Pending') === $status);
}

$pending = filterOrdersByStatus($orders, 'Pending');
$shipped = filterOrdersByStatus($orders, 'Shipped');
$cancelled = filterOrdersByStatus($orders, 'Cancelled');
$delivered = filterOrdersByStatus($orders, 'Delivered');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Orders - Horse & Camel</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/western-theme.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
  <div class="d-flex align-items-center ms-auto text-white">
    <?php
      $imgPath = '../uploads/' . ($_SESSION['user']['profilePicture'] ?? 'default.png');
      if (!file_exists($imgPath)) $imgPath = '../uploads/default.png';
    ?>
    <img src="<?= $imgPath ?>" class="rounded-circle me-2" style="width:35px; height:35px; object-fit:cover;" alt="Profile">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<!-- Western Hero Section -->
<div class="hero-section mb-4">
  <div class="container text-center">
    <h1 class="western-title animate__animated animate__fadeInDown">📦 Order Management</h1>
    <p class="western-subtitle animate__animated animate__fadeInUp">Track and manage all customer orders, partner!</p>
  </div>
</div>

<div class="container py-4">
  <h2 class="western-title text-center mb-4">📦 Order Management</h2>

  <div class="row g-4">
    <!-- Pending -->
    <div class="col-md-6">
      <h5 class="western-title text-center">🕐 Pending Orders</h5>
      <table id="pendingOrders" class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
          <tr><th>ID</th><th>Customer</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pending as $order): 
          $shortId = substr((string)$order['_id'], -5);
          if (isset($order['userId'])) {
              $user = $db->users->findOne(['_id' => $order['userId']]);
              $name = $user['fullName'] ?? 'Unknown';
          } else {
              $name = $order['guestInfo']['name'] ?? 'Guest User';
          }
        ?>
          <tr>
            <td><?= $shortId ?></td>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td>
              <a href="orderDetails.php?id=<?= $order['_id'] ?>" class="btn btn-sm btn-outline-info">🔍 View</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Shipped / Awaiting Collection -->
    <div class="col-md-6">
      <h5 class="western-title text-center">🚚 Shipped / 🏬 Awaiting Collection</h5>
      <table id="shippedOrders" class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
          <tr><th>ID</th><th>Customer</th><th>Fulfillment</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($shipped as $order): 
          $shortId = substr((string)$order['_id'], -5);
          if (isset($order['userId'])) {
              $user = $db->users->findOne(['_id' => $order['userId']]);
              $name = $user['fullName'] ?? 'Unknown';
          } else {
              $name = $order['guestInfo']['name'] ?? 'Guest User';
          }
          $fulfillment = ($order['fulfillmentType'] ?? 'shipping') === 'pickup' ? 'Awaiting Collection' : 'Shipped';
        ?>
          <tr>
            <td><?= $shortId ?></td>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= htmlspecialchars($fulfillment) ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td>
              <a href="orderDetails.php?id=<?= $order['_id'] ?>" class="btn btn-sm btn-outline-info">🔍 View</a>
              <form method="POST" action="updateOrderStatus.php" style="display:inline-block;">
                <input type="hidden" name="orderId" value="<?= $order['_id'] ?>">
                <input type="hidden" name="status" value="Delivered">
                <button type="submit" class="btn btn-sm btn-success ms-2" onclick="return confirm('Mark this order as delivered?')">✅ Mark as Delivered</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- Delivered -->
    <div class="col-md-12 mt-5">
      <h5 class="text-info text-center">📬 Delivered Successfully</h5>
      <table id="deliveredOrders" class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
          <tr><th>ID</th><th>Customer</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($delivered as $order): 
          $shortId = substr((string)$order['_id'], -5);
          if (isset($order['userId'])) {
              $user = $db->users->findOne(['_id' => $order['userId']]);
              $name = $user['fullName'] ?? 'Unknown';
          } else {
              $name = $order['guestInfo']['name'] ?? 'Guest User';
          }
        ?>
          <tr>
            <td><?= $shortId ?></td>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td>
              <a href="orderDetails.php?id=<?= $order['_id'] ?>" class="btn btn-sm btn-outline-info">🔍 View</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Cancelled -->
  <h5 class="text-danger text-center mt-5">❌ Cancelled Orders</h5>
  <table id="cancelledOrders" class="table table-bordered table-striped text-center align-middle">
    <thead class="table-dark">
      <tr><th>ID</th><th>Customer</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php foreach ($cancelled as $order): 
      $shortId = substr((string)$order['_id'], -5);
      if (isset($order['userId'])) {
          $user = $db->users->findOne(['_id' => $order['userId']]);
          $name = $user['fullName'] ?? 'Unknown';
      } else {
          $name = $order['guestInfo']['name'] ?? 'Guest User';
      }
    ?>
      <tr>
        <td><?= $shortId ?></td>
        <td><?= htmlspecialchars($name) ?></td>
        <td><?= htmlspecialchars($order['status']) ?></td>
        <td>
                        <a href="orderDetails.php?id=<?= $order['_id'] ?>" class="btn btn-sm btn-outline-info">🔍 View</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script>
$(function () {
  $('#pendingOrders, #shippedOrders, #cancelledOrders, #deliveredOrders').DataTable({ pageLength: 5 });

  // Alert fade
  $('.alert').each(function () {
    setTimeout(() => $(this).fadeOut(500), 3000);
  });
});
</script>
</body>
</html>
