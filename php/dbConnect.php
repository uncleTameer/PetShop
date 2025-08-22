<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sessionInit.php';

// Initialize session with security settings
initSession();

try {
    $client = new MongoDB\Client(DB_HOST);
    $db = $client->selectDatabase(DB_NAME);
    
    // Create indexes for performance and data integrity
    createDatabaseIndexes($db);
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        echo "<div style='padding: 20px; background-color: #ffdddd; border: 1px solid red; color: darkred; font-family: sans-serif;'>
        <strong>⚠️ Database Connection Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    } else {
        echo "<div style='padding: 20px; background-color: #ffdddd; border: 1px solid red; color: darkred; font-family: sans-serif;'>
        <strong>⚠️ Failed to connect to the database. Please try again later.</strong></div>";
    }
    exit;
}

/**
 * Create necessary database indexes for performance and data integrity
 */
function createDatabaseIndexes($db) {
    try {
        // Users collection indexes
        $db->users->createIndex(['email' => 1], ['unique' => true]);
        $db->users->createIndex(['role' => 1]);
        $db->users->createIndex(['createdAt' => -1]);
        
        // Products collection indexes
        $db->products->createIndex(['category' => 1]);
        $db->products->createIndex(['stock' => 1]);
        $db->products->createIndex(['status' => 1]);
        $db->products->createIndex(['price' => 1]);
        
        // Reviews collection indexes
        $db->reviews->createIndex(['productId' => 1]);
        $db->reviews->createIndex(['userId' => 1]);
        $db->reviews->createIndex(['rating' => 1]);
        $db->reviews->createIndex(['createdAt' => -1]);
        
        // Orders collection indexes
        $db->orders->createIndex(['userId' => 1]);
        $db->orders->createIndex(['status' => 1]);
        $db->orders->createIndex(['createdAt' => -1]);
        
        // Wishlist collection indexes
        $db->wishlist->createIndex(['userId' => 1]);
        $db->wishlist->createIndex(['productId' => 1]);
        $db->wishlist->createIndex(['userId' => 1, 'productId' => 1], ['unique' => true]);
        
        // Alerts collection indexes
        $db->alerts->createIndex(['userId' => 1]);
        $db->alerts->createIndex(['productId' => 1]);
        $db->alerts->createIndex(['type' => 1]);
        $db->alerts->createIndex(['isActive' => 1]);
        
        // Q&A collection indexes
        $db->qa->createIndex(['productId' => 1]);
        $db->qa->createIndex(['askedBy' => 1]);
        $db->qa->createIndex(['isAnswered' => 1]);
        
        // Sessions collection indexes
        $db->sessions->createIndex(['userId' => 1]);
        $db->sessions->createIndex(['sessionId' => 1]);
        $db->sessions->createIndex(['lastSeen' => -1]);
        
        // Notifications collection indexes
        $db->notifications->createIndex(['userId' => 1]);
        $db->notifications->createIndex(['type' => 1]);
        $db->notifications->createIndex(['isRead' => 1]);
        $db->notifications->createIndex(['createdAt' => -1]);
        
        // Cart collection indexes
        $db->cart->createIndex(['userId' => 1]);
        $db->cart->createIndex(['sessionId' => 1]);
        $db->cart->createIndex(['expiresAt' => 1]);
        
    } catch (Exception $e) {
        // Log index creation errors but don't fail the connection
        if (DEBUG_MODE) {
            error_log("Index creation error: " . $e->getMessage());
        }
    }
}

/**
 * Get database instance
 */
function getDatabase() {
    global $db;
    return $db;
}

/**
 * Get collection by name
 */
function getCollection($collectionName) {
    global $db;
    return $db->selectCollection($collectionName);
}

/**
 * Clean up expired guest carts
 */
function cleanupExpiredCarts() {
    try {
        global $db;
        $expiredTime = time() - GUEST_CART_EXPIRY;
        
        $result = $db->cart->deleteMany([
            'userId' => null,
            'expiresAt' => ['$lt' => $expiredTime]
        ]);
        
        if (DEBUG_MODE && $result->getDeletedCount() > 0) {
            error_log("Cleaned up " . $result->getDeletedCount() . " expired guest carts");
        }
        
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Error cleaning up expired carts: " . $e->getMessage());
        }
    }
}

// Clean up expired carts on every connection (for simplicity)
// In production, this should be done via a cron job
cleanupExpiredCarts();
?>
