-- Table for storing admin recommended services for "Top" category
CREATE TABLE IF NOT EXISTS `admin_recommended_services` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT(11) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
