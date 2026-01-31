-- 20260205_add_license_terms_and_renewal_notices.sql
-- Aggiunge durata licenze e tracking preavvisi rinnovo.

ALTER TABLE licenses ADD COLUMN term_months INT NULL AFTER max_users;

ALTER TABLE tenant_licenses ADD COLUMN renewal_notice_sent_at DATETIME NULL AFTER revoked_at;
ALTER TABLE tenant_licenses ADD COLUMN renewal_paid_at DATETIME NULL AFTER renewal_notice_sent_at;
