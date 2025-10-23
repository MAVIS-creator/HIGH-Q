-- Add new columns to payments table for POST UTME
ALTER TABLE payments ADD form_fee_paid BIT DEFAULT 0;
ALTER TABLE payments ADD tutor_fee_paid BIT DEFAULT 0;
ALTER TABLE payments ADD registration_type VARCHAR(10) DEFAULT 'regular';

-- Create post_utme_registrations table
CREATE TABLE post_utme_registrations (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT,
    status VARCHAR(20) DEFAULT 'pending',
    
    -- Personal Information
    institution VARCHAR(255),
    first_name VARCHAR(100),
    surname VARCHAR(100),
    other_name VARCHAR(100),
    gender VARCHAR(10) CHECK (gender IN ('male', 'female')),
    address NVARCHAR(MAX),
    parent_phone VARCHAR(20),
    email VARCHAR(255),
    nin_number VARCHAR(50),
    state_of_origin VARCHAR(100),
    local_government VARCHAR(100),
    place_of_birth VARCHAR(255),
    marital_status VARCHAR(50),
    disability NVARCHAR(MAX),
    nationality VARCHAR(100),
    religion VARCHAR(100),
    mode_of_entry VARCHAR(100),

    -- JAMB Details
    jamb_registration_number VARCHAR(50),
    jamb_score INT,
    jamb_subjects NVARCHAR(MAX), -- JSON array of subjects and scores

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

    -- O'Level Details
    exam_type ENUM('WAEC', 'NECO', 'GCE'),
    candidate_name VARCHAR(255),
    exam_number VARCHAR(50),
    exam_year_month VARCHAR(20),
    olevel_results NVARCHAR(MAX), -- JSON array of subjects and grades
    
    -- System Fields
    passport_photo VARCHAR(255), -- path to uploaded passport
    payment_status VARCHAR(10) CHECK (payment_status IN ('pending', 'partial', 'completed')) DEFAULT 'pending',
    form_fee_paid BIT DEFAULT 0,
    tutor_fee_paid BIT DEFAULT 0,
    created_at DATETIME2 DEFAULT GETDATE(),
    updated_at DATETIME2 DEFAULT GETDATE(),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);