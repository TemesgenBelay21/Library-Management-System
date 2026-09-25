<?php

declare(strict_types=1);

$items = $transactions['items'];
$status = (string) ($filters['status'] ?? '');
$query = (string) ($filters['q'] ?? '');
$sort = (string) ($filters['sort'] ?? '');
$page = (int) $transactions['page'];
$lastPage = (int) $transactions['last_page'];
$windowStart = max(1, $page - 2);
$windowEnd = min($lastPage, $windowStart + 4);
$windowStart = max(1, $windowEnd - 4);
$transactionUrl = function (array $overrides = []) use ($query, $status, $sort, $page): string {
    $params = [
        'q' => $query,
        'status' => $status,
        'sort' => $sort,
        'page' => $page,
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return url('admin/transactions') . '?' . http_build_query($params);
};
?>
<section class="page-header">
    <div>
        <span class="eyebrow">Circulation control</span>
        <h1 class="page-title">Transactions</h1>
        <p class="page-subtitle">Track every issue, return, and overdue balance.</p>
    </div>
    <div class="page-actions">
        <button class="button button-primary" type="button" data-drawer-open="issue-drawer"
            aria-controls="issue-drawer" aria-expanded="false">Issue a book</button>
        <a class="button button-secondary" href="<?= url('admin/transactions/overdue') ?>">Overdue watch</a>
    </div>
</section>

<?php if ($flashMessages !== []): ?>
    <div class="flash-stack" aria-live="polite">
        <?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?= htmlspecialchars((string) $message['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="catalog-summary-grid" aria-label="Transaction summary">
    <div class="catalog-summary-card"><span>Total loans</span><strong>
            <?= number_format((int) $statistics['total_loans']) ?>
        </strong></div>
    <div class="catalog-summary-card"><span>Active</span><strong>
            <?= number_format((int) $statistics['issued_loans']) ?>
        </strong></div>
    <div class="catalog-summary-card"><span>Overdue</span><strong class="text-danger">
            <?= number_format((int) $statistics['overdue_loans']) ?>
        </strong></div>
    <div class="catalog-summary-card"><span>Outstanding fines</span><strong>$
            <?= number_format((float) $statistics['outstanding_fines'], 2) ?>
        </strong></div>
</section>

<section class="glass-panel catalog-filter-panel">
    <form class="catalog-filter-form" method="get" action="<?= url('admin/transactions') ?>">
        <label class="filter-search">
            <span class="sr-only">Search transactions</span>
            <input class="form-control" type="search" name="q"
                value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Book, member, or email">
        </label>
        <label>
            <span class="sr-only">Transaction status</span>
            <select class="form-control" name="status">
                <option value="">All statuses</option>
                <option value="issued" <?= $status === 'issued' ? ' selected' : '' ?>>Issued</option>
                <option value="overdue" <?= $status === 'overdue' ? ' selected' : '' ?>>Overdue</option>
                <option value="returned" <?= $status === 'returned' ? ' selected' : '' ?>>Returned</option>
            </select>
        </label>
        <label>
            <span class="sr-only">Sort transactions</span>
            <select class="form-control" name="sort">
                <option value="" <?= $sort === '' ? ' selected' : '' ?>>Newest first</option>
                <option value="oldest" <?= $sort === 'oldest' ? ' selected' : '' ?>>Oldest first</option>
                <option value="due_soon" <?= $sort === 'due_soon' ? ' selected' : '' ?>>Due soonest</option>
                <option value="title" <?= $sort === 'title' ? ' selected' : '' ?>>Book title</option>
                <option value="member" <?= $sort === 'member' ? ' selected' : '' ?>>Member name</option>
            </select>
        </label>
        <button class="button button-primary filter-submit" type="submit">Filter</button>
    </form>
</section>

<section class="glass-panel dashboard-panel">
    <?php if ($items !== []) : ?>
        <div class="catalog-results-heading">
            <div>
                <span class="section-kicker">Results</span>
                <p>
                    Showing <?= number_format((int) $transactions['from']) ?>–<?= number_format((int) $transactions['to']) ?> of
                    <?= number_format((int) $transactions['total']) ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Book</th>
                    <th scope="col">Member</th>
                    <th scope="col">Status</th>
                    <th scope="col">Due</th>
                    <th scope="col">Fine</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong>
                                <?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?>
                            </strong><small>
                                <?= htmlspecialchars((string) $item['author'], ENT_QUOTES, 'UTF-8') ?>
                            </small></td>
                        <td>
                            <?= htmlspecialchars((string) $item['member_name'], ENT_QUOTES, 'UTF-8') ?><small>
                                <?= htmlspecialchars((string) $item['member_email'], ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        </td>
                        <td><span
                                class="status-badge status-<?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(ucfirst((string) $item['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span></td>
                        <td>
                            <?= htmlspecialchars(date(DISPLAY_DATE_FORMAT, strtotime((string) $item['due_at'])), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>$
                            <?= number_format((float) $item['fine_amount'], 2) ?>
                        </td>
                        <td><a class="table-action" href="<?= url('admin/transactions/' . (int) $item['id']) ?>"
                                aria-label="View transaction">→</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($items === []): ?>
        <div class="empty-state compact">
            <h3>No transactions found</h3>
            <p>Issue a book or adjust the filters to see circulation activity.</p>
        </div>
    <?php endif; ?>
    <?php if ($lastPage > 1) : ?>
        <nav class="pagination-wrap" aria-label="Transaction pages">
            <?php if ($page > 1) : ?>
                <a class="pagination-link" href="<?= htmlspecialchars($transactionUrl(['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Previous page">←</a>
            <?php else : ?>
                <span class="pagination-link is-disabled" aria-disabled="true">←</span>
            <?php endif; ?>
            <?php for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++) : ?>
                <?php if ($pageNumber === $page) : ?>
                    <span class="pagination-link is-current" aria-current="page"><?= $pageNumber ?></span>
                <?php else : ?>
                    <a class="pagination-link" href="<?= htmlspecialchars($transactionUrl(['page' => $pageNumber]), ENT_QUOTES, 'UTF-8') ?>"><?= $pageNumber ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $lastPage) : ?>
                <a class="pagination-link" href="<?= htmlspecialchars($transactionUrl(['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Next page">→</a>
            <?php else : ?>
                <span class="pagination-link is-disabled" aria-disabled="true">→</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<div class="drawer" id="issue-drawer" data-drawer="issue" aria-hidden="true" role="dialog" aria-modal="true"
    aria-labelledby="issue-drawer-title">
    <div class="drawer-panel">
        <div class="drawer-header">
            <div>
                <span class="section-kicker">Circulation control</span>
                <h2 class="drawer-title" id="issue-drawer-title">Issue a book</h2>
            </div>
            <button class="icon-button" type="button" data-drawer-close aria-label="Close issue panel">&times;</button>
        </div>
        <div class="drawer-body">
            <?= $this->view('admin/transactions/_issue-form', [
                'books' => $issueBooks,
                'members' => $issueMembers,
                'old' => $issueOld,
                'errors' => $issueErrors,
            ]) ?>
        </div>
    </div>
</div>
<?php if ($issueErrors !== []) : ?>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var trigger = document.querySelector('[data-drawer-open="issue-drawer"]');
            window.AuraLib.openDrawer(document.getElementById('issue-drawer'), trigger);
        });
    </script>
<?php endif; ?>
