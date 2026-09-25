<?php

declare(strict_types=1);

$items = $transactions['items'];
$status = (string) ($filters['status'] ?? '');
$query = (string) ($filters['q'] ?? '');
?>
<section class="page-header">
    <div>
        <span class="eyebrow">Circulation control</span>
        <h1 class="page-title">Transactions</h1>
        <p class="page-subtitle">Track every issue, return, and overdue balance.</p>
    </div>
    <div class="page-actions">
        <a class="button button-primary" href="<?= url('admin/transactions/issue') ?>">Issue a book</a>
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
        <button class="button button-primary filter-submit" type="submit">Filter</button>
    </form>
</section>

<section class="glass-panel dashboard-panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Fine</th>
                    <th></th>
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
</section>