<?php

final class TutorRegistrationRequest
{
    public static function pendingCount(): int
    {
        $stmt = Database::connection()->query("SELECT COUNT(*) FROM tutor_registration_requests WHERE status = 'Pending'");
        return (int) $stmt->fetchColumn();
    }

    public static function all(?string $status = null): array
    {
        $params = [];
        $sql = 'SELECT requests.*, admins.name AS approved_by_name
                FROM tutor_registration_requests requests
                LEFT JOIN admins ON admins.id = requests.approved_by';

        if ($status) {
            $sql .= ' WHERE requests.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY requests.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tutor_registration_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findLatestByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM tutor_registration_requests WHERE email = :email ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute(['email' => strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $phone, ?string $school = null, ?string $gender = null): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO tutor_registration_requests
                (full_name, email, phone, school_name, gender, password_hash, status)
             VALUES
                (:full_name, :email, :phone, :school_name, :gender, :password_hash, 'Pending')"
        );
        $stmt->execute([
            'full_name' => $name,
            'email' => strtolower($email),
            'phone' => $phone,
            'school_name' => $school ?: null,
            'gender' => $gender ?: null,
            'password_hash' => password_hash($phone, PASSWORD_DEFAULT),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function approve(int $id, int $adminId, string $initialPassword): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $request = self::find($id);
            if (!$request || $request['status'] !== 'Pending') {
                throw new RuntimeException('Registration request is not available for approval.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO tutors
                    (name, email, personal_email, phone, password_hash, status, created_by, school_name, gender, first_login_completed)
                 VALUES
                    (:name, :email, :personal_email, :phone, :password_hash, 'active', :created_by, :school_name, :gender, 0)"
            );
            $stmt->execute([
                'name' => $request['full_name'],
                'email' => strtolower($request['email']),
                'personal_email' => strtolower($request['email']),
                'phone' => $request['phone'],
                'password_hash' => password_hash($initialPassword, PASSWORD_DEFAULT),
                'created_by' => $adminId,
                'school_name' => $request['school_name'] ?: null,
                'gender' => $request['gender'] ?: null,
            ]);
            $tutorId = (int) $pdo->lastInsertId();

            $update = $pdo->prepare(
                "UPDATE tutor_registration_requests
                 SET status = 'Approved', approved_by = :approved_by, approved_at = NOW(), updated_at = NOW()
                 WHERE id = :id"
            );
            $update->execute(['id' => $id, 'approved_by' => $adminId]);

            $pdo->commit();
            return $tutorId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function reject(int $id, int $adminId, string $remarks): void
    {
        Database::connection()->prepare(
            "UPDATE tutor_registration_requests
             SET status = 'Rejected',
                 admin_remarks = :admin_remarks,
                 approved_by = :approved_by,
                 approved_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id AND status = 'Pending'"
        )->execute([
            'id' => $id,
            'approved_by' => $adminId,
            'admin_remarks' => $remarks ?: null,
        ]);
    }
}
