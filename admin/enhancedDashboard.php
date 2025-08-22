<?php
require_once '../php/dbConnect.php';
require_once '../php/config.php';
require_once '../php/orderManager.php';
require_once '../php/productManager.php';
require_once '../php/userManager.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../php/login.php");
    exit;
}

$orderManager = new OrderManager();
$productManager = new ProductManager();
$userManager = new UserManager();

// Get statistics for different periods
$period = $_GET['period'] ?? 'month';
$stats = $orderManager->getOrderStatistics($period);
$topProducts = $orderManager->getTopSellingProducts(5);
$conversionRate = $orderManager->getOrderConversionRate($period);
$churnRate = $orderManager->getCustomerChurnRate($period);

// Get low stock products
$lowStockProducts = $productManager->getLowStockProducts();

// Get recent notifications
$recentNotifications = $db->notifications->find(
    ['type' => ['$in' => ['low_stock', 'login_lockout', 'product_alert']]],
    ['sort' => ['createdAt' => -1], 'limit' => 10]
)->toArray();

// Get user statistics
$totalUsers = $db->users->countDocuments(['role' => 'user']);
$totalAdmins = $db->users->countDocuments(['role' => 'admin']);
$newUsersThisMonth = $db->users->countDocuments([
    'role' => 'user',
    'audit.createdAt' => ['$gte' => strtotime('-1 month')]
]);

// Get product statistics
$totalProducts = $db->products->countDocuments();
$activeProducts = $db->products->countDocuments(['status' => 'active']);
$outOfStockProducts = $db->products->countDocuments(['stock' => 0]);
$lowStockCount = count($lowStockProducts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Admin Dashboard - PetShop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <style>
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .metric-card.success {
            border-left-color: #28a745;
        }
        
        .metric-card.warning {
            border-left-color: #ffc107;
        }
        
        .metric-card.danger {
            border-left-color: #dc3545;
        }
        
        .metric-card.info {
            border-left-color: #17a2b8;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .period-selector {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .quick-actions {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .quick-action-btn {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="enhancedDashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manageProducts.php">
                                <i class="fas fa-box"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manageOrders.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manageUsers.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../php/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Enhanced Admin Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="manageProducts.php" class="btn btn-sm btn-outline-secondary">Manage Products</a>
                            <a href="manageOrders.php" class="btn btn-sm btn-outline-secondary">Manage Orders</a>
                            <a href="manageUsers.php" class="btn btn-sm btn-outline-secondary">Manage Users</a>
                        </div>
                    </div>
                </div>

                <!-- Period Selector -->
                <div class="period-selector">
                    <h5>Select Time Period</h5>
                    <div class="btn-group" role="group">
                        <a href="?period=week" class="btn btn-outline-primary <?= $period === 'week' ? 'active' : '' ?>">Week</a>
                        <a href="?period=month" class="btn btn-outline-primary <?= $period === 'month' ? 'active' : '' ?>">Month</a>
                        <a href="?period=quarter" class="btn btn-outline-primary <?= $period === 'quarter' ? 'active' : '' ?>">Quarter</a>
                        <a href="?period=year" class="btn btn-outline-primary <?= $period === 'year' ? 'active' : '' ?>">Year</a>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-number">$<?= number_format($stats['totalRevenue'], 2) ?></div>
                            <div class="stat-label">Total Revenue (<?= ucfirst($period) ?>)</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-number"><?= $stats['totalOrders'] ?></div>
                            <div class="stat-label">Total Orders (<?= ucfirst($period) ?>)</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-number">$<?= number_format($stats['averageOrderValue'], 2) ?></div>
                            <div class="stat-label">Average Order Value</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-number"><?= $conversionRate ?>%</div>
                            <div class="stat-label">Conversion Rate</div>
                        </div>
                    </div>
                </div>

                <!-- Additional Metrics -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card success">
                            <h5><i class="fas fa-users text-success"></i> User Statistics</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3 class="text-success"><?= $totalUsers ?></h3>
                                        <small>Total Users</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3 class="text-info"><?= $newUsersThisMonth ?></h3>
                                        <small>New This Month</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card info">
                            <h5><i class="fas fa-box text-info"></i> Product Statistics</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3 class="text-info"><?= $activeProducts ?></h3>
                                        <small>Active Products</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3 class="text-warning"><?= $lowStockCount ?></h3>
                                        <small>Low Stock</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Analytics -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5>Revenue Trend</h5>
                            <canvas id="revenueChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5>Order Status Distribution</h5>
                            <canvas id="orderStatusChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Products and Notifications -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h5><i class="fas fa-star text-warning"></i> Top Selling Products</h5>
                            <?php if (!empty($topProducts)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topProducts as $product): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($product['productName']) ?></td>
                                                    <td><?= $product['totalQuantity'] ?></td>
                                                    <td>$<?= number_format($product['totalRevenue'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No sales data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h5><i class="fas fa-bell text-primary"></i> Recent Notifications</h5>
                            <?php if (!empty($recentNotifications)): ?>
                                <?php foreach ($recentNotifications as $notification): ?>
                                    <div class="notification-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($notification['title']) ?></strong>
                                            <small class="text-muted"><?= formatDate($notification['createdAt']) ?></small>
                                        </div>
                                        <div class="text-muted"><?= htmlspecialchars($notification['message']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent notifications</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="quick-actions">
                            <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="addProduct.php" class="quick-action-btn btn btn-primary">
                                        <i class="fas fa-plus"></i><br>
                                        Add Product
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="manageOrders.php" class="quick-action-btn btn btn-success">
                                        <i class="fas fa-shopping-cart"></i><br>
                                        View Orders
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="manageUsers.php" class="quick-action-btn btn btn-info">
                                        <i class="fas fa-users"></i><br>
                                        Manage Users
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="notifications.php" class="quick-action-btn btn btn-warning">
                                        <i class="fas fa-bell"></i><br>
                                        View All Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <?php if (!empty($lowStockProducts)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="metric-card danger">
                            <h5><i class="fas fa-exclamation-triangle text-danger"></i> Low Stock Alert</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Current Stock</th>
                                            <th>Threshold</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?= $product['stock'] ?></span>
                                                </td>
                                                <td><?= $product['lowStockThreshold'] ?? DEFAULT_LOW_STOCK_THRESHOLD ?></td>
                                                <td>
                                                    <a href="editProduct.php?id=<?= $product['_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        Update Stock
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    data: [12, 19, 3, 5, 2],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#007bff',
                        '#28a745',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
