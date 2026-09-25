<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Transaction.php';

final class MemberController extends Controller
{
    public function dashboard(array $params = []): string
    {
        $memberId = $this->currentUserId();
        $transactions = new Transaction();

        $transactions->refreshOverdueStatuses();

        $summary = $transactions->memberSummary($memberId);
        $activeLoans = $transactions->activeForUser($memberId);

        return $this->render('member/dashboard', [
            'pageTitle' => 'My library',
            'pageDescription' => 'Your AuraLib loans, due dates, and reading history.',
            'summary' => $summary,
            'activeLoans' => $activeLoans,
            'overdueLoans' => array_values(array_filter(
                $activeLoans,
                static function (array $loan): bool {
                    return (string) $loan['status'] === 'overdue';
                }
            )),
            'recommendations' => (new Book())->recommendedFor($memberId, 8),
            'categories' => (new Book())->categories(),
            'memberFirstName' => $this->memberFirstName(),
            'loanDays' => LOAN_DAYS,
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function catalog(array $params = []): string
    {
        $book = new Book();
        $perPage = (int) $this->query('per_page', (string) DEFAULT_PER_PAGE);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : DEFAULT_PER_PAGE;

        return $this->render('member/catalog', [
            'pageTitle' => 'Discover books',
            'pageDescription' => 'Browse available titles in the AuraLib collection.',
            'catalog' => $book->paginate([
                'q' => $this->memberQuery('q'),
                'category' => $this->memberQuery('category'),
                'availability' => 'available',
                'sort' => $this->memberQuery('sort', 'title'),
                'page' => max(1, (int) $this->memberQuery('page', '1')),
            ], $perPage),
            'categories' => $book->categories(),
            'perPage' => $perPage,
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function history(array $params = []): string
    {
        $transactions = new Transaction();
        $transactions->refreshOverdueStatuses();

        return $this->render('member/history', [
            'pageTitle' => 'Reading history',
            'pageDescription' => 'Review your active and completed AuraLib loans.',
            'loans' => $transactions->forUser($this->currentUserId(), MAX_PER_PAGE),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    public function showBook(array $params = []): string
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $book = (new Book())->find($id);

        if (!is_array($book)) {
            $this->flash('danger', 'The requested book could not be found.');

            return $this->redirect(url('member/catalog'));
        }

        return $this->render('member/show-book', [
            'pageTitle' => (string) $book['title'],
            'pageDescription' => 'Book details and availability.',
            'book' => $book,
            'hasActiveLoan' => (new Transaction())->hasActiveLoan($id, $this->currentUserId()),
            'flashMessages' => $this->pullFlashMessages(),
        ]);
    }

    private function memberFirstName(): string
    {
        $name = is_array($this->authUser) ? trim((string) ($this->authUser['name'] ?? '')) : '';
        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $first = is_array($parts) && $parts !== [] ? (string) $parts[0] : '';

        return $first !== '' ? $first : 'Reader';
    }

    private function memberQuery(string $key, string $default = ''): string
    {
        $value = $this->query($key, $default);

        return is_string($value) ? trim($value) : $default;
    }
}
