-- create_db.sql
-- Roles
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES ('admin'), ('cassiere');

-- Tenants
CREATE TABLE tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  contact_email VARCHAR(150) NULL,
  contact_phone VARCHAR(60) NULL,
  vat_number VARCHAR(32) NULL,
  company_country VARCHAR(2) NULL,
  company_name VARCHAR(190) NULL,
  company_address VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO tenants (id, name, slug, contact_email, contact_phone, is_active)
VALUES (1, 'Default', 'default', NULL, NULL, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  tenant_id INT NOT NULL DEFAULT 1,
  role_id INT NOT NULL,
  fullname VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Providers
CREATE TABLE providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  name VARCHAR(100) NOT NULL UNIQUE,
  reorder_threshold INT NOT NULL DEFAULT 10,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

INSERT INTO providers (name, reorder_threshold) VALUES
('Iliad', 25),
('Fastweb Mobile', 20),
('Sky Mobile', 15),
('Tiscali Mobile', 15),
('Windtre', 25),
('Digi Mobile', 20);

-- ICCID stock
CREATE TABLE iccid_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  iccid VARCHAR(32) NOT NULL UNIQUE,
  provider_id INT NOT NULL,
  status ENUM('InStock','Reserved','Sold') NOT NULL DEFAULT 'InStock',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes TEXT,
  row_version TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES providers(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Products catalog
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  name VARCHAR(150) NOT NULL,
  sku VARCHAR(100) NULL,
  imei VARCHAR(100) NULL,
  category VARCHAR(100) NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 22.00,
  vat_code VARCHAR(32) NULL,
  notes TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_products_sku (sku),
  UNIQUE KEY idx_products_imei (imei),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Stock alerts per provider
CREATE TABLE stock_alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  provider_id INT NOT NULL,
  current_stock INT NOT NULL,
  threshold INT NOT NULL,
  average_daily_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  days_cover DECIMAL(10,2) NULL,
  last_movement DATETIME NULL,
  status ENUM('Open','Resolved') NOT NULL DEFAULT 'Open',
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  FOREIGN KEY (provider_id) REFERENCES providers(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Discount schemes (legacy semplice)
CREATE TABLE IF NOT EXISTS discount_schemes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  name VARCHAR(150) NOT NULL,
  type ENUM('Amount','Percent') NOT NULL DEFAULT 'Amount',
  value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_discount_schemes_name (name),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Discount campaigns
CREATE TABLE discount_campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  name VARCHAR(150) NOT NULL,
  description VARCHAR(255) NULL,
  type ENUM('Fixed','Percent') NOT NULL DEFAULT 'Fixed',
  value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Sales
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  user_id INT NOT NULL,
  customer_name VARCHAR(200),
  total DECIMAL(10,2) NOT NULL,
  vat DECIMAL(5,2) DEFAULT 22.00,
  discount DECIMAL(10,2) DEFAULT 0.00,
  discount_campaign_id INT NULL,
  payment_method ENUM('Contanti','Carta','POS') DEFAULT 'Contanti',
  status ENUM('Completed','Cancelled','Refunded') NOT NULL DEFAULT 'Completed',
  cancelled_at TIMESTAMP NULL,
  cancellation_note TEXT,
  refunded_at TIMESTAMP NULL,
  refund_note TEXT,
  refunded_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  credited_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (discount_campaign_id) REFERENCES discount_campaigns(id) ON DELETE SET NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Sale items (link ICCID to sale or generic product)
CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  sale_id INT NOT NULL,
  iccid_id INT NULL,
  product_id INT NULL,
  product_imei VARCHAR(100) NULL,
  description VARCHAR(255),
  quantity INT DEFAULT 1,
  price DECIMAL(10,2) NOT NULL,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  vat_code VARCHAR(32) NULL,
  refunded_quantity INT NOT NULL DEFAULT 0,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (iccid_id) REFERENCES iccid_stock(id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Audit log
CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Operator offers (listini e canvass)
CREATE TABLE operator_offers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  provider_id INT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  valid_from DATE NULL,
  valid_to DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES providers(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

INSERT INTO operator_offers (provider_id, title, description, price, status)
VALUES
  (NULL, 'Attivazione SIM standard', 'Attivazione generica con contributo una tantum.', 9.90, 'Active'),
  (1, 'Iliad Voce Plus', 'Pacchetto voce illimitata + 100 SMS.', 7.99, 'Active'),
  (5, 'WindTre Fibra Promo', 'Promo convergente mobile + fibra per 12 mesi.', 24.90, 'Inactive');

-- Sale item refunds (storico resi parziali)
CREATE TABLE sale_item_refunds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  sale_item_id INT NOT NULL,
  user_id INT NOT NULL,
  quantity INT NOT NULL,
  refund_type ENUM('Refund','Credit') NOT NULL,
  note TEXT,
  amount DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sale_item_id) REFERENCES sale_items(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Remember-me tokens
CREATE TABLE user_remember_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_user_remember_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Licenze
CREATE TABLE licenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(150) NULL,
  max_users INT NOT NULL DEFAULT 1,
  term_months INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  expires_at DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE license_activations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  license_id INT NOT NULL,
  tenant_id INT NULL,
  activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  revoked_at TIMESTAMP NULL,
  notes VARCHAR(255) NULL,
  INDEX idx_license_activations_license (license_id),
  INDEX idx_license_activations_tenant (tenant_id),
  INDEX idx_license_activations_revoked (revoked_at),
  CONSTRAINT fk_license_activations_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
  CONSTRAINT fk_license_activations_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
);

CREATE TABLE tenant_licenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  license_id INT NOT NULL,
  max_users_override INT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  revoked_at TIMESTAMP NULL,
  renewal_notice_sent_at DATETIME NULL,
  renewal_paid_at DATETIME NULL,
  notes VARCHAR(255) NULL,
  INDEX idx_tenant_licenses_tenant (tenant_id),
  INDEX idx_tenant_licenses_license (license_id),
  INDEX idx_tenant_licenses_revoked (revoked_at),
  CONSTRAINT fk_tenant_licenses_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_tenant_licenses_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
);
