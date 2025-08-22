<?php
require_once 'dbConnect.php';
require_once 'config.php';

/**
 * Product Management Class
 * Handles products, reviews, Q&A, wishlist, and alerts
 */
class ProductManager {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    /**
     * Get products with filters
     */
    public function getProducts($filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            $query = ['status' => 'active'];
            
            // Apply filters
            if (!empty($filters['category'])) {
                $query['category'] = $filters['category'];
            }
            
            if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
                $priceQuery = [];
                if (!empty($filters['price_min'])) {
                    $priceQuery['$gte'] = (float)$filters['price_min'];
                }
                if (!empty($filters['price_max'])) {
                    $priceQuery['$lte'] = (float)$filters['price_max'];
                }
                $query['price'] = $priceQuery;
            }
            
            if (!empty($filters['in_stock']) && $filters['in_stock'] === 'true') {
                $query['stock'] = ['$gt' => 0];
            }
            
            if (!empty($filters['rating'])) {
                $query['averageRating'] = ['$gte' => (float)$filters['rating']];
            }
            
            if (!empty($filters['search'])) {
                $query['$or'] = [
                    ['name' => ['$regex' => $filters['search'], '$options' => 'i']],
                    ['description' => ['$regex' => $filters['search'], '$options' => 'i']]
                ];
            }
            
            $skip = ($page - 1) * $limit;
            
            $products = $this->db->products->find($query, [
                'sort' => ['createdAt' => -1],
                'skip' => $skip,
                'limit' => $limit
            ])->toArray();
            
            // Get total count for pagination
            $total = $this->db->products->countDocuments($query);
            
            return [
                'products' => $products,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting products: " . $e->getMessage());
            }
            return ['products' => [], 'total' => 0, 'pages' => 0, 'currentPage' => 1];
        }
    }
    
    /**
     * Get product by ID with reviews and Q&A
     */
    public function getProductById($productId) {
        try {
            $product = $this->db->products->findOne(['_id' => $productId]);
            
            if (!$product) {
                return null;
            }
            
            // Get reviews
            $reviews = $this->getProductReviews($productId, 1, REVIEWS_PER_PAGE);
            
            // Get Q&A
            $qa = $this->getProductQA($productId, 1, QA_PER_PAGE);
            
            // Check if user has this in wishlist
            $inWishlist = false;
            if (isset($_SESSION['user']['id'])) {
                $inWishlist = $this->isInWishlist($_SESSION['user']['id'], $productId);
            }
            
            $product['reviews'] = $reviews;
            $product['qa'] = $qa;
            $product['inWishlist'] = $inWishlist;
            
            return $product;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting product by ID: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Add product to wishlist
     */
    public function addToWishlist($userId, $productId) {
        try {
            // Check if already in wishlist
            $existing = $this->db->wishlist->findOne([
                'userId' => $userId,
                'productId' => $productId
            ]);
            
            if ($existing) {
                return ['success' => false, 'message' => 'Product already in wishlist'];
            }
            
            // Get product price
            $product = $this->db->products->findOne(['_id' => $productId]);
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            $wishlistItem = [
                'userId' => $userId,
                'productId' => $productId,
                'priceWhenAdded' => $product['price'],
                'addedAt' => time()
            ];
            
            $result = $this->db->wishlist->insertOne($wishlistItem);
            
            if ($result->getInsertedCount() === 1) {
                return ['success' => true, 'message' => 'Added to wishlist'];
            }
            
            return ['success' => false, 'message' => 'Failed to add to wishlist'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add to wishlist'];
        }
    }
    
    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist($userId, $productId) {
        try {
            $result = $this->db->wishlist->deleteOne([
                'userId' => $userId,
                'productId' => $productId
            ]);
            
            if ($result->getDeletedCount() === 1) {
                return ['success' => true, 'message' => 'Removed from wishlist'];
            }
            
            return ['success' => false, 'message' => 'Failed to remove from wishlist'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to remove from wishlist'];
        }
    }
    
    /**
     * Check if product is in user's wishlist
     */
    public function isInWishlist($userId, $productId) {
        try {
            $wishlistItem = $this->db->wishlist->findOne([
                'userId' => $userId,
                'productId' => $productId
            ]);
            
            return $wishlistItem !== null;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error checking wishlist: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get user's wishlist
     */
    public function getUserWishlist($userId, $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            $skip = ($page - 1) * $limit;
            
            $wishlistItems = $this->db->wishlist->find(
                ['userId' => $userId],
                [
                    'sort' => ['addedAt' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            )->toArray();
            
            // Get product details for each wishlist item
            $products = [];
            foreach ($wishlistItems as $item) {
                $product = $this->db->products->findOne(['_id' => $item['productId']]);
                if ($product) {
                    $product['wishlistInfo'] = $item;
                    $products[] = $product;
                }
            }
            
            // Get total count for pagination
            $total = $this->db->wishlist->countDocuments(['userId' => $userId]);
            
            return [
                'products' => $products,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting user wishlist: " . $e->getMessage());
            }
            return ['products' => [], 'total' => 0, 'pages' => 0, 'currentPage' => 1];
        }
    }
    
    /**
     * Add product review
     */
    public function addReview($userId, $productId, $reviewData) {
        try {
            // Validate review data
            if (empty($reviewData['rating']) || empty($reviewData['text'])) {
                return ['success' => false, 'message' => 'Rating and text are required'];
            }
            
            $rating = (int)$reviewData['rating'];
            if ($rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
            }
            
            // Check if user has already reviewed this product
            $existingReview = $this->db->reviews->findOne([
                'userId' => $userId,
                'productId' => $productId
            ]);
            
            if ($existingReview) {
                return ['success' => false, 'message' => 'You have already reviewed this product'];
            }
            
            // Check if user has purchased this product
            $hasPurchased = $this->hasUserPurchasedProduct($userId, $productId);
            
            $review = [
                'userId' => $userId,
                'productId' => $productId,
                'rating' => $rating,
                'text' => sanitizeInput($reviewData['text']),
                'photos' => $reviewData['photos'] ?? [],
                'verifiedPurchase' => $hasPurchased,
                'helpful' => 0,
                'createdAt' => time(),
                'updatedAt' => time()
            ];
            
            $result = $this->db->reviews->insertOne($review);
            
            if ($result->getInsertedCount() === 1) {
                // Update product average rating
                $this->updateProductRating($productId);
                
                return ['success' => true, 'message' => 'Review added successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to add review'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add review'];
        }
    }
    
    /**
     * Get product reviews
     */
    public function getProductReviews($productId, $page = 1, $limit = REVIEWS_PER_PAGE) {
        try {
            $skip = ($page - 1) * $limit;
            
            $reviews = $this->db->reviews->find(
                ['productId' => $productId],
                [
                    'sort' => ['createdAt' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            )->toArray();
            
            // Get user names for reviews
            foreach ($reviews as &$review) {
                $user = $this->db->users->findOne(['_id' => $review['userId']], ['projection' => ['fullName' => 1]]);
                $review['userName'] = $user ? $user['fullName'] : 'Anonymous';
            }
            
            // Get total count for pagination
            $total = $this->db->reviews->countDocuments(['productId' => $productId]);
            
            return [
                'reviews' => $reviews,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting product reviews: " . $e->getMessage());
            }
            return ['reviews' => [], 'total' => 0, 'pages' => 0, 'currentPage' => 1];
        }
    }
    
    /**
     * Add Q&A question
     */
    public function addQuestion($userId, $productId, $question) {
        try {
            if (empty($question)) {
                return ['success' => false, 'message' => 'Question is required'];
            }
            
            $qa = [
                'productId' => $productId,
                'question' => sanitizeInput($question),
                'answer' => '',
                'askedBy' => $userId,
                'answeredBy' => null,
                'isAnswered' => false,
                'helpful' => 0,
                'createdAt' => time(),
                'answeredAt' => null
            ];
            
            $result = $this->db->qa->insertOne($qa);
            
            if ($result->getInsertedCount() === 1) {
                return ['success' => true, 'message' => 'Question added successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to add question'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add question'];
        }
    }
    
    /**
     * Add Q&A answer (admin only)
     */
    public function addAnswer($qaId, $adminId, $answer) {
        try {
            if (empty($answer)) {
                return ['success' => false, 'message' => 'Answer is required'];
            }
            
            $result = $this->db->qa->updateOne(
                ['_id' => $qaId],
                [
                    '$set' => [
                        'answer' => sanitizeInput($answer),
                        'answeredBy' => $adminId,
                        'isAnswered' => true,
                        'answeredAt' => time()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Answer added successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to add answer'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add answer'];
        }
    }
    
    /**
     * Get product Q&A
     */
    public function getProductQA($productId, $page = 1, $limit = QA_PER_PAGE) {
        try {
            $skip = ($page - 1) * $limit;
            
            $qa = $this->db->qa->find(
                ['productId' => $productId],
                [
                    'sort' => ['createdAt' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            )->toArray();
            
            // Get user names
            foreach ($qa as &$item) {
                $user = $this->db->users->findOne(['_id' => $item['askedBy']], ['projection' => ['fullName' => 1]]);
                $item['askedByName'] = $user ? $user['fullName'] : 'Anonymous';
                
                if ($item['answeredBy']) {
                    $admin = $this->db->users->findOne(['_id' => $item['answeredBy']], ['projection' => ['fullName' => 1]]);
                    $item['answeredByName'] = $admin ? $admin['fullName'] : 'Admin';
                }
            }
            
            // Get total count for pagination
            $total = $this->db->qa->countDocuments(['productId' => $productId]);
            
            return [
                'qa' => $qa,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting product Q&A: " . $e->getMessage());
            }
            return ['qa' => [], 'total' => 0, 'pages' => 0, 'currentPage' => 1];
        }
    }
    
    /**
     * Add product alert (back-in-stock or price-drop)
     */
    public function addAlert($userId, $productId, $alertData) {
        try {
            if (empty($alertData['type'])) {
                return ['success' => false, 'message' => 'Alert type is required'];
            }
            
            // Check if alert already exists
            $existingAlert = $this->db->alerts->findOne([
                'userId' => $userId,
                'productId' => $productId,
                'type' => $alertData['type'],
                'isActive' => true
            ]);
            
            if ($existingAlert) {
                return ['success' => false, 'message' => 'Alert already exists for this product'];
            }
            
            $alert = [
                'userId' => $userId,
                'productId' => $productId,
                'type' => $alertData['type'],
                'targetPrice' => $alertData['targetPrice'] ?? null,
                'isActive' => true,
                'createdAt' => time(),
                'triggeredAt' => null
            ];
            
            $result = $this->db->alerts->insertOne($alert);
            
            if ($result->getInsertedCount() === 1) {
                return ['success' => true, 'message' => 'Alert added successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to add alert'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add alert'];
        }
    }
    
    /**
     * Remove product alert
     */
    public function removeAlert($userId, $productId, $type) {
        try {
            $result = $this->db->alerts->updateOne(
                [
                    'userId' => $userId,
                    'productId' => $productId,
                    'type' => $type
                ],
                ['$set' => ['isActive' => false]]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Alert removed successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to remove alert'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to remove alert'];
        }
    }
    
    /**
     * Get user alerts
     */
    public function getUserAlerts($userId) {
        try {
            $alerts = $this->db->alerts->find(
                ['userId' => $userId, 'isActive' => true],
                ['sort' => ['createdAt' => -1]]
            )->toArray();
            
            // Get product details for each alert
            foreach ($alerts as &$alert) {
                $product = $this->db->products->findOne(['_id' => $alert['productId']]);
                if ($product) {
                    $alert['product'] = $product;
                }
            }
            
            return $alerts;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting user alerts: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Check and trigger alerts
     */
    public function checkAlerts() {
        try {
            // Check back-in-stock alerts
            $this->checkBackInStockAlerts();
            
            // Check price-drop alerts
            $this->checkPriceDropAlerts();
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error checking alerts: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Check back-in-stock alerts
     */
    private function checkBackInStockAlerts() {
        try {
            $alerts = $this->db->alerts->find([
                'type' => 'back-in-stock',
                'isActive' => true
            ])->toArray();
            
            foreach ($alerts as $alert) {
                $product = $this->db->products->findOne(['_id' => $alert['productId']]);
                
                if ($product && $product['stock'] > 0) {
                    // Trigger alert
                    $this->triggerAlert($alert['_id'], 'back-in-stock');
                    
                    // Send notification to user
                    $this->sendAlertNotification($alert['userId'], $product, 'back-in-stock');
                }
            }
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error checking back-in-stock alerts: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Check price-drop alerts
     */
    private function checkPriceDropAlerts() {
        try {
            $alerts = $this->db->alerts->find([
                'type' => 'price-drop',
                'isActive' => true
            ])->toArray();
            
            foreach ($alerts as $alert) {
                $product = $this->db->products->findOne(['_id' => $alert['productId']]);
                
                if ($product && $alert['targetPrice'] && $product['price'] <= $alert['targetPrice']) {
                    // Trigger alert
                    $this->triggerAlert($alert['_id'], 'price-drop');
                    
                    // Send notification to user
                    $this->sendAlertNotification($alert['userId'], $product, 'price-drop');
                }
            }
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error checking price-drop alerts: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Trigger alert
     */
    private function triggerAlert($alertId, $type) {
        try {
            $this->db->alerts->updateOne(
                ['_id' => $alertId],
                [
                    '$set' => [
                        'isActive' => false,
                        'triggeredAt' => time()
                    ]
                ]
            );
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error triggering alert: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Send alert notification
     */
    private function sendAlertNotification($userId, $product, $type) {
        try {
            $notification = [
                'type' => 'product_alert',
                'userId' => $userId,
                'title' => ucfirst($type) . ' Alert',
                'message' => "Your {$type} alert for {$product['name']} has been triggered!",
                'isRead' => false,
                'createdAt' => time()
            ];
            
            $this->db->notifications->insertOne($notification);
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error sending alert notification: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Check if user has purchased product (for verified purchase badge)
     */
    private function hasUserPurchasedProduct($userId, $productId) {
        try {
            $order = $this->db->orders->findOne([
                'userId' => $userId,
                'items.productId' => $productId,
                'status' => ['$in' => ['completed', 'shipped', 'delivered']]
            ]);
            
            return $order !== null;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error checking user purchase: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Update product average rating
     */
    private function updateProductRating($productId) {
        try {
            $reviews = $this->db->reviews->find(['productId' => $productId])->toArray();
            
            if (empty($reviews)) {
                return;
            }
            
            $totalRating = 0;
            foreach ($reviews as $review) {
                $totalRating += $review['rating'];
            }
            
            $averageRating = $totalRating / count($reviews);
            
            $this->db->products->updateOne(
                ['_id' => $productId],
                ['$set' => ['averageRating' => round($averageRating, 1)]]
            );
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error updating product rating: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get low stock products
     */
    public function getLowStockProducts($threshold = null) {
        try {
            if ($threshold === null) {
                $threshold = DEFAULT_LOW_STOCK_THRESHOLD;
            }
            
            $products = $this->db->products->find([
                'stock' => ['$lte' => $threshold],
                'status' => 'active'
            ])->toArray();
            
            return $products;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting low stock products: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Update product stock
     */
    public function updateProductStock($productId, $quantity, $operation = 'decrease') {
        try {
            $updateOperator = $operation === 'decrease' ? '$inc' : '$set';
            $value = $operation === 'decrease' ? -$quantity : $quantity;
            
            $result = $this->db->products->updateOne(
                ['_id' => $productId],
                [$updateOperator => ['stock' => $value]]
            );
            
            if ($result->getModifiedCount() === 1) {
                // Check if stock is now low
                $product = $this->db->products->findOne(['_id' => $productId]);
                if ($product && $product['stock'] <= ($product['lowStockThreshold'] ?? DEFAULT_LOW_STOCK_THRESHOLD)) {
                    $this->notifyLowStock($product);
                }
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error updating product stock: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Notify admins about low stock
     */
    private function notifyLowStock($product) {
        try {
            $admins = $this->db->users->find(['role' => 'admin'])->toArray();
            
            foreach ($admins as $admin) {
                $notification = [
                    'type' => 'low_stock',
                    'userId' => $admin['_id'],
                    'title' => 'Low Stock Alert',
                    'message' => "Product {$product['name']} is running low on stock (Current: {$product['stock']})",
                    'isRead' => false,
                    'createdAt' => time()
                ];
                
                $this->db->notifications->insertOne($notification);
            }
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error notifying low stock: " . $e->getMessage());
            }
        }
    }
}
?>
