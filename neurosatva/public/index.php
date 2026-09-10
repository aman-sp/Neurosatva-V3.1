<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';
require dirname(__DIR__) . '/app/Core/helpers.php';
require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Core/Session.php';
require dirname(__DIR__) . '/app/Core/Auth.php';
require dirname(__DIR__) . '/app/Core/Csrf.php';
require dirname(__DIR__) . '/app/Core/Router.php';
require dirname(__DIR__) . '/app/Core/R2Client.php';

foreach (glob(dirname(__DIR__) . '/app/Models/*.php') as $file) {
    require $file;
}
foreach (glob(dirname(__DIR__) . '/app/Controllers/*.php') as $file) {
    require $file;
}

Session::start();

if (app_config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$router = new Router();

$router->get('/', [AuthController::class, 'adminLogin']);
$router->get('/admin/login', [AuthController::class, 'adminLogin']);
$router->post('/admin/login', [AuthController::class, 'authenticateAdmin']);
$router->get('/tutor/login', [AuthController::class, 'tutorLogin']);
$router->post('/tutor/login', [AuthController::class, 'authenticateTutor']);
$router->get('/tutor/register', [AuthController::class, 'tutorRegister']);
$router->post('/tutor/register', [AuthController::class, 'submitTutorRegistration']);
$router->get('/tutor/register/success', [AuthController::class, 'tutorRegistrationSuccess']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/tutors', [AdminController::class, 'tutors']);
$router->get('/admin/registration-requests', [AdminController::class, 'registrationRequests']);
$router->get('/admin/registration-requests/view', [AdminController::class, 'viewRegistrationRequest']);
$router->post('/admin/registration-requests/approve', [AdminController::class, 'approveRegistrationRequest']);
$router->post('/admin/registration-requests/reject', [AdminController::class, 'rejectRegistrationRequest']);
$router->get('/admin/tutors/edit', [AdminController::class, 'editTutor']);
$router->post('/admin/tutors/edit', [AdminController::class, 'updateTutor']);
$router->post('/admin/tutors/delete', [AdminController::class, 'deleteTutor']);
$router->get('/admin/videos', [AdminController::class, 'videos']);
$router->post('/admin/videos', [AdminController::class, 'storeVideo']);
$router->post('/admin/videos/verify', [AdminController::class, 'verifyVideo']);
$router->get('/admin/profile', [AdminController::class, 'profile']);
$router->post('/admin/profile', [AdminController::class, 'updateProfile']);

$router->get('/tutor/dashboard', [TutorController::class, 'dashboard']);
$router->get('/tutor/videos', [TutorController::class, 'videos']);
$router->get('/tutor/instructions', [TutorController::class, 'instructions']);
$router->post('/tutor/instructions', [TutorController::class, 'submitVideoLink']);
$router->get('/tutor/profile', [TutorController::class, 'profile']);
$router->get('/tutor/official-gmail/setup', [TutorController::class, 'officialGmailSetup']);
$router->post('/tutor/official-gmail', [TutorController::class, 'saveOfficialGmail']);
$router->post('/tutor/official-gmail/verify-otp', [TutorController::class, 'verifyOfficialGmailOtp']);

// === Module Management (Admin) ===
$router->get('/admin/vault', [ModuleController::class, 'vault']);
$router->get('/admin/vault/create', [ModuleController::class, 'createForm']);
$router->post('/admin/vault', [ModuleController::class, 'store']);
$router->get('/admin/vault/edit', [ModuleController::class, 'editForm']);
$router->post('/admin/vault/update', [ModuleController::class, 'update']);
$router->post('/admin/vault/delete', [ModuleController::class, 'delete']);
$router->get('/admin/vault/test', [ModuleController::class, 'test']);
$router->get('/admin/assign', [ModuleController::class, 'assignForm']);
$router->post('/admin/assign', [ModuleController::class, 'assign']);
$router->get('/admin/assignments', [ModuleController::class, 'assignments']);
$router->post('/admin/assignments/revoke', [ModuleController::class, 'revokeAssignment']);

// === Tutor Digital Vault ===
$router->get('/tutor/vault', [TutorVaultController::class, 'vault']);
$router->get('/tutor/vault/play', [TutorVaultController::class, 'play']);

// === JSON API ===
$router->get('/api/modules', [ApiController::class, 'modules']);
$router->get('/api/tutor/modules', [ApiController::class, 'tutorModules']);
$router->get('/api/tutor/module', [ApiController::class, 'tutorModule']);
$router->get('/api/admin/module', [ApiController::class, 'adminModule']);
$router->post('/api/runtime/start', [ApiController::class, 'runtimeStart']);
$router->post('/api/runtime/end', [ApiController::class, 'runtimeEnd']);
$router->post('/api/modules/test', [ApiController::class, 'testModule']);
$router->post('/api/r2/presign', [ApiController::class, 'r2Presign']);

// === Secure Module File Server ===
$router->get('/storage-serve/modules', [ApiController::class, 'serveModuleFile']);

// === One-Click Database Setup & Migrations ===
$router->get('/setup-database', function() {
    try {
        $db = Database::connection();

        $db->exec("CREATE TABLE IF NOT EXISTS admins (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(20) NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active', 'deactivated') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS tutors (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            personal_email VARCHAR(190) NULL,
            phone VARCHAR(20) NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active', 'deactivated') NOT NULL DEFAULT 'active',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS tutor_registration_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(20) NOT NULL DEFAULT '',
            school_name VARCHAR(160) NULL,
            gender VARCHAR(40) NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
            admin_remarks TEXT NULL,
            approved_by BIGINT UNSIGNED NULL,
            approved_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_tutor_requests_admin FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS videos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tutor_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            email_subject VARCHAR(180) NULL,
            source_email VARCHAR(190) NULL,
            storage_path TEXT NULL,
            status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
            admin_remarks TEXT NULL,
            received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_videos_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS video_verifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            video_id BIGINT UNSIGNED NOT NULL,
            admin_id BIGINT UNSIGNED NULL,
            status ENUM('pending', 'verified', 'rejected') NOT NULL,
            remarks TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_video_verifications_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
            CONSTRAINT fk_video_verifications_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS login_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role ENUM('admin', 'tutor') NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            email VARCHAR(190) NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS admin_actions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            metadata JSON NOT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_admin_actions_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NULL,
            entity_id BIGINT UNSIGNED NULL,
            link VARCHAR(255) NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS modules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            folder_name VARCHAR(160) NOT NULL UNIQUE,
            description TEXT NULL,
            video_name VARCHAR(255) NULL,
            thumbnail VARCHAR(255) NULL,
            config_path VARCHAR(255) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            version INT UNSIGNED NOT NULL DEFAULT 1,
            created_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_modules_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS module_assignments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_id BIGINT UNSIGNED NOT NULL,
            tutor_id BIGINT UNSIGNED NOT NULL,
            assigned_by BIGINT UNSIGNED NULL,
            status ENUM('active', 'revoked') NOT NULL DEFAULT 'active',
            assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            revoked_at TIMESTAMP NULL,
            UNIQUE KEY uq_module_tutor (module_id, tutor_id),
            CONSTRAINT fk_ma_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            CONSTRAINT fk_ma_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
            CONSTRAINT fk_ma_assigned_by FOREIGN KEY (assigned_by) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS playback_session_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            module_id BIGINT UNSIGNED NOT NULL,
            tutor_id BIGINT UNSIGNED NOT NULL,
            started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ended_at TIMESTAMP NULL,
            duration_seconds INT UNSIGNED NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            completion_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
            hardware_status ENUM('connected', 'failed', 'not_applicable') NOT NULL DEFAULT 'not_applicable',
            hardware_diagnostics JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            CONSTRAINT fk_psl_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            CONSTRAINT fk_psl_tutor FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("INSERT INTO admins (name, email, password_hash, status)
            VALUES ('Neurosatva Admin', 'admin@neurosatva.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active')
            ON DUPLICATE KEY UPDATE email = email;");

        header('Content-Type: text/html; charset=utf-8');
        echo '<div style=\"font-family:sans-serif; max-width:600px; margin:60px auto; padding:30px; background:#f0fdf4; border:2px solid #22c55e; border-radius:12px; text-align:center;\">
            <h2 style=\"color:#15803d; margin-top:0;\">🎉 Database Initialized Successfully!</h2>
            <p style=\"color:#374151; font-size:16px;\">All 11 tables and the Admin account have been created in your Railway database.</p>
            <div style=\"background:#fff; padding:15px; border-radius:8px; display:inline-block; text-align:left; margin:15px 0; border:1px solid #e5e7eb;\">
                <p style=\"margin:4px 0;\"><strong>Email:</strong> <code>admin@neurosatva.local</code></p>
                <p style=\"margin:4px 0;\"><strong>Password:</strong> <code>password</code></p>
            </div>
            <br>
            <a href=\"/admin/login\" style=\"display:inline-block; background:#16a34a; color:#fff; padding:12px 24px; text-decoration:none; border-radius:8px; font-weight:bold;\">Go to Admin Login →</a>
        </div>';
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo '<h1>Database Setup Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        exit;
    }
});

try {
    $router->dispatch(request_method(), parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
} catch (PDOException $exception) {
    http_response_code(500);
    if (app_config('debug')) {
        exit('Database connection error: ' . e($exception->getMessage()));
    }
    view('errors/database', [
        'title' => 'Database Setup Required',
        'dbError' => $exception->getMessage(),
        'dbHost' => env('DB_HOST', '127.0.0.1'),
        'dbName' => env('DB_NAME', 'neurosatva'),
        'dbUser' => env('DB_USER', 'root'),
    ], 'auth');
} catch (Throwable $exception) {
    http_response_code(500);
    if (app_config('debug')) {
        exit('Application error: ' . e($exception->getMessage()));
    }
    view('errors/404', ['title' => 'Application Error'], 'auth');
}
