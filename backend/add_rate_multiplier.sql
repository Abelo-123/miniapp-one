-- Add rate_multiplier setting to settings table
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('rate_multiplier', '400') ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
