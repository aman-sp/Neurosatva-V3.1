<?php
// One-time migration script — run with: php migrate.php
$host = 'iriguchi.proxy.rlwy.net';
$port = 31459;
$db   = 'railway';
$user = 'root';
$pass = 'TymBEhXFKKRNrgvRNVHJEEuonTnepMhe';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ Connected to Railway MySQL!\n\n";

    $tables = [];

    $tables[] = "CREATE TABLE IF NOT EXISTS admins (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        phone VARCHAR(20) NULL,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM('active','deactivated') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS tutors (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        personal_email VARCHAR(190) NULL,
        phone VARCHAR(20) NULL,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM('active','deactivated') NOT NULL DEFAULT 'active',
        created_by BIGINT UNSIGNED NULL,
        school_name VARCHAR(160) NULL,
        gender VARCHAR(40) NULL,
        official_gmail VARCHAR(190) NULL,
        gmail_verified TINYINT(1) NOT NULL DEFAULT 0,
        gmail_verified_at TIMESTAMP NULL,
        gmail_verification_token VARCHAR(128) NULL,
        gmail_otp_hash VARCHAR(255) NULL,
        gmail_otp_expires_at TIMESTAMP NULL,
        gmail_otp_attempts INT NOT NULL DEFAULT 0,
        gmail_updated_at TIMESTAMP NULL,
        first_login_completed TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_tutors_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS tutor_registration_requests (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(20) NOT NULL DEFAULT '',
        school_name VARCHAR(160) NULL,
        gender VARCHAR(40) NULL,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        admin_remarks TEXT NULL,
        approved_by BIGINT UNSIGNED NULL,
        approved_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_tutor_requests_admin FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS videos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tutor_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL,
        email_subject VARCHAR(180) NULL,
        source_email VARCHAR(190) NULL,
        storage_path TEXT NULL,
        status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
        admin_remarks TEXT NULL,
        received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        verified_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_videos_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS video_verifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        video_id BIGINT UNSIGNED NOT NULL,
        admin_id BIGINT UNSIGNED NULL,
        status ENUM('pending','verified','rejected') NOT NULL,
        remarks TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_video_verifications_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
        CONSTRAINT fk_video_verifications_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS login_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role ENUM('admin','tutor') NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        email VARCHAR(190) NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS admin_actions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id BIGINT UNSIGNED NULL,
        action VARCHAR(80) NOT NULL,
        entity_type VARCHAR(80) NOT NULL,
        entity_id BIGINT UNSIGNED NULL,
        metadata JSON NOT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_admin_actions_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(160) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(80) NOT NULL,
        entity_type VARCHAR(80) NULL,
        entity_id BIGINT UNSIGNED NULL,
        link VARCHAR(255) NULL,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS modules (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        folder_name VARCHAR(160) NOT NULL UNIQUE,
        description TEXT NULL,
        video_name VARCHAR(255) NULL,
        thumbnail VARCHAR(255) NULL,
        config_path VARCHAR(255) NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        version INT UNSIGNED NOT NULL DEFAULT 1,
        created_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_modules_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS module_assignments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        module_id BIGINT UNSIGNED NOT NULL,
        tutor_id BIGINT UNSIGNED NOT NULL,
        assigned_by BIGINT UNSIGNED NULL,
        status ENUM('active','revoked') NOT NULL DEFAULT 'active',
        assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        UNIQUE KEY uq_module_tutor (module_id, tutor_id),
        CONSTRAINT fk_ma_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
        CONSTRAINT fk_ma_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
        CONSTRAINT fk_ma_assigned_by FOREIGN KEY (assigned_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $tables[] = "CREATE TABLE IF NOT EXISTS playback_session_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL,
        module_id BIGINT UNSIGNED NOT NULL,
        tutor_id BIGINT UNSIGNED NOT NULL,
        started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ended_at TIMESTAMP NULL,
        duration_seconds INT UNSIGNED NULL,
        completed TINYINT(1) NOT NULL DEFAULT 0,
        completion_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
        hardware_status ENUM('connected','failed','not_applicable') NOT NULL DEFAULT 'not_applicable',
        hardware_diagnostics JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        CONSTRAINT fk_psl_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
        CONSTRAINT fk_psl_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    foreach ($tables as $i => $sql) {
        $pdo->exec($sql);
        echo "✅ Table " . ($i + 1) . " created (or already exists)\n";
    }

    // Insert default admin
    $pdo->exec("INSERT INTO admins (name, email, password_hash, status)
        VALUES ('Neurosatva Admin', 'admin@neurosatva.local', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active')
        ON DUPLICATE KEY UPDATE email = email");
    echo "\n✅ Admin account created!\n";
    echo "\n🎉 Database setup complete!\n";
    echo "   Email:    admin@neurosatva.local\n";
    echo "   Password: password\n";
    echo "\nLogin at: https://neurosatva-v3-1.vercel.app/admin/login\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
