<?php
require_once 'dbConnect.php';
require_once 'config.php';
require_once 'userManager.php';

/**
 * Order Management Class
 * Handles orders with loyalty points, addresses, and enhanced features
 */
class OrderManager {
    private $db;
    private $userManager;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->userManager = new UserManager();
    }
    
    /**
     * Create new order
     */
    public function createOrder($userId, $orderData) {
        try {
            // Validate order data
            if (empty($orderData['items']) || empty($orderData['shippingAddress'])) {
                return ['success' => false, 'message' => 'Missing required order data'];
            }
            
            // Calculate totals
            $subtotal = $this->calculateSubtotal($orderData['items']);
            $loyaltyPointsUsed = $orderData['loyaltyPointsUsed'] ?? 0;
            $loyaltyDiscount = $this->calculateLoyaltyDiscount($loyaltyPointsUsed);
            $finalTotal = $subtotal - $loyaltyDiscount;
            
            // Validate loyalty points
            if ($loyaltyPointsUsed > 0) {
                $userPoints = $this->userManager->getLoyaltyPoints($userId);
                if ($userPoints < $loyaltyPointsUsed) {
                    return ['success' => false, 'message' => 'Insufficient loyalty points'];
                }
            }
            
            // Create order
            $order = [
                'userId' => $userId,
                'items' => $orderData['items'],
                'subtotal' => $subtotal,
                'loyaltyPointsUsed' => $loyaltyPointsUsed,
                'loyaltyDiscount' => $loyaltyDiscount,
                'finalTotal' => $finalTotal,
                'shippingAddress' => $orderData['shippingAddress'],
                'status' => 'pending',
                'createdAt' => time(),
                'updatedAt' => time()
            ];
            
            $result = $this->db->orders->insertOne($order);
            
            if ($result->getInsertedCount() === 1) {
                $orderId = (string)$result->getInsertedId();
                
                // Update product stock
                $this->updateProductStock($orderData['items']);
                
                // Use loyalty points if any
                if ($loyaltyPointsUsed > 0) {
                    $this->userManager->useLoyaltyPoints($userId, $loyaltyPointsUsed);
                }
                
                // Clear user's cart
                $this->clearUserCart($userId);
                
                return [
                    'success' => true,
                    'orderId' => $orderId,
                    'message' => 'Order created successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create order'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to create order'];
        }
    }
    
    /**
     * Calculate order subtotal
     */
    private function calculateSubtotal($items) {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        return round($subtotal, 2);
    }
    
    /**
     * Calculate loyalty points discount
     */
    private function calculateLoyaltyDiscount($loyaltyPoints) {
        // Convert loyalty points to currency (1 point = $0.01)
        return round($loyaltyPoints * 0.01, 2);
    }
    
    /**
     * Update product stock after order
     */
    private function updateProductStock($items) {
        try {
            foreach ($items as $item) {
                $this->db->products->updateOne(
                    ['_id' => $item['productId']],
                    ['$inc' => ['stock' => -$item['quantity']]]
                );
            }
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error updating product stock: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Clear user's cart after order
     */
    private function clearUserCart($userId) {
        try {
            $this->db->cart->updateOne(
                ['userId' => $userId],
                ['$set' => ['items' => [], 'updatedAt' => time()]]
            );
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error clearing user cart: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get user's orders
     */
    public function getUserOrders($userId, $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            $skip = ($page - 1) * $limit;
            
            $orders = $this->db->orders->find(
                ['userId' => $userId],
                [
                    'sort' => ['createdAt' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            )->toArray();
            
            // Get total count for pagination
            $total = $this->db->orders->countDocuments(['userId' => $userId]);
            
            return [
                'orders' => $orders,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting user orders: " . $e->getMessage());
            }
            return ['orders' => [], 'total' => 0, 'pages' => 0, 'currentPage' => 1];
        }
    }
    
    /**
     * Get order by ID
     */
    public function getOrderById($orderId, $userId = null) {
        try {
            $query = ['_id' => $orderId];
            if ($userId) {
                $query['userId'] = $userId;
            }
            
            $order = $this->db->orders->findOne($query);
            
            if ($order) {
                // Format dates
                $order['createdAtFormatted'] = formatDate($order['createdAt']);
                $order['updatedAtFormatted'] = formatDate($order['updatedAt']);
            }
            
            return $order;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting order by ID: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status, $adminId = null) {
        try {
            $updateData = [
                'status' => $status,
                'updatedAt' => time()
            ];
            
            if ($adminId) {
                $updateData['updatedBy'] = $adminId;
            }
            
            $result = $this->db->orders->updateOne(
                ['_id' => $orderId],
                ['$set' => $updateData]
            );
            
            if ($result->getModifiedCount() === 1) {
                // If order is completed, award loyalty points
                if ($status === 'completed') {
                    $this->awardLoyaltyPoints($orderId);
                }
                
                return ['success' => true, 'message' => 'Order status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update order status'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to update order status'];
        }
    }
    
    /**
     * Award loyalty points for completed order
     */
    private function awardLoyaltyPoints($orderId) {
        try {
            $order = $this->db->orders->findOne(['_id' => $orderId]);
            if (!$order) {
                return;
            }
            
            // Calculate loyalty points (10% of order value)
            $loyaltyPoints = round($order['finalTotal'] * (LOYALTY_POINTS_PERCENTAGE / 100));
            
            if ($loyaltyPoints > 0) {
                $this->userManager->addLoyaltyPoints($order['userId'], $loyaltyPoints);
                
                // Update order with loyalty points earned
                $this->db->orders->updateOne(
                    ['_id' => $orderId],
                    ['$set' => ['loyaltyPointsEarned' => $loyaltyPoints]]
                );
                
                // Send notification to user
                $this->sendLoyaltyPointsNotification($order['userId'], $loyaltyPoints, $order['finalTotal']);
            }
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error awarding loyalty points: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Send loyalty points notification
     */
    private function sendLoyaltyPointsNotification($userId, $points, $orderTotal) {
        try {
            $notification = [
                'type' => 'loyalty_points',
                'userId' => $userId,
                'title' => 'Loyalty Points Earned!',
                'message' => "You've earned {$points} loyalty points for your order of $" . number_format($orderTotal, 2),
                'isRead' => false,
                'createdAt' => time()
            ];
            
            $this->db->notifications->insertOne($notification);
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error sending loyalty points notification: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder($orderId, $userId) {
        try {
            $order = $this->db->orders->findOne(['_id' => $orderId, 'userId' => $userId]);
            
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            if ($order['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Order cannot be cancelled'];
            }
            
            // Update order status
            $result = $this->db->orders->updateOne(
                ['_id' => $orderId],
                [
                    '$set' => [
                        'status' => 'cancelled',
                        'updatedAt' => time()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() === 1) {
                // Restore product stock
                $this->restoreProductStock($order['items']);
                
                // Refund loyalty points if any were used
                if ($order['loyaltyPointsUsed'] > 0) {
                    $this->userManager->addLoyaltyPoints($userId, $order['loyaltyPointsUsed']);
                }
                
                return ['success' => true, 'message' => 'Order cancelled successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to cancel order'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to cancel order'];
        }
    }
    
    /**
     * Restore product stock after order cancellation
     */
    private function restoreProductStock($items) {
        try {
            foreach ($items as $item) {
                $this->db->products->updateOne(
                    ['_id' => $item['productId']],
                    ['$inc' => ['stock' => $item['quantity']]]
                );
            }
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error restoring product stock: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get order statistics for admin dashboard
     */
    public function getOrderStatistics($period = 'month') {
        try {
            $startDate = $this->getPeriodStartDate($period);
            
            $pipeline = [
                [
                    '$match' => [
                        'createdAt' => ['$gte' => $startDate],
                        'status' => ['$in' => ['completed', 'shipped', 'delivered']]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'totalOrders' => ['$sum' => 1],
                        'totalRevenue' => ['$sum' => '$finalTotal'],
                        'averageOrderValue' => ['$avg' => '$finalTotal']
                    ]
                ]
            ];
            
            $result = $this->db->orders->aggregate($pipeline)->toArray();
            
            if (!empty($result)) {
                $stats = $result[0];
                $stats['totalRevenue'] = round($stats['totalRevenue'], 2);
                $stats['averageOrderValue'] = round($stats['averageOrderValue'], 2);
            } else {
                $stats = [
                    'totalOrders' => 0,
                    'totalRevenue' => 0,
                    'averageOrderValue' => 0
                ];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting order statistics: " . $e->getMessage());
            }
            return [
                'totalOrders' => 0,
                'totalRevenue' => 0,
                'averageOrderValue' => 0
            ];
        }
    }
    
    /**
     * Get period start date for statistics
     */
    private function getPeriodStartDate($period) {
        switch ($period) {
            case 'week':
                return strtotime('-1 week');
            case 'month':
                return strtotime('-1 month');
            case 'quarter':
                return strtotime('-3 months');
            case 'year':
                return strtotime('-1 year');
            default:
                return strtotime('-1 month');
        }
    }
    
    /**
     * Get top selling products
     */
    public function getTopSellingProducts($limit = 10) {
        try {
            $pipeline = [
                [
                    '$match' => [
                        'status' => ['$in' => ['completed', 'shipped', 'delivered']]
                    ]
                ],
                [
                    '$unwind' => '$items'
                ],
                [
                    '$group' => [
                        '_id' => '$items.productId',
                        'productName' => ['$first' => '$items.name'],
                        'totalQuantity' => ['$sum' => '$items.quantity'],
                        'totalRevenue' => ['$sum' => ['$multiply' => ['$items.price', '$items.quantity']]]
                    ]
                ],
                [
                    '$sort' => ['totalQuantity' => -1]
                ],
                [
                    '$limit' => $limit
                ]
            ];
            
            $products = $this->db->orders->aggregate($pipeline)->toArray();
            
            // Format revenue
            foreach ($products as &$product) {
                $product['totalRevenue'] = round($product['totalRevenue'], 2);
            }
            
            return $products;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting top selling products: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Get order conversion rate
     */
    public function getOrderConversionRate($period = 'month') {
        try {
            $startDate = $this->getPeriodStartDate($period);
            
            // Count total users who added items to cart
            $usersWithCart = $this->db->cart->distinct('userId', [
                'userId' => ['$ne' => null],
                'updatedAt' => ['$gte' => $startDate]
            ]);
            
            $totalUsers = count($usersWithCart);
            
            if ($totalUsers === 0) {
                return 0;
            }
            
            // Count users who completed orders
            $usersWithOrders = $this->db->orders->distinct('userId', [
                'createdAt' => ['$gte' => $startDate],
                'status' => ['$in' => ['completed', 'shipped', 'delivered']]
            ]);
            
            $convertedUsers = count($usersWithOrders);
            
            return round(($convertedUsers / $totalUsers) * 100, 2);
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting order conversion rate: " . $e->getMessage());
            }
            return 0;
        }
    }
    
    /**
     * Get customer churn rate
     */
    public function getCustomerChurnRate($period = 'month') {
        try {
            $startDate = $this->getPeriodStartDate($period);
            $previousStartDate = $this->getPeriodStartDate($period . '_previous');
            
            // Get active customers in previous period
            $previousCustomers = $this->db->orders->distinct('userId', [
                'createdAt' => ['$gte' => $previousStartDate, '$lt' => $startDate],
                'status' => ['$in' => ['completed', 'shipped', 'delivered']]
            ]);
            
            $totalPreviousCustomers = count($previousCustomers);
            
            if ($totalPreviousCustomers === 0) {
                return 0;
            }
            
            // Get customers who didn't order in current period
            $currentCustomers = $this->db->orders->distinct('userId', [
                'createdAt' => ['$gte' => $startDate],
                'status' => ['$in' => ['completed', 'shipped', 'delivered']]
            ]);
            
            $churnedCustomers = array_diff($previousCustomers, $currentCustomers);
            $churnRate = (count($churnedCustomers) / $totalPreviousCustomers) * 100;
            
            return round($churnRate, 2);
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting customer churn rate: " . $e->getMessage());
            }
            return 0;
        }
    }
}
?>
