<?php

final class ApiController
{
    private function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireAdmin(): void
    {
        if (Auth::role() !== 'admin') {
            $this->json(['error' => 'Unauthorized'], 401);
        }
    }

    private function requireTutor(): void
    {
        if (Auth::role() !== 'tutor') {
            $this->json(['error' => 'Unauthorized'], 401);
        }
    }

    private function requireAdminOrTutor(): void
    {
        if (!Auth::check() || !in_array(Auth::role(), ['admin', 'tutor'], true)) {
            $this->json(['error' => 'Unauthorized'], 401);
        }
    }

    private function fetchWledJson(string $ip, string $endpoint, ?array $payload = null): array
    {
        $url = 'http://' . $ip . $endpoint;

        $context = null;
        if ($payload !== null) {
            $body = json_encode($payload);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAccept: application/json",
                    'content' => $body,
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
            ]);
        }

        $response = $context !== null ? @file_get_contents($url, false, $context) : @file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]));

        if ($response === false) {
            throw new RuntimeException('Unable to reach the WLED controller at ' . $ip . '.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('The WLED controller returned an invalid response.');
        }

        return $decoded;
    }

    // GET /api/modules — admin only, returns all modules
    public function modules(): void
    {
        $this->requireAdmin();
        $this->json(Module::all(input('search'), input('status') ?: null));
    }

    // GET /api/tutor/modules — tutor only, returns assigned modules
    public function tutorModules(): void
    {
        $this->requireTutor();
        $assignments = ModuleAssignment::allForTutor(Auth::id());
        foreach ($assignments as &$a) {
            $a['is_playable'] = ModuleAssignment::isPlayable($a);
        }
        $this->json($assignments);
    }

    // GET /api/tutor/module?id={id} — tutor only, returns config for assigned module
    public function tutorModule(): void
    {
        $this->requireTutor();
        $assignmentId = (int) input('id');
        $assignment = ModuleAssignment::findForTutor($assignmentId, Auth::id());
        if (!$assignment) {
            $this->json(['error' => 'Not found or not authorized'], 404);
        }
        $config = Module::getConfig((int) $assignment['module_id']);
        if (!$config) {
            $this->json(['error' => 'Module configuration missing'], 500);
        }
        // Build file URLs for video and audio
        $folderName = $assignment['folder_name'];
        $config['_video_url'] = path('/storage-serve/modules?folder=' . rawurlencode($folderName) . '&file=' . rawurlencode($config['video']));
        $config['_esp32_ip'] = $assignment['esp32_ip'];
        $config['_assignment_id'] = $assignmentId;
        // Audio URLs
        if (isset($config['timeline']) && is_array($config['timeline'])) {
            foreach ($config['timeline'] as &$scene) {
                if (!empty($scene['audio'])) {
                    $scene['_audio_url'] = path('/storage-serve/modules?folder=' . rawurlencode($folderName) . '&file=' . rawurlencode($scene['audio']));
                }
            }
        }
        $this->json($config);
    }

    // GET /api/admin/module?id={id}&ip={ip} — admin only, returns config for module test
    public function adminModule(): void
    {
        $this->requireAdmin();
        $moduleId = (int) input('id');
        $esp32Ip = trim(input('ip') ?? '');
        $module = Module::find($moduleId);
        if (!$module) {
            $this->json(['error' => 'Module not found'], 404);
        }
        $config = Module::getConfig($moduleId);
        if (!$config) {
            $this->json(['error' => 'Module configuration missing'], 500);
        }
        $folderName = $module['folder_name'];
        $config['_video_url'] = Module::getMediaUrl($folderName, $config['video'] ?? '');
        $config['_esp32_ip'] = $esp32Ip;
        $config['_module_id'] = $moduleId;
        $config['_test_mode'] = true;
        if (isset($config['timeline']) && is_array($config['timeline'])) {
            foreach ($config['timeline'] as &$scene) {
                if (!empty($scene['audio'])) {
                    $scene['_audio_url'] = Module::getMediaUrl($folderName, $scene['audio']);
                }
            }
        }
        $this->json($config);
    }

    public function wledInfo(): void
    {
        $this->requireAdminOrTutor();
        $ip = trim((string) input('ip'));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->json(['error' => 'Invalid IP address format.'], 422);
        }

        try {
            $payload = $this->fetchWledJson($ip, '/json/info');
            $ok = !empty($payload['name']) || !empty($payload['ver']);
            $this->json(['ok' => $ok, 'data' => $payload]);
        } catch (Throwable $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    public function wledState(): void
    {
        $this->requireAdminOrTutor();

        $rawInput = file_get_contents('php://input');
        $payload = $rawInput !== false && trim($rawInput) !== '' ? json_decode($rawInput, true) : null;
        if (!is_array($payload) || empty($payload['ip'])) {
            $payload = [
                'ip' => trim((string) ($_POST['ip'] ?? '')),
                'state' => json_decode((string) ($_POST['state'] ?? '{}'), true),
            ];
        }

        $ip = trim((string) ($payload['ip'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->json(['error' => 'Invalid IP address format.'], 422);
        }

        $state = is_array($payload['state'] ?? null) ? $payload['state'] : [];

        try {
            $result = $this->fetchWledJson($ip, '/json/state', $state);
            $this->json(['ok' => true, 'result' => $result]);
        } catch (Throwable $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // POST /api/runtime/start — tutor only, log session start
    public function runtimeStart(): void
    {
        $this->requireTutor();
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $deviceIp = $_POST['device_ip'] ?? '';

        $assignment = ModuleAssignment::findForTutor($assignmentId, Auth::id());
        if (!$assignment) {
            $this->json(['error' => 'Assignment not found or not authorized'], 403);
        }
        if (!ModuleAssignment::isPlayable($assignment)) {
            $this->json(['error' => 'Module is not playable (expired or no plays remaining)'], 403);
        }

        $logId = SessionLog::start($assignmentId, Auth::id(), (int) $assignment['module_id'], $deviceIp);
        $this->json(['success' => true, 'log_id' => $logId]);
    }

    // POST /api/runtime/end — tutor only, log session end and decrement plays
    public function runtimeEnd(): void
    {
        $this->requireTutor();
        $logId = (int) ($_POST['log_id'] ?? 0);
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $completed = !empty($_POST['completed']);
        $error = $_POST['error'] ?? null;

        // Verify tutor owns this assignment
        $assignment = ModuleAssignment::findForTutor($assignmentId, Auth::id());
        if (!$assignment) {
            $this->json(['error' => 'Not authorized'], 403);
        }

        SessionLog::end($logId, $completed, $error ?: null);
        ModuleAssignment::decrementPlays($assignmentId);

        $this->json(['success' => true]);
    }

    // POST /api/modules/test — admin only, NOT for WLED (that is done in browser)
    // This endpoint validates ESP32 IP format server-side and returns metadata only
    public function testModule(): void
    {
        $this->requireAdmin();
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $esp32Ip = trim($_POST['esp32_ip'] ?? '');

        if (!filter_var($esp32Ip, FILTER_VALIDATE_IP)) {
            $this->json(['error' => 'Invalid IP address format.'], 422);
        }

        $module = Module::find($moduleId);
        if (!$module) {
            $this->json(['error' => 'Module not found.'], 404);
        }

        $config = Module::getConfig($moduleId);
        if (!$config) {
            $this->json(['error' => 'Module configuration missing or corrupt.'], 500);
        }

        $baseUrl = rtrim(app_config('url'), '/');
        $folderName = $module['folder_name'];
        $config['_video_url'] = Module::getMediaUrl($folderName, $config['video'] ?? '');
        $config['_esp32_ip'] = $esp32Ip;
        $config['_module_id'] = $moduleId;
        $config['_test_mode'] = true;
        if (isset($config['timeline']) && is_array($config['timeline'])) {
            foreach ($config['timeline'] as &$scene) {
                if (!empty($scene['audio'])) {
                    $scene['_audio_url'] = Module::getMediaUrl($folderName, $scene['audio']);
                }
            }
        }
        $this->json(['success' => true, 'config' => $config]);
    }

    // GET /storage-serve/modules?folder=X&file=Y — serves module files
    public function serveModuleFile(): void
    {
        $folder = basename(input('folder') ?? ''); 
        $file = basename(input('file') ?? '');
        
        if (!$folder || !$file) {
            http_response_code(400);
            exit('Bad request');
        }
        
        $basePath = dirname(__DIR__, 2) . '/storage/modules/';
        $fullPath = realpath($basePath . $folder . '/' . $file);
        
        // Ensure the resolved path is actually inside the storage/modules directory
        if (!$fullPath || !str_starts_with($fullPath, realpath($basePath))) {
            http_response_code(403);
            exit('Forbidden');
        }
        
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('Not found');
        }
        
        // If tutor, verify they have an active assignment to this module's folder
        if (Auth::role() === 'tutor') {
            $moduleByFolder = Module::findByFolder($folder);
            if (!$moduleByFolder) { http_response_code(403); exit('Forbidden'); }
            $assignments = ModuleAssignment::allForTutor(Auth::id());
            $hasAccess = false;
            foreach ($assignments as $a) {
                if ($a['folder_name'] === $folder) { $hasAccess = true; break; }
            }
            if (!$hasAccess) { http_response_code(403); exit('Forbidden'); }
        }
        
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        if (ob_get_level()) {
            @ob_end_clean();
        }
        
        // Support range requests for video streaming
        $fileSize = filesize($fullPath);
        $start = 0;
        $end = $fileSize - 1;
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
            $start = (int) $matches[1];
            $end = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        }
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
        header('Access-Control-Allow-Headers: Range, Content-Type');
        header('Access-Control-Expose-Headers: Content-Range, Content-Length, Accept-Ranges');
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . ($end - $start + 1));
        header('Cache-Control: private, max-age=3600');
        
        $fp = fopen($fullPath, 'rb');
        fseek($fp, $start);
        $remaining = $end - $start + 1;
        while (!feof($fp) && $remaining > 0 && !connection_aborted()) {
            $chunk = fread($fp, min(32768, $remaining));
            echo $chunk;
            $remaining -= strlen($chunk);
            @ob_flush();
            @flush();
        }
        fclose($fp);
        exit;
    }

    public function r2Presign(): void
    {
        if (Auth::role() !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden. Admin access required.']);
            exit;
        }

        if (!R2Client::isConfigured()) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Cloudflare R2 is not configured on this server.']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput ?: '', true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $folder = trim((string) ($input['folder'] ?? ''));
        $filename = trim((string) ($input['filename'] ?? ''));

        if ($folder === '' || $filename === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing folder or filename.']);
            exit;
        }

        // Sanitize folder and filename
        $cleanFolder = preg_replace('/[^a-zA-Z0-9._-]/', '_', $folder);
        $cleanFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $key = "{$cleanFolder}/{$cleanFilename}";

        try {
            $presignedUrl = R2Client::generatePresignedPutUrl($key, 3600);
            $publicUrl = R2Client::getPublicUrl($key);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'upload_url' => $presignedUrl,
                'public_url' => $publicUrl,
                'key' => $key,
                'filename' => $cleanFilename
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to generate R2 upload URL: ' . $e->getMessage()]);
            exit;
        }
    }

    public function setupDatabase(): void
    {
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
            echo '<div style="font-family:sans-serif; max-width:600px; margin:60px auto; padding:30px; background:#f0fdf4; border:2px solid #22c55e; border-radius:12px; text-align:center;">
                <h2 style="color:#15803d; margin-top:0;">🎉 Database Initialized Successfully!</h2>
                <p style="color:#374151; font-size:16px;">All 11 tables and the Admin account have been created in your Railway database.</p>
                <div style="background:#fff; padding:15px; border-radius:8px; display:inline-block; text-align:left; margin:15px 0; border:1px solid #e5e7eb;">
                    <p style="margin:4px 0;"><strong>Email:</strong> <code>admin@neurosatva.local</code></p>
                    <p style="margin:4px 0;"><strong>Password:</strong> <code>password</code></p>
                </div>
                <br>
                <a href="/admin/login" style="display:inline-block; background:#16a34a; color:#fff; padding:12px 24px; text-decoration:none; border-radius:8px; font-weight:bold;">Go to Admin Login →</a>
            </div>';
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo '<h1>Database Setup Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            exit;
        }
    }
}
