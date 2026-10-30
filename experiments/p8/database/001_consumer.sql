CREATE TABLE p8_schema_migrations (
 role VARCHAR(16) NOT NULL, version INT NOT NULL, sha256 CHAR(64) NOT NULL,
 applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (role, version)
) ENGINE=InnoDB;
CREATE TABLE p8_catalog_cards (
 product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY, aggregate_version BIGINT UNSIGNED NOT NULL DEFAULT 0,
 name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, category_id BIGINT UNSIGNED NULL,
 listed BOOLEAN NOT NULL, sku_count INT UNSIGNED NOT NULL,
 min_price DECIMAL(12,2) NULL, max_price DECIMAL(12,2) NULL,
 last_event_id CHAR(36) NOT NULL, apply_count INT UNSIGNED NOT NULL DEFAULT 0,
 KEY p8_listed_product (listed, product_id)
) ENGINE=InnoDB;
CREATE TABLE p8_inbox (
 event_id CHAR(36) NOT NULL PRIMARY KEY, envelope_sha256 CHAR(64) NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL, aggregate_version BIGINT UNSIGNED NOT NULL,
 outcome VARCHAR(16) NOT NULL, consumed_at DATETIME(6) NOT NULL,
 UNIQUE KEY p8_inbox_stream_version (product_id, aggregate_version)
) ENGINE=InnoDB;
CREATE TABLE p8_delivery_audit (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, event_id VARCHAR(36) NOT NULL,
 outcome VARCHAR(32) NOT NULL, recorded_at DATETIME(6) NOT NULL,
 KEY p8_delivery_event (event_id)
) ENGINE=InnoDB;
CREATE TABLE p8_quarantine (
 envelope_sha256 CHAR(64) NOT NULL PRIMARY KEY, event_id VARCHAR(36) NOT NULL,
 reason_code VARCHAR(32) NOT NULL, attempts INT UNSIGNED NOT NULL DEFAULT 1,
 first_seen_at DATETIME(6) NOT NULL, last_seen_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;
