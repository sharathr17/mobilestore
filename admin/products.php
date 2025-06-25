<?php
    session_start();
    require_once '../config.php';

    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        header("Location: ../login.php");
        exit;
    }

    // Handle product actions (add, edit, delete)
    $message = '';
    $message_type = '';

    // Delete product
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];

        
        // Step 1: Delete related rows in order_items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            $message = "Error deleting related order items: " . $conn->error;
            $message_type = "error";
            $stmt->close();
            return;
        }
        $stmt->close();

        // Step 2: Delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Product deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting product: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }

    // Check if is_new column exists, if not add it
    $check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'is_new'");
    if ($check_column->num_rows == 0) {
        // Column doesn't exist, add it
        $conn->query("ALTER TABLE products ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Check if featured column exists, if not add it
    $check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'featured'");
    if ($check_column->num_rows == 0) {
        // Column doesn't exist, add it
        $conn->query("ALTER TABLE products ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Add or update product
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];   
        $category = $_POST['category'];
        $stock = $_POST['stock'];
        //$image=$_POST['image'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        $is_new = isset($_POST['is_new']) ? 1 : 0;

       
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    // An image file has been uploaded
    $image = basename($_FILES["image"]["name"]);
    echo "Image uploaded: " . $image;
} else {
    // No image uploaded or an error occurred
    echo "No image uploaded or an error occurred.";
}

        // Handle file upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../images/";
            $image = basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image;

            // Check if image file is an actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                // Move uploaded file to the target directory
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                   // $image=$_POST['image'];
                    // File uploaded successfully
                } else {
                    $message = "Error uploading file.";
                    $message_type = "error";
                }
            } else {
                $message = "File is not an image.";
                $message_type = "error";
            }
        }

        // Add or update product in database
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            // Update existing product
            $id = $_POST['id'];

            if ($image) {
                // Update with new image
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=?, image=?, featured=?, is_new=? WHERE id=?");
                $stmt->bind_param("ssdsisiis", $name, $description, $price, $category, $stock, $image, $featured, $is_new, $id);
            } else {
                // Update without changing the image
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=?, featured=?, is_new=? WHERE id=?");
                $stmt->bind_param("ssdsiiis", $name, $description, $price, $category, $stock, $featured, $is_new, $id);
            }

            if ($stmt->execute()) {
                $message = "Product updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating product: " . $conn->error;
                $message_type = "error";
            }
        } else {
            // Add new product
           // echo $image;
           echo"yessss";
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image, featured, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsisii", $name, $description, $price, $category, $stock, $image, $featured, $is_new);

            if ($stmt->execute()) {
                $message = "Product added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding product: " . $conn->error;
                $message_type = "error";
            }
        }
        $stmt->close();
    }

    // Get product for editing if ID is provided
    $edit_product = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $id = $_GET['edit'];
        // Fix SQL injection with prepared statement
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $edit_product = $result->fetch_assoc();
        }
    }

    // Get all products with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $total_pages = ceil($total_products / $limit);

    // Get products with pagination
    $products = $conn->query("SELECT * FROM products");

    // Get distinct categories for filter
    $categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
    $category_filter = isset($_GET['category_filter']) ? $_GET['category_filter'] : '';

    // Apply category filter if set
    if ($category_filter) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC LIMIT ?, ?");
        $stmt->bind_param("sii", $category_filter, $offset, $limit);
        $stmt->execute();
        $products = $stmt->get_result();
        
        // Update total count for pagination
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category = ?");
        $stmt->bind_param("s", $category_filter);
        $stmt->execute();
        $total_products = $stmt->get_result()->fetch_assoc()['count'];
        $total_pages = ceil($total_products / $limit);
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Products - Mobile Store</title>
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="../css/admin.css">
        <link rel="stylesheet" href="../css/adminproducts.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <header>
            <div class="container">
                <h1><i class="fas fa-mobile-alt"></i> Mobile Store Admin</h1>
                <div>
                    <a href="../index.php"><i class="fas fa-home"></i> View Site</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="admin-container">
        <div class="admin-container">
            <div class="sidebar">
                <h3>Admin Menu</h3>
                <ul>
                    <li><a href="index.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-mobile-alt"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="refunds.php"><i class="fas fa-undo"></i> Refunds</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                    <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
                </ul>
            </div>
            <div class="main-content">
                <div class="page-title">
                    <h2><i class="fas fa-box"></i> Manage Products</h2>
                    <?php if (!$edit_product): ?>
                    <a href="#product-form" class="btn"><i class="fas fa-plus"></i> Add New Product</a>
                    <?php endif; ?>
                </div>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$edit_product): ?>
                <div class="filter-bar">
                    <form action="" method="get" class="filter-form">
                        <select name="category_filter" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php while($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['category']; ?>" <?php echo ($category_filter == $category['category']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($category['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <?php if($category_filter): ?>
                            <a href="products.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear Filter</a>
                        <?php endif; ?>
                    </form>
                    
                    <form action="" method="get" class="search-form">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </form>
                </div>
                
                <div class="product-table-container">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products && $products->num_rows > 0): ?>
                                <?php while($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <?php if ($product['image']): ?>
                                                <img src="../images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                            <?php else: ?>
                                                <div class="no-image"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-name"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="product-price">₹<?php echo number_format($product['price'], 2); ?></td>
                                        <td><span class="product-category"><?php echo ucfirst($product['category']); ?></span></td>
                                        <td>
                                            <span class="product-stock <?php 
                                                if ($product['stock'] > 20) echo 'stock-high';
                                                else if ($product['stock'] > 5) echo 'stock-medium';
                                                else echo 'stock-low';
                                            ?>">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="products.php?edit=<?php echo $product['id']; ?>" class="action-btn edit-btn" title="Edit Product">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="products.php?delete=<?php echo $product['id']; ?>" class="action-btn delete-btn" title="Delete Product" onclick="return confirm('Are you sure you want to delete this product?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--border-color); margin-bottom: 15px; display: block;"></i>
                                        <p>No products found. Add your first product!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="products.php?page=<?php echo ($page - 1); ?><?php echo $category_filter ? '&category_filter=' . urlencode($category_filter) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span style="background-color: var(--primary); color: white;"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="products.php?page=<?php echo $i; ?><?php echo $category_filter ? '&category_filter=' . urlencode($category_filter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="products.php?page=<?php echo ($page + 1); ?><?php echo $category_filter ? '&category_filter=' . urlencode($category_filter) : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="product-form" id="product-form">
                    <h3><i class="fas fa-<?php echo $edit_product ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
                    <form action="products.php" method="post" enctype="multipart/form-data">
                        <?php if ($edit_product): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Product Name</label>
                                <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="smartphone" <?php echo ($edit_product && $edit_product['category'] == 'smartphone') ? 'selected' : ''; ?>>Smartphone</option>
                                    <option value="tablet" <?php echo ($edit_product && $edit_product['category'] == 'tablet') ? 'selected' : ''; ?>>Tablet</option>
                                    <option value="accessory" <?php echo ($edit_product && $edit_product['category'] == 'accessory') ? 'selected' : ''; ?>>Accessory</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (₹)</label>
                                <input type="number" id="price" name="price" step="0.01" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" id="stock" name="stock" value="<?php echo $edit_product ? $edit_product['stock'] : '0'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Options</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="featured" name="featured" <?php echo ($edit_product && $edit_product['featured']) ? 'checked' : ''; ?>>
                                <label for="featured">Featured Product</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_new" name="is_new" <?php echo ($edit_product && $edit_product['is_new']) ? 'checked' : ''; ?>>
                                <label for="is_new">Mark as New</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <?php if ($edit_product && $edit_product['image']): ?>
                                <div class="image-preview">
                                    <img src="../images/<?php echo $edit_product['image']; ?>" alt="Current Image">
                                    <p>Current: <?php echo $edit_product['image']; ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="file-input-wrapper">
                                <div class="file-input-button">
                                    <i class="fas fa-upload"></i> Choose Image
                                </div>
                                <input type="file" id="image" name="image" <?php echo $edit_product ? '' : 'required'; ?>>
                            </div>
                            <p style="margin-top: 8px; font-size: 0.9rem; color: var(--text-muted);">Recommended size: 800x800px, Max size: 2MB</p>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_product): ?>
                                <a href="products.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn">
                                <i class="fas fa-<?php echo $edit_product ? 'save' : 'plus-circle'; ?>"></i>
                                <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            // Show filename when file is selected
            document.getElementById('image').addEventListener('change', function() {
                const fileName = this.files[0]?.name;
                if (fileName) {
                    this.previousElementSibling.innerHTML = `<i class="fas fa-file-image"></i> ${fileName}`;
                }
            });
            
            // Animate message
            document.addEventListener('DOMContentLoaded', function() {
                const message = document.querySelector('.message');
                if (message) {
                    setTimeout(() => {
                        message.style.opacity = '0';
                        message.style.transform = 'translateY(-20px)';
                        setTimeout(() => {
                            message.style.display = 'none';
                        }, 300);
                    }, 5000);
                }
            });
        </script>
    </body>
    </html>