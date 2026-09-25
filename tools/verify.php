<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$failures = 0;
$warnings = 0;

function section(string $title): void
{
    echo PHP_EOL . $title . PHP_EOL . str_repeat('-', strlen($title)) . PHP_EOL;
}

function ok(string $message): void
{
    echo '  [ok]   ' . $message . PHP_EOL;
}

function fail(string $message): void
{
    global $failures;
    $failures++;
    echo '  [FAIL] ' . $message . PHP_EOL;
}

function warn(string $message): void
{
    global $warnings;
    $warnings++;
    echo '  [warn] ' . $message . PHP_EOL;
}

section('Runtime');

$requiredVersion = '7.4.0';
if (version_compare(PHP_VERSION, $requiredVersion, '>=')) {
    ok('PHP ' . PHP_VERSION . ' meets the ' . $requiredVersion . ' minimum.');
} else {
    fail('PHP ' . PHP_VERSION . ' is older than the required ' . $requiredVersion . '.');
}

foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'json'] as $extension) {
    if (extension_loaded($extension)) {
        ok('Extension ' . $extension . ' is loaded.');
    } else {
        fail('Extension ' . $extension . ' is not loaded.');
    }
}

section('Configuration');

$envPath = ROOT_PATH . DIRECTORY_SEPARATOR . '.env';
if (is_file($envPath)) {
    ok('.env exists and is readable.');
} else {
    fail('.env is missing. Copy .env.example to .env and fill in the values.');
}

foreach (['APP_URL', 'DB_NAME', 'DB_USERNAME'] as $key) {
    $value = getenv($key);
    if (is_string($value) && trim($value) !== '') {
        ok($key . ' is configured.');
    } else {
        fail($key . ' is not configured.');
    }
}

if (APP_ENV === 'production' && APP_DEBUG) {
    fail('APP_DEBUG must be false when APP_ENV is production.');
} else {
    ok('Debug and environment flags are consistent.');
}

section('Syntax');

$phpFiles = [];
$directoryIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(ROOT_PATH, FilesystemIterator::SKIP_DOTS)
);

foreach ($directoryIterator as $entry) {
    $path = $entry->getPathname();
    if ($entry->isFile() && strtolower($entry->getExtension()) === 'php' && strpos($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR) === false) {
        $phpFiles[] = $path;
    }
}

$syntaxErrors = [];

if (!function_exists('exec')) {
    warn('exec() is disabled on this host, so PHP syntax checks were skipped.');
} else {
    foreach ($phpFiles as $file) {
        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            $syntaxErrors[] = $file . ': ' . implode(' ', $output);
        }
    }

    if ($syntaxErrors === []) {
        ok(count($phpFiles) . ' PHP files parse cleanly.');
    } else {
        foreach ($syntaxErrors as $syntaxError) {
            fail($syntaxError);
        }
    }
}

section('Storage');

foreach (['storage', 'storage/book-covers'] as $relative) {
    $directory = ROOT_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_dir($directory)) {
        fail($relative . ' directory is missing.');
        continue;
    }
    if (is_writable($directory)) {
        ok($relative . ' is writable.');
    } else {
        fail($relative . ' is not writable by the web server user.');
    }
}

section('Database');

$requiredTables = ['users', 'books', 'transactions'];
$requiredColumns = ['transactions' => ['active_loan_key']];

try {
    $pdo = Database::connection();
    ok('Connected to MySQL successfully.');

    $databaseName = (string) getenv('DB_NAME');
    $serverVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    ok('Server version is ' . $serverVersion . ' on database ' . $databaseName . '.');

    foreach ($requiredTables as $table) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table'
        );
        $statement->execute(['schema' => $databaseName, 'table' => $table]);

        if ((int) $statement->fetchColumn() > 0) {
            ok('Table ' . $table . ' exists.');
        } else {
            fail('Table ' . $table . ' is missing. Import public/database.sql.');
        }
    }

    foreach ($requiredColumns as $table => $columns) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = :schema AND table_name = :table AND column_name = :column'
        );

        foreach ($columns as $column) {
            $statement->execute(['schema' => $databaseName, 'table' => $table, 'column' => $column]);
            if ((int) $statement->fetchColumn() > 0) {
                ok('Column ' . $table . '.' . $column . ' exists.');
            } else {
                fail('Column ' . $table . '.' . $column . ' is missing. Run the migration in public/database.sql.');
            }
        }
    }

    $indexStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = :schema AND table_name = :table AND index_name = :index'
    );
    $indexStatement->execute([
        'schema' => $databaseName,
        'table' => 'transactions',
        'index' => 'transactions_active_loan_unique',
    ]);

    if ((int) $indexStatement->fetchColumn() > 0) {
        ok('Unique index transactions_active_loan_unique exists.');
    } else {
        warn('Unique index transactions_active_loan_unique is missing; duplicate active loans are not blocked.');
    }
} catch (Throwable $exception) {
    fail('Database check failed: ' . $exception->getMessage());
}

section('Result');

if ($failures === 0 && $warnings === 0) {
    echo 'All checks passed.' . PHP_EOL;
    exit(0);
}

echo sprintf('%d failure(s), %d warning(s).', $failures, $warnings) . PHP_EOL;
exit($failures === 0 ? 0 : 1);
