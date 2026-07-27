USE neurosatva;

CREATE TABLE IF NOT EXISTS modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    folder_name VARCHAR(220) NOT NULL UNIQUE,
    video_name VARCHAR(220) NOT NULL,
    thumbnail_path VARCHAR(255) NULL,
    config_path VARCHAR(255) NOT NULL,
    audio_count INT UNSIGNED NOT NULL DEFAULT 0,
    scene_count INT UNSIGNED NOT NULL DEFAULT 0,
    version VARCHAR(30) NOT NULL DEFAULT '1.0',
    status ENUM('active', 'draft', 'archived') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_modules_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    esp32_ip VARCHAR(45) NOT NULL,
    remaining_plays INT UNSIGNED NOT NULL,
    expiry_date DATE NULL,
    assigned_by BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'revoked', 'expired') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assignment_tutor_module_active (tutor_id, module_id, status),
    CONSTRAINT fk_assignments_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_admin FOREIGN KEY (assigned_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_session_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id BIGINT UNSIGNED NOT NULL,
    start_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    duration INT UNSIGNED NULL,
    completed TINYINT(1) NOT NULL DEFAULT 0,
    errors TEXT NULL,
    device_ip VARCHAR(45) NULL,
    CONSTRAINT fk_session_logs_assignment FOREIGN KEY (assignment_id) REFERENCES module_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_modules_status ON modules(status);
CREATE INDEX idx_assignments_tutor ON module_assignments(tutor_id, status);
CREATE INDEX idx_session_logs_assignment ON module_session_logs(assignment_id);
