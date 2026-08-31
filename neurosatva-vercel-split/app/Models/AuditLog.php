<?php

final class AuditLog
{
    public static function login(string $role, ?int $userId, string $email, bool $success): void
    {
        Database::connection()->prepare(
            'INSERT INTO login_logs (role, user_id, email, ip_address, user_agent, success)
             VALUES (:role, :user_id, :email, :ip_address, :user_agent, :success)'
        )->execute([
            'role' => $role,
            'user_id' => $userId,
            'email' => strtolower($email),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'success' => $success ? 1 : 0,
        ]);
    }

    public static function adminAction(int $adminId, string $action, string $entityType, ?int $entityId = null, array $metadata = []): void
    {
        Database::connection()->prepare(
            'INSERT INTO admin_actions (admin_id, action, entity_type, entity_id, metadata, ip_address)
             VALUES (:admin_id, :action, :entity_type, :entity_id, :metadata, :ip_address)'
        )->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
