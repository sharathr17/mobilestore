<?php
require_once 'config.php';
session_start();

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['is_admin'] = $row['is_admin'];
            
            // If there's a redirect parameter and user is not admin, redirect there
            if ($redirect && !$row['is_admin']) {
                header("location: " . $redirect);
            } else if ($row['is_admin']) {
                header("location: admin/index.php");
            } else {
                header("location: index.php");
            }
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid username";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mobile Store</title>
    <link rel="stylesheet" href="css/login.css">
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
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                    <li><a href="feedback.php"><i class="fas fa-comment-alt"></i> Feedback</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section>
        <div class="container">
            <h2>Login to Your Account</h2>
            <p class="subtitle">Access your orders, wishlist and recommendations</p>
            
            <div class="form-container">
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($redirect == 'cart.php'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please login to complete your checkout
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo $redirect ? 'login.php?redirect='.urlencode($redirect) : 'login.php'; ?>" method="post">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
                </form>
                
                <div class="form-footer">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
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

    <!-- JavaScript -->
    <script src="js/main.js"></script>
</body>
</html>