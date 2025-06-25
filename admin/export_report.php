<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Get report type from query parameter
$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Generate report based on type
switch ($report_type) {
    case 'sales':
        // Sales report (orders with completed status)
        fputcsv($output, ['Order ID', 'Customer', 'Date', 'Total Amount', 'Status']);
        
        $query = "SELECT o.id, u.username, o.created_at, o.total_amount, o.status 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 WHERE o.status = 'completed' 
                 ORDER BY o.created_at DESC";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    $row['username'],
                    date('Y-m-d H:i:s', strtotime($row['created_at'])),
                    $row['total_amount'],
                    $row['status']
                ]);
            }
        }
        break;
        
    case 'products':
        // Products report
        fputcsv($output, ['ID', 'Name', 'Category', 'Price', 'Stock', 'Sales Count']);
        
        $query = "SELECT p.id, p.name, p.category, p.price, p.stock, 
                 COUNT(oi.product_id) as sales_count 
                 FROM products p 
                 LEFT JOIN order_items oi ON p.id = oi.product_id 
                 GROUP BY p.id 
                 ORDER BY sales_count DESC";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    $row['name'],
                    $row['category'],
                    $row['price'],
                    $row['stock'],
                    $row['sales_count']
                ]);
            }
        }
        break;
        
    case 'customers':
        // Customers report
        fputcsv($output, ['ID', 'Username', 'Email', 'Registration Date', 'Orders Count', 'Total Spent']);
        
        $query = "SELECT u.id, u.username, u.email, u.created_at, 
                 COUNT(o.id) as orders_count, 
                 SUM(o.total_amount) as total_spent 
                 FROM users u 
                 LEFT JOIN orders o ON u.id = o.user_id 
                 WHERE u.is_admin = 0 
                 GROUP BY u.id 
                 ORDER BY total_spent DESC";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    $row['username'],
                    $row['email'],
                    date('Y-m-d H:i:s', strtotime($row['created_at'])),
                    $row['orders_count'],
                    $row['total_spent'] ?: '0'
                ]);
            }
        }
        break;
        
    default:
        fputcsv($output, ['Invalid report type']);
        break;
}

// Close the output stream
fclose($output);
exit;
?>