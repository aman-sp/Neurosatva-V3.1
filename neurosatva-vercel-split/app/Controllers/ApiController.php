<?php

final class ApiController
{
    private function applyCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $frontendUrl = app_config('frontend_url');

        if ($frontendUrl && $origin === $frontendUrl) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        }
    }

    private function json(mixed $data, int $status = 200): never
    {
        $this->applyCors();
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

    public function health(): void
    {
        $this->json([
            'ok' => true,
            'app' => app_config('name'),
            'role' => Auth::role(),
            'user' => Auth::user(),
        ]);
    }

    public function authMe(): void
    {
        $this->json([
            'authenticated' => Auth::check(),
            'user' => Auth::user(),
        ]);
    }

    public function authLogin(): void
    {
        $login = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->json(['error' => 'Email and password are required.'], 422);
        }

        $email = filter_var($login, FILTER_VALIDATE_EMAIL);

        $admin = $email ? Admin::findByEmail($email) : null;
        if ($admin && password_verify($password, $admin['password_hash']) && $admin['status'] === 'active') {
            AuditLog::login('admin', (int) $admin['id'], (string) $email, true);
            Auth::login(['id' => $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'], 'role' => 'admin']);
            $this->json(['success' => true, 'role' => 'admin', 'user' => Auth::user()]);
        }

        $tutor = Tutor::findByLogin($login);
        $ok = $tutor && password_verify($password, $tutor['password_hash']) && $tutor['status'] === 'active';
        AuditLog::login('tutor', $tutor['id'] ?? null, $login, (bool) $ok);

        if (!$ok) {
            $this->json(['error' => 'Invalid credentials or inactive account.'], 401);
        }

        Auth::login(['id' => $tutor['id'], 'name' => $tutor['name'], 'email' => $tutor['email'], 'role' => 'tutor']);
        $this->json(['success' => true, 'role' => 'tutor', 'user' => Auth::user()]);
    }

    public function authLogout(): void
    {
        Auth::logout();
        $this->json(['success' => true]);
    }

    public function adminDashboard(): void
    {
        $this->requireAdmin();

        $tutors = Tutor::all();
        $requests = TutorRegistrationRequest::all();
        $modules = Module::all();
        $videos = Video::all();

        $this->json([
            'metrics' => [
                'total_tutors' => count($tutors),
                'pending_requests' => TutorRegistrationRequest::pendingCount(),
                'unread_notifications' => AdminNotification::unreadCount(),
                'video_metrics' => Video::metrics(),
                'module_count' => count($modules),
                'video_count' => count($videos),
            ],
            'recent_tutors' => array_slice($tutors, 0, 8),
            'recent_requests' => array_slice($requests, 0, 8),
            'recent_modules' => array_slice($modules, 0, 8),
            'recent_videos' => array_slice($videos, 0, 8),
            'notifications' => AdminNotification::recent(),
        ]);
    }

    public function tutorDashboard(): void
    {
        $this->requireTutor();

        $tutor = Tutor::find(Auth::id() ?? 0);
        $assignments = ModuleAssignment::allForTutor(Auth::id() ?? 0);
        $verifiedVideos = Video::verifiedForTutor(Auth::id() ?? 0);

        foreach ($assignments as &$assignment) {
            $assignment['is_playable'] = ModuleAssignment::isPlayable($assignment);
        }

        $this->json([
            'tutor' => $tutor,
            'metrics' => [
                'assignment_count' => count($assignments),
                'verified_video_count' => count($verifiedVideos),
                'has_completed_gmail_setup' => Tutor::gmailIsComplete($tutor ?? []),
            ],
            'assignments' => $assignments,
            'verified_videos' => $verifiedVideos,
        ]);
    }

    public function adminTutors(): void
    {
        $this->requireAdmin();
        $this->json(Tutor::all(input('search'), input('status') ?: null));
    }

    public function adminRegistrationRequests(): void
    {
        $this->requireAdmin();
        $this->json(TutorRegistrationRequest::all(input('status') ?: null));
    }

    public function approveRegistrationRequest(): void
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);

        if (!$id) {
            $this->json(['error' => 'Request id is required.'], 422);
        }

        try {
            $initialPassword = $this->generateInitialTutorPassword();
            $tutorId = TutorRegistrationRequest::approve($id, Auth::id(), $initialPassword);
            AdminNotification::markTutorRegistrationRead($id);
            AuditLog::adminAction(Auth::id(), 'approved_tutor_registration', 'tutor_registration_request', $id, [
                'tutor_id' => $tutorId,
            ]);

            $this->json([
                'success' => true,
                'tutor_id' => $tutorId,
                'user_id' => tutor_user_id($tutorId),
                'initial_password' => $initialPassword,
            ]);
        } catch (Throwable $e) {
            $this->json(['error' => 'Unable to approve this request.'], 400);
        }
    }

    public function rejectRegistrationRequest(): void
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $remarks = (string) ($_POST['admin_remarks'] ?? '');

        if (!$id) {
            $this->json(['error' => 'Request id is required.'], 422);
        }

        TutorRegistrationRequest::reject($id, Auth::id(), $remarks);
        AdminNotification::markTutorRegistrationRead($id);
        AuditLog::adminAction(Auth::id(), 'rejected_tutor_registration', 'tutor_registration_request', $id);
        $this->json(['success' => true]);
    }

    public function updateTutor(): void
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var((string) ($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = trim((string) ($_POST['phone'] ?? '')) ?: null;
        $status = in_array((string) ($_POST['status'] ?? ''), ['active', 'deactivated'], true) ? (string) $_POST['status'] : 'active';
        $password = trim((string) ($_POST['password'] ?? '')) ?: null;

        if (!$id || !$name || !$email) {
            $this->json(['error' => 'Tutor id, name and email are required.'], 422);
        }

        if ($password !== null && strlen($password) < 8) {
            $this->json(['error' => 'Password must be at least 8 characters.'], 422);
        }

        Tutor::update($id, $name, $email, $status, $password, $phone);
        AuditLog::adminAction(Auth::id(), 'updated_tutor', 'tutor', $id, ['status' => $status]);
        $this->json(['success' => true]);
    }

    public function deleteTutor(): void
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);

        if (!$id) {
            $this->json(['error' => 'Tutor id is required.'], 422);
        }

        Tutor::delete($id);
        AuditLog::adminAction(Auth::id(), 'deleted_tutor', 'tutor', $id);
        $this->json(['success' => true]);
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
        $config['_video_url'] = path('/storage-serve/modules?folder=' . rawurlencode($folderName) . '&file=' . rawurlencode($config['video']));
        $config['_esp32_ip'] = $esp32Ip;
        $config['_module_id'] = $moduleId;
        $config['_test_mode'] = true;
        if (isset($config['timeline']) && is_array($config['timeline'])) {
            foreach ($config['timeline'] as &$scene) {
                if (!empty($scene['audio'])) {
                    $scene['_audio_url'] = path('/storage-serve/modules?folder=' . rawurlencode($folderName) . '&file=' . rawurlencode($scene['audio']));
                }
            }
        }
        $this->json($config);
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
        $config['_video_url'] = $baseUrl . '/storage-serve/modules?folder=' . rawurlencode($folderName) . '&file=' . rawurlencode($config['video']);
        $config['_esp32_ip'] = $esp32Ip;
        $config['_module_id'] = $moduleId;
        $config['_test_mode'] = true;
        if (isset($config['timeline']) && is_array($config['timeline'])) {
            foreach ($config['timeline'] as &$scene) {
                if (!empty($scene['audio'])) {
                    $scene['_audio_url'] = $baseUrl . '/storage-serve/modules?folder=' . rawurlencode($folderName) . '&file=' . rawurlencode($scene['audio']);
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
}
