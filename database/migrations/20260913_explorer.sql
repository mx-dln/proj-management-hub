CREATE TABLE IF NOT EXISTS explorer_nodes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 parent_id BIGINT UNSIGNED NULL,
 name VARCHAR(255) NOT NULL,
 kind ENUM('project','program','component_group','activity_group','component','activity','folder','file') NOT NULL,
 entity_id INT NULL,
 original_name VARCHAR(255) NULL,
 path VARCHAR(700) NULL,
 mime_type VARCHAR(150) NULL,
 extension VARCHAR(30) NULL,
 size BIGINT UNSIGNED NULL,
 created_by INT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 deleted_at DATETIME NULL,
 INDEX explorer_parent (parent_id, deleted_at),
 INDEX explorer_entity (kind, entity_id),
 CONSTRAINT explorer_parent_fk FOREIGN KEY (parent_id) REFERENCES explorer_nodes(id),
 CONSTRAINT explorer_owner_fk FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS explorer_migrations (
 version VARCHAR(100) NOT NULL PRIMARY KEY,
 applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
