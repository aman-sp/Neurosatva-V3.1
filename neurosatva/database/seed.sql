USE neurosatva;

INSERT INTO admins (name, email, password_hash, status)
VALUES (
    'Neurosatva Admin',
    'admin@neurosatva.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'active'
)
ON DUPLICATE KEY UPDATE email = email;
