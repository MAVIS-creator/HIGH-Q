-- Migration: create ip_logs and mac_blocklist tables
CREATE TABLE IF NOT EXISTS `ip_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `path` VARCHAR(1024) DEFAULT NULL,
  `referer` VARCHAR(1024) DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `headers` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`ip`),
  INDEX (`user_id`),
  INDEX (`created_at`)
);

CREATE TABLE IF NOT EXISTS `mac_blocklist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mac` VARCHAR(128) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`mac`),
  INDEX (`enabled`)
);

-- Optional helper table to record blocked_ip actions (if you prefer to block IPs as fallback)
CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`ip`)
);
