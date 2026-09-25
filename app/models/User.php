<?php

declare(strict_types=1);

require_once __DIR__ . '/Model.php';

final class User extends Model
{
    private const PASSWORD_OPTIONS = [
        'cost' => 12,
    ];

    private const DUMMY_PASSWORD_HASH = '$2y$12$//LCHZvSvzLDItu.yXgFk.21JqAKJkTmARfhTcblf81Idb443CpOq';

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, email, password, role, status, created_at, updated_at
             FROM users
             WHERE email = :email
             LIMIT 1',
            ['email' => $this->normalizeEmail($email)]
        );
    }

    public function findActiveByEmail(string $email): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, password, role, status, created_at, updated_at
             FROM users
             WHERE email = :email AND status = 'active'
             LIMIT 1",
            ['email' => $this->normalizeEmail($email)]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, email, password, role, status, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function findActiveById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, password, role, status, created_at, updated_at
             FROM users
             WHERE id = :id AND status = 'active'
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function findActiveMemberById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, role, status
             FROM users
             WHERE id = :id AND role = 'member' AND status = 'active'
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function searchActiveMembers(string $query = '', int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $query = trim($query);
        $parameters = [];
        $searchSql = '';

        if ($query !== '') {
            $searchSql = ' AND (name LIKE :name_search OR email LIKE :email_search)';
            $parameters['name_search'] = '%' . $query . '%';
            $parameters['email_search'] = '%' . $query . '%';
        }

        return $this->fetchAll(
            "SELECT id, name, email
             FROM users
             WHERE role = 'member' AND status = 'active'" . $searchSql . '
             ORDER BY name ASC
             LIMIT ' . $limit,
            $parameters
        );
    }

    public function emailExists(string $email): bool
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM users WHERE email = :email',
            ['email' => $this->normalizeEmail($email)],
            0
        ) > 0;
    }

    public function createMember(string $name, string $email, string $passwordHash): int
    {
        $this->execute(
            "INSERT INTO users (name, email, password, role, status)
             VALUES (:name, :email, :password, 'member', 'active')",
            [
                'name' => trim($name),
                'email' => $this->normalizeEmail($email),
                'password' => $passwordHash,
            ]
        );

        return $this->lastInsertId();
    }

    public function hashPassword(string $password): string
    {
        if (strlen($password) > 72) {
            throw new InvalidArgumentException('Passwords cannot exceed 72 bytes.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, self::PASSWORD_OPTIONS);

        if (!is_string($hash)) {
            throw new RuntimeException('Unable to hash the password.');
        }

        return $hash;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        $hash = is_array($user) && isset($user['password']) ? (string) $user['password'] : self::DUMMY_PASSWORD_HASH;
        $passwordIsValid = $this->verifyPassword($password, $hash);

        if (!$passwordIsValid || !is_array($user) || $user['status'] !== 'active') {
            return null;
        }

        if (password_needs_rehash($hash, PASSWORD_BCRYPT, self::PASSWORD_OPTIONS)) {
            $this->execute(
                'UPDATE users SET password = :password WHERE id = :id',
                [
                    'password' => $this->hashPassword($password),
                    'id' => (int) $user['id'],
                ]
            );
        }

        unset($user['password']);

        return $user;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
