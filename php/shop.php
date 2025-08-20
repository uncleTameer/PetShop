<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// --- Inputs ---
$search         = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';

// --- Build query ONCE ---
$query = [];

// 1) Apply user default category preference only if no explicit category was chosen
if (empty($categoryFilter)) {
    if (isset($_SESSION['user'])) {
        $prefId = $_SESSION['preferences']['defaultCategoryId'] ?? null;

        if (!$prefId) {
            $u = $db->users->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user']['id'])],
                ['projection' => ['preferences.defaultCategoryId' => 1]]
            );
            if (!empty($u['preferences']['defaultCategoryId'])) {
                $prefId = (string)$u['preferences']['defaultCategoryId'];
                $_SESSION['preferences']['defaultCategoryId'] = $prefId;
            }
        }

        if (!empty($prefId)) {
            $query['categoryId'] = new MongoDB\BSON\ObjectId($prefId);
        }
    } else {
        if (!empty($_SESSION['preferences']['defaultCategoryId'])) {
            $query['categoryId'] = new MongoDB\BSON\ObjectId($_SESSION['preferences']['defaultCategoryId']);
        }
    }
}

// 2) Explicit category via GET always overrides preference
if (!empty($categoryFilter)) {
    $query['categoryId'] = new MongoDB\BSON\ObjectId($categoryFilter);
}

// 3) Text search
if (!empty($search)) {
    $query['name'] = ['$regex' => $search, '$options' => 'i'];
}

// Fetch data
$products   = $db->products->find($query);
$categories = $db->categories->find([], ['sort' => ['name' => 1]]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop - Pet Shop</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="../php/index.php">🏠 Pet Shop</a>
  <div class="ms-auto text-white d-flex align-items-center gap-2">
    <?php if (isset($_SESSION['user'])): ?>
      <div class="d-flex align-items-center text-white me-2">
        <?php
          $pp = !empty($_SESSION['user']['profilePicture'])
                ? '../uploads/' . htmlspecialchars($_SESSION['user']['profilePicture'])
                : '../uploads/default.png';
        ?>
        <img src="<?= $pp ?>" alt="Profile" class="rounded-circle me-2"
             style="width:35px;height:35px;object-fit:cover;">
        <span>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
      </div>
      <a href="../editProfile.php" class="btn btn-outline-info btn-sm me-2">👤 Edit Profile</a>
      <a href="../myOrders.php" class="btn btn-outline-light btn-sm">📦 My Orders</a>
      <a href="../wishlist.php" class="btn btn-outline-danger btn-sm">❤️ Wishlist</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
    <a href="../cart.php" class="btn btn-warning btn-sm ms-2">🛒 Cart</a>
  </div>
</nav>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger alert-dismissible fade show text-center mx-4" role="alert">
    <?= $_SESSION['error_message'] ?>
  </div>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show text-center mx-4" role="alert">
    <?= $_SESSION['success_message'] ?>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="container py-4">
  <h2 class="text-center mb-4">All Products</h2>

  <!-- Default category preference control -->
  <?php
    $catsArr     = iterator_to_array($categories, false);
    $activePref  = $_SESSION['preferences']['defaultCategoryId'] ?? '';
    // Re-open categories cursor for the filter form below
    $categories  = $db->categories->find([], ['sort' => ['name' => 1]]);
  ?>
  <form method="POST" action="setDefaultCategory.php" class="d-flex align-items-center gap-2 mb-3">
    <label class="mb-0">Default category:</label>
    <select name="categoryId" class="form-select form-select-sm" style="width: 180px">
      <option value="">— none —</option>
      <?php foreach ($catsArr as $c): ?>
        <option value="<?= (string)$c['_id'] ?>" <?= ($activePref === (string)$c['_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
  </form>

  <!-- Search and Filter Bar -->
  <div class="row justify-content-center mb-4">
    <div class="col-md-8">
      <form method="GET" action="shop.php" class="row g-3">
        <div class="col-md-5">
          <input type="text" name="search" class="form-control"
                 placeholder="Search products by name..."
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
          <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
              <?php $catId = (string)$category['_id']; ?>
              <option value="<?= $catId ?>" <?= ($categoryFilter === $catId) ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100">🔍 Search</button>
        </div>
      </form>
      <?php if (!empty($search) || !empty($categoryFilter)): ?>
        <div class="text-center mt-2">
          <a href="shop.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <?php foreach ($products as $product): ?>
      <?php
        // Resolve image path (store like "uploads/xxx" in DB)
        $imgRelative = isset($product['image']) ? (string)$product['image'] : 'uploads/default.png';
        if (strpos($imgRelative, 'uploads/') !== 0) {
            $imgRelative = 'uploads/default.png';
        }
      ?>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <a href="../product.php?id=<?= (string)$product['_id'] ?>" class="text-decoration-none">
            <img src="../<?= htmlspecialchars($imgRelative) ?>"
                 class="card-img-top"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 style="height:200px;object-fit:cover;">
          </a>
          <div class="card-body">
            <h5 class="card-title">
              <a href="../product.php?id=<?= (string)$product['_id'] ?>" class="text-decoration-none text-dark">
                <?= htmlspecialchars($product['name']) ?>
              </a>
            </h5>
            <?php
              $categoryDoc = null;
              if (isset($product['categoryId'])) {
                  $categoryDoc = $db->categories->findOne(['_id' => $product['categoryId']]);
              }
            ?>
            <?php if ($categoryDoc): ?>
              <span class="badge bg-info mb-2"><?= htmlspecialchars($categoryDoc['name']) ?></span>
            <?php endif; ?>
            <p class="card-text">₪<?= number_format((float)$product['price'], 2) ?></p>
            <p class="card-text"><small class="text-muted">Stock: <?= (int)$product['stock'] ?></small></p>
            <div class="d-flex gap-2">
              <a href="../product.php?id=<?= (string)$product['_id'] ?>" class="btn btn-outline-primary flex-fill">👁️ View Details</a>

              <?php if (!empty($product['stock'])): ?>
                <form method="POST" action="addToCart.php" class="flex-fill">
                  <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                  <input type="hidden" name="redirect" value="shop.php">
                  <button type="submit" class="btn btn-primary w-100">🛒 Add to Cart</button>
                </form>
              <?php else: ?>
                <button class="btn btn-secondary w-100" disabled>❌ Out of Stock</button>
              <?php endif; ?>

              <?php if (isset($_SESSION['user'])): ?>
                <form method="POST" action="../wishlist.php" class="flex-fill">
                  <input type="hidden" name="productId" value="<?= (string)$product['_id'] ?>">
                  <button type="submit" name="add_to_wishlist" class="btn btn-outline-danger w-100">❤️ Wishlist</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!isset($product)): ?>
      <div class="col-12">
        <div class="alert alert-info text-center">No products match your filters.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Auto-close alerts after 3 seconds
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(el => {
    const inst = bootstrap.Alert.getOrCreateInstance(el);
    inst.close();
  });
}, 3000);
</script>

</body>
</html>
