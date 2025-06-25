<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle user actions
$message = '';

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Don't allow deleting yourself
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "User deleted successfully!";
        } else {
            $message = "Error deleting user: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "You cannot delete your own account!";
    }
}

// Toggle admin status
if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $id = $_GET['toggle_admin'];
    
    // Don't allow changing your own admin status
    if ($id != $_SESSION['user_id']) {
        // Get current status
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Toggle status
        $new_status = $user['is_admin'] ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        
        if ($stmt->execute()) {
            $message = "User admin status updated successfully!";
        } else {
            $message = "Error updating user: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "You cannot change your own admin status!";
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_pages = ceil($total_users / $limit);

// Fix SQL injection by using prepared statement
$stmt = $conn->prepare("SELECT * FROM users ORDER BY id ASC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Mobile Store</title>
    <link rel="stylesheet" href="../css/users.css">
    <link rel="stylesheet" href="../css/orders.css">
    <link rel="stylesheet" href="../css/admin.css"> 
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">    
    <style>
        .admin-container {
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: #fff;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }
        .sidebar h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
        }
        .sidebar ul {
            list-style: none;
        }
        .sidebar ul li {
            margin-bottom: 10px;
        }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 8px 10px;
            border-radius: 4px;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: #444;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .user-table th {
            background-color: #f4f4f4;
        }
        .pagination {
            display: flex;
            list-style: none;
            margin-top: 20px;
            justify-content: center;
        }
        .pagination li {
            margin: 0 5px;
        }
        .pagination li a {
            display: block;
            padding: 8px 12px;
            background-color: #fff;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination li.active a {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard</h1>
            <div>
                <a href="../index.php">View Site</a>
                <a href="../logout.php">Logout</a>
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
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a></li>
                <li><a href="subscribed.php"><i class="fa-solid fa-envelope-open-text"></i>Subscribed</a></li>
            </ul>
        </div> 
        <div class="main-content">
            <h2>Manage Users</h2>
            
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>           
            <div class="filter-bar">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="user-table-container">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users && $users->num_rows > 0): ?>
                            <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['is_admin'] ? 'status-approved' : 'status-pending'; ?>">
                                            <?php echo $user['is_admin'] ? 'Admin' : 'Customer'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?toggle_admin=<?php echo $user['id']; ?>" class="btn btn-sm">
                                                <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                            </a>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            <span>Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a href="users.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>