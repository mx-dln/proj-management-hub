ALTER TABLE users
    ADD COLUMN registration_status ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved' AFTER is_active,
    ADD COLUMN registration_reviewed_by INT NULL AFTER registration_status,
    ADD COLUMN registration_reviewed_at DATETIME NULL AFTER registration_reviewed_by,
    ADD COLUMN registration_rejection_reason VARCHAR(500) NULL AFTER registration_reviewed_at,
    ADD KEY idx_users_registration_status (registration_status),
    ADD CONSTRAINT fk_users_registration_reviewer FOREIGN KEY (registration_reviewed_by) REFERENCES users(id) ON DELETE SET NULL;
