-- 20260202_add_tenants.sql
-- Tabelle tenant e associazioni licenze.

CREATE TABLE IF NOT EXISTS tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  contact_email VARCHAR(150) NULL,
  contact_phone VARCHAR(60) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO tenants (id, name, slug, contact_email, contact_phone, is_active)
VALUES (1, 'Default', 'default', NULL, NULL, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE users
  ADD COLUMN tenant_id INT NULL AFTER id;

UPDATE users SET tenant_id = 1 WHERE tenant_id IS NULL;

ALTER TABLE users
  MODIFY tenant_id INT NOT NULL,
  ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT;

CREATE TABLE IF NOT EXISTS tenant_licenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  license_id INT NOT NULL,
  max_users_override INT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  revoked_at TIMESTAMP NULL,
  notes VARCHAR(255) NULL,
  INDEX idx_tenant_licenses_tenant (tenant_id),
  INDEX idx_tenant_licenses_license (license_id),
  INDEX idx_tenant_licenses_revoked (revoked_at),
  CONSTRAINT fk_tenant_licenses_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_tenant_licenses_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE license_activations
  ADD COLUMN tenant_id INT NULL AFTER license_id,
  ADD INDEX idx_license_activations_tenant (tenant_id),
  ADD CONSTRAINT fk_license_activations_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL;
