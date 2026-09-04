-- Attendance system database schema.
-- Run this script after selecting the attendancedb database.

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS college (
    college_id INT AUTO_INCREMENT PRIMARY KEY,
    college_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS program (
    program_id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    program_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_program_college
        FOREIGN KEY (college_id) REFERENCES college (college_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_program_college_id (college_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS section (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    section_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_section_program
        FOREIGN KEY (program_id) REFERENCES program (program_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_section_program_id (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_id INT NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    middlename VARCHAR(50) DEFAULT NULL,
    lastname VARCHAR(50) NOT NULL,
    name_ext VARCHAR(5) DEFAULT NULL,
    gender VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_faculty_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_faculty_section
        FOREIGN KEY (section_id) REFERENCES section (section_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY faculty_user_unique (user_id),
    INDEX idx_faculty_section_id (section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_id INT NOT NULL,
    school_id VARCHAR(10) DEFAULT NULL,
    firstname VARCHAR(50) NOT NULL,
    middlename VARCHAR(50) DEFAULT NULL,
    lastname VARCHAR(50) NOT NULL,
    name_ext VARCHAR(5) DEFAULT NULL,
    gender VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_student_section
        FOREIGN KEY (section_id) REFERENCES section (section_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY student_user_unique (user_id),
    INDEX idx_student_section_id (section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ojt_company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    contact_number VARCHAR(30) DEFAULT NULL,
    email_address VARCHAR(100) DEFAULT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_supervisors (
    supervisor_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    middlename VARCHAR(50) DEFAULT NULL,
    lastname VARCHAR(50) NOT NULL,
    name_ext VARCHAR(5) DEFAULT NULL,
    gender VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_company_supervisor_company
        FOREIGN KEY (company_id) REFERENCES ojt_company (company_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_company_supervisor_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ojt_student_company (
    student_company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    student_id INT NOT NULL,
    ojt_start_date DATE NOT NULL,
    ojt_end_date DATE NOT NULL,
    required_hours DECIMAL(7, 2) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ojt_requirement_company
        FOREIGN KEY (company_id) REFERENCES ojt_company (company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ojt_requirement_student
        FOREIGN KEY (student_id) REFERENCES student (student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY ojt_requirement_student_company_unique (student_id, company_id),
    INDEX idx_ojt_requirement_company_id (company_id),
    INDEX idx_ojt_requirement_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_company_id INT NOT NULL,
    attendance_date DATE NOT NULL,

    total_hours DECIMAL(5, 2) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_requirement
        FOREIGN KEY (student_company_id)
        REFERENCES ojt_student_company (student_company_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY attendance_date_unique (
        student_company_id,
        attendance_date
    ),

    INDEX idx_attendance_student_company_id (
        student_company_id
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_log (
    attendance_log_id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,

    attendance_type ENUM(
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out'
    ) NOT NULL,

    attendance_time TIME NOT NULL,

    status ENUM(
        'on_time',
        'late'
    ) NOT NULL DEFAULT 'on_time',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_log_attendance
        FOREIGN KEY (attendance_id)
        REFERENCES attendance(attendance_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY attendance_type_unique (
        attendance_id,
        attendance_type
    ),

    INDEX idx_attendance_log_attendance_id (
        attendance_id
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_evidence (
    attendance_evidence_id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_log_id INT NOT NULL,

    evidence_type ENUM('selfie') NOT NULL DEFAULT 'selfie',

    image_path VARCHAR(500) NOT NULL,

    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_evidence_log
        FOREIGN KEY (attendance_log_id)
        REFERENCES attendance_log(attendance_log_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY attendance_log_evidence_unique (
        attendance_log_id,
        evidence_type
    ),

    INDEX idx_attendance_evidence_log (
        attendance_log_id
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_corrections (
    correction_id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    requested_by INT NOT NULL,
    requested_time_in TIME DEFAULT NULL,
    requested_time_out TIME DEFAULT NULL,
    reason TEXT NOT NULL,
    proof_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_correction_attendance
        FOREIGN KEY (attendance_id) REFERENCES attendance (attendance_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attendance_correction_requester
        FOREIGN KEY (requested_by) REFERENCES student (student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attendance_correction_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_attendance_correction_attendance_id (attendance_id),
    INDEX idx_attendance_correction_requester (requested_by),
    INDEX idx_attendance_correction_reviewer (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ojt_company_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,

    company_id INT NOT NULL,

    morning_in TIME NOT NULL,
    morning_out TIME NOT NULL,

    afternoon_in TIME NOT NULL,
    afternoon_out TIME NOT NULL,

    grace_period_minutes INT NOT NULL DEFAULT 15,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_company_schedule_company
        FOREIGN KEY (company_id)
        REFERENCES ojt_company (company_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY company_schedule_unique (company_id),

    INDEX idx_company_schedule_company_id (company_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;