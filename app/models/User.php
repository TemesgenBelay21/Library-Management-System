<?php

declare(strict_types=1);

require_once __DIR__ . '/Model.php';

final class User extends Model
{
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

    public function emailExists(string $email): bool
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM users WHERE email = :email',
            ['email' => $this->normalizeEmail($email)],
            0
        ) > 0;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
