<?php
require 'public/config/db.php';

echo "Creating missing tables...\n";

// Create site_settings
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  site_name VARCHAR(255) DEFAULT NULL,
  tagline VARCHAR(255) DEFAULT NULL,
  logo_url VARCHAR(1024) DEFAULT NULL,
  vision TEXT DEFAULT NULL,
  about TEXT DEFAULT NULL,
  contact_phone VARCHAR(64) DEFAULT NULL,
  contact_email VARCHAR(255) DEFAULT NULL,
  contact_address TEXT DEFAULT NULL,
  contact_facebook VARCHAR(512) DEFAULT NULL,
  contact_twitter VARCHAR(512) DEFAULT NULL,
  contact_instagram VARCHAR(512) DEFAULT NULL,
  contact_tiktok VARCHAR(512) DEFAULT NULL,
  maintenance TINYINT(1) DEFAULT 0,
  registration TINYINT(1) DEFAULT 1,
  email_verification TINYINT(1) DEFAULT 1,
  two_factor TINYINT(1) DEFAULT 0,
  comment_moderation TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

echo "✓ site_settings created\n";

// Create course_features
$pdo->exec("CREATE TABLE IF NOT EXISTS course_features (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  feature_text VARCHAR(255),
  position INT DEFAULT 0
)");

echo "✓ course_features created\n";
