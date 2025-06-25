-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 07:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mobiledb`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(18, 2, 3, 2, '2025-05-05 05:46:12'),
(19, 2, 1, 1, '2025-05-05 05:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `subject`, `message`, `rating`, `created_at`) VALUES
(1, 'sudeep', 's@g.o', 'zfcfdf', 'dggvdfb', 5, '2025-05-04 16:35:51');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipping_address_id` int(11) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`, `shipping_address_id`, `payment_status`) VALUES
(1, 3, 4396.00, 'processing', '2025-05-04 02:41:25', NULL, 'completed'),
(2, 2, 2198.00, 'delivered', '2025-05-04 03:17:59', NULL, 'pending'),
(3, 2, 1099.00, 'processing', '2025-05-04 03:26:12', NULL, 'pending'),
(4, 2, 2198.00, 'pending', '2025-05-04 03:37:02', NULL, 'pending'),
(5, 3, 1099.00, 'pending', '2025-05-04 03:57:23', NULL, 'pending'),
(6, 3, 1099.00, 'pending', '2025-05-04 03:57:49', NULL, 'pending'),
(7, 3, 1099.00, 'completed', '2025-05-04 04:05:14', 2, 'pending'),
(8, 2, 2198.00, 'refunded', '2025-05-04 04:16:18', NULL, 'pending'),
(9, 2, 1099.00, 'completed', '2025-05-04 04:16:48', 3, 'completed'),
(10, 3, 1099.00, 'processing', '2025-05-04 06:26:44', 4, 'completed'),
(11, 3, 2198.00, 'refunded', '2025-05-04 06:33:37', 5, 'completed'),
(12, 3, 1099.00, 'refund_requested', '2025-05-04 06:43:01', 6, 'completed'),
(13, 2, 10898.99, 'refunded', '2025-05-04 07:25:24', 7, 'completed'),
(14, 2, 1899.99, 'cancelled', '2025-05-04 07:29:20', 8, 'completed'),
(15, 4, 4898.98, 'processing', '2025-05-04 12:55:26', 9, 'completed'),
(16, 3, 8999.00, 'processing', '2025-05-04 13:48:44', 10, 'completed'),
(17, 3, 7897.97, 'delivered', '2025-05-04 16:34:07', 11, 'completed'),
(18, 2, 2198.00, 'cancelled', '2025-05-04 19:19:43', 12, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 4, 1099.00),
(2, 2, 1, 2, 1099.00),
(3, 3, 1, 1, 1099.00),
(4, 4, 1, 2, 1099.00),
(5, 5, 1, 1, 1099.00),
(6, 6, 1, 1, 1099.00),
(7, 7, 1, 1, 1099.00),
(8, 8, 1, 2, 1099.00),
(9, 9, 1, 1, 1099.00),
(10, 10, 1, 1, 1099.00),
(11, 11, 1, 2, 1099.00),
(12, 12, 1, 1, 1099.00),
(13, 13, 2, 1, 8999.00),
(14, 13, 3, 1, 1899.99),
(15, 14, 3, 1, 1899.99),
(16, 15, 1, 1, 1099.00),
(17, 15, 3, 2, 1899.99),
(18, 16, 2, 1, 8999.00),
(19, 17, 1, 2, 1099.00),
(20, 17, 3, 3, 1899.99),
(21, 18, 1, 2, 1099.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `featured` tinyint(1) DEFAULT 0,
  `rating` decimal(3,1) DEFAULT NULL,
  `reviews_count` int(11) DEFAULT 0,
  `storage` varchar(50) DEFAULT NULL,
  `display` varchar(100) DEFAULT NULL,
  `battery` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category`, `stock`, `created_at`, `featured`, `rating`, `reviews_count`, `storage`, `display`, `battery`) VALUES
(1, 'iPhone 14 Pro Max ', 'The latest iPhone with A16 Bionic chip, 48MP camera, and Dynamic Island.  ', 1099.00, 'Screenshot 2025-03-31 115347.png', 'smartphone', 11, '2025-05-04 02:39:05', 1, 4.7, 3, NULL, NULL, NULL),
(2, 'Samsung Galaxy S23', 'The latest Samsung flagship phone with advanced camera system, powerful processor, and stunning display', 8999.00, 'Screenshot 2024-10-13 105629.png', 'smartphone', 38, '2025-05-04 07:21:29', 1, NULL, 0, NULL, NULL, NULL),
(3, '  Samsung Galaxy S22', 'The latest Samsung flagship phone with advanced camera system, powerful processor, and stunning display', 1899.99, 'Screenshot 2025-02-08 113642.png', 'tablet', 8, '2025-05-04 07:24:23', 1, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `request_type` varchar(20) DEFAULT 'refund'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`id`, `order_id`, `user_id`, `reason`, `comments`, `status`, `created_at`, `updated_at`, `processed_by`, `processed_at`, `request_type`) VALUES
(1, 11, 3, 'Item not as described', 'cgcghvn', 'pending', '2025-05-04 06:34:33', '2025-05-04 06:34:33', NULL, NULL, 'refund'),
(2, 12, 3, 'Item damaged', 'wasx', 'pending', '2025-05-04 06:43:32', '2025-05-04 06:43:32', NULL, NULL, 'refund'),
(3, 8, 2, 'Changed mind', 'zxz', 'approved', '2025-05-04 06:47:55', '2025-05-04 06:57:30', 2, '2025-05-04 06:57:30', 'refund'),
(4, 9, 2, 'Changed mind', 'xx', 'pending', '2025-05-04 07:15:01', '2025-05-04 07:15:01', NULL, NULL, 'refund'),
(5, 13, 2, 'Shipping too slow', 'ghghvh', 'approved', '2025-05-04 07:25:51', '2025-05-04 07:26:13', 2, '2025-05-04 07:26:13', 'refund'),
(6, 14, 2, 'Item not as described', 'mbm ', 'approved', '2025-05-04 07:30:59', '2025-05-04 07:31:35', 2, '2025-05-04 07:31:35', 'cancellation'),
(7, 18, 2, 'Changed mind', ',mn,', 'pending', '2025-05-04 19:20:37', '2025-05-04 19:20:37', NULL, NULL, 'refund'),
(8, 18, 2, 'Changed mind', ',mn,', 'pending', '2025-05-04 19:23:16', '2025-05-04 19:23:16', NULL, NULL, 'refund'),
(9, 18, 2, 'Changed mind', ',mn,', 'pending', '2025-05-04 19:24:29', '2025-05-04 19:24:29', NULL, NULL, 'refund'),
(10, 18, 2, 'Ordered wrong item', ' m ', 'pending', '2025-05-04 19:25:16', '2025-05-04 19:25:16', NULL, NULL, 'cancellation');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 4, 5, 'super', '2025-05-04 12:26:57'),
(2, 1, 5, 5, 'cxzc', '2025-05-04 15:02:53'),
(4, 1, 3, 4, 'super', '2025-05-04 16:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_addresses`
--

CREATE TABLE `shipping_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `address_line1` varchar(100) NOT NULL,
  `address_line2` varchar(100) DEFAULT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_addresses`
--

INSERT INTO `shipping_addresses` (`id`, `user_id`, `full_name`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `phone`, `is_default`) VALUES
(1, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 0),
(2, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 0),
(3, 2, 'shart', 'dcd', 'csd', 'sdv', 'svdv', '123456', 'szx', '1131214', 0),
(4, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 0),
(5, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 0),
(6, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 0),
(7, 2, 'shart', 'dcd', 'csd', 'sdv', 'svdv', '123456', 'szx', '1131214', 0),
(8, 2, 'shart', 'dcd', 'csd', 'sdv', 'svdv', '123456', 'szx', '1131214', 0),
(9, 4, 'sharth', 'sdfvdg', '', 'vdsbs', 'sdgvsfgv', '123456', 'dfvffb', '8546918524', 1),
(10, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 0),
(11, 3, 'sharath', 'jbjskb', '', 'sjkbnxajk', 'bxjkgj', '123456', 'xxavnb', '1234567891', 1),
(12, 2, 'shart', 'dcd', 'csd', 'sdv', 'svdv', '123456', 'szx', '9731904524', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscribers`
--

INSERT INTO `subscribers` (`id`, `name`, `email`, `subscribed_at`) VALUES
(1, 'sudeep', 's@g.o', '2025-05-04 16:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `is_admin`, `created_at`) VALUES
(2, 'sudeepa', '$2y$10$CPq5yUs4i/LzVbCfLI52hOLPBCjAbEZutmsK0wfRcAykjMQVB4Hzq', 'sharath@ha.gmail', 1, '2025-05-04 02:36:12'),
(3, 'sudeep', '$2y$10$JCssZRVS9r.G6.OiJB545O6lIrhwvNKe7ncXIGGqgpXl4l6EDPzgm', 's@g.o', 0, '2025-05-04 02:40:31'),
(4, 'sharath', '$2y$10$hI.1kYJ3rOo/.5BwMPFrbuXHNJziYsGztrGa4S6Hkt7IVT0uNA2pC', 'sharathr@gmail.com', 0, '2025-05-04 10:59:26'),
(5, 'Ahad', '$2y$10$LqcSDd5M/CNut6.krY2nkeUhztyqTuGU2YrmS0rQaAeiUWsxLFmo6', 'ahad@gmail.com', 0, '2025-05-04 14:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `added_at`) VALUES
(1, 4, 3, '2025-05-04 12:35:04'),
(2, 4, 1, '2025-05-04 12:35:37'),
(3, 5, 1, '2025-05-04 14:35:59'),
(4, 3, 1, '2025-05-04 16:02:46'),
(5, 3, 3, '2025-05-04 16:30:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD CONSTRAINT `shipping_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
