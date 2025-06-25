<?php
require_once 'config.php';
session_start();

// Fetch featured products from database
$sql = "SELECT * FROM products WHERE featured = 1 LIMIT 6";
$featured_result = $conn->query($sql);

// Fetch latest products
$sql = "SELECT * FROM products ORDER BY id DESC LIMIT 8";
$latest_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Store - Premium Mobile Devices</title>
    <link rel="stylesheet" href="css/style.css">
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

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Premium Mobile Devices at Your Fingertips</h2>
                <p>Discover the latest smartphones and accessories with exclusive deals and free shipping.</p>
                <div class="hero-buttons">
                    <a href="products/" class="btn"><i class="fas fa-shopping-bag"></i> Shop Now</a>
                    <a href="#featured" class="btn btn-secondary"><i class="fas fa-star"></i> Featured Products</a>
                </div>
            </div>
        </div>
    </section>

    <section id="featured">
        <div class="container">
            <h2>Featured Products</h2>
            <p class="subtitle">Handpicked premium devices for our customers</p>
            
            <div class="products-grid">
                <?php
                if ($featured_result && $featured_result->num_rows > 0) {
                    while($row = $featured_result->fetch_assoc()) {
                        echo '<div class="product-card">';
                        if (!empty($row["image"]) && file_exists("images/" . $row["image"])) {
                            echo '<img src="images/' . $row["image"] . '" alt="' . $row["name"] . '">';
                        } else {
                            echo '<div class="no-image"><i class="fas fa-image fa-3x"></i></div>';
                        }
                        echo '<div class="card-content">';
                        echo '<h3>' . $row["name"] . '</h3>';
                        echo '<p class="price">₹' . $row["price"] . '</p>';
                        echo '<a href="products/details.php?id=' . $row["id"] . '" class="btn"><i class="fas fa-eye"></i> View Details</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No featured products available yet.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Why Choose Us</h2>
            <p class="subtitle">We offer the best shopping experience</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Fast Shipping</h3>
                    <p>Free shipping on all orders over ₹3,500 with delivery within 2-3 business days.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure Payments</h3>
                    <p>All transactions are secure and encrypted for your protection.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-undo"></i>
                    <h3>Easy Returns</h3>
                    <p>30-day money-back guarantee for all products, no questions asked.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Our customer service team is available around the clock to assist you.</p>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <h2>Latest Products</h2>
            <p class="subtitle">Check out our newest arrivals</p>
            
            <div class="products-grid">
                <?php
                if ($latest_result && $latest_result->num_rows > 0) {
                    while($row = $latest_result->fetch_assoc()) {
                        echo '<div class="product-card">';
                        if (!empty($row["image"]) && file_exists("images/" . $row["image"])) {
                            echo '<img src="images/' . $row["image"] . '" alt="' . $row["name"] . '">';
                        } else {
                            echo '<div class="no-image"><i class="fas fa-image fa-3x"></i></div>';
                        }
                        echo '<div class="card-content">';
                        echo '<h3>' . $row["name"] . '</h3>';
                        echo '<p class="price">₹' . $row["price"] . '</p>';
                        echo '<a href="products/details.php?id=' . $row["id"] . '" class="btn"><i class="fas fa-eye"></i> View Details</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No products available yet.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Subscribe to Our Newsletter</h2>
            <p>Stay updated with our latest products and exclusive offers.</p>
            <a href="feedback.php?tab=subscribe" class="btn"><i class="fas fa-envelope"></i> Subscribe Now</a>
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