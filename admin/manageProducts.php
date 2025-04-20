<?php
require_once '../php/dbConnect.php';
session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}
$filter = [];

if (isset($_GET['lowStock']) && $_GET['lowStock'] == 1) {
    $filter = ['stock' => ['$lt' => 5]];
}

$products = $db->products->find($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Products</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Back to Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">📦 Manage Products</h2>


<div class="container py-4">

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success text-center mb-4"><?= $_SESSION['success_message'] ?></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger text-center mb-4"><?= $_SESSION['error_message'] ?></div>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

  <a href="manageProducts.php?lowStock=1" class="btn btn-outline-danger mb-3">🔻 View Low Stock</a>
  <a href="manageProducts.php" class="btn btn-outline-secondary mb-3">🔁 View All</a>

  <a href="addProduct.php" class="btn btn-success mb-3">➕ Add New Product</a>

  <table class="table table-bordered table-striped text-center align-middle">
    <thead class="table-dark">
      <tr>
        <th>Name</th>
        <th>Price (₪)</th>
        <th>Category</th>
        <th>Stock</th>
        <th>Image</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $product): ?>
        <tr class="<?= $product['stock'] < 5 ? 'table-warning' : '' ?>">
          <td><?= htmlspecialchars($product['name']) ?></td>
          <td><?= number_format($product['price'], 2) ?></td>
          <td><?= htmlspecialchars($product['category']) ?></td>
          <td><?= $product['stock'] ?></td>
          <td>
            <img src="../<?= $product['image'] ?>" alt="Image" style="height: 60px;">
          </td>
          <td>
            <a href="editProduct.php?id=<?= $product['_id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
            <a href="deleteProduct.php?id=<?= $product['_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
