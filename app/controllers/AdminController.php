<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../services/BookCoverStorage.php';

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

    public function storeBook(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('admin/books/create'));
        }

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The book form expired. Please try again.');

            return $this->redirect(url('admin/books/create'));
        }

        $data = $this->bookFormData();
        $errors = $this->validateBookData($data);

        if ($errors !== []) {
            return $this->failBookForm($errors, $data, url('admin/books/create'));
        }

        $upload = isset($_FILES['cover']) && is_array($_FILES['cover']) ? $_FILES['cover'] : [];
        $storage = new BookCoverStorage();
        $coverImage = null;

        try {
            $coverImage = $storage->store($upload);
            $bookId = (new Book())->create($data, $coverImage);
        } catch (PDOException $exception) {
            $storage->delete($coverImage);

            if ((string) $exception->getCode() === '23000') {
                return $this->failBookForm(
                    ['A book with that ISBN already exists.'],
                    $data,
                    url('admin/books/create')
                );
            }

            throw $exception;
        } catch (InvalidArgumentException $exception) {
            $storage->delete($coverImage);

            return $this->failBookForm([$exception->getMessage()], $data, url('admin/books/create'));
        } catch (RuntimeException $exception) {
            $storage->delete($coverImage);

            return $this->failBookForm([$exception->getMessage()], $data, url('admin/books/create'));
        } catch (Throwable $exception) {
            $storage->delete($coverImage);

            throw $exception;
        }

        $this->flash('success', 'The book was added to the catalog.');

        return $this->redirect(url('admin/books/' . $bookId));
    }

    public function editBook(array $params = []): string
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $bookModel = new Book();
        $book = $bookModel->find($id);

        if (!is_array($book)) {
            $this->flash('danger', 'The requested book could not be found.');

            return $this->redirect(url('admin/books'));
        }

        $book = array_merge([
            'title' => '',
            'author' => '',
            'isbn' => '',
            'category' => '',
            'description' => '',
            'total_copies' => 1,
            'available_copies' => 0,
            'shelf_location' => '',
            'published_year' => '',
            'cover_image' => null,
            'active_loans' => 0,
        ], $book, $this->pullBookOld());

        return $this->render('admin/books/edit', [
            'pageTitle' => 'Edit ' . (string) $book['title'],
            'pageDescription' => 'Update bibliographic and inventory details for this title.',
            'book' => $book,
            'categories' => $bookModel->categories(),
            'minimumCopies' => max(1, (int) $book['active_loans']),
            'hasCover' => is_string($book['cover_image']) && $book['cover_image'] !== '',
            'errors' => $this->pullBookErrors(),
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

    private function bookFormData(): array
    {
        $isbn = strtoupper((string) preg_replace('/[\s-]+/', '', $this->bookPost('isbn')));
        $copies = filter_var($this->bookPost('total_copies'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 10000],
        ]);
        $yearInput = $this->bookPost('published_year');
        $year = $yearInput === '' ? null : filter_var($yearInput, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1000, 'max_range' => (int) date('Y')],
        ]);

        return [
            'title' => $this->bookPost('title'),
            'author' => $this->bookPost('author'),
            'isbn' => $isbn,
            'category' => $this->bookPost('category'),
            'description' => $this->bookPost('description'),
            'total_copies' => $copies === false ? 0 : (int) $copies,
            'shelf_location' => $this->bookPost('shelf_location'),
            'published_year' => $year === false ? 0 : $year,
        ];
    }

    private function validateBookData(array $data): array
    {
        $errors = [];
        $titleLength = $this->textLength((string) $data['title']);
        $authorLength = $this->textLength((string) $data['author']);
        $categoryLength = $this->textLength((string) $data['category']);
        $descriptionLength = $this->textLength((string) $data['description']);
        $shelfLength = $this->textLength((string) $data['shelf_location']);

        if ($titleLength < 2 || $titleLength > 220) {
            $errors[] = 'Title must be between 2 and 220 characters.';
        }

        if ($authorLength < 2 || $authorLength > 180) {
            $errors[] = 'Author must be between 2 and 180 characters.';
        }

        if ($data['isbn'] !== '' && preg_match('/^(?:[0-9]{10}|[0-9]{13})$/D', (string) $data['isbn']) !== 1) {
            $errors[] = 'ISBN must contain 10 or 13 digits.';
        }

        if ($categoryLength < 2 || $categoryLength > 100) {
            $errors[] = 'Category must be between 2 and 100 characters.';
        }

        if ($descriptionLength > 5000) {
            $errors[] = 'Description cannot exceed 5,000 characters.';
        }

        if ((int) $data['total_copies'] < 1 || (int) $data['total_copies'] > 10000) {
            $errors[] = 'Total copies must be between 1 and 10,000.';
        }

        if ($shelfLength > 80) {
            $errors[] = 'Shelf location cannot exceed 80 characters.';
        }

        if ($data['published_year'] !== null && ((int) $data['published_year'] < 1000 || (int) $data['published_year'] > (int) date('Y'))) {
            $errors[] = 'Published year must be between 1000 and the current year.';
        }

        return $errors;
    }

    private function bookPost(string $key, string $default = ''): string
    {
        $value = $this->post($key, $default);

        return is_string($value) ? trim($value) : $default;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? (int) mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function failBookForm(array $errors, array $old, string $path): string
    {
        $_SESSION['book_errors'] = $errors;
        $_SESSION['book_old'] = $old;

        return $this->redirect($path);
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
