-- Neurosattva Digital Module Management Platform
-- Run against the neurosatva database

USE neurosatva;

-- Module metadata table (timeline lives in config.json, NOT here)
CREATE TABLE IF NOT EXISTS modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    folder_name VARCHAR(200) NOT NULL UNIQUE,
    description TEXT NULL,
    video_name VARCHAR(255) NULL,
    thumbnail VARCHAR(255) NULL,
    config_path VARCHAR(500) NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_modules_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tutor-to-module assignments
CREATE TABLE IF NOT EXISTS module_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    esp32_ip VARCHAR(45) NOT NULL,
    remaining_plays INT NOT NULL DEFAULT 1,
    total_plays INT NOT NULL DEFAULT 1,
    expiry_date DATE NULL,
    status ENUM('active','expired','revoked') NOT NULL DEFAULT 'active',
    assigned_by BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignments_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_admin FOREIGN KEY (assigned_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playback session logs
CREATE TABLE IF NOT EXISTS playback_session_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id BIGINT UNSIGNED NOT NULL,
    tutor_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    device_ip VARCHAR(45) NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    duration_seconds INT NULL,
    completed TINYINT(1) NOT NULL DEFAULT 0,
    error_log TEXT NULL,
    CONSTRAINT fk_logs_assignment FOREIGN KEY (assignment_id) REFERENCES module_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_modules_status ON modules(status);
CREATE INDEX IF NOT EXISTS idx_modules_folder ON modules(folder_name);
CREATE INDEX IF NOT EXISTS idx_assignments_tutor ON module_assignments(tutor_id);
CREATE INDEX IF NOT EXISTS idx_assignments_module ON module_assignments(module_id);
CREATE INDEX IF NOT EXISTS idx_assignments_status ON module_assignments(status);
CREATE INDEX IF NOT EXISTS idx_logs_assignment ON playback_session_logs(assignment_id);
