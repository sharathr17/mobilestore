<?php
session_start();
require_once 'config.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to add/update cart item in database
function updateCartInDatabase($user_id, $product_id, $quantity) {
    global $conn;
    
    // Check if item already exists in cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing cart item
        $stmt = $conn->prepare("UPDATE cart SET quantity = ?, added_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
    } else {
        // Insert new cart item
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
    }
    
    return $stmt->execute();
}

// Function to remove item from cart database
function removeFromCartDatabase($user_id, $product_id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    
    return $stmt->execute();
}

// Function to load cart from database
function loadCartFromDatabase($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image 
                           FROM cart c 
                           JOIN products p ON c.product_id = p.id 
                           WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[$row['product_id']] = [
            'id' => $row['product_id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'image' => $row['image'],
            'quantity' => $row['quantity']
        ];
    }
    
    return $cart;
}

// Load cart from database if user is logged in
if (isset($_SESSION['user_id'])) {
    $_SESSION['cart'] = loadCartFromDatabase($_SESSION['user_id']);
}

// Add product to cart
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = $_GET['add'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND stock > 0");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check if product already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        
        // Update cart in database if user is logged in
        if (isset($_SESSION['user_id'])) {
            updateCartInDatabase(
                $_SESSION['user_id'], 
                $product_id, 
                $_SESSION['cart'][$product_id]['quantity']
            );
        }
        
        // Redirect back to products or wherever they came from
        header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
        exit;
    }
}

// Remove from cart
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    $product_id = $_GET['remove'];
    
    // Remove from database if user is logged in
    if (isset($_SESSION['user_id'])) {
        removeFromCartDatabase($_SESSION['user_id'], $product_id);
    }
    
    // Remove from session
    unset($_SESSION['cart'][$product_id]);
    
    header("Location: cart.php");
    exit;
}

// Update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if (isset($_SESSION['cart'][$product_id])) {
            $quantity = max(1, (int)$quantity);
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            
            // Update in database if user is logged in
            if (isset($_SESSION['user_id'])) {
                updateCartInDatabase($_SESSION['user_id'], $product_id, $quantity);
            }
        }
    }
    header("Location: cart.php");
    exit;
}

// Process checkout
if (isset($_POST['checkout'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page with cart as redirect parameter
        header("Location: login.php?redirect=cart.php");
        exit;
    }
    
    if (!empty($_SESSION['cart'])) {
        $user_id = $_SESSION['user_id'];
        $total_amount = 0;
        
        // Calculate total
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("id", $user_id, $total_amount);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Add order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            // Update product stock - Fix SQL injection
            foreach ($_SESSION['cart'] as $item) {
                $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update product stock with prepared statement
                $update_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stmt->bind_param("ii", $item['quantity'], $item['id']);
                $update_stmt->execute();
            }
            
            // Clear cart in database
            $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            
            // Clear cart in session
            $_SESSION['cart'] = [];
            
            // Redirect to checkout page instead of thank_you.php
            header("Location: checkout.php?order_id=$order_id");
            exit;
        }
    }
}

// Calculate cart total
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

// Count items in cart
$cart_count = count($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Mobile Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css"> 
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
                    <li><a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> Cart <?php if($cart_count > 0): ?><span class="cart-count"><?php echo $cart_count; ?></span><?php endif; ?></a></li>
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

    <section class="cart-section">
        <div class="container">
            <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
            <p class="subtitle">Review your items and proceed to checkout</p>
            
            <?php if(empty($_SESSION['cart'])): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart fa-4x"></i>
                    <p>Your cart is empty.</p>
                    <a href="products/" class="btn"><i class="fas fa-shopping-bag"></i> Browse Products</a>
                </div>
            <?php else: ?>
                <form action="cart.php" method="post">
                    <div class="cart-container">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($_SESSION['cart'] as $product_id => $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <?php if($item['image']): ?>
                                                    <img src="images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                                <?php else: ?>
                                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                                <?php endif; ?>
                                                <div>
                                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                    <a href="products/details.php?id=<?php echo $product_id; ?>" class="view-details">View details</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="price">₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <div class="quantity-control">
                                                <button type="button" class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                                                <input type="number" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                                                <button type="button" class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </td>
                                        <td class="subtotal">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td>
                                            <a href="cart.php?remove=<?php echo $product_id; ?>" class="btn-remove" title="Remove item"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="cart-summary">
                            <h3>Order Summary</h3>
                            <div class="summary-row">
                                <span>Items (<?php echo $cart_count; ?>):</span>
                                <span>₹<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping:</span>
                                <span>Free</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span>₹<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button type="submit" name="checkout" class="btn btn-checkout"><i class="fas fa-credit-card"></i> Proceed to Checkout</button>
                            <?php else: ?>
                                <a href="login.php?redirect=cart.php" class="btn btn-checkout"><i class="fas fa-sign-in-alt"></i> Login to Checkout</a>
                            <?php endif; ?>
                            
                            <div class="cart-actions">
                                <button type="submit" name="update_cart" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Update Cart</button>
                                <a href="products/" class="btn btn-secondary"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
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

    <!-- JavaScript for quantity controls -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity controls
        const minusButtons = document.querySelectorAll('.quantity-btn.minus');
        const plusButtons = document.querySelectorAll('.quantity-btn.plus');
        
        minusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        plusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                input.value = value + 1;
            });
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