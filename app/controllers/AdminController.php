<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/User.php';
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
            'pageScripts' => ['js/catalog.js'],
        ]);
    }

    public function searchBooks(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return $this->json(['message' => 'Method not allowed.'], 405);
        }

        $query = $this->catalogTextQuery('q');
        $queryLength = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);

        if ($queryLength === 1) {
            return $this->json([
                'query' => $query,
                'minimum' => true,
                'total' => 0,
                'available' => 0,
                'copies' => 0,
                'html' => '',
            ]);
        }

        $normalizedQuery = function_exists('mb_substr') ? mb_substr($query, 0, 120) : substr($query, 0, 120);
        $perPage = (int) $this->catalogTextQuery('per_page', (string) DEFAULT_PER_PAGE);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : DEFAULT_PER_PAGE;
        $catalog = (new Book())->paginate([
            'q' => $normalizedQuery,
            'category' => $this->catalogTextQuery('category'),
            'status' => $this->catalogTextQuery('status'),
            'availability' => $this->catalogTextQuery('availability'),
            'sort' => $this->catalogTextQuery('sort', 'title'),
            'page' => max(1, (int) $this->catalogTextQuery('page', '1')),
        ], $perPage);
        $available = 0;
        $copies = 0;

        foreach ($catalog['items'] as $book) {
            $available += (int) $book['available_copies'];
            $copies += (int) $book['total_copies'];
        }

        $filtersActive = $catalog['query'] !== ''
            || $catalog['category'] !== ''
            || $catalog['status'] !== ''
            || $catalog['availability'] !== ''
            || $catalog['sort'] !== 'newest'
            || $perPage !== DEFAULT_PER_PAGE;

        return $this->json([
            'query' => $query,
            'minimum' => false,
            'total' => (int) $catalog['total'],
            'available' => $available,
            'copies' => $copies,
            'html' => $this->view('admin/books/_results', compact('catalog', 'perPage', 'filtersActive')),
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

    public function book(array $params = []): string
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $bookModel = new Book();
        $book = $bookModel->find($id);

        if (!is_array($book)) {
            $this->flash('danger', 'The requested book could not be found.');

            return $this->redirect(url('admin/books'));
        }

        return $this->render('admin/books/show', [
            'pageTitle' => (string) $book['title'],
            'pageDescription' => 'Catalog record and circulation status.',
            'book' => $book,
            'activeBorrowers' => $bookModel->activeBorrowers($id),
            'borrowedCopies' => max(0, (int) $book['total_copies'] - (int) $book['available_copies']),
            'availabilityRate' => (int) $book['total_copies'] > 0
                ? (int) round((int) $book['available_copies'] / (int) $book['total_copies'] * 100)
                : 0,
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function updateBook(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('admin/books'));
        }

        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $bookModel = new Book();
        $currentBook = $bookModel->find($id);

        if (!is_array($currentBook)) {
            $this->flash('danger', 'The requested book could not be found.');

            return $this->redirect(url('admin/books'));
        }

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The edit form expired. Please try again.');

            return $this->redirect(url('admin/books/' . $id . '/edit'));
        }

        $data = $this->bookFormData();
        $errors = $this->validateBookData($data);
        $activeLoans = (int) ($currentBook['active_loans'] ?? 0);

        if ((int) $data['total_copies'] < $activeLoans) {
            $errors[] = 'Total copies cannot be lower than the ' . $activeLoans . ' active loans.';
        }

        $editPath = url('admin/books/' . $id . '/edit');

        if ($errors !== []) {
            return $this->failBookForm($errors, $data, $editPath);
        }

        $storage = new BookCoverStorage();
        $upload = isset($_FILES['cover']) && is_array($_FILES['cover']) ? $_FILES['cover'] : [];
        $removeCover = $this->bookPost('remove_cover') === '1';
        $currentCover = isset($currentBook['cover_image']) && is_string($currentBook['cover_image'])
            ? $currentBook['cover_image']
            : null;
        $newCover = null;
        $targetCover = $currentCover;

        try {
            $newCover = $storage->store($upload);

            if ($newCover !== null) {
                $targetCover = $newCover;
            } elseif ($removeCover) {
                $targetCover = null;
            }

            if (!$bookModel->update($id, $data, $targetCover)) {
                $storage->delete($newCover);
                $this->flash('danger', 'The requested book could not be found.');

                return $this->redirect(url('admin/books'));
            }
        } catch (PDOException $exception) {
            $storage->delete($newCover);

            if ((string) $exception->getCode() === '23000') {
                return $this->failBookForm(['A book with that ISBN already exists.'], $data, $editPath);
            }

            throw $exception;
        } catch (DomainException $exception) {
            $storage->delete($newCover);

            return $this->failBookForm([$exception->getMessage()], $data, $editPath);
        } catch (InvalidArgumentException $exception) {
            $storage->delete($newCover);

            return $this->failBookForm([$exception->getMessage()], $data, $editPath);
        } catch (RuntimeException $exception) {
            $storage->delete($newCover);

            return $this->failBookForm([$exception->getMessage()], $data, $editPath);
        } catch (Throwable $exception) {
            $storage->delete($newCover);

            throw $exception;
        }

        if ($currentCover !== null && $currentCover !== $targetCover) {
            $storage->delete($currentCover);
        }

        $this->flash('success', 'Book details were updated.');

        return $this->redirect($editPath);
    }

    public function deleteBook(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('admin/books'));
        }

        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $detailPath = url('admin/books/' . $id);

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The delete form expired. Please try again.');

            return $this->redirect($detailPath);
        }

        try {
            $deletedBook = (new Book())->delete($id);
        } catch (DomainException $exception) {
            $this->flash('warning', $exception->getMessage());

            return $this->redirect($detailPath);
        }

        if (!is_array($deletedBook)) {
            $this->flash('danger', 'The requested book could not be found.');

            return $this->redirect(url('admin/books'));
        }

        if (isset($deletedBook['cover_image']) && is_string($deletedBook['cover_image'])) {
            (new BookCoverStorage())->delete($deletedBook['cover_image']);
        }

        $this->flash('success', 'The book was removed from the catalog.');

        return $this->redirect(url('admin/books'));
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

    public function transactions(array $params = []): string
    {
        $transactionModel = new Transaction();
        $transactionModel->refreshOverdueStatuses();
        $filters = [
            'q' => $this->catalogTextQuery('q'),
            'status' => $this->catalogTextQuery('status'),
            'sort' => $this->catalogTextQuery('sort'),
            'page' => max(1, (int) $this->catalogTextQuery('page', '1')),
        ];

        return $this->render('admin/transactions/index', [
            'pageTitle' => 'Transactions',
            'pageDescription' => 'Monitor loans, returns, and circulation activity.',
            'transactions' => $transactionModel->paginate($filters),
            'statistics' => $transactionModel->statistics(),
            'filters' => $filters,
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function issueForm(array $params = []): string
    {
        $transactionModel = new Transaction();
        $bookQuery = $this->catalogTextQuery('book');
        $memberQuery = $this->catalogTextQuery('member');

        return $this->render('admin/transactions/issue', [
            'pageTitle' => 'Issue a book',
            'pageDescription' => 'Create a circulation record for an active member.',
            'pageScripts' => ['js/issue.js'],
            'books' => $transactionModel->issueableBooks($bookQuery),
            'members' => (new User())->searchActiveMembers($memberQuery),
            'bookQuery' => $bookQuery,
            'memberQuery' => $memberQuery,
            'errors' => $this->pullTransactionErrors(),
            'old' => $this->pullTransactionOld(),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function issueBook(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('admin/transactions/issue'));
        }

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The issue form expired. Please try again.');

            return $this->redirect(url('admin/transactions/issue'));
        }

        $bookId = $this->integerInput('book_id');
        $memberId = $this->integerInput('member_id');
        $loanDays = $this->integerInput('loan_days', LOAN_DAYS);
        $old = ['book_id' => $bookId, 'member_id' => $memberId, 'loan_days' => $loanDays];

        try {
            $transactionId = (new Transaction())->issue($bookId, $memberId, $this->currentUserId(), $loanDays);
        } catch (DomainException $exception) {
            $_SESSION['transaction_errors'] = [$exception->getMessage()];
            $_SESSION['transaction_old'] = $old;

            return $this->redirect(url('admin/transactions/issue'));
        }

        $this->flash('success', 'The book was issued successfully.');

        return $this->redirect(url('admin/transactions/' . $transactionId));
    }

    public function transaction(array $params = []): string
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $transaction = (new Transaction())->find($id);

        if (!is_array($transaction)) {
            $this->flash('danger', 'The requested transaction could not be found.');

            return $this->redirect(url('admin/transactions'));
        }

        return $this->render('admin/transactions/show', [
            'pageTitle' => 'Transaction #' . $id,
            'pageDescription' => 'Review loan details and return status.',
            'transaction' => $transaction,
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function overdue(array $params = []): string
    {
        $transactionModel = new Transaction();
        $transactionModel->refreshOverdueStatuses();

        return $this->render('admin/transactions/overdue', [
            'pageTitle' => 'Overdue watch',
            'pageDescription' => 'Prioritize late returns and outstanding fines.',
            'transactions' => $transactionModel->overdueQueue(),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function searchMembers(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return $this->json(['message' => 'Method not allowed.'], 405);
        }

        return $this->json([
            'members' => (new User())->searchActiveMembers($this->catalogTextQuery('q')),
        ]);
    }

    public function returnBook(array $params = []): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect(url('admin/transactions'));
        }

        $id = isset($params['id']) ? (int) $params['id'] : 0;

        if (!$this->verifyCsrfToken($this->post('_token'))) {
            $this->flash('danger', 'The return form expired. Please try again.');

            return $this->redirect(url('admin/transactions/' . $id));
        }

        try {
            $result = (new Transaction())->markReturned($id);
        } catch (DomainException $exception) {
            $this->flash('warning', $exception->getMessage());

            return $this->redirect(url('admin/transactions/' . $id));
        }

        if ($result === null) {
            $this->flash('danger', 'The requested transaction could not be found.');

            return $this->redirect(url('admin/transactions'));
        }

        $fine = (float) $result['fine_amount'];
        $this->flash('success', $fine > 0
            ? 'Book returned. A $' . number_format($fine, 2) . ' late fine was recorded.'
            : 'Book returned successfully.');

        return $this->redirect(url('admin/transactions/' . $id));
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

    private function pullTransactionErrors(): array
    {
        $errors = isset($_SESSION['transaction_errors']) && is_array($_SESSION['transaction_errors'])
            ? $_SESSION['transaction_errors']
            : [];
        unset($_SESSION['transaction_errors']);

        return $errors;
    }

    private function pullTransactionOld(): array
    {
        $old = isset($_SESSION['transaction_old']) && is_array($_SESSION['transaction_old'])
            ? $_SESSION['transaction_old']
            : [];
        unset($_SESSION['transaction_old']);

        return $old;
    }
}
