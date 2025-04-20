<?php
echo "🧪 Step 1 reached<br>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../php/dbConnect.php';
session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}
echo "🧪 Step 2 (passed session check)<br>";


$products = $db->products->find();
echo "🧪 Step 3 (connected to DB)<br>";

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

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4 text-center">📦 Manage Products</h2>

  <table class="table table-bordered table-striped">
    <thead class="table-dark text-center">
      <tr>
        <th>Name</th>
        <th>Price (₪)</th>
        <th>Category</th>
        <th>Stock</th>
        <th>Image</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody class="text-center">
      <?php foreach ($products as $product): ?>
        <tr>
          <td><?= htmlspecialchars($product['name']) ?></td>
          <td><?= number_format($product['price'], 2) ?></td>
          <td><?= htmlspecialchars($product['category']) ?></td>
          <td><?= $product['stock'] ?></td>
          <td><img src="../<?= $product['image'] ?>" alt="image" style="height: 50px;"></td>
          <td>
            <a href="#" class="btn btn-sm btn-warning">Edit</a>
            <a href="#" class="btn btn-sm btn-danger">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
