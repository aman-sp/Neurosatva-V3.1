<?php

final class Admin
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public static function updateProfile(int $id, string $name, string $email, ?string $password): void
    {
        $sql = 'UPDATE admins SET name = :name, email = :email, updated_at = NOW()';
        $params = ['id' => $id, 'name' => $name, 'email' => strtolower($email)];

        if ($password !== null && $password !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }
}
