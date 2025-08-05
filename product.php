<?php
require 'php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

$productId = $_GET['id'] ?? '';
if (empty($productId)) {
    header("Location: shop.php");
    exit;
}

try {
    $product = $db->products->findOne(['_id' => new ObjectId($productId)]);
    if (!$product) {
        header("Location: shop.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: shop.php");
    exit;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user'])) {
        $_SESSION['error_message'] = "You must be logged in to submit a review.";
        header("Location: php/login.php");
        exit;
    }

    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error_message'] = "Rating must be between 1 and 5.";
    } elseif (empty($comment)) {
        $_SESSION['error_message'] = "Please provide a comment.";
    } else {
        $review = [
            'productId' => new ObjectId($productId),
            'userId' => new ObjectId($_SESSION['user']['id']),
            'userName' => $_SESSION['user']['name'],
            'rating' => $rating,
            'comment' => $comment,
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $db->reviews->insertOne($review);
        $_SESSION['success_message'] = "Thank you for your review!";
        header("Location: product.php?id=" . $productId);
        exit;
    }
}

// Get reviews for this product
$reviews = $db->reviews->find(['productId' => new ObjectId($productId)])->toArray();

// Calculate average rating
$totalRating = 0;
$reviewCount = count($reviews);
foreach ($reviews as $review) {
    $totalRating += $review['rating'];
}
$averageRating = $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Pet Shop</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
    <a class="navbar-brand" href="index.php">🏠 Pet Shop</a>
    <div class="ms-auto text-white">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="d-flex align-items-center text-white me-2">
                <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
                    <img src="uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
                         alt="Profile" class="rounded-circle me-2" 
                         style="width: 35px; height: 35px; object-fit: cover;">
                <?php else: ?>
                    <img src="uploads/default.png" 
                         alt="Default" class="rounded-circle me-2" 
                         style="width: 35px; height: 35px; object-fit: cover;">
                <?php endif; ?>
                <span>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
            </div>
            <a href="editProfile.php" class="btn btn-outline-info btn-sm me-2">👤 Edit Profile</a>
            <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a> 
            <a href="wishlist.php" class="btn btn-outline-danger btn-sm ms-2">❤️ Wishlist</a>
            <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
        <?php else: ?>
            <a href="php/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
            <a href="php/register.php" class="btn btn-outline-light btn-sm">Register</a>
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

<div class="container py-4">
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-6">
            <img src="<?= htmlspecialchars($product['image']) ?>" 
                 class="img-fluid rounded shadow" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        
        <!-- Product Details -->
        <div class="col-md-6">
            <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>
            
            <!-- Rating Display -->
            <div class="mb-3">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= $averageRating ? 'text-warning' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="me-2"><?= $averageRating ?>/5</span>
                    <span class="text-muted">(<?= $reviewCount ?> reviews)</span>
                </div>
            </div>
            
            <h3 class="text-success mb-3">₪<?= number_format($product['price'], 2) ?></h3>
            <p class="text-muted mb-3">Category: <?= htmlspecialchars($product['category']) ?></p>
            <p class="mb-3">Stock: <?= $product['stock'] ?> available</p>
            
            <div class="d-flex gap-2 mb-3">
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" action="php/addToCart.php" class="flex-fill">
                        <input type="hidden" name="name" value="<?= $product['name'] ?>">
                        <input type="hidden" name="redirect" value="product.php?id=<?= $product['_id'] ?>">
                        <button type="submit" class="btn btn-primary btn-lg w-100">🛒 Add to Cart</button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg w-100" disabled>❌ Out of Stock</button>
                <?php endif; ?>
                <?php if (isset($_SESSION['user'])): ?>
                    <form method="POST" action="wishlist.php" class="flex-fill">
                        <input type="hidden" name="productId" value="<?= $product['_id'] ?>">
                        <button type="submit" name="add_to_wishlist" class="btn btn-outline-danger btn-lg w-100">❤️ Add to Wishlist</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <a href="shop.php" class="btn btn-outline-secondary">← Back to Shop</a>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="mt-5">
        <h3>Customer Reviews</h3>
        
        <!-- Add Review Form -->
        <?php if (isset($_SESSION['user'])): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Write a Review</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
                                    <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" class="form-control" rows="3" 
                                      placeholder="Share your experience with this product..." required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <a href="php/login.php">Login</a> to write a review for this product.
            </div>
        <?php endif; ?>
        
        <!-- Reviews List -->
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet. Be the first to review this product!</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($review['userName']) ?></h6>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= date('M j, Y', $review['createdAt']->toDateTime()->getTimestamp()) ?>
                                </small>
                            </div>
                            <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>



<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    gap: 5px;
}

.rating-input input {
    display: none;
}

.rating-input label {
    cursor: pointer;
    font-size: 1.5em;
    color: #ddd;
    transition: color 0.2s;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input:checked ~ label {
    color: #ffc107;
}

.rating-input input:checked ~ label {
    color: #ffc107;
}
</style>

</body>
</html>