<?php

final class Video
{
    public static function metrics(): array
    {
        $row = Database::connection()->query(
            "SELECT
                COUNT(*) AS total_received,
                SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) AS verified,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
             FROM videos"
        )->fetch();

        return [
            'total_received' => (int) ($row['total_received'] ?? 0),
            'verified' => (int) ($row['verified'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
        ];
    }

    public static function all(?string $status = null, ?int $tutorId = null): array
    {
        $where = [];
        $params = [];

        if ($status) {
            $where[] = 'videos.status = :status';
            $params['status'] = $status;
        }

        if ($tutorId) {
            $where[] = 'videos.tutor_id = :tutor_id';
            $params['tutor_id'] = $tutorId;
        }

        $sql = 'SELECT videos.*, tutors.name AS tutor_name, tutors.email AS tutor_email
                FROM videos
                JOIN tutors ON tutors.id = videos.tutor_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY videos.received_at DESC, videos.created_at DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function verifiedForTutor(int $tutorId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM videos
             WHERE tutor_id = :tutor_id AND status = 'verified'
             ORDER BY verified_at DESC, created_at DESC"
        );
        $stmt->execute(['tutor_id' => $tutorId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT videos.*, tutors.name AS tutor_name FROM videos JOIN tutors ON tutors.id = videos.tutor_id WHERE videos.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO videos (tutor_id, title, email_subject, source_email, storage_path, status, admin_remarks, received_at)
             VALUES (:tutor_id, :title, :email_subject, :source_email, :storage_path, :status, :admin_remarks, :received_at)'
        );
        $stmt->execute([
            'tutor_id' => $data['tutor_id'],
            'title' => $data['title'],
            'email_subject' => $data['email_subject'] ?: null,
            'source_email' => $data['source_email'] ?: null,
            'storage_path' => $data['storage_path'] ?: null,
            'status' => $data['status'],
            'admin_remarks' => $data['admin_remarks'] ?: null,
            'received_at' => $data['received_at'] ?: date('Y-m-d H:i:s'),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updateVerification(int $id, string $status, string $remarks, ?string $storagePath, int $adminId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE videos
             SET status = :status,
                 admin_remarks = :admin_remarks,
                 storage_path = COALESCE(:storage_path, storage_path),
                 verified_at = CASE WHEN :status_for_date = 'verified' THEN NOW() ELSE verified_at END,
                 updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'status_for_date' => $status,
            'admin_remarks' => $remarks ?: null,
            'storage_path' => $storagePath ?: null,
        ]);

        $audit = $pdo->prepare(
            'INSERT INTO video_verifications (video_id, admin_id, status, remarks)
             VALUES (:video_id, :admin_id, :status, :remarks)'
        );
        $audit->execute([
            'video_id' => $id,
            'admin_id' => $adminId,
            'status' => $status,
            'remarks' => $remarks ?: null,
        ]);

        $pdo->commit();
    }
}
