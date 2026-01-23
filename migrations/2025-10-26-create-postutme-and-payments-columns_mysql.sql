-- Ensure payments columns exist (idempotent)
ALTER TABLE payments ADD COLUMN IF NOT EXISTS form_fee_paid TINYINT(1) DEFAULT 0;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS tutor_fee_paid TINYINT(1) DEFAULT 0;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS registration_type VARCHAR(20) DEFAULT 'regular';

-- Ensure post_utme_registrations table exists (minimal shape; earlier migration defines full schema)
CREATE TABLE IF NOT EXISTS post_utme_registrations (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  exam_number VARCHAR(50) DEFAULT NULL,
  exam_year_month VARCHAR(20) DEFAULT NULL,
  payment_status VARCHAR(20) DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_postutme_user2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing WAEC columns safely
ALTER TABLE post_utme_registrations ADD COLUMN IF NOT EXISTS waec_token VARCHAR(100) DEFAULT NULL;
ALTER TABLE post_utme_registrations ADD COLUMN IF NOT EXISTS waec_serial VARCHAR(100) DEFAULT NULL;
