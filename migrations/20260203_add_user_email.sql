-- 20260203_add_user_email.sql
-- Aggiunge email agli utenti.

ALTER TABLE users
  ADD COLUMN email VARCHAR(150) NULL AFTER username,
  ADD UNIQUE KEY idx_users_email (email);
