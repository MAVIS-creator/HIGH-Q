-- Create post-utme registrations table
CREATE TABLE post_utme_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    institution_name VARCHAR(255),
    first_name VARCHAR(100),
    surname VARCHAR(100),
    other_name VARCHAR(100),
    gender ENUM('male', 'female'),
    address TEXT,
    parents_phone VARCHAR(20),
    email VARCHAR(255),
    nin_number VARCHAR(50),
    state_of_origin VARCHAR(100),
    local_government VARCHAR(100),
    place_of_birth VARCHAR(255),
    marital_status VARCHAR(50),
    disability TEXT,
    nationality VARCHAR(100),
    religion VARCHAR(100),
    mode_of_entry VARCHAR(100),
    
    -- JAMB Details
    jamb_registration_number VARCHAR(50),
    jamb_score INT,
    jamb_subjects TEXT,
    jamb_grades TEXT,
    
    -- Course Details
    course_first_choice VARCHAR(255),
    course_second_choice VARCHAR(255),
    institution_first_choice VARCHAR(255),
    
    -- Parent Details
    fathers_name VARCHAR(255),
    fathers_phone VARCHAR(20),
    mothers_name VARCHAR(255),
    mothers_phone VARCHAR(20),
    parent_email VARCHAR(255),
    fathers_occupation VARCHAR(255),
    mothers_occupation VARCHAR(255),
    
    -- Education History
    primary_school VARCHAR(255),
    primary_year_ended YEAR,
    secondary_school VARCHAR(255),
    secondary_year_ended YEAR,
    
    -- Sponsor Details
    sponsor_name VARCHAR(255),
    sponsor_address TEXT,
    sponsor_email VARCHAR(255),
    sponsor_phone VARCHAR(20),
    sponsor_relationship VARCHAR(100),
    
    -- Next of Kin Details
    next_of_kin_name VARCHAR(255),
    next_of_kin_address TEXT,
    next_of_kin_email VARCHAR(255),
    next_of_kin_phone VARCHAR(20),
    next_of_kin_relationship VARCHAR(100),
    
    -- O'Level Details
    exam_type ENUM('WAEC', 'NECO', 'GCE'),
    candidate_name VARCHAR(255),
    exam_number VARCHAR(100),
    exam_year_month VARCHAR(20),
    olevel_subjects TEXT,
    olevel_grades TEXT,
    waec_token VARCHAR(100),
    waec_serial_no VARCHAR(100),
    
    -- Document Uploads
    passport_photo VARCHAR(255),
    
    -- Form Status and Payment
    form_fee_paid BOOLEAN DEFAULT FALSE,
    tutor_fee_paid BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Timestamps and Verification
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    verification_status ENUM('unverified', 'verified', 'rejected') DEFAULT 'unverified',
    
    -- Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_jamb_reg (jamb_registration_number),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
