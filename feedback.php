<?php
require_once 'config.php';
session_start();

$feedback_success = false;
$feedback_error = '';
$subscribe_success = false;
$subscribe_error = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'feedback';

// Get user data if logged in
$user_name = '';
$user_email = '';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_name = $user_data['username']; // Changed from 'name' to 'username'
        $user_email = $user_data['email'];
    }
    $user_stmt->close();
}

// Process feedback form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        $feedback_error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback_error = "Please enter a valid email address.";
    } elseif ($rating < 1 || $rating > 5) {
        $feedback_error = "Please select a rating.";
    } else {
        // Insert feedback into database
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, subject, message, rating, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            // If prepare fails, check if table doesn't exist
            if ($conn->errno == 1146) { // Table doesn't exist error
                // Create feedback table
                $create_table = "CREATE TABLE feedback (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    subject VARCHAR(255) DEFAULT NULL,
                    message TEXT NOT NULL,
                    rating INT(1) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )";
                
                if ($conn->query($create_table)) {
                    // Try preparing statement again
                    $stmt = $conn->prepare("INSERT INTO feedback (name, email, subject, message, rating, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                } else {
                    $feedback_error = "Error creating feedback table: " . $conn->error;
                }
            } else {
                $feedback_error = "Database error: " . $conn->error;
            }
        }
        
        if ($stmt) {
            $stmt->bind_param("ssssi", $name, $email, $subject, $message, $rating);
            
            if ($stmt->execute()) {
                $feedback_success = true;
            } else {
                $feedback_error = "Error submitting feedback: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
    
    // Set active tab
    $active_tab = 'feedback';
}

// Process subscription form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_subscribe'])) {
    $email = trim($_POST['subscribe_email']);
    $name = isset($_POST['subscribe_name']) ? trim($_POST['subscribe_name']) : '';
    
    // Validate email
    if (empty($email)) {
        $subscribe_error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subscribe_error = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ?");
        
        if (!$check_stmt) {
            // If prepare fails, check if table doesn't exist
            if ($conn->errno == 1146) { // Table doesn't exist error
                // Create subscribers table
                $create_table = "CREATE TABLE subscribers (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    name VARCHAR(100) DEFAULT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )";
                
                if ($conn->query($create_table)) {
                    // Email doesn't exist in new table
                    $email_exists = false;
                } else {
                    $subscribe_error = "Error creating subscribers table: " . $conn->error;
                }
            } else {
                $subscribe_error = "Database error: " . $conn->error;
            }
        } else {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $email_exists = $check_result->num_rows > 0;
            $check_stmt->close();
        }
        
        // If email doesn't exist, insert it
        if (empty($subscribe_error) && !$email_exists) {
            $insert_stmt = $conn->prepare("INSERT INTO subscribers (name, email) VALUES (?, ?)");
            
            if ($insert_stmt) {
                $insert_stmt->bind_param("ss", $name, $email);
                
                if ($insert_stmt->execute()) {
                    $subscribe_success = true;
                } else {
                    // Check for duplicate entry error
                    if ($insert_stmt->errno == 1062) { // Duplicate entry error
                        $subscribe_error = "This email is already subscribed to our newsletter.";
                    } else {
                        $subscribe_error = "Error subscribing: " . $insert_stmt->error;
                    }
                }
                
                $insert_stmt->close();
            } else {
                $subscribe_error = "Database error: " . $conn->error;
            }
        } elseif (empty($subscribe_error) && $email_exists) {
            $subscribe_error = "This email is already subscribed to our newsletter.";
        }
    }
    
    // Set active tab
    $active_tab = 'subscribe';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Subscribe - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/feedback.css">
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
                    <li><a href="feedback.php" class="active"><i class="fas fa-comment-alt"></i> Feedback</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="feedback-section">
        <div class="container">
            <h2>Feedback & Newsletter</h2>
            
            <div class="tabs">
                <div class="tab <?php echo $active_tab === 'feedback' ? 'active' : ''; ?>" onclick="switchTab('feedback')">
                    <i class="fas fa-comment-alt"></i> Provide Feedback
                </div>
                <div class="tab <?php echo $active_tab === 'subscribe' ? 'active' : ''; ?>" onclick="switchTab('subscribe')">
                    <i class="fas fa-envelope"></i> Subscribe to Newsletter
                </div>
            </div>
            
            <div id="feedback-tab" class="tab-content <?php echo $active_tab === 'feedback' ? 'active' : ''; ?>">
                <div class="form-container">
                    <?php if ($feedback_success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i> Thank you for your feedback! We appreciate your input and will use it to improve our services.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($feedback_error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $feedback_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="feedback.php?tab=feedback">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Your Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject">
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Your Feedback *</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Rate Your Experience *</label>
                            <div class="rating-container">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        
                        <button type="submit" name="submit_feedback" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
            
            <div id="subscribe-tab" class="tab-content <?php echo $active_tab === 'subscribe' ? 'active' : ''; ?>">
                <div class="form-container">
                    <?php if ($subscribe_success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i> Thank you for subscribing to our newsletter! You'll now receive updates on our latest products and exclusive offers.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($subscribe_error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $subscribe_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="feedback.php?tab=subscribe">
                        <div class="form-group">
                            <label for="subscribe_name">Your Name (Optional)</label>
                            <input type="text" id="subscribe_name" name="subscribe_name" value="<?php echo htmlspecialchars($user_name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="subscribe_email">Your Email *</label>
                            <input type="email" id="subscribe_email" name="subscribe_email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                        </div>
                        
                        <button type="submit" name="submit_subscribe" class="submit-btn">
                            <i class="fas fa-envelope"></i> Subscribe Now
                        </button>
                    </form>
                    
                    <div class="subscribe-benefits">
                        <h3>Benefits of Subscribing</h3>
                        <ul class="benefits-list">
                            <li>Get exclusive deals and promotions</li>
                            <li>Be the first to know about new product launches</li>
                            <li>Receive special discount codes for subscribers only</li>
                            <li>Get helpful tips and guides for your devices</li>
                            <li>Stay updated with the latest mobile technology news</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
    function switchTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(function(content) {
            content.classList.remove('active');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.tab').forEach(function(tab) {
            tab.classList.remove('active');
        });
        
        // Show the selected tab content
        document.getElementById(tabId + '-tab').classList.add('active');
        
        // Add active class to the clicked tab
        document.querySelector('.tab:nth-child(' + (tabId === 'feedback' ? '1' : '2') + ')').classList.add('active');
        
        // Update URL parameter
        history.replaceState(null, null, 'feedback.php?tab=' + tabId);
    }
    
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