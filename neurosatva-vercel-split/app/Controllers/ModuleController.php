<?php

final class ModuleController
{
    private function guard(): void
    {
        Auth::requireRole('admin');
    }

    private function checkPostMaxSize(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            Session::flash('error', 'The total size of uploaded files exceeded the server post limit. Please upload smaller files.');
            redirect('/admin/vault');
        }
    }

    public function vault(): void
    {
        $this->guard();
        view('admin/vault', [
            'title' => 'Digital Vault',
            'modules' => Module::all(input('search'), input('status') ?: null),
            'search' => input('search'),
            'status' => input('status'),
        ]);
    }

    public function createForm(): void
    {
        $this->guard();
        view('admin/vault-create', ['title' => 'Create Module']);
    }

    public function store(): void
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        $this->guard();
        $this->checkPostMaxSize();
        Csrf::verify();

        $name = trim(input('name') ?? '');
        $configUploaded = !empty($_FILES['config_json']['name']) && $_FILES['config_json']['error'] === UPLOAD_ERR_OK;
        $parsedConfig = null;

        if ($configUploaded) {
            $jsonContent = file_get_contents($_FILES['config_json']['tmp_name']);
            $parsedConfig = json_decode($jsonContent, true);
            if (is_array($parsedConfig)) {
                if (!$name && !empty($parsedConfig['module_name'])) {
                    $name = trim($parsedConfig['module_name']);
                } elseif (!$name && !empty($parsedConfig['name'])) {
                    $name = trim($parsedConfig['name']);
                }
            }
        }

        if (!$name) {
            Session::flash('error', 'Module name is required.');
            redirect('/admin/vault/create');
        }

        $folderName = Module::generateFolderName($name);
        $dir = Module::storagePath($folderName);
        
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            Session::flash('error', 'Failed to create module directory.');
            redirect('/admin/vault/create');
        }

        // Master Video Upload
        $videoName = null;
        if (!empty($_FILES['video']['name'])) {
            if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                $this->rmdirRecursive($dir);
                Session::flash('error', 'Video upload failed: ' . $this->getUploadErrorMessage($_FILES['video']['error']));
                redirect('/admin/vault/create');
            }
            if (!$this->validateUpload($_FILES['video'], ['mp4', 'mov', 'webm', 'mkv'])) {
                $this->rmdirRecursive($dir);
                Session::flash('error', 'Invalid video file type or size. Allowed: MP4, MOV, WEBM, MKV.');
                redirect('/admin/vault/create');
            }
            $videoName = $this->sanitizeFilename($_FILES['video']['name']);
            move_uploaded_file($_FILES['video']['tmp_name'], $dir . '/' . $videoName);
        } elseif ($parsedConfig && !empty($parsedConfig['video'])) {
            $videoName = $this->sanitizeFilename($parsedConfig['video']);
        }

        // Audio Assets Upload
        if (!empty($_FILES['audio']['name'][0])) {
            foreach ($_FILES['audio']['name'] as $key => $audioName) {
                if (!empty($audioName) && $_FILES['audio']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileInfo = [
                        'name' => $_FILES['audio']['name'][$key],
                        'type' => $_FILES['audio']['type'][$key],
                        'tmp_name' => $_FILES['audio']['tmp_name'][$key],
                        'error' => $_FILES['audio']['error'][$key],
                        'size' => $_FILES['audio']['size'][$key]
                    ];
                    if ($this->validateUpload($fileInfo, ['mp3', 'wav', 'ogg', 'm4a', 'aac'])) {
                        $safeAudioName = $this->sanitizeFilename($fileInfo['name']);
                        move_uploaded_file($fileInfo['tmp_name'], $dir . '/' . $safeAudioName);
                    }
                }
            }
        }

        // Thumbnail Upload
        $thumbnailName = null;
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            if ($this->validateUpload($_FILES['thumbnail'], ['png', 'jpg', 'jpeg', 'webp'])) {
                $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                $thumbnailName = 'thumbnail.' . $ext;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dir . '/' . $thumbnailName);
            }
        }

        // Timeline Builder
        $scenesData = $_POST['scenes'] ?? [];
        $timeline = [];

        if (!empty($scenesData) && is_array($scenesData)) {
            foreach ($scenesData as $scene) {
                $timeline[] = [
                    'duration' => (int) ($scene['duration'] ?? 60),
                    'state' => (string) ($scene['state'] ?? 'focus'),
                    'audio' => (string) ($scene['audio'] ?? ''),
                    'audio_volume' => (float) max(0, min(1, $scene['audio_volume'] ?? 1)),
                    'frequency' => (string) ($scene['frequency'] ?? ''),
                    'modulation' => (string) ($scene['modulation'] ?? 'None'),
                    'brightness' => (int) max(0, min(100, $scene['brightness'] ?? 50)),
                    'cct' => (int) max(0, min(100, $scene['cct'] ?? 50)),
                    'rgb' => [
                        (int) max(0, min(255, $scene['rgb_r'] ?? 0)),
                        (int) max(0, min(255, $scene['rgb_g'] ?? 0)),
                        (int) max(0, min(255, $scene['rgb_b'] ?? 0))
                    ]
                ];
            }
        } elseif ($parsedConfig && (!empty($parsedConfig['timeline']) || !empty($parsedConfig['scenes']))) {
            $rawTimeline = $parsedConfig['timeline'] ?? $parsedConfig['scenes'];
            if (is_array($rawTimeline)) {
                foreach ($rawTimeline as $scene) {
                    $rgb = $scene['rgb'] ?? [
                        $scene['rgb_r'] ?? ($scene['r'] ?? 0),
                        $scene['rgb_g'] ?? ($scene['g'] ?? 0),
                        $scene['rgb_b'] ?? ($scene['b'] ?? 0)
                    ];
                    $timeline[] = [
                        'duration' => (int) ($scene['duration'] ?? 60),
                        'state' => (string) ($scene['state'] ?? 'focus'),
                        'audio' => (string) ($scene['audio'] ?? ''),
                        'audio_volume' => (float) max(0, min(1, $scene['audio_volume'] ?? $scene['volume'] ?? 1)),
                        'frequency' => (string) ($scene['frequency'] ?? ''),
                        'modulation' => (string) ($scene['modulation'] ?? 'None'),
                        'brightness' => (int) max(0, min(100, $scene['brightness'] ?? 50)),
                        'cct' => (int) max(0, min(100, $scene['cct'] ?? 50)),
                        'rgb' => [
                            (int) max(0, min(255, $rgb[0] ?? 0)),
                            (int) max(0, min(255, $rgb[1] ?? 0)),
                            (int) max(0, min(255, $rgb[2] ?? 0))
                        ]
                    ];
                }
            }
        }

        // Save config.json
        $configPath = $dir . '/config.json';
        if ($configUploaded && file_exists($_FILES['config_json']['tmp_name'])) {
            move_uploaded_file($_FILES['config_json']['tmp_name'], $configPath);
        } else {
            $configJson = $this->generateConfig($name, $videoName ?? '', $timeline);
            file_put_contents($configPath, $configJson);
        }

        Module::create([
            'name' => $name,
            'folder_name' => $folderName,
            'description' => input('description'),
            'video_name' => $videoName,
            'thumbnail' => $thumbnailName,
            'config_path' => $configPath,
            'created_by' => Auth::id()
        ]);

        Session::flash('success', 'Module created successfully.');
        redirect('/admin/vault');
    }

    public function editForm(): void
    {
        $this->guard();
        $id = (int) input('id');
        $module = Module::find($id);
        if (!$module) {
            Session::flash('error', 'Module not found.');
            redirect('/admin/vault');
        }
        $config = Module::getConfig($id);
        view('admin/vault-edit', ['title' => 'Edit Module', 'module' => $module, 'config' => $config]);
    }

    public function update(): void
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        $this->guard();
        $this->checkPostMaxSize();
        Csrf::verify();

        $id = (int) input('id');
        $module = Module::find($id);
        if (!$module) {
            Session::flash('error', 'Module not found.');
            redirect('/admin/vault');
        }

        $dir = Module::storagePath($module['folder_name']);
        $videoName = $module['video_name'];
        $thumbnailName = $module['thumbnail'];

        $configUploaded = !empty($_FILES['config_json']['name']) && $_FILES['config_json']['error'] === UPLOAD_ERR_OK;
        $parsedConfig = null;
        if ($configUploaded) {
            $jsonContent = file_get_contents($_FILES['config_json']['tmp_name']);
            $parsedConfig = json_decode($jsonContent, true);
        }

        if (!empty($_FILES['video']['name'])) {
            if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                Session::flash('error', 'Video upload failed: ' . $this->getUploadErrorMessage($_FILES['video']['error']));
                redirect('/admin/vault/edit?id=' . $id);
            }
            if ($this->validateUpload($_FILES['video'], ['mp4', 'mov', 'webm', 'mkv'])) {
                if ($videoName && file_exists($dir . '/' . $videoName)) {
                    unlink($dir . '/' . $videoName);
                }
                $videoName = $this->sanitizeFilename($_FILES['video']['name']);
                move_uploaded_file($_FILES['video']['tmp_name'], $dir . '/' . $videoName);
            }
        }

        if (!empty($_POST['delete_audio']) && is_array($_POST['delete_audio'])) {
            foreach ($_POST['delete_audio'] as $delAudio) {
                $delAudioSafe = basename($delAudio);
                if (file_exists($dir . '/' . $delAudioSafe)) {
                    unlink($dir . '/' . $delAudioSafe);
                }
            }
        }

        if (!empty($_FILES['audio']['name'][0])) {
            foreach ($_FILES['audio']['name'] as $key => $audioName) {
                if (!empty($audioName) && $_FILES['audio']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileInfo = [
                        'name' => $_FILES['audio']['name'][$key],
                        'type' => $_FILES['audio']['type'][$key],
                        'tmp_name' => $_FILES['audio']['tmp_name'][$key],
                        'error' => $_FILES['audio']['error'][$key],
                        'size' => $_FILES['audio']['size'][$key]
                    ];
                    if ($this->validateUpload($fileInfo, ['mp3', 'wav', 'ogg', 'm4a', 'aac'])) {
                        $safeAudioName = $this->sanitizeFilename($fileInfo['name']);
                        move_uploaded_file($fileInfo['tmp_name'], $dir . '/' . $safeAudioName);
                    }
                }
            }
        }

        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            if ($this->validateUpload($_FILES['thumbnail'], ['png', 'jpg', 'jpeg', 'webp'])) {
                if ($thumbnailName && file_exists($dir . '/' . $thumbnailName)) {
                    unlink($dir . '/' . $thumbnailName);
                }
                $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                $thumbnailName = 'thumbnail.' . $ext;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dir . '/' . $thumbnailName);
            }
        }

        $scenesData = $_POST['scenes'] ?? [];
        $timeline = [];

        if (!empty($scenesData) && is_array($scenesData)) {
            foreach ($scenesData as $scene) {
                $timeline[] = [
                    'duration' => (int) ($scene['duration'] ?? 60),
                    'state' => (string) ($scene['state'] ?? 'focus'),
                    'audio' => (string) ($scene['audio'] ?? ''),
                    'audio_volume' => (float) max(0, min(1, $scene['audio_volume'] ?? 1)),
                    'frequency' => (string) ($scene['frequency'] ?? ''),
                    'modulation' => (string) ($scene['modulation'] ?? 'None'),
                    'brightness' => (int) max(0, min(100, $scene['brightness'] ?? 50)),
                    'cct' => (int) max(0, min(100, $scene['cct'] ?? 50)),
                    'rgb' => [
                        (int) max(0, min(255, $scene['rgb_r'] ?? 0)),
                        (int) max(0, min(255, $scene['rgb_g'] ?? 0)),
                        (int) max(0, min(255, $scene['rgb_b'] ?? 0))
                    ]
                ];
            }
        } elseif ($parsedConfig && (!empty($parsedConfig['timeline']) || !empty($parsedConfig['scenes']))) {
            $rawTimeline = $parsedConfig['timeline'] ?? $parsedConfig['scenes'];
            if (is_array($rawTimeline)) {
                foreach ($rawTimeline as $scene) {
                    $rgb = $scene['rgb'] ?? [
                        $scene['rgb_r'] ?? ($scene['r'] ?? 0),
                        $scene['rgb_g'] ?? ($scene['g'] ?? 0),
                        $scene['rgb_b'] ?? ($scene['b'] ?? 0)
                    ];
                    $timeline[] = [
                        'duration' => (int) ($scene['duration'] ?? 60),
                        'state' => (string) ($scene['state'] ?? 'focus'),
                        'audio' => (string) ($scene['audio'] ?? ''),
                        'audio_volume' => (float) max(0, min(1, $scene['audio_volume'] ?? $scene['volume'] ?? 1)),
                        'frequency' => (string) ($scene['frequency'] ?? ''),
                        'modulation' => (string) ($scene['modulation'] ?? 'None'),
                        'brightness' => (int) max(0, min(100, $scene['brightness'] ?? 50)),
                        'cct' => (int) max(0, min(100, $scene['cct'] ?? 50)),
                        'rgb' => [
                            (int) max(0, min(255, $rgb[0] ?? 0)),
                            (int) max(0, min(255, $rgb[1] ?? 0)),
                            (int) max(0, min(255, $rgb[2] ?? 0))
                        ]
                    ];
                }
            }
        }

        $name = trim(input('name') ?? $module['name']);
        $configPath = $dir . '/config.json';
        if ($configUploaded && file_exists($_FILES['config_json']['tmp_name'])) {
            move_uploaded_file($_FILES['config_json']['tmp_name'], $configPath);
        } else {
            $configJson = $this->generateConfig($name, $videoName ?? '', $timeline);
            file_put_contents($configPath, $configJson);
        }

        Module::update($id, [
            'name' => $name,
            'description' => input('description'),
            'video_name' => $videoName,
            'thumbnail' => $thumbnailName
        ]);

        Session::flash('success', 'Module updated successfully.');
        redirect('/admin/vault');
    }

    public function delete(): void
    {
        $this->guard();
        Csrf::verify();

        $id = (int) input('id');
        $module = Module::find($id);
        if (!$module) {
            Session::flash('error', 'Module not found.');
            redirect('/admin/vault');
        }

        $assignedCount = Module::assignedTutorCount($id);
        if ($assignedCount > 0 && input('confirm_delete') !== '1') {
            Session::flash('error', "This module is assigned to {$assignedCount} active tutors. Confirm deletion.");
            redirect('/admin/vault?id=' . $id . '&warn=1');
        }

        $this->deleteModuleFiles($module);
        Module::delete($id);

        Session::flash('success', 'Module deleted successfully.');
        redirect('/admin/vault');
    }

    public function test(): void
    {
        $this->guard();
        $id = (int) input('id');
        $ip = trim(input('ip') ?? '');

        $module = Module::find($id);
        if (!$module) {
            Session::flash('error', 'Module not found.');
            redirect('/admin/vault');
        }

        $config = Module::getConfig($id);
        if (!$config) {
            Session::flash('error', 'Module configuration is missing or corrupt.');
            redirect('/admin/vault');
        }

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            Session::flash('error', 'Valid ESP32 IP address is required for testing.');
            redirect('/admin/vault');
        }

        $assignment = [
            'id' => 0,
            'module_id' => $module['id'],
            'module_name' => $module['name'],
            'esp32_ip' => $ip,
            'remaining_plays' => '∞ (Test Mode)',
            'is_test' => true
        ];

        view('admin/vault-test', [
            'title' => 'Test Module: ' . e($module['name']),
            'module' => $module,
            'assignment' => $assignment,
            'config' => $config,
        ]);
    }

    public function assignForm(): void
    {
        $this->guard();
        view('admin/assign', [
            'title' => 'Assign Module',
            'tutors' => Tutor::active(),
            'modules' => Module::all(null, 'active'),
        ]);
    }

    public function assign(): void
    {
        $this->guard();
        Csrf::verify();

        $tutorId = (int) input('tutor_id');
        $moduleId = (int) input('module_id');
        $esp32Ip = trim(input('esp32_ip') ?? '');
        $plays = (int) (input('total_plays') ?: input('plays'));
        $expiry = input('expiry_date');

        if (!$tutorId || !$moduleId || !$esp32Ip || $plays <= 0) {
            Session::flash('error', 'Please fill all required fields correctly.');
            redirect('/admin/assign');
        }

        if (!filter_var($esp32Ip, FILTER_VALIDATE_IP)) {
            Session::flash('error', 'Invalid ESP32 IP address.');
            redirect('/admin/assign');
        }

        $expiryDate = null;
        if ($expiry) {
            if (strtotime($expiry) < strtotime('today')) {
                Session::flash('error', 'Expiry date cannot be in the past.');
                redirect('/admin/assign');
            }
            $expiryDate = date('Y-m-d', strtotime($expiry));
        }

        ModuleAssignment::create([
            'tutor_id' => $tutorId,
            'module_id' => $moduleId,
            'esp32_ip' => $esp32Ip,
            'remaining_plays' => $plays,
            'total_plays' => $plays,
            'expiry_date' => $expiryDate,
            'assigned_by' => Auth::id()
        ]);

        Session::flash('success', 'Module assigned successfully.');
        redirect('/admin/assignments');
    }

    public function assignments(): void
    {
        $this->guard();
        view('admin/assignments', [
            'title' => 'Module Assignments',
            'assignments' => ModuleAssignment::allForAdmin(
                input('tutor_id') ? (int) input('tutor_id') : null,
                input('module_id') ? (int) input('module_id') : null
            ),
            'tutors' => Tutor::active(),
            'modules' => Module::all(),
        ]);
    }

    public function revokeAssignment(): void
    {
        $this->guard();
        Csrf::verify();
        $id = (int) input('id');
        ModuleAssignment::revoke($id);
        Session::flash('success', 'Assignment revoked.');
        redirect('/admin/assignments');
    }

    private function generateConfig(string $moduleName, string $videoName, array $scenes): string
    {
        $config = [
            'module_name' => $moduleName,
            'video' => $videoName,
            'timeline' => $scenes
        ];
        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function validateUpload(array $file, array $allowedExt, int $maxBytes = 2147483648): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return false;
        }
        if ($file['size'] > $maxBytes) {
            return false;
        }
        return true;
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary upload folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error (Code ' . $errorCode . ').',
        };
    }

    private function deleteModuleFiles(array $module): void
    {
        $path = Module::storagePath($module['folder_name']);
        $this->rmdirRecursive($path);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $full = $dir . '/' . $file;
                if (is_dir($full)) {
                    $this->rmdirRecursive($full);
                } else {
                    unlink($full);
                }
            }
        }
        rmdir($dir);
    }

    private function sanitizeFilename(string $name): string
    {
        return trim(preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $name));
    }
}
