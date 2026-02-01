UPDATE privacy_policies
SET is_active = 0
WHERE tenant_id = 1;

UPDATE privacy_policies
SET tenant_id = 1,
    is_active = 1
WHERE version = '2026.02';
