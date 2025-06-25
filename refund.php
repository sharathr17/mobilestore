<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit;
}

$order_id = $_GET['order_id'];

// Verify order belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my_orders.php");
    exit;
}

$order = $result->fetch_assoc();

// Check if order is eligible for refund (not already cancelled or refunded)
if ($order['status'] === 'cancelled' || $order['status'] === 'refunded') {
    header("Location: my_orders.php");
    exit;
}

// Process refund or cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'];
    $comments = $_POST['comments'];
    $request_type = $_POST['request_type']; // Get request type (refund or cancellation)
    
    // Create refund/cancellation record
    $stmt = $conn->prepare("INSERT INTO refunds (order_id, user_id, reason, comments, status, request_type) VALUES (?, ?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iisss", $order_id, $user_id, $reason, $comments, $request_type);
    
    if ($stmt->execute()) {
        // No longer updating order status
        $success_message = "Your " . $request_type . " request has been submitted successfully. We will process it as soon as possible.";
    } else {
        // Check if the error is due to missing refunds table
        if ($conn->errno == 1146) { // Table doesn't exist error
            $error_message = "Error: Refunds table does not exist. Please contact support.";
        } else {
            $error_message = "Error processing request: " . $conn->error;
        }
    }
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();

// Determine if cancellation is allowed (typically only for recent orders)
$cancellation_allowed = true;
$order_date = new DateTime($order['created_at']);
$current_date = new DateTime();
$interval = $current_date->diff($order_date);

// Only allow cancellation within 24 hours of order placement
if ($interval->days > 0 || $order['status'] == 'shipped' || $order['status'] == 'delivered') {
    $cancellation_allowed = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Refund or Cancellation - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/refund.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Mobile Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products/">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="my_orders.php">My Orders</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="refund-section">
        <div class="container">
            <h2>Request Refund or Cancellation</h2>
            
            <div class="refund-container">
                <?php if($success_message): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="my_orders.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to My Orders
                        </a>
                    </div>
                <?php else: ?>
                    <?php if($error_message): ?>
                        <div class="message error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-summary">
                        <h3><i class="fas fa-info-circle"></i> Order Summary</h3>
                        <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                        <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                    </div>
                    
                    <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                    <table class="order-items">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <?php if($item['image']): ?>
                                                <img src="images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <h3><i class="fas fa-file-alt"></i> Request Form</h3>
                    <form method="post" action="refund.php?order_id=<?php echo $order_id; ?>">
                        <div class="request-type-selector">
                            <label for="type-refund" id="label-refund" class="active">
                                <input type="radio" id="type-refund" name="request_type" value="refund" checked>
                                <i class="fas fa-undo"></i> Request Refund
                            </label>
                            <label for="type-cancellation" id="label-cancellation" <?php echo !$cancellation_allowed ? 'style="opacity: 0.5;"' : ''; ?>>
                                <input type="radio" id="type-cancellation" name="request_type" value="cancellation" <?php echo !$cancellation_allowed ? 'disabled' : ''; ?>>
                                <i class="fas fa-ban"></i> Request Cancellation
                            </label>
                        </div>
                        
                        <?php if (!$cancellation_allowed): ?>
                            <div class="message error">
                                <i class="fas fa-exclamation-circle"></i> Cancellation is only available for orders placed within the last 24 hours and not yet shipped.
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="reason">Reason for Request</label>
                            <select id="reason" name="reason" required>
                                <option value="">Select a reason</option>
                                <option value="Changed mind">Changed my mind</option>
                                <option value="Ordered wrong item">Ordered wrong item</option>
                                <option value="Item not as described">Item not as described</option>
                                <option value="Item damaged">Item damaged or defective</option>
                                <option value="Shipping too slow">Shipping too slow</option>
                                <option value="Other">Other reason</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="comments">Additional Comments</label>
                            <textarea id="comments" name="comments" placeholder="Please provide more details about your request"></textarea>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                            <a href="thank_you.php?order_id=<?php echo $order_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>Contact Us: <a href="mailto:                  <p>Contact Us: <a href="mailto:EMAIL"EMAIL<a href="mailto:EMAIL">info@mobilestore.com</a></p>
            <p>&copy; <?php echo date('Y'); ?> Mobile Store. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the radio buttons and labels
        const refundRadio = document.getElementById('type-refund');
        const cancellationRadio = document.getElementById('type-cancellation');
        const refundLabel = document.getElementById('label-refund');
        const cancellationLabel = document.getElementById('label-cancellation');
        
        // Add event listeners to update active class
        refundRadio.addEventListener('change', function() {
            if (this.checked) {
                refundLabel.classList.add('active');
                cancellationLabel.classList.remove('active');
            }
        });
        
        cancellationRadio.addEventListener('change', function() {
            if (this.checked && !this.disabled) {
                cancellationLabel.classList.add('active');
                refundLabel.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>