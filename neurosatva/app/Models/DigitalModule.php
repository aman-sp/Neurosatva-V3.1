<?php

final class DigitalModule
{
    public static function all(?string $search = null, ?string $sort = null): array
    {
        $where = [];
        $params = [];
        if ($search) {
            $where[] = '(LOWER(module_name) LIKE LOWER(:search) OR LOWER(video_name) LIKE LOWER(:search) OR LOWER(status) LIKE LOWER(:search))';
            $params['search'] = '%' . $search . '%';
        }

        $order = match ($sort) {
            'name' => 'module_name ASC',
            'status' => 'status ASC, created_at DESC',
            'scenes' => 'scene_count DESC',
            default => 'created_at DESC',
        };

        $sql = 'SELECT modules.*,
                    (SELECT COUNT(*) FROM module_assignments WHERE module_assignments.module_id = modules.id AND module_assignments.status = "active") AS active_assignments
                FROM modules';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ' . $order;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function active(): array
    {
        return Database::connection()
            ->query("SELECT * FROM modules WHERE status = 'active' ORDER BY module_name")
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM modules WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO modules (module_name, description, folder_name, video_name, thumbnail_path, config_path, audio_count, scene_count, version, status, created_by)
             VALUES (:module_name, :description, :folder_name, :video_name, :thumbnail_path, :config_path, :audio_count, :scene_count, :version, :status, :created_by)'
        );
        $stmt->execute([
            'module_name' => $data['module_name'],
            'description' => $data['description'] ?: null,
            'folder_name' => $data['folder_name'],
            'video_name' => $data['video_name'],
            'thumbnail_path' => $data['thumbnail_path'] ?: null,
            'config_path' => $data['config_path'],
            'audio_count' => $data['audio_count'],
            'scene_count' => $data['scene_count'],
            'version' => $data['version'] ?: '1.0',
            'status' => $data['status'] ?: 'active',
            'created_by' => $data['created_by'] ?: null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::connection()->prepare(
            'UPDATE modules
             SET module_name = :module_name,
                 description = :description,
                 folder_name = :folder_name,
                 video_name = :video_name,
                 thumbnail_path = :thumbnail_path,
                 config_path = :config_path,
                 audio_count = :audio_count,
                 scene_count = :scene_count,
                 version = :version,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'module_name' => $data['module_name'],
            'description' => $data['description'] ?: null,
            'folder_name' => $data['folder_name'],
            'video_name' => $data['video_name'],
            'thumbnail_path' => $data['thumbnail_path'] ?: null,
            'config_path' => $data['config_path'],
            'audio_count' => $data['audio_count'],
            'scene_count' => $data['scene_count'],
            'version' => $data['version'] ?: '1.0',
            'status' => $data['status'] ?: 'active',
        ]);
    }

    public static function activeAssignmentCount(int $id): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM module_assignments WHERE module_id = :id AND status = 'active'");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM modules WHERE id = :id')->execute(['id' => $id]);
    }
}
