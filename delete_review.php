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
    if (!isset($_POST['review_id']) || !isset($_POST['product_id'])) {
        $_SESSION['error'] = "Missing required information.";
        header("Location: products/details.php?id=" . $_POST['product_id']);
        exit;
    }
    
    $review_id = $_POST['review_id'];
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify the review belongs to the user
    $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $review_id, $user_id);
    $check_stmt->execute();
    $existing_review = $check_stmt->get_result();
    
    if ($existing_review->num_rows === 0) {
        $_SESSION['error'] = "You can only delete your own reviews.";
        header("Location: products/details.php?id=$product_id");
        exit;
    }
    
    // Delete the review
    $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $review_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Your review has been deleted.";
        
        // Update product rating
        updateProductRating($conn, $product_id);
    } else {
        $_SESSION['error'] = "Failed to delete your review. Please try again.";
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
    
    $avg_rating = $rating_result['count'] > 0 ? round($rating_result['avg_rating'], 1) : NULL;
    $reviews_count = $rating_result['count'];
    
    // Update product rating
    $update_stmt = $conn->prepare("UPDATE products SET rating = ?, reviews_count = ? WHERE id = ?");
    $update_stmt->bind_param("dii", $avg_rating, $reviews_count, $product_id);
    $update_stmt->execute();
}