<?php

final class ModuleAssignment
{
    public static function allForAdmin(?int $tutorId = null, ?int $moduleId = null): array
    {
        $sql = "SELECT ma.*, t.name AS tutor_name, t.email AS tutor_email,
                       m.name AS module_name, m.folder_name, m.version
                FROM module_assignments ma
                JOIN tutors t ON t.id = ma.tutor_id
                JOIN modules m ON m.id = ma.module_id
                WHERE 1=1";
        $params = [];
        
        if ($tutorId) {
            $sql .= " AND ma.tutor_id = :tutor_id";
            $params['tutor_id'] = $tutorId;
        }
        
        if ($moduleId) {
            $sql .= " AND ma.module_id = :module_id";
            $params['module_id'] = $moduleId;
        }
        
        $sql .= " ORDER BY ma.assigned_at DESC";
        
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function allForTutor(int $tutorId): array
    {
        $sql = "SELECT ma.*, m.name AS module_name, m.folder_name, m.thumbnail, m.video_name
                FROM module_assignments ma
                JOIN modules m ON m.id = ma.module_id
                WHERE ma.tutor_id = :tutor_id 
                  AND ma.status = 'active'
                  AND (ma.expiry_date IS NULL OR ma.expiry_date >= CURDATE())
                ORDER BY ma.assigned_at DESC";
                
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tutor_id' => $tutorId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT ma.*, m.name AS module_name, m.folder_name, m.video_name, m.thumbnail, m.config_path, m.version
                FROM module_assignments ma 
                JOIN modules m ON m.id = ma.module_id
                WHERE ma.id = :id LIMIT 1";
                
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findForTutor(int $assignmentId, int $tutorId): ?array
    {
        $sql = "SELECT ma.*, m.name AS module_name, m.folder_name, m.video_name, m.thumbnail, m.config_path, m.version
                FROM module_assignments ma 
                JOIN modules m ON m.id = ma.module_id
                WHERE ma.id = :id AND ma.tutor_id = :tutor_id LIMIT 1";
                
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $assignmentId, 'tutor_id' => $tutorId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO module_assignments (tutor_id, module_id, esp32_ip, remaining_plays, total_plays, expiry_date, assigned_by)
                VALUES (:tutor_id, :module_id, :esp32_ip, :remaining_plays, :total_plays, :expiry_date, :assigned_by)";
                
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tutor_id' => $data['tutor_id'],
            'module_id' => $data['module_id'],
            'esp32_ip' => $data['esp32_ip'],
            'remaining_plays' => $data['remaining_plays'],
            'total_plays' => $data['total_plays'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'assigned_by' => $data['assigned_by'] ?? null,
        ]);
        
        return (int) Database::connection()->lastInsertId();
    }

    public static function decrementPlays(int $id): void
    {
        $sql = "UPDATE module_assignments
                SET remaining_plays = GREATEST(remaining_plays - 1, 0),
                    status = CASE WHEN remaining_plays <= 1 THEN 'expired' ELSE status END,
                    updated_at = NOW()
                WHERE id = :id";
                
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public static function revoke(int $id): void
    {
        $sql = "UPDATE module_assignments SET status = 'revoked', updated_at = NOW() WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public static function isPlayable(array $assignment): bool
    {
        if ($assignment['status'] !== 'active') {
            return false;
        }
        if ($assignment['remaining_plays'] <= 0) {
            return false;
        }
        if ($assignment['expiry_date'] !== null && $assignment['expiry_date'] < date('Y-m-d')) {
            return false;
        }
        return true;
    }

    public static function isExpired(array $assignment): bool
    {
        if ($assignment['status'] !== 'active') {
            return true;
        }
        if ($assignment['remaining_plays'] <= 0) {
            return true;
        }
        if ($assignment['expiry_date'] !== null && $assignment['expiry_date'] < date('Y-m-d')) {
            return true;
        }
        return false;
    }
}
