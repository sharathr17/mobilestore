<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Process refund approval/rejection
if ((isset($_POST['approve_refund']) || isset($_POST['reject_refund'])) && isset($_POST['refund_id']) && isset($_POST['order_id'])) {
    $refund_id = $_POST['refund_id'];
    $order_id = $_POST['order_id'];
    $request_type = $_POST['request_type'];
    $admin_id = $_SESSION['user_id'];
    $status = isset($_POST['approve_refund']) ? 'approved' : 'rejected';
    
    // Update refund status
    $update_refund = $conn->prepare("UPDATE refunds SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
    $update_refund->bind_param("sii", $status, $admin_id, $refund_id);
    $update_refund->execute();
    
    // If approved, update order status
    if (isset($_POST['approve_refund'])) {
        $new_status = ($request_type == 'cancel') ? 'cancelled' : 'refunded';
        $update_order = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_order->bind_param("si", $new_status, $order_id);
        $update_order->execute();
    }
}

// Get refund details if viewing a specific refund
$refund_details = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $refund_id = $_GET['view'];
    
    // Get refund details
    $stmt = $conn->prepare("SELECT r.*, o.total_amount, o.status as order_status, u.username, u.email 
                          FROM refunds r 
                          JOIN orders o ON r.order_id = o.id 
                          JOIN users u ON r.user_id = u.id 
                          WHERE r.id = ?");
    $stmt->bind_param("i", $refund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $refund_details = $result->fetch_assoc();
        
        // Get order items
        $order_items = $conn->query("SELECT oi.*, p.name, p.image FROM order_items oi 
                                  JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = {$refund_details['order_id']}");
    }
}

// Get all refunds with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_refunds = $conn->query("SELECT COUNT(*) as count FROM refunds")->fetch_assoc()['count'];
$total_pages = ceil($total_refunds / $limit);

// Filter by status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$status_condition = '';
$params = [];

if ($status_filter) {
    $status_condition = "WHERE r.status = ?";
    $params[] = $status_filter;
}

// Prepare the query with pagination
$query = "SELECT r.*, o.total_amount, o.status as order_status, u.username 
          FROM refunds r 
          JOIN orders o ON r.order_id = o.id 
          JOIN users u ON r.user_id = u.id 
          $status_condition 
          ORDER BY r.created_at DESC 
          LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $limit;

// Create the prepared statement
$stmt = $conn->prepare($query);

// Bind parameters dynamically
$types = str_repeat('s', count($params) - 2) . 'ii'; // string params + 2 integers for pagination
$stmt->bind_param($types, ...$params);
$stmt->execute();
$refunds = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Refunds - Mobile Store</title>
    <link rel="stylesheet" href="../css/orders.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="refunds.php" class="active"><i class="fas fa-undo"></i> Refunds</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div>

        <div class="main-content">
            <?php if ($refund_details): ?>
                <h2>Refund Request #<?php echo $refund_details['id']; ?> Details</h2>
                
                <div class="refund-details-card">
                    <h3>Refund Information</h3>
                    <p><strong>Refund ID:</strong> #<?php echo $refund_details['id']; ?></p>
                    <p><strong>Order ID:</strong> #<?php echo $refund_details['order_id']; ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($refund_details['username']); ?> (<?php echo htmlspecialchars($refund_details['email']); ?>)</p>
                    <p><strong>Request Type:</strong> <?php echo ucfirst($refund_details['request_type'] ?? 'Refund'); ?></p>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($refund_details['reason']); ?></p>
                    <p><strong>Comments:</strong> <?php echo htmlspecialchars($refund_details['comments']); ?></p>
                    <p><strong>Date Requested:</strong> <?php echo date('F j, Y, g:i a', strtotime($refund_details['created_at'])); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($refund_details['status']); ?></p>
                    <p><strong>Order Status:</strong> <?php echo ucfirst($refund_details['order_status']); ?></p>
                    <p><strong>Order Amount:</strong> $<?php echo number_format($refund_details['total_amount'], 2); ?></p>
                    
                    <?php if($refund_details['status'] == 'pending'): ?>
                    <div style="margin-top: 20px;">
                        <form action="refunds.php?view=<?php echo $refund_details['id']; ?>" method="post" style="display: inline;">
                            <input type="hidden" name="refund_id" value="<?php echo $refund_details['id']; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $refund_details['order_id']; ?>">
                            <input type="hidden" name="request_type" value="<?php echo $refund_details['request_type'] ?? 'refund'; ?>">
                            <button type="submit" name="approve_refund" class="btn" style="background-color: #28a745;">Approve Request</button>
                            <button type="submit" name="reject_refund" class="btn" style="background-color: #dc3545;">Reject Request</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                
                <h3>Order Items</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Image</th>
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
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <img src="../images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            No Image
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr>
                                <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                                <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No items found for this order.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px;">
                    <a href="refunds.php" class="btn" style="background-color: #6c757d;">Back to Refunds</a>
                    <a href="orders.php?view=<?php echo $refund_details['order_id']; ?>" class="btn">View Order</a>
                </div>
            <?php else: ?>
                <h2>Manage Refunds</h2>
                
                <div class="filter-bar">
                    <label for="status-filter"><strong>Filter by Status:</strong></label>
                    <select id="status-filter" onchange="window.location.href='refunds.php?status='+this.value">
                        <option value="" <?php echo $status_filter == '' ? 'selected' : ''; ?>>All Requests</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <table class="refund-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($refunds && $refunds->num_rows > 0): ?>
                            <?php while($refund = $refunds->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $refund['id']; ?></td>
                                    <td>#<?php echo $refund['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($refund['username']); ?></td>
                                    <td><?php echo ucfirst($refund['request_type'] ?? 'Refund'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($refund['reason'], 0, 30)) . (strlen($refund['reason']) > 30 ? '...' : ''); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($refund['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $refund['status']; ?>">
                                            <?php echo ucfirst($refund['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="refunds.php?view=<?php echo $refund['id']; ?>" class="btn btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No refund requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a href="refunds.php?page=<?php echo $i; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>