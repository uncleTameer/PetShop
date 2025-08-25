<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle category operations
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description'] ?? '');
                
                if (!empty($name)) {
                    // Check if category already exists
                    $existing = $db->categories->findOne(['name' => $name]);
                    if (!$existing) {
                        $db->categories->insertOne([
                            'name' => $name,
                            'description' => $description,
                            'createdAt' => new MongoDB\BSON\UTCDateTime()
                        ]);
                        $_SESSION['success_message'] = "✅ Category '$name' added successfully!";
                    } else {
                        $_SESSION['error_message'] = "❌ Category '$name' already exists!";
                    }
                }
                break;
                
            case 'delete':
                $categoryId = $_POST['categoryId'];
                // Check if category is used by any products
                $productCount = $db->products->countDocuments(['categoryId' => new MongoDB\BSON\ObjectId($categoryId)]);
                
                if ($productCount > 0) {
                    $_SESSION['error_message'] = "❌ Cannot delete category - it has $productCount product(s) assigned to it!";
                } else {
                    $db->categories->deleteOne(['_id' => new MongoDB\BSON\ObjectId($categoryId)]);
                    $_SESSION['success_message'] = "✅ Category deleted successfully!";
                }
                break;
        }
    }
    header("Location: manageCategories.php");
    exit;
}

// Get all categories
$categories = $db->categories->find([], ['sort' => ['name' => 1]]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/western-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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

<!-- Western Hero Section -->
<div class="hero-section mb-4">
    <div class="container text-center">
        <h1 class="western-title animate__animated animate__fadeInDown">🌵 Manage Categories</h1>
        <p class="western-subtitle animate__animated animate__fadeInUp">Organize your product catalog, partner!</p>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card product-card">
                <div class="card-header">
                    <h5 class="mb-0">➕ Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="e.g., Toys, Food, Accessories">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Brief description of this category"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">➕ Add Category</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📂 All Categories</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): 
                                    $productCount = $db->products->countDocuments(['categoryId' => $category['_id']]);
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($category['description'] ?? 'No description') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $productCount ?> products</span>
                                        </td>
                                        <td>
                                            <?php if ($productCount == 0): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="categoryId" value="<?= $category['_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">🗑️ Delete</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Cannot delete (has products)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                                         <?php if ($db->categories->countDocuments() == 0): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>No categories created yet. Add your first category!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html> 