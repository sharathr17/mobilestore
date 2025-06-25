<?php
session_start();
require_once '../config.php';

// Handle category filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query based on filter
$query = "SELECT * FROM products";
if ($category_filter) {
    // Fix SQL injection vulnerability by using prepared statement
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY name ASC");
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $query .= " ORDER BY name ASC";
    $products = $conn->query($query);
}

// Get all categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Mobile Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-mobile-alt"></i> Mobile Store</h1>
            <nav>
                <button class="mobile-menu-toggle"><i class="fas fa-bars"></i></button>
                <ul>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="./" class="active"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="../cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="../my_orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="../register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                    <li><a href="../feedback.php"><i class="fas fa-comment-alt"></i> Feedback</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="products-header">
        <div class="container">
            <h2>Explore Our Products</h2>
            <p>Discover the latest smartphones and accessories with exclusive deals</p>
        </div>
    </div>

    <main>
        <div class="container">
            <div class="filter-container">
                <form action="" method="get" class="filter-form">
                    <div>
                        <label for="category"><i class="fas fa-filter"></i> Filter by Category:</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php while($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['category']; ?>" <?php echo ($category_filter == $category['category']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($category['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
                
                <?php if($category_filter): ?>
                    <a href="./" class="clear-filter-btn"><i class="fas fa-times"></i> Clear Filter</a>
                <?php endif; ?>
            </div>
            
            <!-- Replace the existing product card section with this updated version -->
            <div class="products-grid">
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while($product = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <!-- Product Image Section - Keep as is -->
                            <div class="product-image-wrapper">
                                <?php if($product['image']): ?>
                                    <img class="product-image" src="../images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                        <p>No image available</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="image-overlay"></div>
                                
                                <!-- Quick Actions - Keep as is -->
                                <div class="quick-actions">
                                    <button class="action-btn" onclick="addToWishlist(<?php echo $product['id']; ?>)" title="Add to Wishlist">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                                
                                <!-- Product Badges - Keep as is -->
                                <div class="product-badges">
                                    <?php if($product['category']): ?>
                                        <span class="badge badge-category"><?php echo ucfirst(htmlspecialchars($product['category'])); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($product['featured']) && $product['featured']): ?>
                                        <span class="badge badge-featured"><i class="fas fa-star"></i> Featured</span>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($product['is_new']) && $product['is_new']): ?>
                                        <span class="badge badge-new">New</span>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($product['discount']) && $product['discount'] > 0): ?>
                                        <span class="badge badge-sale">Sale</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Product Content - Keep as is but add description if available -->
                            <div class="product-content">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <div class="product-price">
                                    <span>₹<?php echo number_format($product['price'], 2); ?></span>
                                    
                                    <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                        <span class="original-price">₹<?php echo number_format($product['original_price'], 2); ?></span>
                                        <?php 
                                            $discount = round(($product['original_price'] - $product['price']) / $product['original_price'] * 100);
                                            echo "<span class='discount-badge'>-{$discount}%</span>";
                                        ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Meta - Keep as is -->
                                <div class="product-meta">
                                    <!-- Rating and stock info - Keep as is -->
                                </div>
                                
                                <!-- Product Actions - Keep as is -->
                                <div class="product-actions">
                                    <a href="details.php?id=<?php echo $product['id']; ?>" class="btn-details">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>, event)" title="Add to Cart">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-search"></i>
                        <p>No products found in this category.</p>
                        <?php if($category_filter): ?>
                            <a href="./" class="btn">View All Products</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
                        <li><a href="../index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="./"><i class="fas fa-chevron-right"></i> Products</a></li>
                        <li><a href="../cart.php"><i class="fas fa-chevron-right"></i> Cart</a></li>
                        <li><a href="../login.php"><i class="fas fa-chevron-right"></i> Account</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="../feedback.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
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
                        <li><a href="../feedback.php"><i class="fas fa-comment-alt"></i> Send Feedback</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Mobile Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../js/main.js"></script>
    <script src="../js/products.js"></script>
</body>
</html>