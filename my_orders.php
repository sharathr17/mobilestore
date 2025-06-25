<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all orders for this user
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/myorder.css"> <!-- Add this line -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Remove the inline styles that were here -->
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
                    <li><a href="my_orders.php" class="active"><i class="fas fa-box"></i> My Orders</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="orders-section"> <!-- Changed from main to section for consistency -->
        <div class="container">
            <h2>My Orders</h2>
            
            <div class="orders-container">
                <?php if ($orders && $orders->num_rows > 0): ?>
                    <?php while($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p>Placed on: <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div>
                                    <p><strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        // Convert status to more descriptive Order Status Type
                                        $statusTypes = [
                                            'pending' => 'Payment Pending',
                                            'processing' => 'Order Processing',
                                            'shipped' => 'Order Shipped',
                                            'delivered' => 'Order Delivered',
                                            'completed' => 'Order Completed',
                                            'cancelled' => 'Order Cancelled'
                                        ];
                                        
                                        // Display the descriptive status or fallback to capitalized status
                                        echo isset($statusTypes[$order['status']]) ? $statusTypes[$order['status']] : ucfirst($order['status']);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Modify the order items query to include product stock information -->
                            <?php
                            // Get order items
                            $items_stmt = $conn->prepare("SELECT oi.*, p.name, p.stock FROM order_items oi 
                                                JOIN products p ON oi.product_id = p.id 
                                                WHERE oi.order_id = ?");
                            $items_stmt->bind_param("i", $order['id']);
                            $items_stmt->execute();
                            $items = $items_stmt->get_result();
                            ?>
                            
                            <!-- Update the table headers to include Status column -->
                            <table class="order-items">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = $items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <td>
                                                <?php if(isset($item['stock'])): ?>
                                                    <?php if($item['stock'] > 10): ?>
                                                        <span class="product-status in-stock"><i class="fas fa-check-circle"></i> In Stock</span>
                                                    <?php elseif($item['stock'] > 0): ?>
                                                        <span class="product-status low-stock"><i class="fas fa-exclamation-circle"></i> Low Stock</span>
                                                    <?php else: ?>
                                                        <span class="product-status out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="product-status unknown"><i class="fas fa-question-circle"></i> Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            
                            <div style="margin-top: 15px; text-align: right;">
                                <a href="thank_you.php?order_id=<?php echo $order['id']; ?>" class="btn-view"><i class="fas fa-eye"></i> View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-orders">
                        <p>You haven't placed any orders yet.</p>
                        <a href="products/"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
                    </div>
                <?php endif; ?>
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