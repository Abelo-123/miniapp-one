-- Orders table for SMM platform
-- Run this SQL to create the orders table

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(50) NOT NULL,
    `api_order_id` VARCHAR(50) NOT NULL,
    `service_id` INT(11) NOT NULL,
    `service_name` VARCHAR(255) NOT NULL,
    `link` TEXT NOT NULL,
    `quantity` INT(11) NOT NULL,
    `charge` DECIMAL(10, 4) NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded') DEFAULT 'pending',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
