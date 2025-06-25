<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (!isset($_POST['review_id']) || !isset($_POST['rating']) || !isset($_POST['comment']) || !isset($_POST['product_id'])) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: products/details.php?id=" . $_POST['product_id']);
        exit;
    }
    
    $review_id = $_POST['review_id'];
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Invalid rating.";
        header("Location: products/details.php?id=$product_id");
        exit;
    }
    
    // Verify the review belongs to the user
    $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $review_id, $user_id);
    $check_stmt->execute();
    $existing_review = $check_stmt->get_result();
    
    if ($existing_review->num_rows === 0) {
        $_SESSION['error'] = "You can only edit your own reviews.";
        header("Location: products/details.php?id=$product_id");
        exit;
    }
    
    // Update the review
    $update_stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("isii", $rating, $comment, $review_id, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Your review has been updated.";
        
        // Update product rating
        updateProductRating($conn, $product_id);
    } else {
        $_SESSION['error'] = "Failed to update your review. Please try again.";
    }
    
    // Redirect back to product details page
    header("Location: products/details.php?id=$product_id");
    exit;
}

/**
 * Update product rating based on reviews
 * @param mysqli $conn Database connection
 * @param int $product_id Product ID
 */
function updateProductRating($conn, $product_id) {
    // Calculate average rating
    $rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ?");
    $rating_stmt->bind_param("i", $product_id);
    $rating_stmt->execute();
    $rating_result = $rating_stmt->get_result()->fetch_assoc();
    
    $avg_rating = round($rating_result['avg_rating'], 1);
    $reviews_count = $rating_result['count'];
    
    // Check if rating and reviews_count columns exist in products table
    $check_columns = $conn->query("SHOW COLUMNS FROM products LIKE 'rating'");
    if ($check_columns->num_rows > 0) {
        // Update product rating
        $update_stmt = $conn->prepare("UPDATE products SET rating = ?, reviews_count = ? WHERE id = ?");
        $update_stmt->bind_param("dii", $avg_rating, $reviews_count, $product_id);
        $update_stmt->execute();
    }
}