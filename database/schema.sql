-- STOCKWISE DATABASE SCHEMA
CREATE DATABASE `stockwise_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stockwise_db`;

-- Roles
CREATE TABLE `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Offices
CREATE TABLE `offices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `office_code` VARCHAR(20) NOT NULL UNIQUE,
  `office_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `office_id` INT(11) DEFAULT NULL,
  `role_id` INT(11) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `office_id` (`office_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Locations
CREATE TABLE `locations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Items
CREATE TABLE `items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) DEFAULT NULL,
  `location_id` INT(11) DEFAULT NULL,
  `item_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(20) DEFAULT NULL,
  `price` DECIMAL(12,2) DEFAULT 0.00,
  `reorder_point` INT(11) DEFAULT 0,
  `current_qty` INT(11) DEFAULT 0,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `barcode_image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions
CREATE TABLE `transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `type` ENUM('stock_in','stock_out','adjustment','transfer') NOT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `from_location_id` INT(11) DEFAULT NULL,
  `to_location_id` INT(11) DEFAULT NULL,
  `quantity` INT(11) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`),
  KEY `from_location_id` (`from_location_id`),
  KEY `to_location_id` (`to_location_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings
CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Archives
CREATE TABLE `archives` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT(11) NOT NULL,
  `data_json` MEDIUMTEXT,
  `archived_by` INT(11) DEFAULT NULL,
  `archived_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `entity` (`entity_type`, `entity_id`),
  CONSTRAINT `archives_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Office Limits
CREATE TABLE `office_limits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `office_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `year` YEAR(4) NOT NULL,
  `max_qty` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_limit` (`office_id`, `item_id`, `year`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `office_limits_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `office_limits_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requests
CREATE TABLE `requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_number` VARCHAR(50) NOT NULL UNIQUE,
  `office_id` INT(11) NOT NULL,
  `requestor_id` INT(11) NOT NULL,
  `type` ENUM('RIS','PPMP','PPE','ARE','BS') NOT NULL,
  `status` ENUM('draft','in_review','returned','approved') NOT NULL DEFAULT 'draft',
  `current_step_role` VARCHAR(30) DEFAULT NULL,
  `purpose` TEXT DEFAULT NULL,
  `needed_by` DATETIME DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `submission_count` INT(11) NOT NULL DEFAULT 0,
  `requestor_signature` MEDIUMTEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `office_id` (`office_id`),
  KEY `requestor_id` (`requestor_id`),
  KEY `status` (`status`),
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`requestor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Request Items 
CREATE TABLE `request_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `requested_qty` INT(11) NOT NULL,
  `approved_qty` INT(11) DEFAULT 0,
  `unit` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Approval steps
CREATE TABLE `request_approval_steps` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `step_index` INT(11) NOT NULL,
  `role_code` VARCHAR(30) NOT NULL,
  `role_label` VARCHAR(60) NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `delegation_status` ENUM('none','auto','manual') NOT NULL DEFAULT 'none',
  `assigned_to` INT(11) DEFAULT NULL,
  `delegated_by` INT(11) DEFAULT NULL,
  `delegated_to` INT(11) DEFAULT NULL,
  `signature_base64` MEDIUMTEXT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `acted_at` DATETIME DEFAULT NULL,
  `delegated_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `steps_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `steps_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `steps_ibfk_3` FOREIGN KEY (`delegated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `steps_ibfk_4` FOREIGN KEY (`delegated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Status history / audit log
CREATE TABLE `request_status_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `actor_id` INT(11) DEFAULT NULL,
  `actor_name` VARCHAR(100) DEFAULT NULL,
  `actor_role` VARCHAR(60) DEFAULT NULL,
  `action` VARCHAR(40) NOT NULL,
  `label` VARCHAR(80) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `history_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `history_ibfk_2` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Logs
CREATE TABLE `audit_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA

INSERT INTO `roles` (`role_name`) VALUES
('admin'), ('supply_personnel'), ('requestor'),
('budget_head'), ('procurement_head'), ('vp_finance');

INSERT INTO `offices` (`office_code`, `office_name`) VALUES
('ADMIN', 'System Administration Office'),
('BUDGET', 'Budget Office'),
('PROC', 'Procurement Office'),
('FIN', 'Finance Office'),
('SUPPLY', 'Supply Office');

INSERT INTO `users` (`office_id`, `role_id`, `email`, `password_hash`, `full_name`, `is_active`)
VALUES
(1, 1, 'admin@stockwise.local', '$2y$10$NVnHLCiWyERE0LlCJw.i9uI58GSjBTg7zAfsZhHvOEtruBj2rV72S', 'System Administrator', 1),

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'StockWise'),
('app_timezone', 'Asia/Manila'),
('notify_low_stock', '1'),
('notify_on_register', '1'),
('items_per_page', '50'),
('default_reorder_point', '5'),
('supply_admin_delegation_enabled', '0');