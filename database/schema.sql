-- Skema Database Laundria (MySQL / MariaDB InnoDB)
-- Jalankan file ini untuk instalasi baru atau migrasi tabel tambahan.

CREATE DATABASE IF NOT EXISTS `laundria2`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `laundria2`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','employee') NOT NULL DEFAULT 'employee',
  `position` varchar(50) NOT NULL DEFAULT 'Karyawan',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL,
  `plan` enum('basic','premium') NOT NULL DEFAULT 'basic',
  `messaging` tinyint(1) NOT NULL DEFAULT 0,
  `shop_name` varchar(100) NOT NULL DEFAULT 'Laundria',
  `shop_address` varchar(255) NOT NULL DEFAULT 'Jl. Melati No. 8, Kediri',
  `shop_phone` varchar(30) NOT NULL DEFAULT '0812-0000-1111',
  `receipt_footer` varchar(255) NOT NULL DEFAULT 'Simpan nota ini sebagai bukti pengambilan.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `consent` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `paid_amount` int(11) NOT NULL DEFAULT 0,
  `status` enum('Menunggu','Diproses','Siap Diambil','Selesai') NOT NULL DEFAULT 'Menunggu',
  `due_date` date NOT NULL,
  `payment` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(20) NOT NULL,
  `service_id` int(11) NOT NULL,
  `quantity` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cash_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opened_by` varchar(100) NOT NULL,
  `opened_at` datetime NOT NULL,
  `opening_balance` int(11) NOT NULL DEFAULT 0,
  `closed_at` datetime DEFAULT NULL,
  `expected_cash` int(11) DEFAULT NULL,
  `counted_cash` int(11) DEFAULT NULL,
  `difference` int(11) DEFAULT NULL,
  `closing_note` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(20) NOT NULL,
  `amount` int(11) NOT NULL,
  `method` varchar(50) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `actor` varchar(100) NOT NULL,
  `paid_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `shift_id` (`shift_id`),
  CONSTRAINT `order_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_payments_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `cash_shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cash_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_id` int(11) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `amount` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `note` varchar(255) NOT NULL,
  `actor` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shift_id` (`shift_id`),
  CONSTRAINT `cash_movements_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cash_shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(150) NOT NULL,
  `amount` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 0.00,
  `minimum` decimal(8,2) NOT NULL DEFAULT 5.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `quantity` decimal(8,2) NOT NULL,
  `before_qty` decimal(8,2) NOT NULL,
  `after_qty` decimal(8,2) NOT NULL,
  `note` varchar(255) NOT NULL,
  `actor` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ai_insight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipe` varchar(30) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `dibuat_pada` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------
-- Data Awal (Demo)
-- Password default akun demo: laundria2025
-- ------------------------------------------------------

INSERT IGNORE INTO `settings` (`id`, `plan`, `messaging`, `shop_name`, `shop_address`, `shop_phone`, `receipt_footer`) VALUES
(1, 'basic', 0, 'Laundria', 'Jl. Melati No. 8, Kediri', '0812-0000-1111', 'Simpan nota ini sebagai bukti pengambilan.');

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `position`, `active`) VALUES
(1, 'Budi Santoso', 'owner@laundria.id', '$2y$12$HGs79IehahpfeEXwQl/g6uLynG/zIC4IgFwRCyW2LmMJOpcHWI4n2', 'owner', 'Owner', 1),
(2, 'Siti Aminah', 'siti@laundria.id', '$2y$12$HGs79IehahpfeEXwQl/g6uLynG/zIC4IgFwRCyW2LmMJOpcHWI4n2', 'employee', 'Karyawan', 1);

INSERT IGNORE INTO `services` (`id`, `name`, `unit`, `price`) VALUES
(1, 'Cuci Komplit Reguler', 'kg', 7000),
(2, 'Cuci Kering Lipat', 'kg', 5000),
(3, 'Setrika Saja', 'kg', 4500),
(4, 'Cuci Bed Cover', 'pcs', 25000),
(5, 'Cuci Sepatu', 'pcs', 30000);

INSERT IGNORE INTO `inventory_items` (`id`, `name`, `category`, `unit`, `quantity`, `minimum`) VALUES
(1, 'Deterjen Cair', 'Bahan cuci', 'liter', 15.00, 5.00),
(2, 'Parfum Lavender', 'Pewangi', 'liter', 8.00, 3.00),
(3, 'Plastik Packing', 'Kemasan', 'pack', 10.00, 4.00);
