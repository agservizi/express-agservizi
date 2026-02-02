-- 20260206_add_tenant_checkout_requests.sql
-- Registra richieste checkout Stripe per l'attivazione tenant.

CREATE TABLE tenant_checkout_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_key VARCHAR(32) NOT NULL,
    tenant_name VARCHAR(190) NOT NULL,
    tenant_slug VARCHAR(190) NOT NULL,
    tenant_email VARCHAR(190) NOT NULL,
    tenant_phone VARCHAR(64) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    stripe_session_id VARCHAR(255) NULL,
    stripe_payment_intent_id VARCHAR(255) NULL,
    stripe_customer_email VARCHAR(190) NULL,
    tenant_id INT NULL,
    license_id INT NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    UNIQUE KEY uniq_tenant_checkout_session (stripe_session_id),
    KEY idx_tenant_checkout_status (status),
    KEY idx_tenant_checkout_slug (tenant_slug),
    KEY idx_tenant_checkout_email (tenant_email)
);
