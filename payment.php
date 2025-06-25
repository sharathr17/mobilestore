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

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you would integrate with a payment gateway here
    // For this example, we'll just update the order status
    
    $payment_method = $_POST['payment_method'];
    $new_status = 'processing'; // Change status to processing after payment
    $payment_status = 'completed'; // Set payment status to completed
    
    // Update both order status and payment status
    $update_stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $new_status, $payment_status, $order_id);
    
    if ($update_stmt->execute()) {
        // Payment successful
        header("Location: payment_success.php?order_id=" . $order_id);
        exit;
    } else {
        $error = "Payment processing failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/payment.css"> <!-- Add this line -->
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

    <section class="payment-section">
        <div class="container">
            <h2>Complete Your Payment</h2>
            
            <div class="payment-container">
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <p>
                        <span><strong>Order ID:</strong> #<?php echo $order_id; ?></span>
                    </p>
                    <p>
                        <span><strong>Total Amount:</strong></span>
                        <span class="total-amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </p>
                    <p>
                        <span><strong>Payment Status:</strong></span>
                        <span class="payment-status <?php echo $order['payment_status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                        </span>
                    </p>
                </div>
                
                <?php if(isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" class="payment-form">
                    <h3>Select Payment Method</h3>
                    
                    <div class="payment-methods">
                        <div class="payment-method" onclick="selectPayment('credit_card')">
                            <input type="radio" id="credit_card" name="payment_method" value="credit_card" checked>
                            <label for="credit_card">Credit Card</label>
                        </div>
                        <div class="payment-method" onclick="selectPayment('upi')">
                            <input type="radio" id="upi" name="payment_method" value="upi">
                            <label for="upi">UPI</label>
                        </div>
                        <div class="payment-method" onclick="selectPayment('bank_transfer')">
                            <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                            <label for="bank_transfer">Bank Transfer</label>
                        </div>
                    </div>
                    
                    <div id="credit_card_details">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        <div class="card-row">
                            <div class="form-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" id="expiry" name="expiry" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="card_name">Name on Card</label>
                            <input type="text" id="card_name" name="card_name" placeholder="John Doe">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-pay">Pay Now ₹<?php echo number_format($order['total_amount'], 2); ?></button>
                    
                    <div class="security-badge">
                        All payments are secure and encrypted
                    </div>
                </form>
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
    
    <!-- Remove the outer script tag - it's causing the error -->
    <script>
        function selectPayment(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to the clicked payment method
            document.querySelector(`#${method}`).closest('.payment-method').classList.add('selected');
            
            // Check the radio button
            document.querySelector(`#${method}`).checked = true;
            
            // Hide all payment details sections
            document.getElementById('credit_card_details').style.display = 'none';
            document.getElementById('upi_details').style.display = 'none';
            
            // Show the selected payment details section
            if (method === 'credit_card') {
                document.getElementById('credit_card_details').style.display = 'block';
            } else if (method === 'upi') {
                document.getElementById('upi_details').style.display = 'block';
            }
        }
        
        // Format credit card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            // Remove all non-digit characters
            let value = this.value.replace(/\D/g, '');
            
            // Add a space after every 4 digits
            value = value.replace(/(.{4})/g, '$1 ').trim();
            
            // Update the input value
            this.value = value;
        });
        
        // Format expiry date with slash
        document.getElementById('expiry').addEventListener('input', function(e) {
            // Remove all non-digit characters
            let value = this.value.replace(/\D/g, '');
            
            // Add slash after first 2 digits
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            
            // Update the input value
            this.value = value;
        });
        
        // Limit CVV to numbers only
        document.getElementById('cvv').addEventListener('input', function(e) {
            // Remove all non-digit characters
            this.value = this.value.replace(/\D/g, '');
        });
        
        // Format UPI ID
        document.getElementById('upi_id').addEventListener('input', function(e) {
            // Allow only alphanumeric characters, @, and .
            this.value = this.value.replace(/[^a-zA-Z0-9@.]/g, '');
        });
        
        // Initialize
        selectPayment('credit_card');
        
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

<!-- Add UPI Details Section -->
<div id="upi_details" style="display: none;">
    <div class="form-group">
        <label for="upi_id">UPI ID</label>
        <input type="text" id="upi_id" name="upi_id" placeholder="yourname@upi" maxlength="50">
        <small style="display: block; margin-top: 5px; color: #6c757d;">Enter your UPI ID (e.g., yourname@ybl, yourname@okhdfcbank)</small>
    </div>
</div>