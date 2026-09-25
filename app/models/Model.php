<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';

abstract class Model
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    protected function execute(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }

    protected function fetchOne(string $sql, array $parameters = []): ?array
    {
        $record = $this->execute($sql, $parameters)->fetch();

        return is_array($record) ? $record : null;
    }

    protected function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->execute($sql, $parameters)->fetchAll();
    }

    protected function fetchValue(string $sql, array $parameters = [], $default = null)
    {
        $value = $this->execute($sql, $parameters)->fetchColumn();

        return $value === false ? $default : $value;
    }

    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    protected function transaction(callable $operation)
    {
        $this->db->beginTransaction();

        try {
            $result = $operation($this->db);
            $this->db->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }
}
