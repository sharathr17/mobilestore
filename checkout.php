<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    // If no order_id, redirect to cart
    header("Location: cart.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Verify order belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: cart.php");
    exit;
}

$order = $result->fetch_assoc();

// Get user's default shipping address if exists
$address_stmt = $conn->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? AND is_default = 1");
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$address_result = $address_stmt->get_result();
$default_address = $address_result->num_rows > 0 ? $address_result->fetch_assoc() : null;

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = $_POST['full_name'];
    $address_line1 = $_POST['address_line1'];
    $address_line2 = isset($_POST['address_line2']) ? $_POST['address_line2'] : '';
    $city = $_POST['city'];
    $state = $_POST['state'];
    $postal_code = $_POST['postal_code'];
    $country = $_POST['country'];
    $phone = $_POST['phone'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate phone number
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = "Phone number must be exactly 10 digits.";
    } else {
        // If setting as default, unset any existing default
        if ($is_default) {
            $update_stmt = $conn->prepare("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
        }
        
        // Insert new shipping address
        $insert_stmt = $conn->prepare("INSERT INTO shipping_addresses (user_id, full_name, address_line1, address_line2, city, state, postal_code, country, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("issssssssi", $user_id, $full_name, $address_line1, $address_line2, $city, $state, $postal_code, $country, $phone, $is_default);
        
        if ($insert_stmt->execute()) {
            $shipping_id = $conn->insert_id;
            
            // Update order with shipping address ID
            $order_update = $conn->prepare("UPDATE orders SET shipping_address_id = ? WHERE id = ?");
            $order_update->bind_param("ii", $shipping_id, $order_id);
            $order_update->execute();
            
            // Redirect to payment page
            header("Location: payment.php?order_id=" . $order_id);
            exit;
        } else {
            $error_message = "Error saving shipping address: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css"> <!-- Add this line -->
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

    <section class="checkout-section">
        <div class="container">
            <h2>Complete Your Order</h2>
            
            <div class="checkout-container">
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                    <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
                
                <?php if($success_message): ?>
                    <div class="message success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                    <div class="message error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="post" class="checkout-form">
                    <h3>Shipping Address</h3>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $default_address ? htmlspecialchars($default_address['full_name']) : ''; ?>" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" value="<?php echo $default_address ? htmlspecialchars($default_address['address_line1']) : ''; ?>" placeholder="Street address, P.O. box, company name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address_line2">Address Line 2 (Optional)</label>
                        <input type="text" id="address_line2" name="address_line2" value="<?php echo $default_address ? htmlspecialchars($default_address['address_line2']) : ''; ?>" placeholder="Apartment, suite, unit, building, floor, etc.">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo $default_address ? htmlspecialchars($default_address['city']) : ''; ?>" placeholder="Enter your city" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State/Province</label>
                            <input type="text" id="state" name="state" value="<?php echo $default_address ? htmlspecialchars($default_address['state']) : ''; ?>" placeholder="Enter your state or province" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo $default_address ? htmlspecialchars($default_address['postal_code']) : ''; ?>" placeholder="Enter your postal code" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?php echo $default_address ? htmlspecialchars($default_address['country']) : ''; ?>" placeholder="Enter your country" required>
                        </div>
                    </div>
                    
                    <!-- Phone field with special styling -->
                    <div class="form-group phone-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $default_address ? htmlspecialchars($default_address['phone']) : ''; ?>" 
                               pattern="[0-9]{10}" maxlength="10" placeholder="10-digit phone number" 
                               title="Please enter exactly 10 digits" required>
                        <small class="form-text">Enter a 10-digit phone number without spaces or dashes</small>
                    </div>
                    
                    <div class="checkbox-container">
                        <label>
                            <input type="checkbox" name="is_default" <?php echo (!$default_address || ($default_address && $default_address['is_default'])) ? 'checked' : ''; ?>>
                            Save as default shipping address
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-checkout-submit">
                        <i class="fas fa-credit-card"></i> Continue to Payment
                    </button>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        
        // Format as user types
        phoneInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
        
        // Validate on form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = phoneInput.value.replace(/\D/g, '');
            
            if (phone.length !== 10) {
                e.preventDefault();
                alert('Please enter exactly 10 digits for the phone number.');
                phoneInput.focus();
            }
        });
        
        // Mobile menu toggle
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('nav ul');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('show');
            });
        }
    });
    </script>
</body>
</html>