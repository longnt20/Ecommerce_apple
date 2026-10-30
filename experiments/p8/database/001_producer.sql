CREATE TABLE p8_schema_migrations (
 role VARCHAR(16) NOT NULL, version INT NOT NULL, sha256 CHAR(64) NOT NULL,
 applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (role, version)
) ENGINE=InnoDB;
CREATE TABLE p8_outbox_streams (
 product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY, version BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;
CREATE TABLE p8_outbox_messages (
 id CHAR(36) NOT NULL PRIMARY KEY, product_id BIGINT UNSIGNED NOT NULL,
 aggregate_version BIGINT UNSIGNED NOT NULL, envelope JSON NOT NULL,
 created_at DATETIME(6) NOT NULL, published_at DATETIME(6) NULL,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY p8_stream_version_unique (product_id, aggregate_version),
 KEY p8_pending (published_at, created_at, id)
) ENGINE=InnoDB;
CREATE TABLE p8_publish_attempts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, event_id CHAR(36) NOT NULL,
 outcome VARCHAR(32) NOT NULL, recorded_at DATETIME(6) NOT NULL,
 KEY p8_publish_event (event_id)
) ENGINE=InnoDB;
