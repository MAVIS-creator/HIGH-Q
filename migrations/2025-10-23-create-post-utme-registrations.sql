CREATE TABLE post_utme_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    status VARCHAR(20) DEFAULT 'pending',
    
    -- Personal Information
    institution VARCHAR(255),
    first_name VARCHAR(100),
    surname VARCHAR(100),
    other_name VARCHAR(100),
    gender ENUM('male', 'female'),
    address TEXT,
    parent_phone VARCHAR(20),
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
    jamb_subjects TEXT, -- JSON array of subjects and scores

    -- Course Preferences
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

    -- Next of Kin
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
    olevel_results TEXT, -- JSON array of subjects and grades
    
    -- Token Details
    waec_token VARCHAR(100),
    waec_serial_no VARCHAR(100),

    -- System Fields
    passport_photo VARCHAR(255), -- path to uploaded passport
    payment_status ENUM('pending', 'partial', 'completed') DEFAULT 'pending',
    form_fee_paid BOOLEAN DEFAULT FALSE,
    tutor_fee_paid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
