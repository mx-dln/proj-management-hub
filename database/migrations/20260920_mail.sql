CREATE TABLE IF NOT EXISTS email_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    notification_id INT NOT NULL,
    category VARCHAR(30) NOT NULL,
    status ENUM('pending', 'sending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_error VARCHAR(300) DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_notification (notification_id),
    KEY idx_email_delivery (status, available_at),
    CONSTRAINT fk_email_notification FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
