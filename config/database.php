<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = self::env('DB_HOST', '127.0.0.1');
        $port = self::env('DB_PORT', '3306');
        $database = self::env('DB_NAME');
        $username = self::env('DB_USERNAME');
        $password = self::env('DB_PASSWORD');

        $validatedPort = filter_var($port, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 65535,
            ],
        ]);

        if ($validatedPort === false) {
            throw new RuntimeException('DB_PORT must be a valid TCP port.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $validatedPort,
            $database
        );

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_PERSISTENT => true,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to establish a database connection.',
                0,
                $exception
            );
        }

        return self::$connection;
    }

    private static function env(string $key, ?string $default = null): string
    {
        $value = getenv($key);

        if ($value === false && $default !== null) {
            return $default;
        }

        if ($value === false || trim($value) === '') {
            throw new RuntimeException(
                sprintf('Required environment variable %s is not configured.', $key)
            );
        }

        return $value;
    }

    private function __construct()
    {
    }
}
