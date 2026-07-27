USE neurosatva;

CREATE TABLE IF NOT EXISTS admin_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    link VARCHAR(255) NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admin_notifications
    ADD COLUMN IF NOT EXISTS entity_type VARCHAR(80) NULL AFTER type,
    ADD COLUMN IF NOT EXISTS entity_id BIGINT UNSIGNED NULL AFTER entity_type;

CREATE INDEX IF NOT EXISTS idx_admin_notifications_read_at ON admin_notifications(read_at);
CREATE INDEX IF NOT EXISTS idx_admin_notifications_entity ON admin_notifications(entity_type, entity_id);

UPDATE admin_notifications notifications
JOIN tutor_registration_requests requests
    ON notifications.type = 'tutor_registration'
    AND notifications.entity_id IS NULL
    AND notifications.message LIKE CONCAT('%', requests.full_name, '%')
SET notifications.entity_type = 'tutor_registration_request',
    notifications.entity_id = requests.id;

UPDATE admin_notifications notifications
LEFT JOIN tutor_registration_requests requests
    ON notifications.entity_type = 'tutor_registration_request'
    AND notifications.entity_id = requests.id
SET notifications.read_at = COALESCE(notifications.read_at, NOW())
WHERE notifications.type = 'tutor_registration'
    AND (requests.id IS NULL OR requests.status <> 'Pending');
