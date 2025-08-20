<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
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

// ---------- FILTER LOGIC: default show all; filter only when lowStock present ----------
$filter = [];
$lowStockThreshold = null;

if (isset($_GET['lowStock']) && is_numeric($_GET['lowStock']) && (int)$_GET['lowStock'] > 0) {
    $lowStockThreshold = (int) $_GET['lowStock'];
    $filter = ['stock' => ['$lt' => $lowStockThreshold]];
}
// ----------------------------------------------------------------

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

  <!-- Controls -->
  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <!-- View All -->
    <a href="manageProducts.php" class="btn btn-outline-secondary">🔁 View All</a>

    <!-- Low Stock Filter Form -->
    <form id="lowStockForm" method="GET" action="manageProducts.php" class="d-flex flex-wrap gap-2 align-items-center">
      <?php
        $current  = ($lowStockThreshold !== null) ? (int)$lowStockThreshold : null;
        $options  = [5, 10, 20, 30];
        $inPreset = $current !== null && in_array($current, $options, true);
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
             value="<?= (!$inPreset && $current !== null) ? htmlspecialchars((string)$current) : '' ?>"
             placeholder="Any">

      <button type="submit" class="btn btn-outline-primary btn-sm">Apply</button>

      <noscript>
        <!-- Fallback if JS disabled -->
        <input type="hidden" name="lowStock" value="<?= $current ?? '' ?>">
      </noscript>
    </form>

    <!-- Active Filter Badge -->
    <?php if ($current !== null): ?>
      <span class="badge bg-warning text-dark">Filtering: stock &lt; <?= (int)$current ?></span>
    <?php endif; ?>

    <!-- Add New Product -->
    <a href="addProduct.php" class="btn btn-success ms-auto">➕ Add New Product</a>
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
        <tr class="<?= ($lowStockThreshold !== null && isset($product['stock']) && $product['stock'] < $lowStockThreshold) ? 'table-warning' : '' ?>">
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
      setTimeout(() => alert.remove(), 500);
    }, 3000);
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

<script>
(function () {
  const form   = document.getElementById('lowStockForm');
  if (!form) return;

  const select = form.querySelector('#lowStockSelect');
  const input  = form.querySelector('#lowStockCustom');

  // Keep the two inputs mutually exclusive
  select.addEventListener('change', () => { input.value = ''; });
  input.addEventListener('input',  () => { if (input.value !== '') select.value = ''; });

  // Ensure exactly one lowStock value is submitted
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
