<?php

final class SessionLog
{
    public static function start(int $assignmentId, int $tutorId, int $moduleId, string $deviceIp): int
    {
        $sql = "INSERT INTO playback_session_logs (assignment_id, tutor_id, module_id, device_ip)
                VALUES (:assignment_id, :tutor_id, :module_id, :device_ip)";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'assignment_id' => $assignmentId,
            'tutor_id' => $tutorId,
            'module_id' => $moduleId,
            'device_ip' => $deviceIp
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function end(int $logId, bool $completed, ?string $error = null): void
    {
        $sql = "UPDATE playback_session_logs
                SET ended_at = NOW(),
                    duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
                    completed = :completed,
                    error_log = :error
                WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'completed' => $completed ? 1 : 0,
            'error' => $error,
            'id' => $logId
        ]);
    }
}
