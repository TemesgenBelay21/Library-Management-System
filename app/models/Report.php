<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Model.php';

final class Report extends Model
{
    public function summaryMetrics(): array
    {
        $record = $this->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM books) AS total_books,
                (SELECT COALESCE(SUM(total_copies), 0) FROM books) AS total_copies,
                (SELECT COALESCE(SUM(available_copies), 0) FROM books) AS available_copies,
                (SELECT COUNT(*) FROM users WHERE role = \'member\' AND status = \'active\') AS active_members,
                (SELECT COUNT(*) FROM transactions WHERE status IN (\'issued\', \'overdue\')) AS active_loans,
                (SELECT COUNT(*) FROM transactions WHERE status = \'overdue\') AS overdue_loans'
        ) ?: [];

        return [
            'total_books' => (int) ($record['total_books'] ?? 0),
            'total_copies' => (int) ($record['total_copies'] ?? 0),
            'available_copies' => (int) ($record['available_copies'] ?? 0),
            'active_members' => (int) ($record['active_members'] ?? 0),
            'active_loans' => (int) ($record['active_loans'] ?? 0),
            'overdue_loans' => (int) ($record['overdue_loans'] ?? 0),
        ];
    }

    public function recentTransactions(int $limit = 7): array
    {
        $limit = max(1, min(25, $limit));

        return $this->fetchAll(
            'SELECT
                transactions.id,
                transactions.status,
                transactions.issued_at,
                transactions.due_at,
                transactions.returned_at,
                books.title,
                books.author,
                users.name AS member_name
             FROM transactions
             INNER JOIN books ON books.id = transactions.book_id
             INNER JOIN users ON users.id = transactions.user_id
             ORDER BY transactions.created_at DESC, transactions.id DESC
             LIMIT ' . $limit
        );
    }

    public function overdueTransactions(int $limit = 6): array
    {
        $limit = max(1, min(25, $limit));

        return $this->fetchAll(
            'SELECT
                transactions.id,
                transactions.issued_at,
                transactions.due_at,
                transactions.fine_amount,
                DATEDIFF(CURRENT_DATE, DATE(transactions.due_at)) AS days_overdue,
                books.title,
                books.author,
                users.name AS member_name,
                users.email AS member_email
             FROM transactions
             INNER JOIN books ON books.id = transactions.book_id
             INNER JOIN users ON users.id = transactions.user_id
             WHERE transactions.status = \'overdue\'
                OR (transactions.status = \'issued\' AND transactions.due_at < CURRENT_DATE)
             ORDER BY transactions.due_at ASC, transactions.id ASC
             LIMIT ' . $limit
        );
    }

    public function categoryBreakdown(int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));

        return $this->fetchAll(
            'SELECT
                category,
                COUNT(*) AS title_count,
                COALESCE(SUM(total_copies), 0) AS copy_count
             FROM books
             GROUP BY category
             ORDER BY copy_count DESC, category ASC
             LIMIT ' . $limit
        );
    }

    public function circulationTrend(int $days = 7): array
    {
        $days = max(3, min(14, $days));
        $cursor = new DateTimeImmutable('today');
        $periods = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = $cursor->sub(new DateInterval('P' . $offset . 'D'));
            $key = $date->format('Y-m-d');
            $periods[$key] = [
                'date' => $key,
                'label' => $date->format('D'),
                'issued' => 0,
                'returned' => 0,
            ];
        }

        $start = $cursor->sub(new DateInterval('P' . ($days - 1) . 'D'))->format('Y-m-d 00:00:00');
        $rows = $this->fetchAll(
            'SELECT activity_date, SUM(issued_count) AS issued_count, SUM(returned_count) AS returned_count
             FROM (
                SELECT DATE(issued_at) AS activity_date, COUNT(*) AS issued_count, 0 AS returned_count
                FROM transactions
                WHERE issued_at >= :issued_start
                GROUP BY DATE(issued_at)
                UNION ALL
                SELECT DATE(returned_at) AS activity_date, 0 AS issued_count, COUNT(*) AS returned_count
                FROM transactions
                WHERE returned_at >= :returned_start
                GROUP BY DATE(returned_at)
             ) AS activity
             GROUP BY activity_date',
            [
                'issued_start' => $start,
                'returned_start' => $start,
            ]
        );

        foreach ($rows as $row) {
            $date = isset($row['activity_date']) ? substr((string) $row['activity_date'], 0, 10) : '';

            if (isset($periods[$date])) {
                $periods[$date]['issued'] = (int) $row['issued_count'];
                $periods[$date]['returned'] = (int) $row['returned_count'];
            }
        }

        return array_values($periods);
    }
}
