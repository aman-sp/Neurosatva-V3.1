<?php

final class ModuleFileController
{
    public function serve(): void
    {
        $module = DigitalModule::find((int) input('module_id'));
        $file = basename(input('file'));
        if (!$module || !$file || !$this->canAccess((int) $module['id'])) {
            http_response_code(403);
            exit('Forbidden');
        }

        $path = dirname(__DIR__, 2) . '/storage/modules/' . $module['folder_name'] . '/' . $file;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!is_file($path) || !in_array($extension, ['mp4', 'mov', 'mp3', 'json', 'png'], true)) {
            http_response_code(404);
            exit('File not found');
        }

        $types = ['mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'mp3' => 'audio/mpeg', 'json' => 'application/json', 'png' => 'image/png'];
        header('Content-Type: ' . $types[$extension]);
        $size = filesize($path);
        $start = 0;
        $end = $size - 1;
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end = $matches[2] !== '' ? (int) $matches[2] : $end;
            $end = min($end, $size - 1);
            if ($start <= $end) {
                http_response_code(206);
                header("Content-Range: bytes {$start}-{$end}/{$size}");
            }
        }
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . (($end - $start) + 1));
        $handle = fopen($path, 'rb');
        fseek($handle, $start);
        $remaining = ($end - $start) + 1;
        while ($remaining > 0 && !feof($handle)) {
            $chunk = fread($handle, min(8192, $remaining));
            echo $chunk;
            $remaining -= strlen($chunk);
        }
        fclose($handle);
        exit;
    }

    private function canAccess(int $moduleId): bool
    {
        if (Auth::role() === 'admin') {
            return true;
        }
        if (Auth::role() !== 'tutor') {
            return false;
        }
        foreach (ModuleAssignment::forTutor(Auth::id()) as $assignment) {
            if ((int) $assignment['module_id'] === $moduleId) {
                return true;
            }
        }
        return false;
    }
}
