-- 20260207_add_tenant_vat_details.sql
-- Aggiunge dati fiscali per tenant e richieste checkout.

ALTER TABLE tenants
  ADD COLUMN vat_number VARCHAR(32) NULL AFTER contact_phone,
  ADD COLUMN company_country VARCHAR(2) NULL AFTER vat_number,
  ADD COLUMN company_name VARCHAR(190) NULL AFTER company_country,
  ADD COLUMN company_address VARCHAR(255) NULL AFTER company_name;

ALTER TABLE tenant_checkout_requests
  ADD COLUMN vat_number VARCHAR(32) NULL AFTER tenant_phone,
  ADD COLUMN company_country VARCHAR(2) NULL AFTER vat_number,
  ADD COLUMN company_name VARCHAR(190) NULL AFTER company_country,
  ADD COLUMN company_address VARCHAR(255) NULL AFTER company_name;
