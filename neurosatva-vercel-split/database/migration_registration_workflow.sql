USE neurosatva;

ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS school_name VARCHAR(160) NULL AFTER created_by,
    ADD COLUMN IF NOT EXISTS gender VARCHAR(40) NULL AFTER school_name,
    ADD COLUMN IF NOT EXISTS official_gmail VARCHAR(190) NULL AFTER gender,
    ADD COLUMN IF NOT EXISTS gmail_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER official_gmail,
    ADD COLUMN IF NOT EXISTS gmail_verified_at TIMESTAMP NULL AFTER gmail_verified,
    ADD COLUMN IF NOT EXISTS gmail_verification_token VARCHAR(128) NULL AFTER gmail_verified_at,
    ADD COLUMN IF NOT EXISTS gmail_updated_at TIMESTAMP NULL AFTER gmail_verification_token,
    ADD COLUMN IF NOT EXISTS first_login_completed TINYINT(1) NOT NULL DEFAULT 0 AFTER gmail_updated_at;

CREATE TABLE IF NOT EXISTS tutor_registration_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    school_name VARCHAR(160) NOT NULL,
    gender VARCHAR(40) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    admin_remarks TEXT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tutor_requests_admin FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_tutors_official_gmail ON tutors(official_gmail);
CREATE INDEX idx_tutor_requests_status ON tutor_registration_requests(status);
CREATE INDEX idx_tutor_requests_email ON tutor_registration_requests(email);
