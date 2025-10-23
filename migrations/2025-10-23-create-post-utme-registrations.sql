CREATE TABLE post_utme_registrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name_of_institution VARCHAR(255),
    first_name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    other_name VARCHAR(255),
    gender ENUM('male', 'female'),
    address TEXT,
    parent_phone VARCHAR(20),
    email_address VARCHAR(255) NOT NULL,
    nin_number VARCHAR(50),
    local_government VARCHAR(255),
    place_of_birth VARCHAR(255),
    nationality VARCHAR(100),
    mode_of_entry VARCHAR(100),
    state_of_origin VARCHAR(100),
    marital_status VARCHAR(50),
    disability VARCHAR(255),
    religion VARCHAR(100),
    
    -- JAMB Details
    jamb_registration_number VARCHAR(50),
    jamb_score VARCHAR(50),
    jamb_subjects JSON,
    
    -- Course Details
    course_first_choice VARCHAR(255),
    course_second_choice VARCHAR(255),
    institution_first_choice VARCHAR(255),
    
    -- Parent Details
    father_name VARCHAR(255),
    father_phone VARCHAR(20),
    father_email VARCHAR(255),
    father_occupation VARCHAR(255),
    mother_name VARCHAR(255),
    mother_phone VARCHAR(20),
    mother_occupation VARCHAR(255),
    
    -- Education History
    primary_school_name VARCHAR(255),
    primary_year_ended VARCHAR(4),
    secondary_school_name VARCHAR(255),
    secondary_year_ended VARCHAR(4),
    
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
    exam_number VARCHAR(50),
    exam_year_month VARCHAR(20),
    olevel_subjects JSON,
    waec_token VARCHAR(255),
    waec_serial_no VARCHAR(255),
    
    -- System Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    form_fee_paid BOOLEAN DEFAULT FALSE,
    tutor_fee_paid BOOLEAN DEFAULT FALSE,
    total_amount_paid DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    
    PRIMARY KEY (id),
    UNIQUE KEY unique_email (email_address),
    UNIQUE KEY unique_jamb_reg (jamb_registration_number),
    UNIQUE KEY unique_payment_ref (payment_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;