-- 20260208_add_checkout_billing_cycle.sql
-- Aggiunge billing cycle e subscription id alle richieste checkout.

ALTER TABLE tenant_checkout_requests
  ADD COLUMN billing_cycle VARCHAR(16) NOT NULL DEFAULT 'annual' AFTER plan_key,
  ADD COLUMN stripe_subscription_id VARCHAR(255) NULL AFTER stripe_payment_intent_id;
