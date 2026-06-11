CREATE DATABASE IF NOT EXISTS sazulis_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sazulis_v2;

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
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client', 'admin') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_ref VARCHAR(24) NOT NULL UNIQUE,
  user_id INT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_name VARCHAR(190) DEFAULT NULL,
  customer_address TEXT,
  total DECIMAL(10,2) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_name VARCHAR(190) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

INSERT INTO users (full_name, email, password_hash, role) VALUES
('Sazulis Admin', 'admin@sazulis.fr', '$2y$10$tURJ8yr5uz6mLQLyStMW3.0Wx/G/Yh9bD/0qwqvOFp72dxF/sDgtW', 'admin')
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name),
  password_hash = VALUES(password_hash),
  role = VALUES(role);

INSERT INTO products (slug, name, description, price, image, badges, stock, featured) VALUES
('application-web-html', 'Application Web HTML', 'Developpement d\'une application web en HTML.', 5015.13, '/assets/img/application-web.png', 'Dev Web', 10, 1),
('application-web-nextjs', 'Application Web Next.js', 'Developpement d\'une application web avec Next.js.', 6087.36, '/assets/img/application-web.png', 'Dev Web', 10, 1),
('e-commerce-html', 'E-commerce HTML', 'Site e-commerce en HTML.', 6373.63, '/assets/img/e-commerce2.png', 'E-commerce', 10, 1),
('e-commerce-nextjs', 'E-commerce Next.js', 'Site e-commerce avec Next.js.', 8299.00, '/assets/img/e-commerce2.png', 'E-commerce,Top Vente', 10, 1),
('hebergement-basic-html', 'Hébergement Basic HTML', 'Hebergement basique pour site HTML.', 149.99, '/assets/img/hebergement.jpg', 'Hebergement', 50, 0),
('hebergement-basic-nextjs', 'Hébergement Basic Next.js', 'Hebergement basique pour site Next.js.', 299.99, '/assets/img/hebergement.jpg', 'Hebergement', 50, 0),
('hebergement-business-html', 'Hébergement Business HTML', 'Hebergement business pour site HTML.', 289.99, '/assets/img/hebergement.jpg', 'Hebergement', 50, 0),
('hebergement-business-nextjs', 'Hébergement Business Next.js', 'Hebergement business pour site Next.js.', 579.98, '/assets/img/hebergement.jpg', 'Hebergement', 50, 0),
('hebergement-premium-html', 'Hébergement Premium HTML', 'Hebergement premium pour site HTML.', 579.99, '/assets/img/hebergement.jpg', 'Hebergement,Premium', 50, 0),
('hebergement-premium-nextjs', 'Hébergement Premium Next.js', 'Hebergement premium pour site Next.js.', 1159.96, '/assets/img/hebergement.jpg', 'Hebergement,Premium', 50, 0),
('maintenance-basic', 'Maintenance Basic', 'Maintenance basique.', 350.00, '/assets/img/maintenance.jpg', 'Maintenance', 100, 0),
('maintenance-business', 'Maintenance Business', 'Maintenance business.', 650.00, '/assets/img/maintenance.jpg', 'Maintenance', 100, 0),
('maintenance-premium', 'Maintenance Premium', 'Maintenance premium.', 950.00, '/assets/img/maintenance.jpg', 'Maintenance,Premium', 100, 0),
('maintenance-urgente', 'Maintenance Urgente', 'Maintenance urgente.', 200.00, '/assets/img/urgente.png', 'Maintenance', 100, 0),
('refonte-site-html', 'Refonte site HTML', 'Refonte de site en HTML.', 250.00, '/assets/img/refonte-site-internet.jpg', 'Refonte', 25, 0),
('refonte-site-nextjs', 'Refonte site Next.js', 'Refonte de site avec Next.js.', 600.00, '/assets/img/refonte-site-internet.jpg', 'Refonte', 25, 0),
('seo-basic', 'SEO Basic', 'Pack SEO basic pour ameliorer la visibilite Google.', 299.99, '/assets/img/seo.png', 'SEO', 100, 0),
('seo-business', 'SEO Business', 'Pack SEO business.', 459.98, '/assets/img/seo.png', 'SEO', 100, 0),
('seo-premium', 'SEO Premium', 'Pack SEO premium.', 599.99, '/assets/img/seo.png', 'SEO,Premium', 100, 0),
('site-vitrine-html', 'Site vitrine HTML', 'Site vitrine en HTML.', 4326.87, '/assets/img/site-vitrine.png', 'Site Vitrine', 12, 1),
('site-vitrine-nextjs', 'Site vitrine Next.js', 'Site vitrine avec Next.js.', 5399.10, '/assets/img/site-vitrine.png', 'Site Vitrine,Top Vente', 12, 1),
('site-vitrine-pack-starter-html', 'Site vitrine pack-starter HTML', 'Pack starter site vitrine HTML.', 1316.98, '/assets/img/vitrine-site.png', 'Site Vitrine', 15, 0),
('site-vitrine-pack-starter-nextjs', 'Site vitrine pack-starter Next.js', 'Pack starter site vitrine Next.js.', 2899.10, '/assets/img/vitrine-site.png', 'Site Vitrine', 15, 0),
('wordpress-cms-html', 'WordPress CMS HTML', 'Site WordPress CMS HTML.', 3603.64, '/assets/img/wordpress.jpg', 'WordPress', 10, 0),
('wordpress-cms-nextjs', 'WordPress CMS Next.js', 'Site WordPress CMS Next.js.', 4675.87, '/assets/img/wordpress.jpg', 'WordPress', 10, 0);
