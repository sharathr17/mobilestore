<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle feedback actions (delete, mark as read, etc.)
$message = '';
$message_type = '';

// Delete feedback
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Feedback deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting feedback: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Get feedback details if viewing a specific feedback
$feedback_details = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $feedback_id = $_GET['view'];
    
    // Get feedback details
    $stmt = $conn->prepare("SELECT * FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $feedback_details = $result->fetch_assoc();
    }
}

// Get all feedback with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$total_pages = ceil($total_feedback / $limit);

// Filter by rating if provided
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$rating_condition = '';
$params = [];

if ($rating_filter > 0) {
    $rating_condition = "WHERE rating = ?";
    $params[] = $rating_filter;
}

// Prepare the query with pagination
$query = "SELECT * FROM feedback $rating_condition ORDER BY created_at DESC LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $limit;

// Create the prepared statement
$stmt = $conn->prepare($query);

if ($stmt) {
    // Bind parameters dynamically
    if (!empty($params)) {
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $feedback = $stmt->get_result();
} else {
    // Handle case where feedback table might not exist
    $feedback = false;
    $message = "Feedback table may not exist. Please ensure users have submitted feedback.";
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - Mobile Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/feedback.css">
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
        <div class="sidebar">
            <h3>Admin Menu</h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="refunds.php"><i class="fas fa-undo"></i> Refunds</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php" class="active"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div>

        <div class="main-content">
            <?php if ($feedback_details): ?>
                <div class="page-title">
                    <h2><i class="fas fa-comment-alt"></i> Feedback Details</h2>
                    <a href="feedback.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Feedback</a>
                </div>
                
                <div class="feedback-details-card">
                    <div class="feedback-header">
                        <h3><?php echo htmlspecialchars($feedback_details['subject'] ?: 'No Subject'); ?></h3>
                        <div class="feedback-meta">
                            <span class="feedback-date"><i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($feedback_details['created_at'])); ?></span>
                            <span class="feedback-time"><i class="far fa-clock"></i> <?php echo date('g:i a', strtotime($feedback_details['created_at'])); ?></span>
                            <span class="feedback-rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo ($i <= $feedback_details['rating']) ? 'rated' : ''; ?>"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="feedback-user-info">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($feedback_details['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($feedback_details['email']); ?></p>
                    </div>
                    
                    <div class="feedback-message">
                        <h4>Message:</h4>
                        <p><?php echo nl2br(htmlspecialchars($feedback_details['message'])); ?></p>
                    </div>
                    
                    <div class="feedback-actions">
                        <a href="feedback.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                        <a href="feedback.php?delete=<?php echo $feedback_details['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this feedback?');"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="page-title">
                    <h2><i class="fas fa-comments"></i> Manage Feedback</h2>
                </div>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="filter-bar">
                    <form action="" method="get" class="filter-form">
                        <select name="rating" onchange="this.form.submit()">
                            <option value="0" <?php echo $rating_filter === 0 ? 'selected' : ''; ?>>All Ratings</option>
                            <option value="5" <?php echo $rating_filter === 5 ? 'selected' : ''; ?>>5 Stars</option>
                            <option value="4" <?php echo $rating_filter === 4 ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="3" <?php echo $rating_filter === 3 ? 'selected' : ''; ?>>3 Stars</option>
                            <option value="2" <?php echo $rating_filter === 2 ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="1" <?php echo $rating_filter === 1 ? 'selected' : ''; ?>>1 Star</option>
                        </select>
                        <?php if ($rating_filter > 0): ?>
                            <a href="feedback.php" class="clear-filter-btn"><i class="fas fa-times"></i> Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="feedback-table-container">
                    <table class="feedback-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Rating</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($feedback && $feedback->num_rows > 0): ?>
                                <?php while($item = $feedback->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                                        <td><?php echo htmlspecialchars($item['subject'] ?: 'No Subject'); ?></td>
                                        <td>
                                            <div class="star-rating">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo ($i <= $item['rating']) ? 'rated' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                        <td>
                                            <a href="feedback.php?view=<?php echo $item['id']; ?>" class="btn btn-sm"><i class="fas fa-eye"></i> View</a>
                                            <a href="feedback.php?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this feedback?');"><i class="fas fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No feedback found. Users can submit feedback from the <a href="../feedback.php">feedback page</a>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a href="feedback.php?page=<?php echo $i; ?><?php echo $rating_filter > 0 ? '&rating=' . $rating_filter : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .feedback-details-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--shadow-color);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .feedback-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .feedback-header h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .feedback-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .feedback-rating {
            margin-left: auto;
        }
        
        .feedback-rating .fa-star {
            color: #ddd;
            margin-right: 2px;
        }
        
        .feedback-rating .fa-star.rated {
            color: #f39c12;
        }
        
        .feedback-user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .feedback-user-info p {
            margin: 5px 0;
        }
        
        .feedback-message {
            margin-bottom: 25px;
        }
        
        .feedback-message h4 {
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .feedback-message p {
            line-height: 1.6;
            color: var(--text-color);
        }
        
        .feedback-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .feedback-table-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--shadow-color);
            padding: 25px;
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .feedback-table th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            color: var(--text-color);
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .feedback-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .feedback-table tr:last-child td {
            border-bottom: none;
        }
        
        .feedback-table tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }
        
        .star-rating .fa-star {
            color: #ddd;
            margin-right: 2px;
        }
        
        .star-rating .fa-star.rated {
            color: #f39c12;
        }
        
        .no-data {
            text-align: center;
            padding: 30px 15px;
            color: var(--text-muted);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 992px) {
            .feedback-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .feedback-rating {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .feedback-table-container {
                padding: 15px;
            }
            
            .feedback-table {
                min-width: 800px;
            }
        }
    </style>
</body>
</html>