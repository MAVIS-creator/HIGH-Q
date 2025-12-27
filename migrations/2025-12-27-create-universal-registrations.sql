-- Create universal_registrations table for new wizard
CREATE TABLE IF NOT EXISTS universal_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_type VARCHAR(50) NOT NULL,
  first_name VARCHAR(150) NOT NULL,
  last_name VARCHAR(150) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  payment_reference VARCHAR(100) DEFAULT NULL,
  payment_status VARCHAR(50) DEFAULT 'pending',
  amount DECIMAL(12,2) DEFAULT 0.00,
  payment_method VARCHAR(50) DEFAULT 'online',
  payload JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_program_status (program_type, status),
  INDEX idx_payment_ref (payment_reference),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
