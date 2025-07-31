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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Orders - Horse & Camel</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

<div class="container py-4">
  <h2 class="text-center mb-4">📦 Order Management</h2>

  <div class="row g-4">
    <!-- Pending -->
    <div class="col-md-6">
      <h5 class="text-primary text-center">🕐 Pending Orders</h5>
      <table id="pendingOrders" class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
          <tr><th>ID</th><th>Customer</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pending as $order): 
          $user = $db->users->findOne(['_id' => $order['userId']]);
          $name = $user['fullName'] ?? 'Unknown';
          $shortId = substr((string)$order['_id'], -5);
        ?>
          <tr>
            <td><?= $shortId ?></td>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-info" onclick="loadOrderDetails('<?= $order['_id'] ?>')">🔍 View</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Shipped -->
    <div class="col-md-6">
      <h5 class="text-success text-center">🚚 Shipped Orders</h5>
      <table id="shippedOrders" class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
          <tr><th>ID</th><th>Customer</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($shipped as $order): 
          $user = $db->users->findOne(['_id' => $order['userId']]);
          $name = $user['fullName'] ?? 'Unknown';
          $shortId = substr((string)$order['_id'], -5);
        ?>
          <tr>
            <td><?= $shortId ?></td>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= htmlspecialchars($order['status']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-info" onclick="loadOrderDetails('<?= $order['_id'] ?>')">🔍 View</button>
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
      $user = $db->users->findOne(['_id' => $order['userId']]);
      $name = $user['fullName'] ?? 'Unknown';
      $shortId = substr((string)$order['_id'], -5);
    ?>
      <tr>
        <td><?= $shortId ?></td>
        <td><?= htmlspecialchars($name) ?></td>
        <td><?= htmlspecialchars($order['status']) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-info" onclick="loadOrderDetails('<?= $order['_id'] ?>')">🔍 View</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">📦 Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="orderDetailsContent">Loading...</div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script>
$(function () {
  $('#pendingOrders, #shippedOrders, #cancelledOrders').DataTable({ pageLength: 5 });

  // Alert fade
  $('.alert').each(function () {
    setTimeout(() => $(this).fadeOut(500), 3000);
  });
});

function loadOrderDetails(orderId) {
  const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
  $('#orderDetailsContent').html("Loading...");
  fetch(`fetchOrderDetails.php?id=${orderId}`)
    .then(res => res.text())
    .then(html => {
      $('#orderDetailsContent').html(html);
      modal.show();
    }).catch(() => {
      $('#orderDetailsContent').html("<div class='text-danger'>Failed to load order details.</div>");
    });
}
</script>
</body>
</html>
