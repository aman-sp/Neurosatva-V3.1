<?php

final class Tutor
{
    public static function all(?string $search = null, ?string $status = null): array
    {
        $where = [];
        $params = [];

        if ($search) {
            $where[] = '(LOWER(name) LIKE LOWER(:search) OR LOWER(email) LIKE LOWER(:search) OR LOWER(personal_email) LIKE LOWER(:search) OR phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT tutors.*,
                       (SELECT COUNT(*) FROM videos WHERE videos.tutor_id = tutors.id) AS video_count
                FROM tutors';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY tutors.created_at DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function active(): array
    {
        return Database::connection()
            ->query("SELECT id, name, email FROM tutors WHERE status = 'active' ORDER BY name")
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tutors WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tutors WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public static function findByLogin(string $login): ?array
    {
        $email = filter_var($login, FILTER_VALIDATE_EMAIL);
        if ($email) {
            return self::findByEmail($email);
        }

        $normalized = strtoupper(trim($login));
        if (preg_match('/^(?:NS-)?TUT(?:OR)?-?0*([0-9]+)$/', $normalized, $matches)) {
            return self::find((int) $matches[1]);
        }

        return null;
    }

    public static function create(string $name, string $email, string $password, int $adminId, ?string $school = null, ?string $gender = null, ?string $phone = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO tutors (name, email, personal_email, phone, password_hash, status, created_by, school_name, gender)
             VALUES (:name, :email, :personal_email, :phone, :password_hash, :status, :created_by, :school_name, :gender)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => strtolower($email),
            'personal_email' => strtolower($email),
            'phone' => $phone ?: null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active',
            'created_by' => $adminId,
            'school_name' => $school,
            'gender' => $gender,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name, string $email, string $status, ?string $password = null, ?string $phone = null): void
    {
        $sql = 'UPDATE tutors SET name = :name, email = :email, phone = :phone, status = :status, updated_at = NOW()';
        $params = ['id' => $id, 'name' => $name, 'email' => strtolower($email), 'phone' => $phone, 'status' => $status];

        if ($password !== null && $password !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM tutors WHERE id = :id')->execute(['id' => $id]);
    }

    public static function gmailIsComplete(array $tutor): bool
    {
        return !empty($tutor['official_gmail'])
            && (int) ($tutor['gmail_verified'] ?? 0) === 1
            && (int) ($tutor['first_login_completed'] ?? 0) === 1;
    }

    public static function gmailStatus(array $tutor): string
    {
        if (empty($tutor['official_gmail'])) {
            return 'Pending Gmail Setup';
        }

        if ((int) ($tutor['gmail_verified'] ?? 0) !== 1) {
            return 'Awaiting Email Verification';
        }

        return 'Verified';
    }

    public static function startOfficialGmailVerification(int $id, string $gmail): string
    {
        $existing = self::findByEmail($gmail);
        if ($existing && (int) $existing['id'] !== $id) {
            throw new RuntimeException('This Gmail address is already assigned to another tutor.');
        }

        $otp = (string) random_int(100000, 999999);
        Database::connection()->prepare(
            'UPDATE tutors
             SET official_gmail = :official_gmail,
                 gmail_verified = 0,
                 gmail_verified_at = NULL,
                 gmail_verification_token = NULL,
                 gmail_otp_hash = :gmail_otp_hash,
                 gmail_otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
                 gmail_otp_attempts = 0,
                 gmail_updated_at = NOW(),
                 first_login_completed = 0,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'official_gmail' => strtolower($gmail),
            'gmail_otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
        ]);

        return $otp;
    }

    public static function completeOfficialGmailVerification(int $id): void
    {
        Database::connection()->prepare(
            'UPDATE tutors
             SET email = official_gmail,
                 gmail_verified = 1,
                 gmail_verified_at = NOW(),
                 gmail_verification_token = NULL,
                 gmail_otp_hash = NULL,
                 gmail_otp_expires_at = NULL,
                 gmail_otp_attempts = 0,
                 first_login_completed = 1,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute(['id' => $id]);
    }

    public static function verifyOfficialGmailOtp(int $id, string $otp): bool
    {
        $tutor = self::find($id);
        if (!$tutor || empty($tutor['gmail_otp_hash']) || empty($tutor['gmail_otp_expires_at'])) {
            return false;
        }

        if (strtotime($tutor['gmail_otp_expires_at']) < time()) {
            return false;
        }

        if ((int) ($tutor['gmail_otp_attempts'] ?? 0) >= 5) {
            return false;
        }

        $valid = password_verify($otp, $tutor['gmail_otp_hash']);
        if (!$valid) {
            Database::connection()->prepare(
                'UPDATE tutors SET gmail_otp_attempts = gmail_otp_attempts + 1, updated_at = NOW() WHERE id = :id'
            )->execute(['id' => $id]);
            return false;
        }

        self::completeOfficialGmailVerification($id);
        return true;
    }
}
