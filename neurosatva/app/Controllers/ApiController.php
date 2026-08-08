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
