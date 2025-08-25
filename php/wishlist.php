<?php
require 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "You must be logged in to access your wishlist.";
    header("Location: login.php");
    exit;
}

$userId = new ObjectId($_SESSION['user']['id']);

// Handle add to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $productId = $_POST['productId'];
    
    // Check if already in wishlist
    $existing = $db->wishlist->findOne([
        'userId' => $userId,
        'productId' => new ObjectId($productId)
    ]);
    
    if (!$existing) {
        $wishlistItem = [
            'userId' => $userId,
            'productId' => new ObjectId($productId),
            'addedAt' => new MongoDB\BSON\UTCDateTime()
        ];
        $db->wishlist->insertOne($wishlistItem);
        $_SESSION['success_message'] = "Product added to wishlist!";
    } else {
        $_SESSION['error_message'] = "Product is already in your wishlist.";
    }
    header("Location: wishlist.php");
    exit;
}

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $productId = $_POST['productId'];
    
    $db->wishlist->deleteOne([
        'userId' => $userId,
        'productId' => new ObjectId($productId)
    ]);
    
    $_SESSION['success_message'] = "Product removed from wishlist.";
    header("Location: wishlist.php");
    exit;
}

// Get wishlist items with product details
$wishlistItems = $db->wishlist->aggregate([
    [
        '$match' => ['userId' => $userId]
    ],
    [
        '$lookup' => [
            'from' => 'products',
            'localField' => 'productId',
            'foreignField' => '_id',
            'as' => 'product'
        ]
    ],
    [
        '$unwind' => '$product'
    ]
])->toArray();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist - Pet Shop</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/western-theme.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
    <a class="navbar-brand" href="index.php">🏠 Pet Shop</a>
    <div class="ms-auto text-white">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="d-flex align-items-center text-white me-2">
                <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
                         alt="Profile" class="rounded-circle me-2" 
                         style="width: 35px; height: 35px; object-fit: cover;">
                <?php else: ?>
                    <img src="../uploads/default.png" 
                         alt="Default" class="rounded-circle me-2" 
                         style="width: 35px; height: 35px; object-fit: cover;">
                <?php endif; ?>
                <span>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
            </div>
            <a href="editProfile.php" class="btn btn-outline-info btn-sm me-2">👤 Edit Profile</a>
            <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a> 
            <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
<a href="register.php" class="btn btn-outline-light btn-sm">Register</a>
        <?php endif; ?>
        <a href="cart.php" class="btn btn-warning btn-sm ms-3">🛒 Cart</a>
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

<!-- Western Hero Section -->
<div class="hero-section mb-4">
    <div class="container text-center">
        <h1 class="western-title animate__animated animate__fadeInDown">❤️ Wishlist Corral</h1>
        <p class="western-subtitle animate__animated animate__fadeInUp">Your favorite products, gathered like prized horses</p>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="western-title">❤️ My Wishlist</h2>
        <a href="shop.php" class="btn btn-warning">🛒 Continue Shopping</a>
    </div>

    <?php if (empty($wishlistItems)): ?>
        <div class="text-center py-5">
            <div class="western-border p-5">
                <i class="fas fa-heart text-muted" style="font-size: 4rem; color: var(--rustic-red);"></i>
                <h3 class="western-title mt-3">Your wishlist corral is empty</h3>
                <p class="western-subtitle">Start gathering products you love, partner!</p>
                <a href="shop.php" class="btn btn-warning btn-lg">🌵 Browse Products</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="product.php?id=<?= $item['product']['_id'] ?>" class="text-decoration-none">
                            <img src="<?= htmlspecialchars($item['product']['image']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($item['product']['name']) ?>" 
                                 style="height: 200px; object-fit: cover;">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title western-subtitle">
                                <a href="product.php?id=<?= $item['product']['_id'] ?>" 
                                   class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($item['product']['name']) ?>
                                </a>
                            </h5>
                            <p class="card-text western-accent fs-4 fw-bold">
                                ₪<?= number_format($item['product']['price'], 2) ?>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    📦 Stock: <?= $item['product']['stock'] ?> available
                                </small>
                            </p>
                            
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($item['product']['stock'] > 0): ?>
                                    <form method="POST" action="addToCart.php" class="flex-fill mb-2">
                                        <input type="hidden" name="name" value="<?= $item['product']['name'] ?>">
                                        <input type="hidden" name="redirect" value="wishlist.php">
                                        <button type="submit" class="btn btn-success w-100">🛒 Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 mb-2" disabled>❌ Out of Stock</button>
                                <?php endif; ?>
                                
                                <form method="POST" class="flex-fill mb-2">
                                    <input type="hidden" name="productId" value="<?= $item['product']['_id'] ?>">
                                    <button type="submit" name="remove_from_wishlist" 
                                            class="btn btn-outline-danger w-100" 
                                            onclick="return confirm('Remove from wishlist?')">
                                        ❌ Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>



</body>
</html>