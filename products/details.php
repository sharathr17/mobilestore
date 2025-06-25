<?php
session_start();
require_once '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ./");
    exit;
}

$product_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ./");
    exit;
}

$product = $result->fetch_assoc();

// Get related products (same category)
$related_stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$related_stmt->bind_param("si", $product['category'], $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Mobile Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/product-details.css">
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
                    <li><a href="./"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="../cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="../my_orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="../register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Breadcrumb navigation -->
            <div class="breadcrumb">
                <a href="../index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="./">Products</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
            
            <!-- Product Details Card -->
            <div class="product-details">
                <!-- Product Gallery Section - Simplified for one image -->
                <div class="product-gallery">
                    <div class="main-image">
                        <?php if($product['image']): ?>
                            <img src="../images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="product-main-image">
                            <div class="zoom-icon" onclick="openZoomView()">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                <p>No image available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Product Info Section -->
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Product Badges -->
                    <div class="product-badges">
                        <?php if($product['category']): ?>
                            <span class="badge badge-category"><?php echo ucfirst(htmlspecialchars($product['category'])); ?></span>
                        <?php endif; ?>
                        
                        <?php if(isset($product['featured']) && $product['featured']): ?>
                            <span class="badge badge-featured"><i class="fas fa-star"></i> Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Rating (if available) -->
                    <?php if(isset($product['rating'])): ?>
                    <div class="product-rating">
                        <?php 
                            $rating = $product['rating'];
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif($i - 0.5 <= $rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            if(isset($product['reviews_count'])) {
                                echo " <span>({$product['reviews_count']} reviews)</span>";
                            }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Product Price -->
                    <div class="product-price-container">
                        <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                        
                        <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                            <span class="original-price">₹<?php echo number_format($product['original_price'], 2); ?></span>
                            <?php 
                                $discount = round(($product['original_price'] - $product['price']) / $product['original_price'] * 100);
                                echo "<span class='discount-badge'>-{$discount}%</span>";
                            ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Description -->
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <!-- Product Meta Information -->
                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value"><?php echo ucfirst($product['category']); ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Availability:</span>
                            <?php if($product['stock'] > 10): ?>
                                <span class="meta-value in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock']; ?> available)</span>
                            <?php elseif($product['stock'] > 0): ?>
                                <span class="meta-value low-stock"><i class="fas fa-exclamation-circle"></i> Low Stock (Only <?php echo $product['stock']; ?> left)</span>
                            <?php else: ?>
                                <span class="meta-value out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Product Variants section removed -->
                    
                    <!-- Add to Cart Form -->
                    <?php if($product['stock'] > 0): ?>
                        <form action="../cart.php" method="get" class="cart-form">
                            <input type="hidden" name="add" value="<?php echo $product['id']; ?>">
                            
                            <div class="quantity-control">
                                <label for="quantity">Quantity:</label>
                                <div class="quantity-input">
                                    <button type="button" class="quantity-btn minus" onclick="decrementQuantity()"><i class="fas fa-minus"></i></button>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                    <button type="button" class="quantity-btn plus" onclick="incrementQuantity(<?php echo $product['stock']; ?>)"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            
                            <div class="product-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <button type="button" class="btn-wishlist" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="out-of-stock-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>This product is currently out of stock. Please check back later or browse similar products.</p>
                        </div>
                        <button class="btn-notify" onclick="notifyWhenAvailable(<?php echo $product['id']; ?>)">
                            <i class="fas fa-bell"></i> Notify Me When Available
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Tabs Section -->
            <div class="product-tabs">
                <div class="tabs-header">
                    <button class="tab-btn active" data-tab="description">Description</button>
                    <button class="tab-btn" data-tab="specifications">Specifications</button>
                    <button class="tab-btn" data-tab="reviews">Reviews</button>
                </div>
                
                <div class="tabs-content">
                    <div class="tab-panel active" id="description">
                        <h3>Product Description</h3>
                        <div class="tab-content">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>
                    
                    <div class="tab-panel" id="specifications">
                        <h3>Technical Specifications</h3>
                        <div class="tab-content">
                            <table class="specs-table">
                                <tbody>
                                    <tr>
                                        <td>Brand</td>
                                        <td><?php echo isset($product['brand']) ? $product['brand'] : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Model</td>
                                        <td><?php echo isset($product['model']) ? $product['model'] : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Category</td>
                                        <td><?php echo isset($product['category']) ? ucfirst($product['category']) : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Stock</td>
                                        <td><?php echo isset($product['stock']) ? $product['stock'] : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Rating</td>
                                        <td>
                                            <?php if(isset($product['rating'])): ?>
                                                <div class="spec-rating">
                                                    <?php 
                                                        $rating = $product['rating'];
                                                        for($i = 1; $i <= 5; $i++) {
                                                            if($i <= $rating) {
                                                                echo '<i class="fas fa-star"></i>';
                                                            } elseif($i - 0.5 <= $rating) {
                                                                echo '<i class="fas fa-star-half-alt"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star"></i>';
                                                            }
                                                        }
                                                        echo " <span>({$rating}/5)</span>";
                                                    ?>
                                                </div>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Added On</td>
                                        <td><?php echo isset($product['created_at']) ? date('F j, Y', strtotime($product['created_at'])) : 'N/A'; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-panel" id="reviews">
                        <h3>Customer Reviews</h3>
                        <div class="tab-content">
                            <?php
                            // Get reviews for this product
                            $reviews_stmt = $conn->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
                            $reviews_stmt->bind_param("i", $product_id);
                            $reviews_stmt->execute();
                            $reviews = $reviews_stmt->get_result();
                            
                            if ($reviews && $reviews->num_rows > 0): 
                            ?>
                                <div class="reviews-container">
                                    <table class="reviews-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Rating</th>
                                                <th>Comment</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($review = $reviews->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($review['username']); ?></td>
                                                    <td>
                                                        <div class="review-rating">
                                                            <?php 
                                                                for($i = 1; $i <= 5; $i++) {
                                                                    if($i <= $review['rating']) {
                                                                        echo '<i class="fas fa-star"></i>';
                                                                    } else {
                                                                        echo '<i class="far fa-star"></i>';
                                                                    }
                                                                }
                                                            ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo nl2br(htmlspecialchars($review['comment'])); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                                    <td class="review-actions">
                                                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                                                            <button class="btn-edit-review" data-review-id="<?php echo $review['id']; ?>" 
                                                                    data-rating="<?php echo $review['rating']; ?>" 
                                                                    data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn-delete-review" data-review-id="<?php echo $review['id']; ?>" 
                                                                    data-product-id="<?php echo $product_id; ?>">
                                                                <i class="fas fa-trash-alt"></i> Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-reviews-message">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No reviews yet for this product.</p>
                                    <p class="no-reviews-subtext">Be the first to share your experience with this product!</p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <div class="write-review-container">
                                    <h4>Write a Review</h4>
                                    <form action="../submit_review.php" method="post" class="review-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        
                                        <div class="form-group">
                                            <label>Rating:</label>
                                            <div class="rating-select">
                                                <?php for($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="review-comment">Your Review:</label>
                                            <textarea id="review-comment" name="comment" rows="5" required></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit Review
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <p><a href="../login.php">Log in</a> to write a review.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Products Section -->
            <div class="related-products">
                <h2>You May Also Like</h2>
                <div class="products-grid">
                    <?php if ($related_products && $related_products->num_rows > 0): ?>
                        <?php while($related = $related_products->fetch_assoc()): ?>
                            <div class="product-card">
                                <div class="product-image-wrapper">
                                    <?php if($related['image']): ?>
                                        <img class="product-image" src="../images/<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                            <p>No image available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-content">
                                    <h3 class="product-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                                    <div class="product-price">₹<?php echo number_format($related['price'], 2); ?></div>
                                    <a href="details.php?id=<?php echo $related['id']; ?>" class="btn-details">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-related">No related products found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="navigation-buttons">
                <a href="./" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
            <!-- Floating Action Buttons -->
            <div class="floating-actions">
                <a href="../wishlist.php" class="fab fab-wishlist">
                    <span class="fab-count wishlist-count">0</span>
                    <i class="fas fa-heart"></i>
                </a>
                <a href="../cart.php" class="fab fab-cart">
                    <span class="fab-count cart-count">0</span>
                    <i class="fas fa-shopping-cart"></i>
                </a>
                <button class="fab fab-top" id="backToTop">
                    <i class="fas fa-arrow-up"></i>
                </button>
            </div>
        </div>
        
        <!-- Image Zoom View -->
        <div class="zoom-container">
            <div class="zoom-close"><i class="fas fa-times"></i></div>
            <img src="" alt="Zoomed Product Image" class="zoom-image">
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
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="./">Products</a></li>
                        <li><a href="../cart.php">Cart</a></li>
                        <li><a href="../login.php">Account</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Main Street, City, Country</p>
                    <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                    <p><i class="fas fa-envelope"></i> info@mobilestore.com</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Mobile Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../js/main.js"></script>
    <script src="../js/product-details.js"></script>
</body>
</html>

<!-- Edit Review Modal -->
<div id="editReviewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Edit Your Review</h3>
        <form id="editReviewForm" action="../update_review.php" method="post" class="review-form">
            <input type="hidden" name="review_id" id="edit-review-id">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <div class="form-group">
                <label>Rating:</label>
                <div class="rating-select" id="edit-rating-select">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="edit-star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                        <label for="edit-star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit-review-comment">Your Review:</label>
                <textarea id="edit-review-comment" name="comment" rows="5" required></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Update Review
                </button>
                <button type="button" class="btn-secondary" id="cancelEditReview">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Review Confirmation Modal -->
<div id="deleteReviewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Delete Review</h3>
        <p>Are you sure you want to delete your review? This action cannot be undone.</p>
        <form id="deleteReviewForm" action="../delete_review.php" method="post">
            <input type="hidden" name="review_id" id="delete-review-id">
            <input type="hidden" name="product_id" id="delete-product-id">
            
            <div class="form-actions">
                <button type="submit" class="btn-danger">
                    <i class="fas fa-trash-alt"></i> Delete Review
                </button>
                <button type="button" class="btn-secondary" id="cancelDeleteReview">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>