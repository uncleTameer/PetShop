<?php
require_once '../php/dbConnect.php';
require_once '../php/config.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit;
}

// Get admin name
$adminName = $_SESSION['user']['firstName'] ?? 'Admin';

// Get basic statistics
$totalProducts = $db->products->countDocuments();
$totalUsers = $db->users->countDocuments(['role' => 'user']);
$totalOrders = $db->orders->countDocuments();
$lowStockItems = $db->products->countDocuments(['stock' => ['$lte' => 5]]);
$unreadNotifications = $db->notifications->countDocuments(['read' => false]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PetShop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5dc; /* Light beige background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background-color: #8B4513 !important; /* Brown navbar */
            padding: 10px 0;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        
        .welcome-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .welcome-text {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .card-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .metric-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        /* Card specific colors */
        .card-blue { border-left: 4px solid #007bff; }
        .card-green { border-left: 4px solid #28a745; }
        .card-cyan { border-left: 4px solid #17a2b8; }
        .card-red { border-left: 4px solid #dc3545; }
        .card-yellow { border-left: 4px solid #ffc107; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="enhancedDashboard.php">
                <i class="fas fa-home"></i> ADMIN DASHBOARD
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($adminName, 0, 2)) ?>
                </div>
                <span><?= htmlspecialchars($adminName) ?></span>
                <a href="../php/logout.php" class="btn btn-outline-light btn-sm">LOGOUT</a>
            </div>
        </div>
    </nav>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1 class="welcome-text">Welcome. <?= htmlspecialchars($adminName) ?> 👋</h1>
    </div>

    <!-- Dashboard Cards -->
    <div class="dashboard-cards">
        <!-- Product Management -->
        <a href="manageProducts.php" class="dashboard-card card-blue">
            <div class="card-icon text-primary">
                <i class="fas fa-box"></i>
            </div>
            <div class="metric-number"><?= $totalProducts ?></div>
            <div class="card-title">Total Products</div>
            <div class="card-description">Manage your product inventory</div>
        </a>

        <!-- User Management -->
        <a href="manageUsers.php" class="dashboard-card card-green">
            <div class="card-icon text-success">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-number"><?= $totalUsers ?></div>
            <div class="card-title">Registered Users</div>
            <div class="card-description">Manage user accounts and permissions</div>
        </a>

        <!-- Order Management -->
        <a href="manageOrders.php" class="dashboard-card card-cyan">
            <div class="card-icon text-info">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="metric-number"><?= $totalOrders ?></div>
            <div class="card-title">Total Orders</div>
            <div class="card-description">View and manage customer orders</div>
        </a>

        <!-- Low Stock Alert -->
        <a href="manageProducts.php?filter=lowstock" class="dashboard-card card-red">
            <div class="card-icon text-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="metric-number"><?= $lowStockItems ?></div>
            <div class="card-title">Low Stock Items</div>
            <div class="card-description">Products that need restocking</div>
        </a>

        <!-- Notifications -->
        <a href="notifications.php" class="dashboard-card card-yellow">
            <div class="card-icon text-warning">
                <i class="fas fa-bell"></i>
            </div>
            <div class="metric-number"><?= $unreadNotifications ?></div>
            <div class="card-title">Unread Notifications</div>
            <div class="card-description">System alerts and messages</div>
        </a>

        <!-- Order Report -->
        <a href="orderReport.php" class="dashboard-card card-blue">
            <div class="card-icon text-primary">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="card-title">Order Report</div>
            <div class="card-description">View detailed order analytics</div>
        </a>

        <!-- Category Management -->
        <a href="manageCategories.php" class="dashboard-card card-green">
            <div class="card-icon text-success">
                <i class="fas fa-tags"></i>
            </div>
            <div class="card-title">Category Management</div>
            <div class="card-description">Organize product categories</div>
        </a>

        <!-- Create Account -->
        <a href="createAccount.php" class="dashboard-card card-cyan">
            <div class="card-icon text-info">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="card-title">Create Account</div>
            <div class="card-description">Add new user accounts</div>
        </a>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>