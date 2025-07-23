-- SQL script to create requirement_types table
CREATE TABLE IF NOT EXISTS `requirement_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `is_required` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default requirement types
INSERT INTO `requirement_types` (`name`, `description`, `is_required`) VALUES 
('Document', 'Official documents required for enrollment', 1),
('Payment', 'Payment receipts and financial requirements', 1),
('Form', 'Forms that need to be filled out', 1),
('Other', 'Other miscellaneous requirements', 1); 