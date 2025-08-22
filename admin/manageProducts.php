<?php
require_once '../php/dbConnect.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $product = $db->products->findOne(['_id' => new ObjectId($id)]);

        if (!$product) {
            $_SESSION['error_message'] = "❌ Product not found.";
        } else {
            // Delete image if exists
            if (isset($product['image'])) {
                $imagePath = '../' . $product['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $db->products->deleteOne(['_id' => new ObjectId($id)]);
            $_SESSION['success_message'] = "🗑️ Product deleted successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "❌ Error: " . $e->getMessage();
    }
    
    header("Location: manageProducts.php");
    exit;
}

// Handle low stock threshold update
if (isset($_POST['updateThreshold']) && isset($_POST['threshold'])) {
    $threshold = (int)$_POST['threshold'];
    if ($threshold > 0) {
        // Update all products with the new threshold
        $db->products->updateMany(
            [],
            ['$set' => ['lowStockThreshold' => $threshold]]
        );
        $_SESSION['success_message'] = "✅ Low stock threshold updated to {$threshold} items.";
        header("Location: manageProducts.php");
        exit;
    }
}

// Get current threshold (default to 5 if not set)
$currentThreshold = 5;
$thresholdDoc = $db->products->findOne(['lowStockThreshold' => ['$exists' => true]]);
if ($thresholdDoc && isset($thresholdDoc['lowStockThreshold'])) {
    $currentThreshold = $thresholdDoc['lowStockThreshold'];
}

$filter = [];

if (isset($_GET['lowStock']) && $_GET['lowStock'] == 1) {
    $filter = ['stock' => ['$lt' => $currentThreshold]];
}

$products = $db->products->find($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Products</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/western-theme.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Back to Dashboard</a>
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

  <div class="row mb-3">
    <div class="col-md-6">
      <a href="manageProducts.php?lowStock=1" class="btn btn-outline-danger mb-2">🔻 View Low Stock</a>
      <a href="manageProducts.php" class="btn btn-outline-secondary mb-2">🔁 View All</a>
      <a href="addProduct.php" class="btn btn-success mb-2">➕ Add New Product</a>
    </div>
    <div class="col-md-6">
      <form method="POST" class="d-flex gap-2 align-items-end">
        <div class="flex-grow-1">
          <label for="threshold" class="form-label">Low Stock Threshold:</label>
          <input type="number" id="threshold" name="threshold" value="<?= $currentThreshold ?>" 
                 min="1" max="100" class="form-control" required>
        </div>
        <button type="submit" name="updateThreshold" class="btn btn-warning">📊 Update Threshold</button>
      </form>
    </div>
  </div>

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
        <tr class="<?= $product['stock'] < $currentThreshold ? 'table-warning' : '' ?>">
          <td><?= htmlspecialchars($product['name']) ?></td>
          <td><?= number_format($product['price'], 2) ?></td>
          <td>
            <?php 
            if (isset($product['categoryId'])) {
                $category = $db->categories->findOne(['_id' => $product['categoryId']]);
                echo htmlspecialchars($category['name'] ?? 'Unknown Category');
            } else {
                echo htmlspecialchars($product['category'] ?? 'No Category');
            }
            ?>
          </td>
          <td><?= $product['stock'] ?></td>
          <td>
            <img src="../<?= $product['image'] ?>" alt="Image" style="height: 60px;">
          </td>
          <td>
            <a href="editProduct.php?id=<?= $product['_id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
            <a href="manageProducts.php?action=delete&id=<?= $product['_id'] ?>" 
               class="btn btn-danger btn-sm delete-btn"
               onclick="return confirm('Are you sure you want to delete this product?')">
               🗑️ Delete
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500); // remove after fade
    }, 3000); // 3 seconds
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const deleteButtons = document.querySelectorAll('.delete-btn');
  deleteButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
    });
  });
});
</script>

</body>
</html>
