<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Update order status
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
}

// Get order details if viewing a specific order
$order_details = null;
$order_items = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = $_GET['view'];
    
    // Get order details
    $stmt = $conn->prepare("SELECT o.*, u.username, u.email FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        
        // Get order items
        $order_items = $conn->query("SELECT oi.*, p.name, p.image FROM order_items oi 
                                  JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = $order_id");
    }
}

// Get all orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_pages = ceil($total_orders / $limit);

$orders = $conn->query("SELECT o.*, u.username FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC 
                      LIMIT $offset, $limit");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Mobile Store</title>
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
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="refunds.php"><i class="fas fa-undo"></i> Refunds</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div>

        <div class="main-content">
            <?php if ($order_details): ?>
                <div class="page-title">
                    <h2><i class="fas fa-file-invoice"></i> Order #<?php echo $order_details['id']; ?> Details</h2>
                    <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                </div>
                
                <div class="order-details">
                    <div class="order-meta">
                        <div class="order-meta-item">
                            <h4>Order ID</h4>
                            <p>#<?php echo $order_details['id']; ?></p>
                        </div>
                        <div class="order-meta-item">
                            <h4>Date</h4>
                            <p><i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($order_details['created_at'])); ?></p>
                            <p style="font-size: 0.8rem; margin-top: 5px; color: var(--text-muted);"><i class="far fa-clock"></i> <?php echo date('g:i a', strtotime($order_details['created_at'])); ?></p>
                        </div>
                        <div class="order-meta-item">
                            <h4>Customer</h4>
                            <p><i class="far fa-user"></i> <?php echo htmlspecialchars($order_details['username']); ?></p>
                        </div>
                        <div class="order-meta-item">
                            <h4>Email</h4>
                            <p><i class="far fa-envelope"></i> <?php echo htmlspecialchars($order_details['email']); ?></p>
                        </div>
                        <div class="order-meta-item">
                            <h4>Total</h4>
                            <p>₹<?php echo number_format($order_details['total_amount'], 2); ?></p>
                        </div>
                        <div class="order-meta-item">
                            <h4>Status</h4>
                            <p>
                                <span class="status-badge status-<?php echo strtolower($order_details['status']); ?>">
                                    <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                                    <?php echo ucfirst($order_details['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="order-meta-item">
                            <h4>Payment Status</h4>
                            <p>
                                <span class="status-badge status-<?php echo strtolower($order_details['payment_status'] ?? 'pending'); ?>">
                                    <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                                    <?php echo ucfirst($order_details['payment_status'] ?? 'pending'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($shipping_address): ?>
                    <div class="shipping-info">
                        <h3><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                        <p><strong><?php echo htmlspecialchars($shipping_address['full_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($shipping_address['address_line1']); ?></p>
                        <?php if (!empty($shipping_address['address_line2'])): ?>
                            <p><?php echo htmlspecialchars($shipping_address['address_line2']); ?></p>
                        <?php endif; ?>
                        <p>
                            <?php echo htmlspecialchars($shipping_address['city']); ?>, 
                            <?php echo htmlspecialchars($shipping_address['state']); ?> 
                            <?php echo htmlspecialchars($shipping_address['postal_code']); ?>
                        </p>
                        <p><?php echo htmlspecialchars($shipping_address['country']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
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
                            <?php if ($order_items && $order_items->num_rows > 0): ?>
                                <?php 
                                $total = 0;
                                while($item = $order_items->fetch_assoc()): 
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
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <span style="background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-weight: 600;">
                                                <?php echo $item['quantity']; ?>
                                            </span>
                                        </td>
                                        <td><strong>₹<?php echo number_format($subtotal, 2); ?></strong></td>
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
                    
                    <?php if ($refund_result && $refund_result->num_rows > 0): ?>
                    <div class="section-title" style="margin-top: 30px;">
                        <h3><i class="fas fa-undo"></i> Refund/Cancellation Requests</h3>
                    </div>
                    <table class="order-items">
                        <thead>
                            <tr>
                                <th>Request Type</th>
                                <th>Reason</th>
                                <th>Comments</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($refund = $refund_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo ucfirst($refund['request_type'] ?? 'refund'); ?></td>
                                    <td><?php echo htmlspecialchars($refund['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($refund['comments']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($refund['status']); ?>">
                                            <?php echo ucfirst($refund['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($refund['created_at'])); ?></td>
                                    <td>
                                        <?php if($refund['status'] == 'pending'): ?>
                                            <a href="refunds.php?view=<?php echo $refund['id']; ?>" class="btn btn-sm">Manage</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    
                    <div class="action-bar">
                        <form action="orders.php" method="post" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                            <label for="status"><strong>Order Status:</strong></label>
                            <select name="status" id="status">
                                <option value="pending" <?php echo ($order_details['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order_details['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo ($order_details['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo ($order_details['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo ($order_details['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn"><i class="fas fa-sync-alt"></i> Update Status</button>
                        </form>
                        
                        <form action="orders.php" method="post" class="status-form" style="margin-left: 20px;">
                            <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                            <label for="payment_status"><strong>Payment Status:</strong></label>
                            <select name="payment_status" id="payment_status">
                                <option value="pending" <?php echo (isset($order_details['payment_status']) && $order_details['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo (isset($order_details['payment_status']) && $order_details['payment_status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="failed" <?php echo (isset($order_details['payment_status']) && $order_details['payment_status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo (isset($order_details['payment_status']) && $order_details['payment_status'] == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                            <button type="submit" name="update_payment_status" class="btn"><i class="fas fa-sync-alt"></i> Update Payment</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="page-title">
                    <h2><i class="fas fa-shopping-cart"></i> Manage Orders</h2>
                    <div class="quick-actions">
                        <div class="search-box">
                            <form action="" method="get">
                                <input type="text" name="search" placeholder="Search orders..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit"><i class="fas fa-search"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Replace the existing table with this enhanced version (around line 300) -->
                <div class="order-table-container">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Order Status</th>
                                <th>Payment Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders && $orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td class="order-id">#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center;">
                                                <div style="width: 32px; height: 32px; background-color: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                    <i class="fas fa-user" style="color: #888;"></i>
                                                </div>
                                                <span><?php echo htmlspecialchars($order['username']); ?></span>
                                            </div>
                                        </td>
                                        <td class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status status-<?php echo strtolower($order['status']); ?>">
                                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status status-<?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                                                <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('g:i A', strtotime($order['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-table">
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-cart"></i>
                                            <p>No orders found</p>
                                            <span>When customers place orders, they will appear here</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="orders.php?page=<?php echo ($page - 1); ?>" class="pagination-arrow"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="orders.php?page=<?php echo $i; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="orders.php?page=<?php echo ($page + 1); ?>" class="pagination-arrow"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Add animation when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const mainContent = document.querySelector('.main-content');
            mainContent.style.opacity = '0';
            mainContent.style.transform = 'translateY(20px)';
            mainContent.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(function() {
                mainContent.style.opacity = '1';
                mainContent.style.transform = 'translateY(0)';
            }, 100);
            
            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('.order-table tbody tr');
            tableRows.forEach(row => {
                row.style.transition = 'background-color 0.3s ease';
            });
        });
    </script>
</body>
</html>
