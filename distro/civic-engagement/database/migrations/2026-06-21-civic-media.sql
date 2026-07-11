-- Apply once to existing Civic Platform installations.
-- Replace wp_ with the active WordPress table prefix when necessary.

CREATE TABLE IF NOT EXISTS wp_civic_media (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    attachment_id BIGINT UNSIGNED NOT NULL,
    caption TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entity_sort (entity_type, entity_id, sort_order),
    KEY idx_attachment_id (attachment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
