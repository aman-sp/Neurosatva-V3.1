<?php

final class ModuleAssignment
{
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT module_assignments.*, tutors.name AS tutor_name, tutors.email AS tutor_email, modules.module_name
             FROM module_assignments
             JOIN tutors ON tutors.id = module_assignments.tutor_id
             JOIN modules ON modules.id = module_assignments.module_id
             ORDER BY module_assignments.assigned_at DESC'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT module_assignments.*, modules.module_name, modules.folder_name, modules.video_name, modules.config_path
             FROM module_assignments
             JOIN modules ON modules.id = module_assignments.module_id
             WHERE module_assignments.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO module_assignments (tutor_id, module_id, esp32_ip, remaining_plays, expiry_date, assigned_by, status)
             VALUES (:tutor_id, :module_id, :esp32_ip, :remaining_plays, :expiry_date, :assigned_by, :status)'
        );
        $stmt->execute([
            'tutor_id' => $data['tutor_id'],
            'module_id' => $data['module_id'],
            'esp32_ip' => $data['esp32_ip'],
            'remaining_plays' => $data['remaining_plays'],
            'expiry_date' => $data['expiry_date'] ?: null,
            'assigned_by' => $data['assigned_by'] ?: null,
            'status' => $data['status'] ?: 'active',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::connection()->prepare(
            'UPDATE module_assignments
             SET esp32_ip = :esp32_ip,
                 remaining_plays = :remaining_plays,
                 expiry_date = :expiry_date,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'esp32_ip' => $data['esp32_ip'],
            'remaining_plays' => $data['remaining_plays'],
            'expiry_date' => $data['expiry_date'] ?: null,
            'status' => $data['status'] ?: 'active',
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM module_assignments WHERE id = :id')->execute(['id' => $id]);
    }

    public static function forTutor(int $tutorId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT module_assignments.*, modules.module_name, modules.description, modules.folder_name, modules.video_name,
                    modules.thumbnail_path, modules.config_path, modules.scene_count, modules.audio_count
             FROM module_assignments
             JOIN modules ON modules.id = module_assignments.module_id
             WHERE module_assignments.tutor_id = :tutor_id
             ORDER BY module_assignments.assigned_at DESC'
        );
        $stmt->execute(['tutor_id' => $tutorId]);
        return $stmt->fetchAll();
    }

    public static function playableForTutor(int $assignmentId, int $tutorId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT module_assignments.*, modules.module_name, modules.description, modules.folder_name, modules.video_name,
                    modules.thumbnail_path, modules.config_path, modules.scene_count, modules.audio_count
             FROM module_assignments
             JOIN modules ON modules.id = module_assignments.module_id
             WHERE module_assignments.id = :id
               AND module_assignments.tutor_id = :tutor_id
               AND module_assignments.status = 'active'
             LIMIT 1"
        );
        $stmt->execute(['id' => $assignmentId, 'tutor_id' => $tutorId]);
        return $stmt->fetch() ?: null;
    }

    public static function decrementPlay(int $id): void
    {
        Database::connection()->prepare(
            'UPDATE module_assignments SET remaining_plays = GREATEST(remaining_plays - 1, 0), updated_at = NOW() WHERE id = :id'
        )->execute(['id' => $id]);
    }
}
