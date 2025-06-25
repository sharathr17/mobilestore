<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Get some stats for dashboard
$product_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
$order_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?: 0;

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");

// Get top selling products
$top_products = $conn->query("SELECT p.name, COUNT(oi.product_id) as order_count 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            GROUP BY oi.product_id 
                            ORDER BY order_count DESC 
                            LIMIT 5");

// Get monthly revenue for the last 6 months
$monthly_revenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_start = $month . '-01';
    $month_end = date('Y-m-t', strtotime($month_start));
    
    $result = $conn->query("SELECT SUM(total_amount) as total FROM orders 
                          WHERE created_at BETWEEN '$month_start' AND '$month_end' 
                          AND status = 'completed'")->fetch_assoc();
    
    $monthly_revenue[] = [
        'month' => date('M', strtotime($month_start)),
        'revenue' => $result['total'] ?: 0
    ];
}

// Get pending actions count
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$pending_refunds = $conn->query("SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND stock > 0")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock = 0")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<!-- Replace the existing style tag with this link tag in the head section -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mobile Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-mobile-alt"></i> Mobile Store Admin</h1>
            <div>
                <a href="../index.php"><i class="fas fa-home"></i> View Site</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div class="sidebar">
            <h3>Admin Menu</h3>
            <ul>
                <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="refunds.php"><i class="fas fa-undo"></i> Refunds</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-title">
                <h2>Dashboard</h2>
                <div class="quick-actions">
                    <a href="products.php?action=new" class="btn"><i class="fas fa-plus"></i> Add Product</a>

                <!-- With this dropdown: -->
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle"><i class="fas fa-download"></i> Export Report</button>
                    <div class="dropdown-content">
                        <a href="export_report.php?type=sales">Sales Report</a>
                        <a href="export_report.php?type=products">Products Report</a>
                        <a href="export_report.php?type=customers">Customers Report</a>
                    </div>
                </div>                     
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-products">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p><?php echo $product_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?php echo $user_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p><?php echo $order_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>₹<?php echo number_format($revenue, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>
                        Revenue Overview
                        <a href="#">View Report</a>
                    </h3>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3>
                        Top Selling Products
                        <a href="products.php">View All</a>
                    </h3>
                    <div class="top-products">
                        <ul class="product-list">
                            <?php if ($top_products && $top_products->num_rows > 0): ?>
                                <?php while($product = $top_products->fetch_assoc()): ?>
                                    <li>
                                        <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                        <span class="product-count"><?php echo $product['order_count']; ?> sold</span>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li>No products sold yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="dashboard-card recent-orders">
                <h3>
                    Recent Orders
                    <a href="orders.php">View All</a>
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td class="order-id">#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No orders yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h3 style="margin-top: 30px;">Pending Actions</h3>
            <div class="action-items">
                <div class="action-item">
                    <div class="action-icon action-orders">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="action-info">
                        <h4>Pending Orders</h4>
                        <p>
                            <?php echo $pending_orders; ?>
                            <a href="orders.php?status=pending">View</a>
                        </p>
                    </div>
                </div>
                <div class="action-item">
                    <div class="action-icon action-refunds">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="action-info">
                        <h4>Pending Refunds</h4>
                        <p>
                            <?php echo $pending_refunds; ?>
                            <a href="refunds.php?status=pending">View</a>
                        </p>
                    </div>
                </div>
                <div class="action-item">
                    <div class="action-icon action-stock">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="action-info">
                        <h4>Low Stock Items</h4>
                        <p>
                            <?php echo $low_stock; ?>
                            <a href="products.php?stock=low">View</a>
                        </p>
                    </div>
                </div>
                <div class="action-item">
                    <div class="action-icon action-out">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="action-info">
                        <h4>Out of Stock</h4>
                        <p>
                            <?php echo $out_of_stock; ?>
                            <a href="products.php?stock=out">View</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("', '", array_column($monthly_revenue, 'month')) . "'"; ?>],
                datasets: [{
                    label: 'Monthly Revenue',
                    data: [<?php echo implode(", ", array_column($monthly_revenue, 'revenue')); ?>],
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderColor: '#4361ee',
                    borderWidth: 3,
                    tension: 0.3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4361ee',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#2b2d42',
                        bodyColor: '#2b2d42',
                        borderColor: '#dee2e6',
                        borderWidth: 1,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '$ ' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>