<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Model.php';

final class Transaction extends Model
{
    private const SORT_OPTIONS = [
        'newest' => 'transactions.created_at DESC, transactions.id DESC',
        'oldest' => 'transactions.created_at ASC, transactions.id ASC',
        'due_soon' => 'transactions.due_at ASC, transactions.id ASC',
        'title' => 'books.title ASC, transactions.id DESC',
        'member' => 'users.name ASC, transactions.id DESC',
    ];

    private const SELECT_COLUMNS = 'transactions.id,
                transactions.status,
                transactions.issued_at,
                transactions.due_at,
                transactions.returned_at,
                transactions.fine_amount,
                transactions.created_at,
                transactions.updated_at,
                books.id AS book_id,
                books.title,
                books.author,
                books.category,
                books.cover_image,
                books.total_copies,
                books.available_copies,
                books.shelf_location,
                users.id AS member_id,
                users.name AS member_name,
                users.email AS member_email,
                issuers.name AS issued_by_name,
                GREATEST(DATEDIFF(CURRENT_DATE, DATE(transactions.due_at)), 0) AS days_overdue';

    private const FROM_CLAUSES = 'FROM transactions
             INNER JOIN books ON books.id = transactions.book_id
             INNER JOIN users ON users.id = transactions.user_id
             INNER JOIN users AS issuers ON issuers.id = transactions.issued_by';

    public function paginate(array $filters = [], int $perPage = DEFAULT_PER_PAGE): array
    {
        $perPage = max(1, min(MAX_PER_PAGE, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $where = ['books.deleted_at IS NULL'];
        $parameters = [];
        $query = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        $memberId = (int) ($filters['member_id'] ?? 0);
        $bookId = (int) ($filters['book_id'] ?? 0);

        if ($query !== '') {
            $where[] = '(books.title LIKE :title_search
                OR books.author LIKE :author_search
                OR users.name LIKE :member_search
                OR users.email LIKE :email_search)';
            $parameters['title_search'] = '%' . $query . '%';
            $parameters['author_search'] = '%' . $query . '%';
            $parameters['member_search'] = '%' . $query . '%';
            $parameters['email_search'] = '%' . $query . '%';
        }

        if (in_array($status, ['issued', 'returned', 'overdue'], true)) {
            $where[] = 'transactions.status = :status';
            $parameters['status'] = $status;
        }

        if ($memberId > 0) {
            $where[] = 'transactions.user_id = :member_id';
            $parameters['member_id'] = $memberId;
        }

        if ($bookId > 0) {
            $where[] = 'transactions.book_id = :book_id';
            $parameters['book_id'] = $bookId;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $countRecord = $this->fetchOne(
            'SELECT COUNT(*) AS total ' . self::FROM_CLAUSES . $whereSql,
            $parameters
        ) ?: [];
        $total = (int) ($countRecord['total'] ?? 0);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $sortKey = isset($filters['sort']) && isset(self::SORT_OPTIONS[$filters['sort']])
            ? (string) $filters['sort']
            : 'newest';
        $items = $this->fetchAll(
            'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_CLAUSES . $whereSql . '
             ORDER BY ' . self::SORT_OPTIONS[$sortKey] . '
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
            'status' => $status,
            'member_id' => $memberId,
            'book_id' => $bookId,
            'sort' => $sortKey,
        ];
    }

    public function statistics(): array
    {
        $record = $this->fetchOne(
            'SELECT
                COUNT(*) AS total_loans,
                SUM(transactions.status = \'issued\') AS issued_loans,
                SUM(transactions.status = \'overdue\') AS overdue_loans,
                SUM(transactions.status = \'returned\') AS returned_loans,
                COALESCE(SUM(CASE WHEN transactions.status <> \'returned\' THEN transactions.fine_amount ELSE 0 END), 0) AS outstanding_fines,
                COALESCE(SUM(CASE WHEN transactions.status = \'returned\' THEN transactions.fine_amount ELSE 0 END), 0) AS collected_fines
             FROM transactions
             INNER JOIN books ON books.id = transactions.book_id
             WHERE books.deleted_at IS NULL'
        ) ?: [];

        return [
            'total_loans' => (int) ($record['total_loans'] ?? 0),
            'issued_loans' => (int) ($record['issued_loans'] ?? 0),
            'overdue_loans' => (int) ($record['overdue_loans'] ?? 0),
            'returned_loans' => (int) ($record['returned_loans'] ?? 0),
            'outstanding_fines' => (float) ($record['outstanding_fines'] ?? 0),
            'collected_fines' => (float) ($record['collected_fines'] ?? 0),
        ];
    }

    public function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_CLAUSES . '
             WHERE transactions.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function forUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        if ($userId < 1) {
            return [];
        }

        $limit = max(1, min(MAX_PER_PAGE, $limit));
        $offset = max(0, $offset);

        return $this->fetchAll(
            'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_CLAUSES . '
             WHERE transactions.user_id = :user_id
               AND books.deleted_at IS NULL
             ORDER BY
                CASE WHEN transactions.status = \'returned\' THEN 1 ELSE 0 END ASC,
                transactions.issued_at DESC,
                transactions.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            ['user_id' => $userId]
        );
    }

    public function countForUser(int $userId): int
    {
        if ($userId < 1) {
            return 0;
        }

        return (int) $this->fetchValue(
            'SELECT COUNT(*)
             FROM transactions
             INNER JOIN books ON books.id = transactions.book_id
             WHERE transactions.user_id = :user_id
               AND books.deleted_at IS NULL',
            ['user_id' => $userId],
            0
        );
    }

    public function memberSummary(int $userId): array
    {
        if ($userId < 1) {
            return [
                'active_loans' => 0,
                'overdue_loans' => 0,
                'returned_loans' => 0,
                'total_loans' => 0,
                'outstanding_fines' => 0.0,
            ];
        }

        $record = $this->fetchOne(
            'SELECT
                SUM(transactions.status = \'issued\') AS active_loans,
                SUM(transactions.status = \'overdue\') AS overdue_loans,
                SUM(transactions.status = \'returned\') AS returned_loans,
                COUNT(*) AS total_loans,
                COALESCE(SUM(CASE WHEN transactions.status <> \'returned\' THEN transactions.fine_amount ELSE 0 END), 0) AS outstanding_fines
             FROM transactions
             INNER JOIN books ON books.id = transactions.book_id
             WHERE transactions.user_id = :user_id
               AND books.deleted_at IS NULL',
            ['user_id' => $userId]
        ) ?: [];

        return [
            'active_loans' => (int) ($record['active_loans'] ?? 0),
            'overdue_loans' => (int) ($record['overdue_loans'] ?? 0),
            'returned_loans' => (int) ($record['returned_loans'] ?? 0),
            'total_loans' => (int) ($record['total_loans'] ?? 0),
            'outstanding_fines' => (float) ($record['outstanding_fines'] ?? 0),
        ];
    }

    public function activeForUser(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_CLAUSES . '
             WHERE transactions.user_id = :user_id
               AND transactions.status IN (\'issued\', \'overdue\')
               AND books.deleted_at IS NULL
             ORDER BY transactions.due_at ASC, transactions.id ASC',
            ['user_id' => $userId]
        );
    }

    public function hasActiveLoan(int $bookId, int $userId): bool
    {
        if ($bookId < 1 || $userId < 1) {
            return false;
        }

        return (int) $this->fetchValue(
            'SELECT COUNT(*)
             FROM transactions
             WHERE book_id = :book_id
               AND user_id = :user_id
               AND status IN (\'issued\', \'overdue\')',
            ['book_id' => $bookId, 'user_id' => $userId],
            0
        ) > 0;
    }

    public function issue(int $bookId, int $userId, int $issuedBy, ?int $loanDays = null): int
    {
        if ($bookId < 1 || $userId < 1 || $issuedBy < 1) {
            throw new DomainException('A book, a member, and an issuing librarian are all required.');
        }

        $loanDays = $loanDays !== null && $loanDays > 0 ? min($loanDays, 365) : LOAN_DAYS;

        return $this->transaction(function (PDO $database) use ($bookId, $userId, $issuedBy, $loanDays): int {
            $bookStatement = $database->prepare(
                'SELECT id, title, total_copies, available_copies
                 FROM books
                 WHERE id = :id
                   AND deleted_at IS NULL
                 FOR UPDATE'
            );
            $bookStatement->execute(['id' => $bookId]);
            $book = $bookStatement->fetch();

            if (!is_array($book)) {
                throw new DomainException('The selected book is no longer in the catalog.');
            }

            if ((int) $book['available_copies'] < 1) {
                throw new DomainException(sprintf('Every copy of "%s" is currently on loan.', (string) $book['title']));
            }

            $memberStatement = $database->prepare(
                "SELECT id, name
                 FROM users
                 WHERE id = :id
                   AND role = 'member'
                   AND status = 'active'
                 FOR UPDATE"
            );
            $memberStatement->execute(['id' => $userId]);
            $member = $memberStatement->fetch();

            if (!is_array($member)) {
                throw new DomainException('The selected member is not an active library member.');
            }

            $duplicateStatement = $database->prepare(
                "SELECT id
                 FROM transactions
                 WHERE book_id = :book_id
                   AND user_id = :user_id
                   AND status IN ('issued', 'overdue')
                 LIMIT 1"
            );
            $duplicateStatement->execute(['book_id' => $bookId, 'user_id' => $userId]);

            if ($duplicateStatement->fetch() !== false) {
                throw new DomainException(sprintf(
                    '%s already has an active loan for "%s".',
                    (string) $member['name'],
                    (string) $book['title']
                ));
            }

            $issuedAt = new DateTimeImmutable();
            $dueAt = $issuedAt->add(new DateInterval('P' . $loanDays . 'D'));

            $insertStatement = $database->prepare(
                "INSERT INTO transactions
                    (book_id, user_id, issued_by, issued_at, due_at, status, fine_amount, active_loan_key)
                 VALUES
                    (:book_id, :user_id, :issued_by, :issued_at, :due_at, 'issued', 0.00, :active_loan_key)"
            );
            try {
                $insertStatement->execute([
                    'book_id' => $bookId,
                    'user_id' => $userId,
                    'issued_by' => $issuedBy,
                    'issued_at' => $issuedAt->format('Y-m-d H:i:s'),
                    'due_at' => $dueAt->format('Y-m-d H:i:s'),
                    'active_loan_key' => $bookId . ':' . $userId,
                ]);
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    throw new DomainException(sprintf(
                        '%s already has an active loan for "%s".',
                        (string) $member['name'],
                        (string) $book['title']
                    ));
                }

                throw $exception;
            }

            $transactionId = (int) $database->lastInsertId();

            $this->syncBookInventory($database, $bookId);

            return $transactionId;
        });
    }

    public function markReturned(int $id, ?DateTimeImmutable $returnedAt = null): ?array
    {
        if ($id < 1) {
            return null;
        }

        $returnedAt = $returnedAt ?? new DateTimeImmutable();

        return $this->transaction(function (PDO $database) use ($id, $returnedAt): ?array {
            $loanStatement = $database->prepare(
                'SELECT id, book_id, user_id, status, due_at
                 FROM transactions
                 WHERE id = :id
                 FOR UPDATE'
            );
            $loanStatement->execute(['id' => $id]);
            $loan = $loanStatement->fetch();

            if (!is_array($loan)) {
                return null;
            }

            if ((string) $loan['status'] === 'returned') {
                throw new DomainException('This loan has already been returned.');
            }

            $returnedDate = $returnedAt->format('Y-m-d H:i:s');
            $fineStatement = $database->prepare(
                'SELECT GREATEST(DATEDIFF(:returned_date, DATE(due_at)), 0) * :fine_per_day AS fine
                 FROM transactions
                 WHERE id = :id'
            );
            $fineStatement->execute([
                'returned_date' => $returnedAt->format('Y-m-d'),
                'fine_per_day' => FINE_PER_DAY,
                'id' => $id,
            ]);
            $fine = (float) $fineStatement->fetchColumn();

            $updateStatement = $database->prepare(
                "UPDATE transactions
                 SET returned_at = :returned_at,
                     status = 'returned',
                     fine_amount = :fine_amount,
                     active_loan_key = NULL
                 WHERE id = :id"
            );
            $updateStatement->execute([
                'returned_at' => $returnedDate,
                'fine_amount' => number_format($fine, 2, '.', ''),
                'id' => $id,
            ]);

            $this->syncBookInventory($database, (int) $loan['book_id']);

            return [
                'id' => $id,
                'book_id' => (int) $loan['book_id'],
                'fine_amount' => $fine,
                'returned_at' => $returnedDate,
            ];
        });
    }

    public function refreshOverdueStatuses(): int
    {
        $statement = $this->execute(
            "UPDATE transactions
             SET status = 'overdue',
                 fine_amount = GREATEST(DATEDIFF(CURRENT_DATE, DATE(due_at)), 0) * :fine_per_day
             WHERE status = 'issued'
               AND due_at < NOW()",
            ['fine_per_day' => FINE_PER_DAY]
        );

        return $statement->rowCount();
    }

    public function overdueQueue(int $limit = 25): array
    {
        $limit = max(1, min(MAX_PER_PAGE, $limit));

        return $this->fetchAll(
            'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_CLAUSES . '
             WHERE (transactions.status = \'overdue\'
                OR (transactions.status = \'issued\' AND transactions.due_at < NOW()))
               AND books.deleted_at IS NULL
             ORDER BY transactions.due_at ASC, transactions.id ASC
             LIMIT ' . $limit
        );
    }

    public function issueableBooks(string $query = '', int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $query = trim($query);
        $parameters = [];

        if ($query !== '') {
            $parameters['title_search'] = '%' . $query . '%';
            $parameters['author_search'] = '%' . $query . '%';
        }

        $searchSql = $query === ''
            ? ''
            : ' AND (books.title LIKE :title_search OR books.author LIKE :author_search)';

        return $this->fetchAll(
            'SELECT books.id, books.title, books.author, books.category, books.available_copies, books.total_copies
             FROM books
             WHERE books.deleted_at IS NULL
               AND books.available_copies > 0' . $searchSql . '
             ORDER BY books.title ASC
             LIMIT ' . $limit,
            $parameters
        );
    }

    private function syncBookInventory(PDO $database, int $bookId): void
    {
        $statement = $database->prepare(
            "UPDATE books
             SET available_copies = GREATEST(total_copies - (
                     SELECT COUNT(*)
                     FROM transactions
                     WHERE transactions.book_id = books.id
                       AND transactions.status IN ('issued', 'overdue')
                 ), 0),
                 status = IF(
                     GREATEST(total_copies - (
                         SELECT COUNT(*)
                         FROM transactions
                         WHERE transactions.book_id = books.id
                           AND transactions.status IN ('issued', 'overdue')
                     ), 0) > 0,
                     'available',
                     'checked_out'
                 )
             WHERE books.id = :id"
        );
        $statement->execute(['id' => $bookId]);
    }
}
