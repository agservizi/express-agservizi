ALTER TABLE pda_imports
    ADD COLUMN file_hash CHAR(64) NULL AFTER stored_path,
    ADD COLUMN contract_date DATE NULL AFTER file_hash,
    ADD COLUMN template_key VARCHAR(120) NULL AFTER contract_date,
    ADD COLUMN ocr_used TINYINT(1) NOT NULL DEFAULT 0 AFTER template_key,
    ADD COLUMN ocr_text LONGTEXT NULL AFTER raw_text,
    ADD COLUMN warnings LONGTEXT NULL AFTER ocr_text,
    ADD COLUMN errors LONGTEXT NULL AFTER warnings,
    ADD COLUMN preview_payload LONGTEXT NULL AFTER errors,
    MODIFY COLUMN status ENUM('Preview','Processed','Failed','Duplicate') NOT NULL DEFAULT 'Processed';

CREATE INDEX idx_pda_imports_hash ON pda_imports (file_hash);
CREATE INDEX idx_pda_imports_contract_date ON pda_imports (contract_date);
CREATE INDEX idx_pda_imports_template ON pda_imports (template_key);
