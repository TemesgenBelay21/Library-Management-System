<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../services/BookCoverStorage.php';

final class BookController extends Controller
{
    public function cover(array $params = []): string
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $book = (new Book())->find($id);

        if (!is_array($book) || !isset($book['cover_image']) || !is_string($book['cover_image'])) {
            http_response_code(404);

            return '';
        }

        (new BookCoverStorage())->stream($book['cover_image']);

        return '';
    }
}
