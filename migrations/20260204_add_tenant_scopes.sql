-- 20260204_add_tenant_scopes.sql
-- Aggiunge tenant_id alle tabelle operative.

-- Core cataloghi
ALTER TABLE providers ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE providers SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE providers MODIFY tenant_id INT NOT NULL;

ALTER TABLE iccid_stock ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE iccid_stock SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE iccid_stock MODIFY tenant_id INT NOT NULL;

ALTER TABLE products ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE products SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE products MODIFY tenant_id INT NOT NULL;

ALTER TABLE stock_alerts ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE stock_alerts SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE stock_alerts MODIFY tenant_id INT NOT NULL;

ALTER TABLE discount_schemes ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE discount_schemes SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE discount_schemes MODIFY tenant_id INT NOT NULL;

ALTER TABLE discount_campaigns ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE discount_campaigns SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE discount_campaigns MODIFY tenant_id INT NOT NULL;

-- Vendite
ALTER TABLE sales ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE sales SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE sales MODIFY tenant_id INT NOT NULL;

ALTER TABLE sale_items ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE sale_items SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE sale_items MODIFY tenant_id INT NOT NULL;

ALTER TABLE sale_item_refunds ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE sale_item_refunds SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE sale_item_refunds MODIFY tenant_id INT NOT NULL;

-- Audit
ALTER TABLE audit_log ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE audit_log SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE audit_log MODIFY tenant_id INT NOT NULL;

-- Offerte
ALTER TABLE operator_offers ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE operator_offers SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE operator_offers MODIFY tenant_id INT NOT NULL;

-- Clienti e portale
ALTER TABLE customers ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE customers SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE customers MODIFY tenant_id INT NOT NULL;

ALTER TABLE customer_product_requests ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE customer_product_requests SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE customer_product_requests MODIFY tenant_id INT NOT NULL;

ALTER TABLE customer_portal_accounts ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE customer_portal_accounts SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE customer_portal_accounts MODIFY tenant_id INT NOT NULL;

ALTER TABLE customer_portal_sessions ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE customer_portal_sessions SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE customer_portal_sessions MODIFY tenant_id INT NOT NULL;

ALTER TABLE customer_support_requests ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE customer_support_requests SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE customer_support_requests MODIFY tenant_id INT NOT NULL;

ALTER TABLE customer_payments ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE customer_payments SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE customer_payments MODIFY tenant_id INT NOT NULL;

-- Magazzino prodotti
ALTER TABLE product_stock_movements ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE product_stock_movements SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE product_stock_movements MODIFY tenant_id INT NOT NULL;

ALTER TABLE product_stock_alerts ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE product_stock_alerts SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE product_stock_alerts MODIFY tenant_id INT NOT NULL;

-- Energia
ALTER TABLE energy_providers ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE energy_providers SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE energy_providers MODIFY tenant_id INT NOT NULL;

ALTER TABLE energy_offers ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE energy_offers SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE energy_offers MODIFY tenant_id INT NOT NULL;

ALTER TABLE energy_contracts ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE energy_contracts SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE energy_contracts MODIFY tenant_id INT NOT NULL;

ALTER TABLE energy_sim_requests ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE energy_sim_requests SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE energy_sim_requests MODIFY tenant_id INT NOT NULL;

-- Notifiche & import
ALTER TABLE system_notifications ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE system_notifications SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE system_notifications MODIFY tenant_id INT NOT NULL;

ALTER TABLE pda_imports ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE pda_imports SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE pda_imports MODIFY tenant_id INT NOT NULL;

-- Privacy
ALTER TABLE privacy_policies ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE privacy_policies SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE privacy_policies MODIFY tenant_id INT NOT NULL;

ALTER TABLE privacy_policy_acceptances ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE privacy_policy_acceptances SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE privacy_policy_acceptances MODIFY tenant_id INT NOT NULL;

-- SSO
ALTER TABLE sso_clients ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE sso_clients SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE sso_clients MODIFY tenant_id INT NOT NULL;

ALTER TABLE sso_auth_codes ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE sso_auth_codes SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE sso_auth_codes MODIFY tenant_id INT NOT NULL;

ALTER TABLE sso_tokens ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE sso_tokens SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE sso_tokens MODIFY tenant_id INT NOT NULL;

-- MFA
ALTER TABLE user_mfa_recovery_codes ADD COLUMN tenant_id INT NULL AFTER id;
UPDATE user_mfa_recovery_codes SET tenant_id = 1 WHERE tenant_id IS NULL;
ALTER TABLE user_mfa_recovery_codes MODIFY tenant_id INT NOT NULL;
