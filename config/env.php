<?php

declare(strict_types=1);

final class Environment
{
    private const ASSIGNMENT_PATTERN = '/^(?:export[ \t]+)?([A-Z][A-Z0-9_]*)=(.*)$/';

    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (preg_match(self::ASSIGNMENT_PATTERN, $line, $matches) !== 1) {
                continue;
            }

            $key = $matches[1];
            $value = self::normalize($matches[2]);

            if ($value === null || getenv($key) !== false) {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private static function normalize(string $value): ?string
    {
        if (strpbrk($value, "\r\n") !== false) {
            return null;
        }

        $length = strlen($value);
        $quote = $length > 0 ? $value[0] : '';

        if ($length > 1 && ($quote === '"' || $quote === "'") && $value[$length - 1] === $quote) {
            $value = substr($value, 1, -1);
        } else {
            $comment = strpos($value, ' #');

            if ($comment !== false) {
                $value = rtrim(substr($value, 0, $comment));
            }
        }

        return $value;
    }

    private function __construct()
    {
    }
}
