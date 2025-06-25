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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/payment.css"> <!-- Add this line to include payment.css -->
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

    <section class="payment-section"> <!-- Use the same section class as payment.php -->
        <div class="container">
            <h2>Payment Successful</h2>
            
            <div class="success-container">
                <div class="success-icon"></div>
                <h2>Thank You for Your Purchase!</h2>
                <p>Your payment has been processed successfully and your order is now being prepared.</p>
                
                <div class="order-details">
                    <h3>Order Details</h3>
                    <p>
                        <span><strong>Order ID:</strong></span>
                        <span>#<?php echo $order_id; ?></span>
                    </p>
                    <p>
                        <span><strong>Date:</strong></span>
                        <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                    </p>
                    <p>
                        <span><strong>Total Amount:</strong></span>
                        <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </p>
                    <p>
                        <span><strong>Status:</strong></span>
                        <span><?php echo ucfirst($order['status']); ?></span>
                    </p>
                </div>
                
                <div class="success-actions">
                    <a href="my_orders.php" class="btn btn-primary">View My Orders</a>
                    <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
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