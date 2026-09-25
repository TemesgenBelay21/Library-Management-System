<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Model.php';

final class Book extends Model
{
    private const SORT_OPTIONS = [
        'title' => 'books.title ASC',
        'author' => 'books.author ASC',
        'category' => 'books.category ASC, books.title ASC',
        'newest' => 'books.created_at DESC, books.id DESC',
        'availability' => 'books.available_copies DESC, books.title ASC',
    ];

    public function paginate(array $filters = [], int $perPage = DEFAULT_PER_PAGE): array
    {
        $perPage = max(1, min(MAX_PER_PAGE, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $where = ['books.deleted_at IS NULL'];
        $parameters = [];
        $query = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $category = isset($filters['category']) ? trim((string) $filters['category']) : '';
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        $availability = isset($filters['availability']) ? trim((string) $filters['availability']) : '';

        if ($query !== '') {
            $where[] = '(books.title LIKE :title_search OR books.author LIKE :author_search OR books.isbn LIKE :isbn_search)';
            $parameters['title_search'] = '%' . $query . '%';
            $parameters['author_search'] = '%' . $query . '%';
            $parameters['isbn_search'] = '%' . $query . '%';
        }

        if ($category !== '') {
            $where[] = 'books.category = :category';
            $parameters['category'] = $category;
        }

        if (in_array($status, ['available', 'checked_out'], true)) {
            $where[] = 'books.status = :status';
            $parameters['status'] = $status;
        }

        if ($availability === 'available') {
            $where[] = 'books.available_copies > 0';
        } elseif ($availability === 'unavailable') {
            $where[] = 'books.available_copies = 0';
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $countRecord = $this->fetchOne('SELECT COUNT(*) AS total FROM books' . $whereSql, $parameters) ?: [];
        $total = (int) ($countRecord['total'] ?? 0);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $sort = isset($filters['sort']) && isset(self::SORT_OPTIONS[$filters['sort']])
            ? self::SORT_OPTIONS[$filters['sort']]
            : self::SORT_OPTIONS['newest'];
        $items = $this->fetchAll(
            'SELECT
                books.id,
                books.title,
                books.author,
                books.isbn,
                books.category,
                books.description,
                books.cover_image,
                books.total_copies,
                books.available_copies,
                books.status,
                books.shelf_location,
                books.published_year,
                books.created_at,
                books.updated_at,
                (SELECT COUNT(*) FROM transactions WHERE transactions.book_id = books.id AND transactions.status IN (\'issued\', \'overdue\')) AS active_loans
             FROM books' . $whereSql . '
             ORDER BY ' . $sort . '
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $parameters
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($total, $offset + $perPage),
            'query' => $query,
            'category' => $category,
            'status' => $status,
            'availability' => $availability,
            'sort' => isset(self::SORT_OPTIONS[$filters['sort'] ?? '']) ? (string) $filters['sort'] : 'newest',
        ];
    }

    public function create(array $data, ?string $coverImage = null): int
    {
        return $this->transaction(function (PDO $database) use ($data, $coverImage): int {
            $statement = $database->prepare(
                'INSERT INTO books
                    (title, author, isbn, category, description, cover_image, total_copies, available_copies, status, shelf_location, published_year)
                 VALUES
                    (:title, :author, :isbn, :category, :description, :cover_image, :total_copies, :available_copies, \'available\', :shelf_location, :published_year)'
            );
            $statement->execute([
                'title' => (string) $data['title'],
                'author' => (string) $data['author'],
                'isbn' => $data['isbn'] !== '' ? (string) $data['isbn'] : null,
                'category' => (string) $data['category'],
                'description' => $data['description'] !== '' ? (string) $data['description'] : null,
                'cover_image' => $coverImage,
                'total_copies' => (int) $data['total_copies'],
                'available_copies' => (int) $data['total_copies'],
                'shelf_location' => $data['shelf_location'] !== '' ? (string) $data['shelf_location'] : null,
                'published_year' => $data['published_year'] !== null ? (int) $data['published_year'] : null,
            ]);

            return (int) $database->lastInsertId();
        });
    }

    public function update(int $id, array $data, ?string $coverImage): bool
    {
        if ($id < 1) {
            return false;
        }

        return $this->transaction(function (PDO $database) use ($id, $data, $coverImage): bool {
            $bookStatement = $database->prepare(
                'SELECT id FROM books WHERE id = :id AND deleted_at IS NULL FOR UPDATE'
            );
            $bookStatement->execute(['id' => $id]);
            $book = $bookStatement->fetch();

            if (!is_array($book)) {
                return false;
            }

            $loanStatement = $database->prepare(
                'SELECT COUNT(*) AS active_loans
                 FROM transactions
                 WHERE book_id = :book_id
                   AND status IN (\'issued\', \'overdue\')'
            );
            $loanStatement->execute(['book_id' => $id]);
            $activeLoans = (int) $loanStatement->fetchColumn();
            $totalCopies = (int) $data['total_copies'];

            if ($totalCopies < $activeLoans) {
                throw new DomainException('Total copies cannot be lower than the number of active loans.');
            }

            $availableCopies = $totalCopies - $activeLoans;
            $statement = $database->prepare(
                'UPDATE books
                 SET title = :title,
                     author = :author,
                     isbn = :isbn,
                     category = :category,
                     description = :description,
                     cover_image = :cover_image,
                     total_copies = :total_copies,
                     available_copies = :available_copies,
                     status = :status,
                     shelf_location = :shelf_location,
                     published_year = :published_year
                 WHERE id = :id
                   AND deleted_at IS NULL'
            );
            $statement->execute([
                'id' => $id,
                'title' => (string) $data['title'],
                'author' => (string) $data['author'],
                'isbn' => $data['isbn'] !== '' ? (string) $data['isbn'] : null,
                'category' => (string) $data['category'],
                'description' => $data['description'] !== '' ? (string) $data['description'] : null,
                'cover_image' => $coverImage,
                'total_copies' => $totalCopies,
                'available_copies' => $availableCopies,
                'status' => $availableCopies > 0 ? 'available' : 'checked_out',
                'shelf_location' => $data['shelf_location'] !== '' ? (string) $data['shelf_location'] : null,
                'published_year' => $data['published_year'] !== null ? (int) $data['published_year'] : null,
            ]);

            return true;
        });
    }

    public function delete(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->transaction(function (PDO $database) use ($id): ?array {
            $bookStatement = $database->prepare(
                'SELECT id, cover_image
                 FROM books
                 WHERE id = :id
                   AND deleted_at IS NULL
                 FOR UPDATE'
            );
            $bookStatement->execute(['id' => $id]);
            $book = $bookStatement->fetch();

            if (!is_array($book)) {
                return null;
            }

            $loanStatement = $database->prepare(
                'SELECT COUNT(*)
                 FROM transactions
                 WHERE book_id = :book_id
                   AND status IN (\'issued\', \'overdue\')'
            );
            $loanStatement->execute(['book_id' => $id]);
            $activeLoans = (int) $loanStatement->fetchColumn();

            if ($activeLoans > 0) {
                throw new DomainException('Books with active loans must be returned before deletion.');
            }

            $statement = $database->prepare(
                'UPDATE books
                 SET deleted_at = CURRENT_TIMESTAMP
                 WHERE id = :id
                   AND deleted_at IS NULL'
            );
            $statement->execute(['id' => $id]);

            return [
                'id' => $id,
                'cover_image' => isset($book['cover_image']) && is_string($book['cover_image'])
                    ? $book['cover_image']
                    : null,
            ];
        });
    }

    public function categories(): array
    {
        return $this->fetchAll(
            'SELECT category, COUNT(*) AS title_count, COALESCE(SUM(total_copies), 0) AS copy_count
             FROM books
             WHERE deleted_at IS NULL
             GROUP BY category
             ORDER BY category ASC'
        );
    }

    public function activeBorrowers(int $id): array
    {
        if ($id < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT
                transactions.id,
                transactions.status,
                transactions.issued_at,
                transactions.due_at,
                transactions.fine_amount,
                users.name AS member_name,
                users.email AS member_email
             FROM transactions
             INNER JOIN users ON users.id = transactions.user_id
             WHERE transactions.book_id = :book_id
               AND transactions.status IN (\'issued\', \'overdue\')
             ORDER BY transactions.due_at ASC, transactions.id ASC',
            ['book_id' => $id]
        );
    }

    public function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT books.*,
                (SELECT COUNT(*) FROM transactions WHERE transactions.book_id = books.id AND transactions.status IN (\'issued\', \'overdue\')) AS active_loans
             FROM books
             WHERE books.id = :id
               AND books.deleted_at IS NULL
             LIMIT 1',
            ['id' => $id]
        );
    }
}
