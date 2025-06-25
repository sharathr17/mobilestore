<?php
require_once 'config.php';

// Clear existing data
$conn->query("TRUNCATE TABLE order_items");
$conn->query("TRUNCATE TABLE orders");
$conn->query("DELETE FROM products");
$conn->query("DELETE FROM users WHERE id != 1"); // Keep the admin user

// Sample products
$products = [
    [
        'name' => 'iPhone 13 Pro',
        'description' => 'Apple iPhone 13 Pro with A15 Bionic chip, Pro camera system, and Super Retina XDR display with ProMotion.',
        'price' => 999.99,
        'image' => 'iphone13pro.jpg',
        'category' => 'smartphone',
        'stock' => 50
    ],
    [
        'name' => 'Samsung Galaxy S21',
        'description' => 'Samsung Galaxy S21 with Exynos 2100 processor, 8GB RAM, and 128GB storage.',
        'price' => 799.99,
        'image' => 'galaxys21.jpg',
        'category' => 'smartphone',
        'stock' => 45
    ],
    [
        'name' => 'Google Pixel 6',
        'description' => 'Google Pixel 6 with Google Tensor chip, 8GB RAM, and advanced camera system.',
        'price' => 699.99,
        'image' => 'pixel6.jpg',
        'category' => 'smartphone',
        'stock' => 30
    ],
    [
        'name' => 'iPad Pro 12.9"',
        'description' => 'Apple iPad Pro with M1 chip, Liquid Retina XDR display, and Thunderbolt support.',
        'price' => 1099.99,
        'image' => 'ipadpro.jpg',
        'category' => 'tablet',
        'stock' => 25
    ],
    [
        'name' => 'Samsung Galaxy Tab S7',
        'description' => 'Samsung Galaxy Tab S7 with 11-inch display, S Pen included, and Snapdragon 865+ processor.',
        'price' => 649.99,
        'image' => 'tabs7.jpg',
        'category' => 'tablet',
        'stock' => 20
    ],
    [
        'name' => 'AirPods Pro',
        'description' => 'Apple AirPods Pro with Active Noise Cancellation, Transparency mode, and Adaptive EQ.',
        'price' => 249.99,
        'image' => 'airpodspro.jpg',
        'category' => 'accessory',
        'stock' => 100
    ],
    [
        'name' => 'Samsung Galaxy Watch 4',
        'description' => 'Samsung Galaxy Watch 4 with body composition analysis, advanced sleep tracking, and fitness tracking.',
        'price' => 249.99,
        'image' => 'galaxywatch4.jpg',
        'category' => 'accessory',
        'stock' => 40
    ],
    [
        'name' => 'Wireless Charger',
        'description' => '15W Fast Wireless Charger compatible with iPhone and Android devices.',
        'price' => 29.99,
        'image' => 'wirelesscharger.jpg',
        'category' => 'accessory',
        'stock' => 200
    ]
];

// Insert products
$stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category, stock) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($products as $product) {
    $stmt->bind_param("ssdssi", 
        $product['name'], 
        $product['description'], 
        $product['price'], 
        $product['image'], 
        $product['category'], 
        $product['stock']
    );
    $stmt->execute();
    echo "Added product: {$product['name']}\n";
}

// Sample users
$users = [
    [
        'username' => 'customer1',
        'email' => 'customer1@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'is_admin' => 0
    ],
    [
        'username' => 'customer2',
        'email' => 'customer2@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'is_admin' => 0
    ]
];

// Insert users
$stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");

foreach ($users as $user) {
    $stmt->bind_param("sssi", 
        $user['username'], 
        $user['email'], 
        $user['password'], 
        $user['is_admin']
    );
    $stmt->execute();
    echo "Added user: {$user['username']}\n";
}

echo "\nDatabase seeded successfully!";
?>