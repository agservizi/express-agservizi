-- 20260128_add_energy_contracts.sql
-- Tabelle per gestori energia e contratti luce/gas.

CREATE TABLE IF NOT EXISTS energy_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  service_type ENUM('luce','gas','luce_gas') NOT NULL DEFAULT 'luce_gas',
  token_luce DECIMAL(10,2) NOT NULL DEFAULT 0,
  token_gas DECIMAL(10,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_energy_providers_name (name)
);

CREATE TABLE IF NOT EXISTS energy_contracts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  customer_name VARCHAR(150) NOT NULL,
  contract_type ENUM('luce','gas','luce_gas') NOT NULL,
  provider_id INT NOT NULL,
  token_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  user_id INT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_energy_contracts_created (created_at),
  INDEX idx_energy_contracts_provider (provider_id),
  INDEX idx_energy_contracts_customer (customer_id),
  CONSTRAINT fk_energy_contracts_provider FOREIGN KEY (provider_id) REFERENCES energy_providers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_energy_contracts_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);
