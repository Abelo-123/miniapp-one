CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('marquee_text', 'Welcome to Paxyo SMM! Cheapest services available.') ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
