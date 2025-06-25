<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle subscriber actions (delete, export, etc.)
$message = '';
$message_type = '';

// Delete subscriber
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM subscribers WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Subscriber deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting subscriber: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Export subscribers to CSV
if (isset($_POST['export_csv'])) {
    // Query to get all subscribers
    $result = $conn->query("SELECT * FROM subscribers ORDER BY id DESC");
    
    if ($result && $result->num_rows > 0) {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['ID', 'Name', 'Email', 'Date Subscribed']);
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['email'],
                date('Y-m-d H:i:s', strtotime($row['subscribed_at']))
            ]);
        }
        
        // Close the output stream
        fclose($output);
        exit;
    }
}

// Get all subscribers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_subscribers = $conn->query("SELECT COUNT(*) as count FROM subscribers")->fetch_assoc()['count'];
$total_pages = ceil($total_subscribers / $limit);

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
$params = [];

if ($search) {
    $search_condition = "WHERE name LIKE ? OR email LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Prepare the query with pagination
$query = "SELECT * FROM subscribers $search_condition ORDER BY subscribed_at DESC LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $limit;

// Create the prepared statement
$stmt = $conn->prepare($query);

if ($stmt) {
    // Bind parameters dynamically
    if (!empty($params)) {
        $types = str_repeat('s', count($params) - 2) . 'ii'; // string params + 2 integers for pagination
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $subscribers = $stmt->get_result();
} else {
    // Handle case where subscribers table might not exist
    $subscribers = false;
    $message = "Subscribers table may not exist. Please ensure the newsletter subscription feature is set up.";
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscribers - Mobile Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
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
                <li><a href="index.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="refunds.php"><i class="fas fa-undo"></i> Refunds</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php" class="active"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-title">
                <h2><i class="fas fa-envelope-open-text"></i> Manage Newsletter Subscribers</h2>
                <form method="post" action="">
                    <button type="submit" name="export_csv" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                </form>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="filter-bar">
                <form action="" method="get" class="search-form">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                    <?php if ($search): ?>
                        <a href="subscribed.php" class="clear-search-btn"><i class="fas fa-times"></i> Clear Search</a>
                    <?php endif; ?>
                </form>
                <div class="subscriber-stats">
                    <span><i class="fas fa-users"></i> Total Subscribers: <?php echo $total_subscribers; ?></span>
                </div>
            </div>
            
            <div class="subscriber-table-container">
                <table class="subscriber-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date Subscribed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($subscribers && $subscribers->num_rows > 0): ?>
                            <?php while($subscriber = $subscribers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $subscriber['id']; ?></td>
                                    <td><?php echo htmlspecialchars($subscriber['name'] ?: 'Not Provided'); ?></td>
                                    <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                    <td><?php echo isset($subscriber['subscribed_at']) ? date('M d, Y', strtotime($subscriber['subscribed_at'])) : 'Unknown'; ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($subscriber['email']); ?>" class="btn btn-sm"><i class="fas fa-envelope"></i> Email</a>
                                        <a href="subscribed.php?delete=<?php echo $subscriber['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subscriber?');"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">No subscribers found. Users can subscribe from the <a href="../feedback.php">newsletter subscription page</a>.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a href="subscribed.php?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .subscriber-table-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--shadow-color);
            padding: 25px;
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .subscriber-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .subscriber-table th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            color: var(--text-color);
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .subscriber-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .subscriber-table tr:last-child td {
            border-bottom: none;
        }
        
        .subscriber-table tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
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
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px var(--shadow-color);
        }
        
        .search-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-input {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-input input {
            padding: 10px 15px;
            padding-right: 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            min-width: 250px;
        }
        
        .search-input input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .search-btn {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
        }
        
        .clear-search-btn {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .clear-search-btn:hover {
            color: var(--primary);
        }
        
        .subscriber-stats {
            background-color: rgba(67, 97, 238, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            color: var(--primary);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .subscriber-stats i {
            margin-right: 5px;
        }
        
        @media (max-width: 992px) {
            .filter-bar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-form {
                width: 100%;
            }
            
            .search-input {
                width: 100%;
            }
            
            .search-input input {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .subscriber-table-container {
                padding: 15px;
            }
            
            .subscriber-table {
                min-width: 800px;
            }
        }
    </style>
</body>
</html>