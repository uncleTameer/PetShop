<?php
require_once 'dbConnect.php';
require_once 'config.php';
require_once 'productManager.php';
require_once 'cartManager.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productManager = new ProductManager();
$cartManager = new CartManager();

// Get product ID from URL
$productId = $_GET['id'] ?? null;
if (!$productId) {
    header("Location: shop.php");
    exit;
}

// Get product details
$product = $productManager->getProductById($productId);
if (!$product) {
    header("Location: shop.php");
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_to_cart':
                if (!isset($_SESSION['user']['id'])) {
                    $message = 'Please login to add items to cart';
                    $messageType = 'warning';
                } else {
                    $quantity = (int)($_POST['quantity'] ?? 1);
                    $result = $cartManager->addToCart($_SESSION['user']['id'], $productId, $quantity);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'add_review':
                if (!isset($_SESSION['user']['id'])) {
                    $message = 'Please login to add a review';
                    $messageType = 'warning';
                } else {
                    $reviewData = [
                        'rating' => (int)$_POST['rating'],
                        'text' => $_POST['review_text'],
                        'photos' => [] // Handle photo uploads separately
                    ];
                    
                    $result = $productManager->addReview($_SESSION['user']['id'], $productId, $reviewData);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'add_question':
                if (!isset($_SESSION['user']['id'])) {
                    $message = 'Please login to ask a question';
                    $messageType = 'warning';
                } else {
                    $question = $_POST['question'];
                    $result = $productManager->addQuestion($_SESSION['user']['id'], $productId, $question);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'add_alert':
                if (!isset($_SESSION['user']['id'])) {
                    $message = 'Please login to set alerts';
                    $messageType = 'warning';
                } else {
                    $alertData = [
                        'type' => $_POST['alert_type'],
                        'targetPrice' => $_POST['alert_type'] === 'price-drop' ? (float)$_POST['target_price'] : null
                    ];
                    
                    $result = $productManager->addAlert($_SESSION['user']['id'], $productId, $alertData);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'toggle_wishlist':
                if (!isset($_SESSION['user']['id'])) {
                    $message = 'Please login to manage wishlist';
                    $messageType = 'warning';
                } else {
                    if ($product['inWishlist']) {
                        $result = $productManager->removeFromWishlist($_SESSION['user']['id'], $productId);
                    } else {
                        $result = $productManager->addToWishlist($_SESSION['user']['id'], $productId);
                    }
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                    
                    // Refresh product data to update wishlist status
                    $product = $productManager->getProductById($productId);
                }
                break;
        }
    }
}

// Get current page for reviews and Q&A
$reviewPage = $_GET['review_page'] ?? 1;
$qaPage = $_GET['qa_page'] ?? 1;

// Get reviews and Q&A for current page
$reviews = $productManager->getProductReviews($productId, $reviewPage, REVIEWS_PER_PAGE);
$qa = $productManager->getProductQA($productId, $qaPage, QA_PER_PAGE);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - PetShop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../js/bootstrap.bundle.min.js" defer></script>
    <style>
        .product-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .review-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s ease;
        }
        
        .review-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .verified-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .qa-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .question {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .answer {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
        }
        
        .alert-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .stock-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .stock-status.in-stock {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .stock-status.low-stock {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .stock-status.out-of-stock {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .wishlist-btn {
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover {
            transform: scale(1.05);
        }
        
        .wishlist-btn.in-wishlist {
            background: #dc3545;
            border-color: #dc3545;
        }
        
        .wishlist-btn.in-wishlist:hover {
            background: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Flash Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Image -->
            <div class="col-md-6">
                <img src="../<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
            </div>

            <!-- Product Details -->
            <div class="col-md-6">
                <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                
                <!-- Stock Status -->
                <?php
                $stockClass = 'in-stock';
                $stockText = 'In Stock';
                if ($product['stock'] === 0) {
                    $stockClass = 'out-of-stock';
                    $stockText = 'Out of Stock';
                } elseif ($product['stock'] <= ($product['lowStockThreshold'] ?? DEFAULT_LOW_STOCK_THRESHOLD)) {
                    $stockClass = 'low-stock';
                    $stockText = 'Low Stock - Only ' . $product['stock'] . ' left!';
                }
                ?>
                <div class="stock-status <?= $stockClass ?>">
                    <?= $stockText ?>
                </div>

                <!-- Rating -->
                <?php if (isset($product['averageRating'])): ?>
                    <div class="mb-3">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $product['averageRating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="ms-2"><?= $product['averageRating'] ?> out of 5</span>
                        <span class="ms-2 text-muted">(<?= $reviews['total'] ?> reviews)</span>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="mb-3">
                    <h2 class="text-primary">$<?= number_format($product['price'], 2) ?></h2>
                    <?php if (isset($product['originalPrice']) && $product['originalPrice'] > $product['price']): ?>
                        <span class="text-muted text-decoration-line-through">$<?= number_format($product['originalPrice'], 2) ?></span>
                        <span class="badge bg-danger ms-2">Save $<?= number_format($product['originalPrice'] - $product['price'], 2) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <?php if (!empty($product['description'])): ?>
                    <div class="mb-3">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart Form -->
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="action" value="add_to_cart">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantity</label>
                                <select name="quantity" id="quantity" class="form-select">
                                    <?php for ($i = 1; $i <= min(10, $product['stock']); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Wishlist Button -->
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="toggle_wishlist">
                    <button type="submit" class="btn wishlist-btn <?= $product['inWishlist'] ? 'in-wishlist' : 'btn-outline-danger' ?> w-100">
                        <i class="fas fa-heart"></i>
                        <?= $product['inWishlist'] ? 'Remove from Wishlist' : 'Add to Wishlist' ?>
                    </button>
                </form>

                <!-- Alerts Section -->
                <div class="alert-section">
                    <h5><i class="fas fa-bell"></i> Get Notified</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_alert">
                                <input type="hidden" name="alert_type" value="back-in-stock">
                                <button type="submit" class="btn btn-outline-warning w-100" <?= $product['stock'] > 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-bell"></i> Back in Stock Alert
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_alert">
                                <input type="hidden" name="alert_type" value="price-drop">
                                <div class="input-group">
                                    <input type="number" name="target_price" class="form-control" placeholder="Target Price" step="0.01" min="0">
                                    <button type="submit" class="btn btn-outline-info">
                                        <i class="fas fa-tag"></i> Price Alert
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-md-8">
                <h3>Customer Reviews</h3>
                
                <!-- Add Review Form -->
                <?php if (isset($_SESSION['user']['id'])): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Write a Review</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_review">
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <select name="rating" id="rating" class="form-select" required>
                                        <option value="">Select rating</option>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Very Good</option>
                                        <option value="3">3 - Good</option>
                                        <option value="2">2 - Fair</option>
                                        <option value="1">1 - Poor</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Review</label>
                                    <textarea name="review_text" id="review_text" class="form-control" rows="4" required placeholder="Share your experience with this product..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <?php if (!empty($reviews['reviews'])): ?>
                    <?php foreach ($reviews['reviews'] as $review): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?= htmlspecialchars($review['userName']) ?></strong>
                                    <?php if ($review['verifiedPurchase']): ?>
                                        <span class="verified-badge">✓ Verified Purchase</span>
                                    <?php endif; ?>
                                </div>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-2"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                            <?php if (!empty($review['photos'])): ?>
                                <div class="review-photos">
                                    <?php foreach ($review['photos'] as $photo): ?>
                                        <img src="../<?= htmlspecialchars($photo) ?>" alt="Review photo" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted"><?= formatDate($review['createdAt']) ?></small>
                        </div>
                    <?php endforeach; ?>

                    <!-- Reviews Pagination -->
                    <?php if ($reviews['pages'] > 1): ?>
                        <nav aria-label="Reviews pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $reviews['pages']; $i++): ?>
                                    <li class="page-item <?= $i == $reviewPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?id=<?= $productId ?>&review_page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                <?php endif; ?>
            </div>

            <!-- Q&A Section -->
            <div class="col-md-4">
                <h3>Questions & Answers</h3>
                
                <!-- Ask Question Form -->
                <?php if (isset($_SESSION['user']['id'])): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Ask a Question</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_question">
                                <div class="mb-3">
                                    <textarea name="question" class="form-control" rows="3" required placeholder="What would you like to know about this product?"></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-primary">Ask Question</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Q&A List -->
                <?php if (!empty($qa['qa'])): ?>
                    <?php foreach ($qa['qa'] as $item): ?>
                        <div class="qa-item">
                            <div class="question">
                                <strong>Q: <?= htmlspecialchars($item['question']) ?></strong>
                                <div class="text-muted mt-1">
                                    <small>Asked by <?= htmlspecialchars($item['askedByName']) ?> on <?= formatDate($item['createdAt']) ?></small>
                                </div>
                            </div>
                            
                            <?php if ($item['isAnswered']): ?>
                                <div class="answer">
                                    <strong>A: <?= htmlspecialchars($item['answer']) ?></strong>
                                    <div class="text-muted mt-1">
                                        <small>Answered by <?= htmlspecialchars($item['answeredByName']) ?> on <?= formatDate($item['answeredAt']) ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">
                                    <small>No answer yet</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Q&A Pagination -->
                    <?php if ($qa['pages'] > 1): ?>
                        <nav aria-label="Q&A pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $qa['pages']; $i++): ?>
                                    <li class="page-item <?= $i == $qaPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?id=<?= $productId ?>&qa_page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No questions yet. Be the first to ask!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>Related Products</h3>
                <div class="row">
                    <?php
                    $relatedProducts = $productManager->getProducts(['category' => $product['category']], 1, 4);
                    foreach ($relatedProducts['products'] as $relatedProduct):
                        if ($relatedProduct['_id'] != $productId):
                    ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <img src="../<?= htmlspecialchars($relatedProduct['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($relatedProduct['name']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($relatedProduct['name']) ?></h5>
                                    <p class="card-text text-primary">$<?= number_format($relatedProduct['price'], 2) ?></p>
                                    <a href="enhancedProduct.php?id=<?= $relatedProduct['_id'] ?>" class="btn btn-outline-primary">View Product</a>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>
