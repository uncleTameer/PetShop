<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit;
}

$orderId = $_GET['id'] ?? '';

if (!$orderId) {
    $_SESSION['error_message'] = "Order ID is required.";
    header("Location: manageOrders.php");
    exit;
}

try {
    $order = $db->orders->findOne(['_id' => new MongoDB\BSON\ObjectId($orderId)]);
    
    if (!$order) {
        $_SESSION['error_message'] = "Order not found.";
        header("Location: manageOrders.php");
        exit;
    }
    
    if (isset($order['userId'])) {
        $user = $db->users->findOne(['_id' => $order['userId']]);
        $userName = $user['fullName'] ?? 'Unknown User';
        $userEmail = $user['email'] ?? '';
        $userAddress = $user['address'] ?? '';
        $userZipCode = $user['zipCode'] ?? '';
    } else {
        $userName = $order['guestInfo']['name'] ?? 'Guest User';
        $userEmail = $order['guestInfo']['email'] ?? '';
        $userAddress = $order['guestInfo']['address'] ?? '';
        $userZipCode = $order['guestInfo']['zipCode'] ?? '';
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error loading order details.";
    header("Location: manageOrders.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details - Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="dashboard.php">🏠 Admin Dashboard</a>
    <div class="ms-auto">
        <a href="manageOrders.php" class="btn btn-outline-light btn-sm">← Back to Orders</a>
        <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <h2>📦 Order Details</h2>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <p><strong>Order ID:</strong> <?= substr((string)$order['_id'], -5) ?></p>
                            <p><strong>Customer:</strong> <?= htmlspecialchars($userName) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($userAddress) ?></p>
                            <p><strong>ZIP Code:</strong> <?= htmlspecialchars($userZipCode) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $order['status'] === 'Pending' ? 'warning' : ($order['status'] === 'Shipped' ? 'info' : ($order['status'] === 'Delivered' ? 'success' : 'danger')) ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </p>
                            <p><strong>Date:</strong> <?= $order['createdAt']->toDateTime()->format('M j, Y g:i A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Order Summary</h5>
                            <p><strong>Total Items:</strong> <?= count($order['items']) ?></p>
                            <p><strong>Total Amount:</strong> ₪<?= number_format($order['total'], 2) ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Items Ordered</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td>₪<?= number_format($item['price'], 2) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>₪<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th>₪<?= number_format($order['total'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <h5>Actions</h5>
            <div class="card">
                <div class="card-body">
                    <?php if ($order['status'] === 'Pending'): ?>
                        <form method="POST" action="updateOrderStatus.php" class="mb-2">
                            <input type="hidden" name="orderId" value="<?= $order['_id'] ?>">
                            <input type="hidden" name="status" value="Shipped">
                            <button type="submit" class="btn btn-info w-100" onclick="return confirm('Mark this order as shipped?')">
                                🚚 Mark as Shipped
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] === 'Shipped'): ?>
                        <form method="POST" action="updateOrderStatus.php" class="mb-2">
                            <input type="hidden" name="orderId" value="<?= $order['_id'] ?>">
                            <input type="hidden" name="status" value="Delivered">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Mark this order as delivered?')">
                                ✅ Mark as Delivered
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (in_array($order['status'], ['Pending', 'Shipped'])): ?>
                        <form method="POST" action="updateOrderStatus.php">
                            <input type="hidden" name="orderId" value="<?= $order['_id'] ?>">
                            <input type="hidden" name="status" value="Cancelled">
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Cancel this order? This action cannot be undone.')">
                                ❌ Cancel Order
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
