<?php
require_once 'dbConnect.php';
require_once 'config.php';

/**
 * Cart Management Class
 * Handles user and guest carts with expiration and loyalty points
 */
class CartManager {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    /**
     * Get user's cart or create guest cart
     */
    public function getCart($userId = null, $sessionId = null) {
        try {
            if ($userId) {
                // Get user cart
                $cart = $this->db->cart->findOne(['userId' => $userId]);
            } else {
                // Get or create guest cart
                $cart = $this->db->cart->findOne(['sessionId' => $sessionId]);
                
                if (!$cart) {
                    $cart = $this->createGuestCart($sessionId);
                } else {
                    // Check if guest cart has expired
                    if ($cart['expiresAt'] && time() > $cart['expiresAt']) {
                        $this->db->cart->deleteOne(['_id' => $cart['_id']]);
                        $cart = $this->createGuestCart($sessionId);
                    }
                }
            }
            
            if ($cart) {
                // Get product details for each cart item
                $cart['items'] = $this->enrichCartItems($cart['items']);
                $cart['total'] = $this->calculateCartTotal($cart['items']);
            }
            
            return $cart;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting cart: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Create guest cart
     */
    private function createGuestCart($sessionId) {
        try {
            $cart = [
                'userId' => null,
                'sessionId' => $sessionId,
                'items' => [],
                'expiresAt' => time() + GUEST_CART_EXPIRY,
                'createdAt' => time(),
                'updatedAt' => time()
            ];
            
            $result = $this->db->cart->insertOne($cart);
            
            if ($result->getInsertedCount() === 1) {
                $cart['_id'] = $result->getInsertedId();
                return $cart;
            }
            
            return null;
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error creating guest cart: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Add item to cart
     */
    public function addToCart($userId, $productId, $quantity = 1, $sessionId = null) {
        try {
            // Validate product and stock
            $product = $this->db->products->findOne(['_id' => $productId]);
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            if ($product['stock'] < $quantity) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }
            
            // Get or create cart
            $cart = $this->getCart($userId, $sessionId);
            if (!$cart) {
                return ['success' => false, 'message' => 'Failed to get cart'];
            }
            
            // Check if item already exists in cart
            $existingItemIndex = -1;
            foreach ($cart['items'] as $index => $item) {
                if ($item['productId'] == $productId) {
                    $existingItemIndex = $index;
                    break;
                }
            }
            
            if ($existingItemIndex >= 0) {
                // Update existing item quantity
                $newQuantity = $cart['items'][$existingItemIndex]['quantity'] + $quantity;
                
                if ($newQuantity > $product['stock']) {
                    return ['success' => false, 'message' => 'Insufficient stock for requested quantity'];
                }
                
                $cart['items'][$existingItemIndex]['quantity'] = $newQuantity;
                $cart['items'][$existingItemIndex]['updatedAt'] = time();
                
            } else {
                // Add new item
                $cartItem = [
                    'productId' => $productId,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'addedAt' => time(),
                    'updatedAt' => time()
                ];
                
                $cart['items'][] = $cartItem;
            }
            
            // Update cart
            $cart['updatedAt'] = time();
            if (!$userId) {
                $cart['expiresAt'] = time() + GUEST_CART_EXPIRY;
            }
            
            $result = $this->db->cart->updateOne(
                ['_id' => $cart['_id']],
                ['$set' => $cart]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Item added to cart'];
            }
            
            return ['success' => false, 'message' => 'Failed to add item to cart'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add item to cart'];
        }
    }
    
    /**
     * Update cart item quantity
     */
    public function updateCartItem($userId, $productId, $quantity, $sessionId = null) {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($userId, $productId, $sessionId);
            }
            
            // Validate product and stock
            $product = $this->db->products->findOne(['_id' => $productId]);
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            if ($product['stock'] < $quantity) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }
            
            // Get cart
            $cart = $this->getCart($userId, $sessionId);
            if (!$cart) {
                return ['success' => false, 'message' => 'Cart not found'];
            }
            
            // Find and update item
            $itemUpdated = false;
            foreach ($cart['items'] as &$item) {
                if ($item['productId'] == $productId) {
                    $item['quantity'] = $quantity;
                    $item['updatedAt'] = time();
                    $itemUpdated = true;
                    break;
                }
            }
            
            if (!$itemUpdated) {
                return ['success' => false, 'message' => 'Item not found in cart'];
            }
            
            // Update cart
            $cart['updatedAt'] = time();
            if (!$userId) {
                $cart['expiresAt'] = time() + GUEST_CART_EXPIRY;
            }
            
            $result = $this->db->cart->updateOne(
                ['_id' => $cart['_id']],
                ['$set' => $cart]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Cart updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update cart'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to update cart'];
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart($userId, $productId, $sessionId = null) {
        try {
            // Get cart
            $cart = $this->getCart($userId, $sessionId);
            if (!$cart) {
                return ['success' => false, 'message' => 'Cart not found'];
            }
            
            // Remove item
            $cart['items'] = array_filter($cart['items'], function($item) use ($productId) {
                return $item['productId'] != $productId;
            });
            
            // Update cart
            $cart['updatedAt'] = time();
            if (!$userId) {
                $cart['expiresAt'] = time() + GUEST_CART_EXPIRY;
            }
            
            $result = $this->db->cart->updateOne(
                ['_id' => $cart['_id']],
                ['$set' => $cart]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Item removed from cart'];
            }
            
            return ['success' => false, 'message' => 'Failed to remove item from cart'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to remove item from cart'];
        }
    }
    
    /**
     * Clear cart
     */
    public function clearCart($userId, $sessionId = null) {
        try {
            $query = $userId ? ['userId' => $userId] : ['sessionId' => $sessionId];
            
            $result = $this->db->cart->updateOne(
                $query,
                ['$set' => ['items' => [], 'updatedAt' => time()]]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Cart cleared successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to clear cart'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to clear cart'];
        }
    }
    
    /**
     * Convert guest cart to user cart
     */
    public function convertGuestCart($sessionId, $userId) {
        try {
            $guestCart = $this->db->cart->findOne(['sessionId' => $sessionId]);
            if (!$guestCart) {
                return ['success' => false, 'message' => 'Guest cart not found'];
            }
            
            // Check if user already has a cart
            $userCart = $this->db->cart->findOne(['userId' => $userId]);
            
            if ($userCart) {
                // Merge guest cart items into user cart
                $mergedItems = $this->mergeCartItems($userCart['items'], $guestCart['items']);
                
                $result = $this->db->cart->updateOne(
                    ['userId' => $userId],
                    [
                        '$set' => [
                            'items' => $mergedItems,
                            'updatedAt' => time()
                        ]
                    ]
                );
                
                if ($result->getModifiedCount() === 1) {
                    // Delete guest cart
                    $this->db->cart->deleteOne(['sessionId' => $sessionId]);
                    return ['success' => true, 'message' => 'Guest cart merged successfully'];
                }
                
            } else {
                // Convert guest cart to user cart
                $result = $this->db->cart->updateOne(
                    ['sessionId' => $sessionId],
                    [
                        '$set' => [
                            'userId' => $userId,
                            'sessionId' => null,
                            'expiresAt' => null,
                            'updatedAt' => time()
                        ]
                    ]
                );
                
                if ($result->getModifiedCount() === 1) {
                    return ['success' => true, 'message' => 'Guest cart converted successfully'];
                }
            }
            
            return ['success' => false, 'message' => 'Failed to convert guest cart'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to convert guest cart'];
        }
    }
    
    /**
     * Merge cart items (handle duplicates)
     */
    private function mergeCartItems($userItems, $guestItems) {
        $merged = $userItems;
        
        foreach ($guestItems as $guestItem) {
            $found = false;
            
            foreach ($merged as &$userItem) {
                if ($userItem['productId'] == $guestItem['productId']) {
                    $userItem['quantity'] += $guestItem['quantity'];
                    $userItem['updatedAt'] = time();
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $merged[] = $guestItem;
            }
        }
        
        return $merged;
    }
    
    /**
     * Calculate cart total
     */
    private function calculateCartTotal($items) {
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return round($total, 2);
    }
    
    /**
     * Enrich cart items with product details
     */
    private function enrichCartItems($items) {
        $enriched = [];
        
        foreach ($items as $item) {
            $product = $this->db->products->findOne(['_id' => $item['productId']]);
            if ($product) {
                $enrichedItem = array_merge($item, [
                    'image' => $product['image'] ?? null,
                    'description' => $product['description'] ?? '',
                    'stock' => $product['stock'] ?? 0,
                    'available' => $product['stock'] >= $item['quantity']
                ]);
                
                $enriched[] = $enrichedItem;
            }
        }
        
        return $enriched;
    }
    
    /**
     * Get cart summary (for display)
     */
    public function getCartSummary($userId, $sessionId = null) {
        try {
            $cart = $this->getCart($userId, $sessionId);
            
            if (!$cart) {
                return [
                    'itemCount' => 0,
                    'total' => 0,
                    'items' => []
                ];
            }
            
            $itemCount = 0;
            foreach ($cart['items'] as $item) {
                $itemCount += $item['quantity'];
            }
            
            return [
                'itemCount' => $itemCount,
                'total' => $cart['total'],
                'items' => $cart['items']
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting cart summary: " . $e->getMessage());
            }
            return ['itemCount' => 0, 'total' => 0, 'items' => []];
        }
    }
    
    /**
     * Validate cart before checkout
     */
    public function validateCartForCheckout($userId, $sessionId = null) {
        try {
            $cart = $this->getCart($userId, $sessionId);
            
            if (!$cart || empty($cart['items'])) {
                return ['success' => false, 'message' => 'Cart is empty'];
            }
            
            $errors = [];
            $validItems = [];
            
            foreach ($cart['items'] as $item) {
                $product = $this->db->products->findOne(['_id' => $item['productId']]);
                
                if (!$product) {
                    $errors[] = "Product {$item['name']} no longer exists";
                    continue;
                }
                
                if ($product['status'] !== 'active') {
                    $errors[] = "Product {$item['name']} is not available";
                    continue;
                }
                
                if ($product['stock'] < $item['quantity']) {
                    $errors[] = "Insufficient stock for {$item['name']} (Available: {$product['stock']}, Requested: {$item['quantity']})";
                    continue;
                }
                
                // Update price to current price
                $item['price'] = $product['price'];
                $validItems[] = $item;
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            // Update cart with validated items and current prices
            $cart['items'] = $validItems;
            $cart['total'] = $this->calculateCartTotal($validItems);
            $cart['updatedAt'] = time();
            
            $this->db->cart->updateOne(
                ['_id' => $cart['_id']],
                ['$set' => $cart]
            );
            
            return ['success' => true, 'cart' => $cart];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to validate cart'];
        }
    }
    
    /**
     * Clean up expired guest carts
     */
    public function cleanupExpiredCarts() {
        try {
            $expiredTime = time() - GUEST_CART_EXPIRY;
            
            $result = $this->db->cart->deleteMany([
                'userId' => null,
                'expiresAt' => ['$lt' => $expiredTime]
            ]);
            
            if (DEBUG_MODE && $result->getDeletedCount() > 0) {
                error_log("Cleaned up " . $result->getDeletedCount() . " expired guest carts");
            }
            
            return $result->getDeletedCount();
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error cleaning up expired carts: " . $e->getMessage());
            }
            return 0;
        }
    }
}
?>
