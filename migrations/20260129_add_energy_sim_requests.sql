CREATE TABLE IF NOT EXISTS energy_sim_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_type VARCHAR(30) NOT NULL,
    contact_name VARCHAR(120) NOT NULL,
    contact_email VARCHAR(160) NULL,
    contact_phone VARCHAR(60) NULL,
    preferred_time VARCHAR(80) NULL,
    contact_note TEXT NULL,
    sim_payload JSON NULL,
    sim_summary VARCHAR(500) NULL,
    bill_file_path VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_energy_sim_requests_type (request_type),
    KEY idx_energy_sim_requests_created (created_at),
    KEY idx_energy_sim_requests_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
