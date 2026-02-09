-- 20260209_add_checkout_discount_codes.sql
-- Aggiunge codici sconto per checkout e campi applicazione.

CREATE TABLE tenant_discount_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    code VARCHAR(32) NOT NULL,
    type ENUM('Fixed','Percent') NOT NULL DEFAULT 'Fixed',
    value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    starts_at DATE NULL,
    ends_at DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tenant_discount_code (tenant_id, code),
    KEY idx_discount_active (is_active)
);

ALTER TABLE tenant_checkout_requests
  ADD COLUMN discount_code VARCHAR(32) NULL AFTER company_address,
  ADD COLUMN discount_type ENUM('Fixed','Percent') NULL AFTER discount_code,
  ADD COLUMN discount_value DECIMAL(10,2) NULL AFTER discount_type,
  ADD COLUMN discount_amount DECIMAL(10,2) NULL AFTER discount_value;
