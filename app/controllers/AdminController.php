<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Book.php';

final class AdminController extends Controller
{
    public function dashboard(array $params = []): string
    {
        $report = new Report();
        $dashboard = [
            'metrics' => $report->summaryMetrics(),
            'recentTransactions' => $report->recentTransactions(7),
            'overdueTransactions' => $report->overdueTransactions(6),
            'categoryBreakdown' => $report->categoryBreakdown(6),
            'circulationTrend' => $report->circulationTrend(7),
        ];

        return $this->render('admin/dashboard', [
            'pageTitle' => 'Admin dashboard',
            'pageDescription' => 'AuraLib collection, circulation, and overdue intelligence.',
            'dashboard' => $dashboard,
            'overdueCount' => (int) $dashboard['metrics']['overdue_loans'],
            'flashMessages' => $this->pullFlashMessages(),
            'pageScripts' => ['js/dashboard.js'],
        ]);
    }

    public function books(array $params = []): string
    {
        $book = new Book();
        $perPage = (int) $this->query('per_page', (string) DEFAULT_PER_PAGE);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : DEFAULT_PER_PAGE;
        $filters = [
            'q' => $this->catalogTextQuery('q'),
            'category' => $this->catalogTextQuery('category'),
            'status' => $this->catalogTextQuery('status'),
            'availability' => $this->catalogTextQuery('availability'),
            'sort' => $this->catalogTextQuery('sort'),
            'page' => max(1, (int) $this->catalogTextQuery('page', '1')),
        ];
        $catalog = $book->paginate($filters, $perPage);

        return $this->render('admin/books/index', [
            'pageTitle' => 'Book catalog',
            'pageDescription' => 'Manage the AuraLib collection, inventory, and shelving.',
            'catalog' => $catalog,
            'categories' => $book->categories(),
            'perPage' => $perPage,
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function createBook(array $params = []): string
    {
        $book = new Book();
        $old = array_merge([
            'title' => '',
            'author' => '',
            'isbn' => '',
            'category' => '',
            'description' => '',
            'total_copies' => 1,
            'shelf_location' => '',
            'published_year' => '',
        ], $this->pullBookOld());

        return $this->render('admin/books/create', [
            'pageTitle' => 'Add book',
            'pageDescription' => 'Add a title to the AuraLib catalog.',
            'book' => $old,
            'categories' => $book->categories(),
            'errors' => $this->pullBookErrors(),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    private function catalogTextQuery(string $key, string $default = ''): string
    {
        $value = $this->query($key, $default);

        return is_string($value) ? trim($value) : $default;
    }

    private function pullBookErrors(): array
    {
        $errors = isset($_SESSION['book_errors']) && is_array($_SESSION['book_errors'])
            ? $_SESSION['book_errors']
            : [];
        unset($_SESSION['book_errors']);

        return $errors;
    }

    private function pullBookOld(): array
    {
        $old = isset($_SESSION['book_old']) && is_array($_SESSION['book_old'])
            ? $_SESSION['book_old']
            : [];
        unset($_SESSION['book_old']);

        return $old;
    }
}
