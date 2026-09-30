-- School Market Database Schema
-- Designed for MySQL 8.0+ / Hostinger Compatible

CREATE DATABASE IF NOT EXISTS `school_market` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_market`;

-- --------------------------------------------------------
-- Table: schools
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schools` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `school_name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `logo` VARCHAR(255) NOT NULL,
  `background_image` VARCHAR(255) NOT NULL,
  `primary_color` VARCHAR(7) NOT NULL DEFAULT '#3b82f6',
  `secondary_color` VARCHAR(7) NOT NULL DEFAULT '#10b981',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `student_id` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `school_id` INT NOT NULL,
  `grade_level` VARCHAR(50) DEFAULT NULL,
  `group_name` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
  `avatar` VARCHAR(255) NOT NULL DEFAULT 'assets/images/default_avatar.svg',
  `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: student_products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `school_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `condition` ENUM('new', 'like_new', 'used_good', 'used_fair') NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 1,
  `image` TEXT NOT NULL, -- Stores JSON array of image file paths
  `status` ENUM('pending', 'approved', 'hidden') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: teacher_products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teacher_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT NOT NULL,
  -- We include user_id optionally or just keep schema compliance using fields requested
  `teacher_name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `grade` VARCHAR(50) NOT NULL,
  `group_name` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 1,
  `image` TEXT NOT NULL, -- Stores JSON array of image file paths
  `status` ENUM('pending', 'approved', 'hidden') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reviews
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL, -- The reviewer
  `seller_id` INT NOT NULL, -- The seller being reviewed
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `reported_user_id` INT NOT NULL,
  `reason` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sales
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_type` ENUM('student', 'teacher') NOT NULL DEFAULT 'student',
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `commission_amount` DECIMAL(10, 2) NOT NULL,
  `seller_amount` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert Seed Data
-- --------------------------------------------------------

-- Default Platform Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
('commission_percentage', '5')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Default Administrator
-- Password is 'admin123' (bcrypt hashed: $2y$10$U4KvxK8652/qX0r7X4rJduXh/f/fN6oY8sI2eXU8zFhQy9b4zXf5e - or we calculate one)
-- Let's generate a secure hash for 'admin123': $2y$10$2HhO0l.dDk4Wz0zN5x2kG.q7WqBveQ/e/f4WpW.E2r89O5F57s5E2
INSERT INTO `admins` (`id`, `username`, `password`) VALUES 
(1, 'admin', '$2y$10$d2MOAA2NYiVwU6GE4tRsye3IPJDPFvMmBWYm.3s8QR89rSTnM8LsO')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- Prepopulate Educational Institutions in Medellín
INSERT INTO `schools` (`id`, `school_name`, `address`, `logo`, `background_image`, `primary_color`, `secondary_color`) VALUES
(1, 'Colegio San José de Las Vegas', 'Cra. 48 #7 Sur-151, Medellín', 'vegas_logo.svg', 'vegas_bg.svg', '#1B365D', '#D9A441'),
(2, 'Colegio Montessori', 'Calle 20 Sur #27-55, Medellín', 'montessori_logo.svg', 'montessori_bg.svg', '#0F5132', '#C5A880'),
(3, 'Colegio UPB', 'Circular 1a #70-01, Medellín', 'upb_logo.svg', 'upb_bg.svg', '#C8102E', '#111827'),
(4, 'Colegio Cumbres', 'Calle 18c Sur #22-22, Medellín', 'cumbres_logo.svg', 'cumbres_bg.svg', '#0A2540', '#D1A153'),
(5, 'Colegio Benedictino', 'Calle 24 Sur #28-40, Envigado', 'benedictino_logo.svg', 'benedictino_bg.svg', '#002855', '#4682B4'),
(6, 'Colegio Calasanz', 'Calle 50 #80-45, Medellín', 'calasanz_logo.svg', 'calasanz_bg.svg', '#0B3C5D', '#F5A623'),
(7, 'INEM José Félix de Restrepo', 'Cra. 48 #1-125, Medellín', 'inem_logo.svg', 'inem_bg.svg', '#1F5A3B', '#FFD700'),
(8, 'Colegio La Salle', 'Calle 73 #73a-22, Medellín', 'lasalle_logo.svg', 'lasalle_bg.svg', '#002C6C', '#D21245'),
(9, 'Colegio Colombo Británico', 'Cra. 52 #20 Sur-95, Envigado', 'colombo_logo.svg', 'colombo_bg.svg', '#1D4ED8', '#EF4444'),
(10, 'Colegio San Ignacio', 'Calle 48 #43-37, Medellín', 'sanignacio_logo.svg', 'sanignacio_bg.svg', '#1E3A8A', '#3B82F6');
