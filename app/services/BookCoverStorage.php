<?php

declare(strict_types=1);

final class BookCoverStorage
{
    private const MAX_BYTES = 5242880;

    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function store(array $upload, ?string $currentCover = null): ?string
    {
        $error = isset($upload['error']) ? (int) $upload['error'] : UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return $currentCover;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The cover upload did not complete successfully.');
        }

        $temporaryPath = isset($upload['tmp_name']) && is_string($upload['tmp_name']) ? $upload['tmp_name'] : '';
        $size = isset($upload['size']) ? (int) $upload['size'] : 0;

        if ($temporaryPath === '' || $size < 1 || $size > self::MAX_BYTES || !is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('Select a valid cover image under 5 MB.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extension = is_string($mimeType) ? (self::ALLOWED_TYPES[$mimeType] ?? null) : null;
        $dimensions = @getimagesize($temporaryPath);

        if (
            $extension === null
            || !is_array($dimensions)
            || (int) $dimensions[0] < 200
            || (int) $dimensions[1] < 200
            || (int) $dimensions[0] > 6000
            || (int) $dimensions[1] > 6000
        ) {
            throw new InvalidArgumentException('Cover images must be JPEG, PNG, or WebP and at least 200 × 200 pixels.');
        }

        $this->ensureStorageDirectory();
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->storagePath($filename);

        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('The cover image could not be stored.');
        }

        @chmod($destination, 0640);
        $this->delete($currentCover);

        return $filename;
    }

    public function delete(?string $filename): void
    {
        if (
            $filename === null
            || $filename === ''
            || preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/D', $filename) !== 1
        ) {
            return;
        }

        $path = STORAGE_PATH . DIRECTORY_SEPARATOR . 'book-covers' . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function stream(string $filename): void
    {
        $path = $this->resolveFilename($filename);
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $allowed = is_string($mimeType) ? (self::ALLOWED_TYPES[$mimeType] ?? null) : null;

        if ($allowed === null) {
            throw new RuntimeException('The stored cover image is unavailable.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="book-cover.' . $allowed . '"');
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    private function ensureStorageDirectory(): void
    {
        $directory = STORAGE_PATH . DIRECTORY_SEPARATOR . 'book-covers';

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('The private cover directory could not be created.');
        }
    }

    private function resolveFilename(string $filename): string
    {
        if (preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/D', $filename) !== 1) {
            throw new InvalidArgumentException('Invalid cover filename.');
        }

        $path = STORAGE_PATH . DIRECTORY_SEPARATOR . 'book-covers' . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path)) {
            throw new RuntimeException('The cover image could not be found.');
        }

        return $path;
    }
}
