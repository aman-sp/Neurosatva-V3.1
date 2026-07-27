<?php

final class ModuleStorageService
{
    private const VIDEO_EXTENSIONS = ['mp4', 'mov'];
    private const AUDIO_EXTENSIONS = ['mp3'];
    private const THUMBNAIL_EXTENSIONS = ['png'];

    public function saveFromRequest(?array $existing = null): array
    {
        $moduleName = input('module_name');
        if ($moduleName === '') {
            throw new InvalidArgumentException('Module name is required.');
        }

        $folderName = $existing['folder_name'] ?? $this->uniqueFolderName($moduleName);
        $folder = $this->moduleRoot() . DIRECTORY_SEPARATOR . $folderName;
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        $videoName = $existing['video_name'] ?? '';
        if ($this->hasUpload('video')) {
            if ($videoName) {
                $this->deleteFile($folder, $videoName);
            }
            $videoName = $this->moveUpload($_FILES['video'], $folder, self::VIDEO_EXTENSIONS);
        }
        if ($videoName === '') {
            throw new InvalidArgumentException('Upload a .mp4 or .mov video.');
        }

        $thumbnailPath = $existing['thumbnail_path'] ?? null;
        if ($this->hasUpload('thumbnail')) {
            if ($thumbnailPath) {
                $oldThumbnail = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $thumbnailPath);
                if (is_file($oldThumbnail)) {
                    unlink($oldThumbnail);
                }
            }
            $thumbnailName = $this->moveUpload($_FILES['thumbnail'], $folder, self::THUMBNAIL_EXTENSIONS);
            $thumbnailPath = 'storage/modules/' . $folderName . '/' . $thumbnailName;
        }

        $renamedAudio = [];
        foreach ($_POST['delete_audio'] ?? [] as $audioToDelete) {
            $this->deleteFile($folder, basename((string) $audioToDelete));
        }

        foreach ($_POST['rename_audio_from'] ?? [] as $index => $from) {
            $from = basename((string) $from);
            $to = $this->sanitizeFilename((string) ($_POST['rename_audio_to'][$index] ?? ''));
            if ($from && $to && strtolower(pathinfo($to, PATHINFO_EXTENSION)) === 'mp3' && is_file($folder . DIRECTORY_SEPARATOR . $from)) {
                rename($folder . DIRECTORY_SEPARATOR . $from, $folder . DIRECTORY_SEPARATOR . $to);
                $renamedAudio[$from] = $to;
            }
        }

        if (!empty($_FILES['audio_files']['name'][0])) {
            foreach ($this->normalizeMultipleUpload($_FILES['audio_files']) as $audioUpload) {
                $this->moveUpload($audioUpload, $folder, self::AUDIO_EXTENSIONS);
            }
        }

        $audioFiles = $this->listAudioFiles($folder);
        $timeline = $this->timelineFromPost($audioFiles, $renamedAudio);
        $config = [
            'module_name' => $moduleName,
            'video' => $videoName,
            'timeline' => $timeline,
        ];

        file_put_contents(
            $folder . DIRECTORY_SEPARATOR . 'config.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return [
            'module_name' => $moduleName,
            'description' => input('description') ?: null,
            'folder_name' => $folderName,
            'video_name' => $videoName,
            'thumbnail_path' => $thumbnailPath,
            'config_path' => 'storage/modules/' . $folderName . '/config.json',
            'audio_count' => count($audioFiles),
            'scene_count' => count($timeline),
            'version' => input('version') ?: ($existing['version'] ?? '1.0'),
            'status' => in_array(input('status'), ['active', 'draft', 'archived'], true) ? input('status') : 'active',
        ];
    }

    public function modulePayload(array $module): array
    {
        $folder = $this->moduleRoot() . DIRECTORY_SEPARATOR . $module['folder_name'];
        $configPath = $folder . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath)) {
            throw new RuntimeException('Module configuration file is missing.');
        }

        $config = json_decode((string) file_get_contents($configPath), true);
        if (!is_array($config) || !isset($config['timeline']) || !is_array($config['timeline'])) {
            throw new RuntimeException('Module configuration JSON is invalid.');
        }

        $moduleId = (int) ($module['module_id'] ?? $module['id']);
        $base = path('/modules/file?module_id=' . $moduleId . '&file=');
        return [
            'module' => $module,
            'config' => $config,
            'video_url' => $base . rawurlencode($config['video'] ?? $module['video_name']),
            'audio_urls' => array_reduce($this->listAudioFiles($folder), function (array $urls, string $audio) use ($base): array {
                $urls[$audio] = $base . rawurlencode($audio);
                return $urls;
            }, []),
        ];
    }

    public function deleteModuleFolder(array $module): void
    {
        $folder = $this->moduleRoot() . DIRECTORY_SEPARATOR . $module['folder_name'];
        if (!is_dir($folder)) {
            return;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($folder);
    }

    public function listAudioFiles(string $folder): array
    {
        $files = glob($folder . DIRECTORY_SEPARATOR . '*.mp3') ?: [];
        return array_values(array_map('basename', $files));
    }

    private function timelineFromPost(array $audioFiles, array $renamedAudio = []): array
    {
        $timeline = [];
        foreach ($_POST['scene_duration'] ?? [] as $index => $duration) {
            $audio = basename((string) ($_POST['scene_audio'][$index] ?? ''));
            $audio = $renamedAudio[$audio] ?? $audio;
            if (!in_array($audio, $audioFiles, true)) {
                throw new InvalidArgumentException('Every scene must use one uploaded MP3 audio file.');
            }

            $rgb = [
                $this->intInRange($_POST['scene_rgb_r'][$index] ?? 0, 0, 255, 'RGB'),
                $this->intInRange($_POST['scene_rgb_g'][$index] ?? 0, 0, 255, 'RGB'),
                $this->intInRange($_POST['scene_rgb_b'][$index] ?? 0, 0, 255, 'RGB'),
            ];

            $timeline[] = [
                'duration' => $this->intInRange($duration, 1, 86400, 'Duration'),
                'state' => trim((string) ($_POST['scene_state'][$index] ?? 'focus')) ?: 'focus',
                'audio' => $audio,
                'audio_volume' => $this->floatInRange($_POST['scene_audio_volume'][$index] ?? 1, 0, 1, 'Audio volume'),
                'frequency' => trim((string) ($_POST['scene_frequency'][$index] ?? '')),
                'modulation' => trim((string) ($_POST['scene_modulation'][$index] ?? '')),
                'brightness' => $this->intInRange($_POST['scene_brightness'][$index] ?? 100, 0, 255, 'Brightness'),
                'cct' => $this->intInRange($_POST['scene_cct'][$index] ?? 30, 0, 255, 'CCT'),
                'rgb' => $rgb,
            ];
        }

        if (!$timeline) {
            throw new InvalidArgumentException('Add at least one timeline scene.');
        }

        return $timeline;
    }

    private function moveUpload(array $upload, string $folder, array $allowedExtensions): string
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('One of the uploaded files could not be saved.');
        }

        $name = $this->sanitizeFilename((string) $upload['name']);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Only allowed module file types may be uploaded.');
        }

        $target = $folder . DIRECTORY_SEPARATOR . $name;
        if (is_file($target)) {
            $base = pathinfo($name, PATHINFO_FILENAME);
            $name = $base . '-' . time() . '.' . $extension;
            $target = $folder . DIRECTORY_SEPARATOR . $name;
        }

        if (!move_uploaded_file($upload['tmp_name'], $target)) {
            throw new RuntimeException('Unable to move uploaded file.');
        }

        return $name;
    }

    private function normalizeMultipleUpload(array $files): array
    {
        $uploads = [];
        foreach ($files['name'] as $index => $name) {
            if ($name === '') {
                continue;
            }
            $uploads[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }
        return $uploads;
    }

    private function hasUpload(string $key): bool
    {
        return isset($_FILES[$key]) && ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function uniqueFolderName(string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) ?: 'module';
        $base = trim($base, '-');
        $folder = $base;
        $i = 2;
        while (is_dir($this->moduleRoot() . DIRECTORY_SEPARATOR . $folder)) {
            $folder = $base . '-' . $i++;
        }
        return $folder;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = basename($name);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = trim(preg_replace('/[^a-z0-9 _.-]+/i', '', pathinfo($name, PATHINFO_FILENAME)));
        $base = $base ?: 'file';
        return $base . ($extension ? '.' . $extension : '');
    }

    private function deleteFile(string $folder, string $file): void
    {
        $path = rtrim($folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($file);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function intInRange(mixed $value, int $min, int $max, string $label): int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false || $int < $min || $int > $max) {
            throw new InvalidArgumentException($label . ' is out of range.');
        }
        return $int;
    }

    private function floatInRange(mixed $value, float $min, float $max, string $label): float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($float === false || $float < $min || $float > $max) {
            throw new InvalidArgumentException($label . ' is out of range.');
        }
        return (float) $float;
    }

    private function moduleRoot(): string
    {
        return dirname(__DIR__, 2) . '/storage/modules';
    }
}
