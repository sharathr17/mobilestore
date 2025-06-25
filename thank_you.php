<?php
session_start();
require_once 'config.php';

if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$order = $result->fetch_assoc();

// Get order items
$items = $conn->query("SELECT oi.*, p.name, p.image FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = $order_id");
// Get shipping address if exists
if (isset($order['shipping_address_id'])) {
    $shipping_stmt = $conn->prepare("SELECT * FROM shipping_addresses WHERE id = ?");
    $shipping_stmt->bind_param("i", $order['shipping_address_id']);
    $shipping_stmt->execute();
    $shipping_result = $shipping_stmt->get_result();
    $shipping_address = $shipping_result->num_rows > 0 ? $shipping_result->fetch_assoc() : null;
} else {
    // Try to find any shipping address for this user
    $shipping_stmt = $conn->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $shipping_stmt->bind_param("i", $user_id);
    $shipping_stmt->execute();
    $shipping_result = $shipping_stmt->get_result();
    $shipping_address = $shipping_result->num_rows > 0 ? $shipping_result->fetch_assoc() : null;
}

// Define more descriptive status types
$statusTypes = [
    'pending' => 'Payment Pending',
    'processing' => 'Order Processing',
    'shipped' => 'Order Shipped',
    'delivered' => 'Order Delivered',
    'completed' => 'Order Completed',
    'cancelled' => 'Order Cancelled',
    'refunded' => 'Order Refunded'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/thank_you.css">
    <link rel="stylesheet" href="css/myorder.css"> <!-- For status badges -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-mobile-alt"></i> Mobile Store</h1>
            <nav>
                <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="products/"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="my_orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section class="confirmation-section">
        <div class="container">
            <h2>Order Confirmation</h2>
            
            <div class="order-confirmation">
                <div class="order-details">
                    <h3>Thank you for your order!</h3>
                    <p>
                        <?php 
                        // Display different messages based on order status
                        switch($order['status']) {
                            case 'pending':
                                echo "Your order has been placed successfully. Payment confirmation is pending."; 
                                break;
                            case 'processing':
                                echo "Your order has been confirmed and is being processed. We will ship it soon.";
                                break;
                            case 'shipped':
                                echo "Your order has been shipped and is on its way to you.";
                                break;
                            case 'delivered':
                                echo "Your order has been delivered. We hope you enjoy your purchase!";
                                break;
                            case 'completed':
                                echo "Your order has been completed. Thank you for shopping with us!";
                                break;
                            case 'cancelled':
                                echo "Your order has been cancelled. Please contact customer support if you have any questions.";
                                break;
                            case 'refunded':
                                echo "Your order has been refunded. The amount should appear in your account within 3-5 business days.";
                                break;
                            default:
                                echo "Your order has been placed successfully. We will process it as soon as possible.";
                        }
                        ?>
                    </p>
                    <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                    <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                    <p>
                        <strong>Status:</strong> 
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo isset($statusTypes[$order['status']]) ? $statusTypes[$order['status']] : ucfirst($order['status']); ?>
                        </span>
                    </p>
                </div>
                
                <?php if (isset($shipping_address)): ?>
                <div class="shipping-info">
                    <h3>Shipping Address</h3>
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
                
                <h3>Order Items</h3>
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
                                    <div class="product-info">
                                        <?php if($item['image']): ?>
                                            <img src="images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="product-image">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="action-buttons">
                    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'refunded'): ?>
                        <a href="refund.php?order_id=<?php echo $order_id; ?>&type=refund" class="btn btn-danger">Request Refund</a>
                        
                        <?php 
                        // Only show cancellation option for recent orders (within 24 hours)
                        $order_date = new DateTime($order['created_at']);
                        $current_date = new DateTime();
                        $interval = $current_date->diff($order_date);
                        $cancellation_allowed = ($interval->days == 0 && $order['status'] != 'shipped' && $order['status'] != 'delivered');
                        
                        if ($cancellation_allowed): 
                        ?>
                            <a href="refund.php?order_id=<?php echo $order_id; ?>&type=cancellation" class="btn btn-secondary">Request Cancellation</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="my_orders.php" class="btn btn-primary">Back to My Orders</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Mobile Store</h3>
                    <p>Your one-stop shop for premium mobile devices and accessories.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="products/"><i class="fas fa-chevron-right"></i> Products</a></li>
                        <li><a href="cart.php"><i class="fas fa-chevron-right"></i> Cart</a></li>
                        <li><a href="login.php"><i class="fas fa-chevron-right"></i> Account</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="feedback.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> FAQs</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Shipping Policy</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Return Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-envelope"></i> info@mobilestore.com</li>
                        <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main St, City</li>
                        <li><a href="feedback.php"><i class="fas fa-comment-alt"></i> Send Feedback</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Mobile Store. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('nav ul');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('show');
            });
        }
    </script>
</body>
</html>