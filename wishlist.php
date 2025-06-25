<?php
session_start();
require_once 'config.php';

// Handle AJAX requests
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        http_response_code(401);
        echo "User not logged in";
        exit;
    } else {
        header("Location: login.php?redirect=wishlist.php");
        exit;
    }
}
$user_id = $_SESSION['user_id'];
$message = '';

// Add product to wishlist
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = $_GET['add'];
    
    // Check if product exists
    $check_product = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check_product->bind_param("i", $product_id);
    $check_product->execute();
    $product_result = $check_product->get_result();
    
    if ($product_result->num_rows > 0) {
        // Check if already in wishlist
        $check_wishlist = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_wishlist->bind_param("ii", $user_id, $product_id);
        $check_wishlist->execute();
        $wishlist_result = $check_wishlist->get_result();
        
        if ($wishlist_result->num_rows == 0) {
            // Add to wishlist
            $add_stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $add_stmt->bind_param("ii", $user_id, $product_id);
            
            if ($add_stmt->execute()) {
                $message = "Product added to your wishlist!";
                
                // Return early for AJAX requests
                if ($is_ajax) {
                    echo $message;
                    exit;
                }
            } else {
                $message = "Error adding product to wishlist: " . $conn->error;
                
                // Return error for AJAX requests
                if ($is_ajax) {
                    http_response_code(500);
                    echo $message;
                    exit;
                }
            }
        } else {
            $message = "This product is already in your wishlist.";
        }
    } else {
        $message = "Product not found.";
    }
}

// Remove product from wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $wishlist_id = $_GET['remove'];
    
    // Verify wishlist item belongs to user
    $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $wishlist_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $remove_stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ?");
        $remove_stmt->bind_param("i", $wishlist_id);
        
        if ($remove_stmt->execute()) {
            $message = "Product removed from your wishlist.";
        } else {
            $message = "Error removing product from wishlist: " . $conn->error;
        }
    }
}

// Get wishlist items
$stmt = $conn->prepare("SELECT w.*, p.name, p.price, p.image, p.stock 
                      FROM wishlist w 
                      JOIN products p ON w.product_id = p.id 
                      WHERE w.user_id = ? 
                      ORDER BY w.added_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Mobile Store</title>
s     <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/wishlist.css">
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
                    <li><a href="wishlist.php" class="active"><i class="fas fa-heart"></i> Wishlist</a></li>
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

    <main>
        <section class="wishlist-section">
            <div class="container">
                <h2>My Wishlist</h2>
                
                <?php if($message): ?>
                    <div class="message"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="wishlist-container">
                    <?php if ($wishlist_items && $wishlist_items->num_rows > 0): ?>
                        <?php while($item = $wishlist_items->fetch_assoc()): ?>
                            <div class="wishlist-item">
                                <img src="images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="wishlist-item-image">
                                
                                <div class="wishlist-item-details">
                                    <div class="wishlist-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="wishlist-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="wishlist-item-date">Added on: <?php echo date('F j, Y', strtotime($item['added_at'])); ?></div>
                                    
                                    <div class="wishlist-item-actions">
                                        <?php if($item['stock'] > 0): ?>
                                            <a href="cart.php?add=<?php echo $item['product_id']; ?>" class="btn-sm"><i class="fas fa-shopping-cart"></i> Add to Cart</a>
                                        <?php else: ?>
                                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                                        <?php endif; ?>
                                        <a href="wishlist.php?remove=<?php echo $item['id']; ?>" class="btn-sm"><i class="fas fa-trash-alt"></i> Remove</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-wishlist">
                            <i class="far fa-heart"></i>
                            <p>Your wishlist is empty.</p>
                            <p><a href="products/"><i class="fas fa-shopping-bag"></i> Browse products</a> to add items to your wishlist.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
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
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
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
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Mobile Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>