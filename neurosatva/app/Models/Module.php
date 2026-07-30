<?php

/**
 * Module model — manages module metadata.
 * Timeline/config data always comes from config.json on disk.
 * Version increments only on successful content edits.
 */
final class Module
{
    /**
     * Return all modules with audio file count from filesystem.
     * Audio count is calculated by reading the module folder.
     */
    public static function all(?string $search = null, ?string $status = null): array
    {
        $sql = "SELECT * FROM modules WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND name LIKE :search";
            $params['search'] = "%{$search}%";
        }
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $modules = $stmt->fetchAll();
        
        foreach ($modules as &$row) {
            $dir = self::storagePath($row['folder_name']);
            $row['audio_count'] = count(glob($dir . '/*.mp3') ?: []);
            
            $config = self::getConfig($row['id']);
            $row['scene_count'] = count($config['timeline'] ?? []);
        }
        
        return $modules;
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM modules WHERE id = :id LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findByFolder(string $folder): ?array
    {
        $sql = "SELECT * FROM modules WHERE folder_name = :folder LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['folder' => $folder]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO modules (name, folder_name, description, video_name, thumbnail, config_path, created_by)
                VALUES (:name, :folder_name, :description, :video_name, :thumbnail, :config_path, :created_by)";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'folder_name' => $data['folder_name'],
            'description' => $data['description'] ?? null,
            'video_name' => $data['video_name'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'config_path' => $data['config_path'],
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = "UPDATE modules 
                SET name = :name, 
                    description = :description, 
                    video_name = :video_name, 
                    thumbnail = :thumbnail, 
                    version = version + 1, 
                    updated_at = NOW() 
                WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'video_name' => $data['video_name'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $sql = "DELETE FROM modules WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public static function assignedTutorCount(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM module_assignments WHERE module_id = :id AND status = 'active'";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function getConfig(int $id): ?array
    {
        $module = self::find($id);
        if (!$module) {
            return null;
        }
        $path = self::storagePath($module['folder_name']) . '/config.json';
        if (!file_exists($path)) {
            return null;
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public static function generateFolderName(string $moduleName): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($moduleName));
        $base = trim($base, '-');
        
        $sql = "SELECT folder_name FROM modules WHERE folder_name LIKE :pattern";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['pattern' => $base . '%']);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array($base, $existing, true)) {
            return $base;
        }
        
        $i = 2;
        while (in_array($base . '-' . $i, $existing, true)) {
            $i++;
        }
        return $base . '-' . $i;
    }

    public static function storagePath(string $folderName): string
    {
        return dirname(__DIR__, 2) . '/storage/modules/' . $folderName;
    }
}
