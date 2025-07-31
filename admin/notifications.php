<?php
require_once '../php/dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit;
}

use MongoDB\BSON\ObjectId;

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = $_POST['notification_id'];
    $db->notifications->updateOne(
        ['_id' => new ObjectId($notificationId)],
        ['$set' => ['read' => true, 'readAt' => new MongoDB\BSON\UTCDateTime()]]
    );
    header("Location: notifications.php");
    exit;
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $db->notifications->updateMany(
        ['read' => ['$ne' => true]],
        ['$set' => ['read' => true, 'readAt' => new MongoDB\BSON\UTCDateTime()]]
    );
    header("Location: notifications.php");
    exit;
}

// Get notifications
$notifications = $db->notifications->find(
    [],
    ['sort' => ['timestamp' => -1]]
)->toArray();

$unreadCount = $db->notifications->countDocuments(['read' => ['$ne' => true]]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
    <a class="navbar-brand" href="dashboard.php">🏠 Admin Dashboard</a>
    <div class="ms-auto text-white">
        <div class="d-flex align-items-center text-white me-2">
            <img src="../uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture'] ?? 'default.png') ?>" 
                 alt="Profile" class="rounded-circle me-2" 
                 style="width: 35px; height: 35px; object-fit: cover;">
            <span>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
        </div>
        <a href="../index.php" class="btn btn-outline-light btn-sm me-2">🏠 Main Site</a>
        <a href="../php/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>🔔 System Notifications</h1>
        <div class="d-flex gap-2">
            <?php if ($unreadCount > 0): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-outline-success">
                        ✅ Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-outline-primary">← Back to Dashboard</a>
        </div>
    </div>

    <?php if ($unreadCount > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            You have <strong><?= $unreadCount ?></strong> unread notification<?= $unreadCount > 1 ? 's' : '' ?>.
        </div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-5">
            <i class="fas fa-bell text-muted" style="font-size: 4rem;"></i>
            <h3 class="mt-3 text-muted">No notifications</h3>
            <p class="text-muted">You're all caught up!</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($notifications as $notification): ?>
                <div class="col-12 mb-3">
                    <div class="card <?= empty($notification['read']) ? 'border-warning' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if ($notification['type'] === 'login_lockout'): ?>
                                            <i class="fas fa-shield-alt text-danger me-2"></i>
                                            <h6 class="mb-0 text-danger">Login Lockout Alert</h6>
                                        <?php else: ?>
                                            <i class="fas fa-bell text-primary me-2"></i>
                                            <h6 class="mb-0">System Notification</h6>
                                        <?php endif; ?>
                                        
                                        <?php if (empty($notification['read'])): ?>
                                            <span class="badge bg-warning ms-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                                    
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('M j, Y \a\t g:i A', $notification['timestamp']->toDateTime()->getTimestamp()) ?>
                                        
                                        <?php if ($notification['type'] === 'login_lockout'): ?>
                                            <span class="ms-3">
                                                <i class="fas fa-user me-1"></i>
                                                User: <?= htmlspecialchars($notification['userName']) ?>
                                                (<?= htmlspecialchars($notification['userEmail']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (empty($notification['read'])): ?>
                                    <form method="POST" class="ms-3">
                                        <input type="hidden" name="notification_id" value="<?= $notification['_id'] ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success">
                                            ✅ Mark Read
                                        </button>
                                    </form>
                                <?php endif; ?>
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