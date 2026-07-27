<?php

final class ModuleSessionLog
{
    public static function start(int $assignmentId, string $deviceIp): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO module_session_logs (assignment_id, device_ip) VALUES (:assignment_id, :device_ip)'
        );
        $stmt->execute(['assignment_id' => $assignmentId, 'device_ip' => $deviceIp]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function end(int $id, bool $completed, ?int $duration, ?string $errors = null): void
    {
        Database::connection()->prepare(
            'UPDATE module_session_logs
             SET end_time = NOW(), duration = :duration, completed = :completed, errors = :errors
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'duration' => $duration,
            'completed' => $completed ? 1 : 0,
            'errors' => $errors ?: null,
        ]);
    }
}
