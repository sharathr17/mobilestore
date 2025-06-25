<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['id'];

// Get order details - Fixed SQL injection vulnerability
$stmt = $conn->prepare("SELECT o.*, u.username, u.email FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    header("Location: orders.php");
    exit;
}

$order = $order_result->fetch_assoc();

// Get order items - Fixed SQL injection vulnerability
$items_stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();

// Get shipping address if available
$shipping_address = '';
if (isset($order['shipping_address']) && !empty($order['shipping_address'])) {
    $shipping_address = $order['shipping_address'];
}
?>

<!DOCTYPE html>
<html lang="en">
<!-- Replace the existing style tag with this link tag in the head section -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mobile Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-mobile-alt"></i> Mobile Store Admin</h1>
            <div>
                <a href="../index.php"><i class="fas fa-store"></i> View Site</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div class="sidebar">
            <h3>Admin Menu</h3>
            <ul>
                <li><a href="index.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="refunds.php"><i class="fas fa-undo"></i> Refunds</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-title">
                <h2><i class="fas fa-file-invoice"></i> Order Details</h2>
                <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
            </div>
            
            <div class="order-details">
                <div class="order-meta">
                    <div class="order-meta-item">
                        <h4>Order ID</h4>
                        <p>#<?php echo $order['id']; ?></p>
                    </div>
                    <div class="order-meta-item">
                        <h4>Date</h4>
                        <p><i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                        <p style="font-size: 0.8rem; margin-top: 5px; color: var(--text-muted);"><i class="far fa-clock"></i> <?php echo date('g:i a', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="order-meta-item">
                        <h4>Customer</h4>
                        <p><i class="far fa-user"></i> <?php echo htmlspecialchars($order['username']); ?></p>
                    </div>
                    <div class="order-meta-item">
                        <h4>Email</h4>
                        <p><i class="far fa-envelope"></i> <?php echo htmlspecialchars($order['email']); ?></p>
                    </div>
                    <div class="order-meta-item">
                        <h4>Total</h4>
                        <p>₹<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>
                    <div class="order-meta-item">
                        <h4>Status</h4>
                        <p>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($shipping_address)): ?>
                <div class="shipping-info">
                    <h3><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                    <p><?php echo nl2br(htmlspecialchars($shipping_address)); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="section-title">
                    <h3><i class="fas fa-box-open"></i> Order Items</h3>
                </div>
                
                <table class="order-items">
                    <thead>
                        <tr>
                            <th width="80">Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items && $items->num_rows > 0): ?>
                            <?php 
                            $total = 0;
                            while($item = $items->fetch_assoc()): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <img src="../images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #ccc; font-size: 1.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                                            Product ID: #<?php echo $item['product_id']; ?>
                                        </div>
                                    </td>
                                    <td><i class="fas fa-rupee-sign"></i> <?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <span style="background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-weight: 600;">
                                            <?php echo $item['quantity']; ?>
                                        </span>
                                    </td>
                                    <td><strong><i class="fas fa-rupee-sign"></i> <?php echo number_format($subtotal, 2); ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr class="total-row">
                                <td colspan="4" style="text-align: right;">Total:</td>
                                <td><strong>₹<?php echo number_format($total, 2); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-box-open" style="font-size: 2rem; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                    <p style="margin: 0; color: var(--text-muted);">No items found for this order.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="action-bar">
                    <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                    
                    <form action="orders.php" method="post" class="status-form">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="status">
                            <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn"><i class="fas fa-sync-alt"></i> Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add animation when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const orderDetails = document.querySelector('.order-details');
            orderDetails.style.opacity = '0';
            orderDetails.style.transform = 'translateY(20px)';
            orderDetails.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(function() {
                orderDetails.style.opacity = '1';
                orderDetails.style.transform = 'translateY(0)';
            }, 100);
            
            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('.order-items tbody tr');
            tableRows.forEach(row => {
                row.style.transition = 'background-color 0.3s ease';
            });
        });
    </script>
</body>
</html>