<?php
require_once '../php/dbConnect.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manageUsers.php");
    exit;
}

$userId = $_GET['id'];
try {
    $user = $db->users->findOne(['_id' => new ObjectId($userId)]);
    if (!$user) {
        $_SESSION['error_message'] = "❌ User not found.";
        header("Location: manageUsers.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "❌ Invalid user ID.";
    header("Location: manageUsers.php");
    exit;
}

// Get user statistics
$userOrders = $db->orders->find(['userId' => new ObjectId($userId)])->toArray();
$userReviews = $db->reviews->find(['userId' => new ObjectId($userId)])->toArray();
$userWishlist = $db->wishlist->find(['userId' => new ObjectId($userId)])->toArray();
$userAlerts = $db->alerts->find(['userId' => new ObjectId($userId)])->toArray();
$userSessions = $db->sessions->find(['userId' => new ObjectId($userId)])->toArray();

// Calculate statistics
$totalSpent = array_sum(array_column($userOrders, 'total'));
$averageOrderValue = count($userOrders) > 0 ? $totalSpent / count($userOrders) : 0;
$totalLoyaltyPoints = $user['loyaltyPoints'] ?? 0;
$lastOrderDate = count($userOrders) > 0 ? max(array_column($userOrders, 'createdAt')) : null;
$lastLoginDate = $user['lastLoginAt'] ?? null;

// Get recent activity
$recentOrders = array_slice($userOrders, 0, 5);
$recentReviews = array_slice($userReviews, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile - <?= htmlspecialchars($user['fullName']) ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/western-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../js/bootstrap.bundle.min.js" defer></script>
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .section-title {
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .badge-custom {
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
    <a class="navbar-brand" href="manageUsers.php">⬅ Back to Users</a>
    <div class="d-flex align-items-center ms-auto text-white">
        <?php
        $imgPath = '../uploads/' . ($_SESSION['user']['profilePicture'] ?? 'default.png');
        if (!file_exists($imgPath)) $imgPath = '../uploads/default.png';
        ?>
        <img src="<?= $imgPath ?>" alt="Profile" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
        <?= htmlspecialchars($_SESSION['user']['name']) ?>
        <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    </div>
</nav>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success text-center m-3"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger text-center m-3"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<!-- Profile Header -->
<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <img src="../uploads/<?= $user['profilePicture'] ?? 'default.png' ?>" 
                     alt="Profile" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
            </div>
            <div class="col-md-10">
                <h1 class="mb-2"><?= htmlspecialchars($user['fullName']) ?></h1>
                <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email']) ?></p>
                <p class="mb-1">
                    <i class="fas fa-user-tag me-2"></i>
                    <span class="badge bg-<?= ($user['role'] ?? 'user') === 'admin' ? 'primary' : 'secondary' ?> badge-custom">
                        <?= ucfirst($user['role'] ?? 'user') ?>
                    </span>
                    <?php if (!empty($user['suspended'])): ?>
                        <span class="badge bg-danger badge-custom">Suspended</span>
                    <?php endif; ?>
                </p>
                <p class="mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Member since: <?= date('M j, Y', strtotime($user['createdAt'] ?? time())) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <h3 class="text-primary"><?= count($userOrders) ?></h3>
                <p class="text-muted mb-0">Total Orders</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <h3 class="text-success">₪<?= number_format($totalSpent, 2) ?></h3>
                <p class="text-muted mb-0">Total Spent</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <h3 class="text-info">₪<?= number_format($averageOrderValue, 2) ?></h3>
                <p class="text-muted mb-0">Average Order</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <h3 class="text-warning"><?= $totalLoyaltyPoints ?></h3>
                <p class="text-muted mb-0">Loyalty Points</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- User Information -->
        <div class="col-md-6">
            <div class="stat-card">
                <h4 class="section-title"><i class="fas fa-user me-2"></i>Personal Information</h4>
                
                <div class="row mb-2">
                    <div class="col-4"><strong>Full Name:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['fullName']) ?></div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-4"><strong>Email:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                
                <?php if (isset($user['phone'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Phone:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['phone']) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($user['address'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Address:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['address']) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($user['zipCode'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>ZIP Code:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['zipCode']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-2">
                    <div class="col-4"><strong>Created:</strong></div>
                    <div class="col-8"><?= date('M j, Y g:i A', strtotime($user['createdAt'] ?? time())) ?></div>
                </div>
                
                <?php if ($lastLoginDate): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Last Login:</strong></div>
                    <div class="col-8"><?= date('M j, Y g:i A', strtotime($lastLoginDate)) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($lastOrderDate): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Last Order:</strong></div>
                    <div class="col-8"><?= date('M j, Y g:i A', strtotime($lastOrderDate)) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Security Information -->
            <div class="stat-card">
                <h4 class="section-title"><i class="fas fa-shield-alt me-2"></i>Security & Audit</h4>
                
                <?php if (isset($user['security'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>2FA Enabled:</strong></div>
                    <div class="col-8">
                        <span class="badge bg-<?= ($user['security']['twoFactorEnabled'] ?? false) ? 'success' : 'secondary' ?>">
                            <?= ($user['security']['twoFactorEnabled'] ?? false) ? 'Yes' : 'No' ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($user['lockout'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Login Attempts:</strong></div>
                    <div class="col-8"><?= $user['lockout']['loginAttempts'] ?? 0 ?></div>
                </div>
                
                <?php if (isset($user['lockout']['locked']) && $user['lockout']['locked']): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Account Locked:</strong></div>
                    <div class="col-8">
                        <span class="badge bg-danger">Yes</span>
                        until <?= date('M j, Y g:i A', strtotime($user['lockout']['lockoutTime'] ?? time())) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if (isset($user['audit'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Created IP:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['audit']['createdIp'] ?? 'Unknown') ?></div>
                </div>
                
                <?php if (isset($user['audit']['lastLoginIp'])): ?>
                <div class="row mb-2">
                    <div class="col-4"><strong>Last Login IP:</strong></div>
                    <div class="col-8"><?= htmlspecialchars($user['audit']['lastLoginIp']) ?></div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity & History -->
        <div class="col-md-6">
            <!-- Recent Orders -->
            <div class="stat-card">
                <h4 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h4>
                <?php if (empty($recentOrders)): ?>
                    <p class="text-muted">No orders yet.</p>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>Order #<?= $order['_id'] ?></strong><br>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($order['createdAt'] ?? time())) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span><br>
                                    <strong>₪<?= number_format($order['total'], 2) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($userOrders) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="#" class="btn btn-sm btn-outline-primary">View All Orders</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Reviews -->
            <div class="stat-card">
                <h4 class="section-title"><i class="fas fa-star me-2"></i>Recent Reviews</h4>
                <?php if (empty($recentReviews)): ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-<?= $i <= $review['rating'] ? 'warning' : 'muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($review['createdAt'] ?? time())) ?>
                                    </small>
                                </div>
                                <?php if (isset($review['verifiedPurchase']) && $review['verifiedPurchase']): ?>
                                    <span class="badge bg-success">Verified Purchase</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($review['text'])): ?>
                                <p class="mb-0 mt-1"><?= htmlspecialchars(substr($review['text'], 0, 100)) ?>...</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($userReviews) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="#" class="btn btn-sm btn-outline-primary">View All Reviews</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Wishlist & Alerts -->
            <div class="stat-card">
                <h4 class="section-title"><i class="fas fa-heart me-2"></i>Wishlist & Alerts</h4>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-danger"><?= count($userWishlist) ?></h5>
                        <small class="text-muted">Wishlist Items</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-info"><?= count($userAlerts) ?></h5>
                        <small class="text-muted">Active Alerts</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="userOperations.php?action=edit&id=<?= $user['_id'] ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>Edit User
            </a>
            <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                <a href="userOperations.php?action=updateRole&email=<?= urlencode($user['email']) ?>&role=user" class="btn btn-secondary me-2">
                    <i class="fas fa-user me-1"></i>Demote to User
                </a>
            <?php else: ?>
                <a href="userOperations.php?action=updateRole&email=<?= urlencode($user['email']) ?>&role=admin" class="btn btn-success me-2">
                    <i class="fas fa-user-shield me-1"></i>Promote to Admin
                </a>
            <?php endif; ?>
            
            <?php if (!empty($user['suspended'])): ?>
                <a href="userOperations.php?action=suspend&email=<?= urlencode($user['email']) ?>&suspendAction=unsuspend" class="btn btn-info me-2">
                    <i class="fas fa-unlock me-1"></i>Unsuspend
                </a>
            <?php else: ?>
                <a href="userOperations.php?action=suspend&email=<?= urlencode($user['email']) ?>&suspendAction=suspend" class="btn btn-warning me-2">
                    <i class="fas fa-lock me-1"></i>Suspend
                </a>
            <?php endif; ?>
            
            <a href="userOperations.php?action=delete&id=<?= $user['_id'] ?>" class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                <i class="fas fa-trash me-1"></i>Delete User
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>

</body>
</html>
