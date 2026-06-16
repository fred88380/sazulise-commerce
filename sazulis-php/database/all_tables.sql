CREATE DATABASE IF NOT EXISTS sazulis_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sazulis_v2;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(140) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  description TEXT NOT NULL,
  long_description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  badges VARCHAR(255) DEFAULT NULL,
  highlights_json TEXT NULL,
  included_json TEXT NULL,
  not_included_json TEXT NULL,
  stock INT NOT NULL DEFAULT 0,
  unlimited_stock TINYINT(1) NOT NULL DEFAULT 1,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_products_featured (featured),
  INDEX idx_products_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client', 'admin') NOT NULL DEFAULT 'client',
  totp_secret VARCHAR(64) NULL,
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_ref VARCHAR(24) NOT NULL UNIQUE,
  user_id INT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_name VARCHAR(190) DEFAULT NULL,
  customer_address TEXT NULL,
  total DECIMAL(10,2) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  acompte_paye TINYINT(1) NOT NULL DEFAULT 0,
  solde_regle TINYINT(1) NOT NULL DEFAULT 0,
  client_signature_path VARCHAR(255) DEFAULT NULL,
  client_signature_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_orders_ref (order_ref),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_name VARCHAR(190) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
