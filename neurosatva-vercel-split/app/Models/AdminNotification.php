<?php

final class AdminNotification
{
    public static function create(string $title, string $message, string $type, ?string $link = null, ?string $entityType = null, ?int $entityId = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_notifications (title, message, type, link, entity_type, entity_id)
             VALUES (:title, :message, :type, :link, :entity_type, :entity_id)'
        );
        $stmt->execute([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function createTutorRegistration(array $request): int
    {
        return self::create(
            'New tutor registration',
            $request['full_name'] . ' registered and is waiting for approval.',
            'tutor_registration',
            '/admin/registration-requests?status=Pending',
            'tutor_registration_request',
            (int) $request['id']
        );
    }

    public static function unreadCount(): int
    {
        $stmt = Database::connection()->query(
            "SELECT COUNT(*)
             FROM admin_notifications notifications
             JOIN tutor_registration_requests requests ON notifications.entity_type = 'tutor_registration_request'
                AND notifications.entity_id = requests.id
             WHERE notifications.read_at IS NULL AND requests.status = 'Pending'"
        );
        return (int) $stmt->fetchColumn();
    }

    public static function recent(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT notifications.*
             FROM admin_notifications notifications
             JOIN tutor_registration_requests requests ON notifications.entity_type = 'tutor_registration_request'
                AND notifications.entity_id = requests.id
             WHERE notifications.read_at IS NULL AND requests.status = 'Pending'
             ORDER BY notifications.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markTutorRegistrationRead(int $requestId): void
    {
        Database::connection()->prepare(
            "UPDATE admin_notifications
             SET read_at = NOW()
             WHERE entity_type = 'tutor_registration_request'
                AND entity_id = :entity_id
                AND read_at IS NULL"
        )->execute(['entity_id' => $requestId]);
    }
}
