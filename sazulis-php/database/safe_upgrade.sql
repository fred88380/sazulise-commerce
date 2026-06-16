CREATE DATABASE IF NOT EXISTS sazulis_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sazulis_v2;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Base tables (non destructif)
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(140) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  badges VARCHAR(255) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client', 'admin') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_name VARCHAR(190) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Evolve schema safely (non destructif, relancable)
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS long_description TEXT NULL,
  ADD COLUMN IF NOT EXISTS highlights_json TEXT NULL,
  ADD COLUMN IF NOT EXISTS included_json TEXT NULL,
  ADD COLUMN IF NOT EXISTS not_included_json TEXT NULL,
  ADD COLUMN IF NOT EXISTS unlimited_stock TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS acompte_paye TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS solde_regle TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS client_signature_path VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS client_signature_at DATETIME DEFAULT NULL;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) NOT NULL DEFAULT 0;

-- 3) Indexes (non destructif)
CREATE INDEX IF NOT EXISTS idx_products_featured ON products(featured);
CREATE INDEX IF NOT EXISTS idx_products_slug ON products(slug);
CREATE INDEX IF NOT EXISTS idx_orders_ref ON orders(order_ref);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);

-- 4) Data patch (sans ecraser si deja rempli)
UPDATE products
SET unlimited_stock = 1,
    stock = 0
WHERE COALESCE(unlimited_stock, 0) <> 1 OR COALESCE(stock, 0) <> 0;

UPDATE products
SET included_json = '["Developpement du projet", "Configuration technique de base", "Recette fonctionnelle avant livraison", "Hebergement d\'un an offert", "SEO inclus", "3 mois de maintenance Basic offerts"]'
WHERE (
    slug LIKE 'site-vitrine%'
    OR slug LIKE 'e-commerce%'
    OR slug LIKE 'wordpress-cms%'
    OR slug LIKE 'application-web%'
)
AND (included_json IS NULL OR included_json = '');

SET FOREIGN_KEY_CHECKS = 1;
