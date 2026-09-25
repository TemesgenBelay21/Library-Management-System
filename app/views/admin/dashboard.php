<?php

declare(strict_types=1);

$totalBooks = (int) $dashboard['metrics']['total_books'];
$totalCopies = (int) $dashboard['metrics']['total_copies'];
$availableCopies = (int) $dashboard['metrics']['available_copies'];
$activeMembers = (int) $dashboard['metrics']['active_members'];
$activeLoans = (int) $dashboard['metrics']['active_loans'];
$overdueLoans = (int) $dashboard['metrics']['overdue_loans'];
$collectionRate = $totalCopies > 0 ? (int) round($availableCopies / $totalCopies * 100) : 0;
$loanRate = $totalCopies > 0 ? (int) round($activeLoans / $totalCopies * 100) : 0;
$overdueRate = $activeLoans > 0 ? (int) round($overdueLoans / $activeLoans * 100) : 0;
$metrics = [
    [
        'key' => 'total_books',
        'label' => 'Catalog titles',
        'value' => $totalBooks,
        'detail' => $totalBooks === 1 ? 'registered title' : 'registered titles',
        'progress' => $collectionRate,
        'tone' => 'violet',
        'icon' => 'books',
    ],
    [
        'key' => 'total_copies',
        'label' => 'Total copies',
        'value' => $totalCopies,
        'detail' => $availableCopies . ' currently available',
        'progress' => $collectionRate,
        'tone' => 'cyan',
        'icon' => 'copies',
    ],
    [
        'key' => 'active_members',
        'label' => 'Active members',
        'value' => $activeMembers,
        'detail' => 'members in good standing',
        'progress' => null,
        'tone' => 'emerald',
        'icon' => 'members',
    ],
    [
        'key' => 'active_loans',
        'label' => 'Active loans',
        'value' => $activeLoans,
        'detail' => $loanRate . '% of total inventory',
        'progress' => $loanRate,
        'tone' => 'blue',
        'icon' => 'loans',
    ],
    [
        'key' => 'available_copies',
        'label' => 'Available now',
        'value' => $availableCopies,
        'detail' => $collectionRate . '% collection availability',
        'progress' => $collectionRate,
        'tone' => 'emerald',
        'icon' => 'available',
    ],
    [
        'key' => 'overdue_loans',
        'label' => 'Overdue pressure',
        'value' => $overdueLoans,
        'detail' => $overdueRate . '% of active loans',
        'progress' => $overdueRate,
        'tone' => 'rose',
        'icon' => 'overdue',
    ],
];
$categoryMaximum = 1;

foreach ($dashboard['categoryBreakdown'] as $category) {
    $categoryMaximum = max($categoryMaximum, (int) $category['copy_count']);
}

$trendMaximum = 1;

foreach ($dashboard['circulationTrend'] as $trend) {
    $trendMaximum = max($trendMaximum, (int) $trend['issued'], (int) $trend['returned']);
}
?>
<section class="page-header page-heading">
    <div>
        <span class="eyebrow"><span class="pulse-dot"></span> Live operations</span>
        <h1>Command center</h1>
        <p>Collection health and member circulation at a glance.</p>
    </div>
    <div class="page-actions">
        <a class="button button-secondary" href="<?= url('admin/transactions') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13"></path><circle cx="3" cy="6" r="1"></circle><circle cx="3" cy="12" r="1"></circle><circle cx="3" cy="18" r="1"></circle></svg>
            Circulation
        </a>
        <a class="button button-primary" href="<?= url('admin/books/create') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
            Add book
        </a>
    </div>
</section>

<?php if ($flashMessages !== []) : ?>
    <div class="flash-stack" aria-live="polite">
        <?php foreach ($flashMessages as $message) : ?>
            <div class="alert alert-<?= htmlspecialchars((string) $message['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="metric-grid" aria-label="Library metrics">
    <?php foreach ($metrics as $metric) : ?>
        <?php require VIEW_PATH . '/partials/metric-card.php'; ?>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid">
    <article class="glass-panel dashboard-panel category-panel">
        <header class="panel-heading">
            <div>
                <span class="section-kicker">Collection intelligence</span>
                <h2>Category mix</h2>
            </div>
            <span class="panel-signal">Live</span>
        </header>
        <?php if ($dashboard['categoryBreakdown'] === []) : ?>
            <div class="empty-state compact">
                <div class="empty-state-icon">⌁</div>
                <h3>No collection data</h3>
                <p>Add books to reveal category intelligence.</p>
            </div>
        <?php else : ?>
            <div class="category-list">
                <?php foreach ($dashboard['categoryBreakdown'] as $category) : ?>
                    <?php $categoryCount = (int) $category['copy_count']; ?>
                    <?php $categoryWidth = (int) round($categoryCount / $categoryMaximum * 100); ?>
                    <div class="category-row">
                        <div class="category-label">
                            <strong><?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= number_format($categoryCount) ?> copies</span>
                        </div>
                        <div class="progress-track" aria-label="<?= $categoryWidth ?> percent of leading category">
                            <span style="width: <?= $categoryWidth ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="glass-panel dashboard-panel trend-panel">
        <header class="panel-heading">
            <div>
                <span class="section-kicker">Last seven days</span>
                <h2>Circulation rhythm</h2>
            </div>
            <div class="chart-legend">
                <span><i class="legend-issued"></i>Issued</span>
                <span><i class="legend-returned"></i>Returned</span>
            </div>
        </header>
        <div class="trend-chart" data-trend-chart aria-label="Daily issued and returned loans">
            <?php foreach ($dashboard['circulationTrend'] as $trend) : ?>
                <?php
                $issuedHeight = (int) round((int) $trend['issued'] / $trendMaximum * 100);
                $returnedHeight = (int) round((int) $trend['returned'] / $trendMaximum * 100);
                ?>
                <div class="trend-column" title="<?= htmlspecialchars((string) $trend['label'], ENT_QUOTES, 'UTF-8') ?>: <?= (int) $trend['issued'] ?> issued, <?= (int) $trend['returned'] ?> returned">
                    <div class="trend-bars">
                        <span class="trend-bar trend-issued" style="height: <?= $issuedHeight ?>%" data-value="<?= (int) $trend['issued'] ?>"></span>
                        <span class="trend-bar trend-returned" style="height: <?= $returnedHeight ?>%" data-value="<?= (int) $trend['returned'] ?>"></span>
                    </div>
                    <span class="trend-label"><?= htmlspecialchars((string) $trend['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="glass-panel dashboard-panel overdue-panel">
    <header class="panel-heading">
        <div>
            <span class="section-kicker danger-kicker">Immediate follow-up</span>
            <h2>Overdue pressure</h2>
        </div>
        <div class="overdue-heading-actions">
            <span class="overdue-total"><strong><?= number_format($overdueCount) ?></strong> open</span>
            <a class="text-link" href="<?= url('admin/transactions/overdue') ?>">Review queue <span aria-hidden="true">→</span></a>
        </div>
    </header>
    <?php if ($dashboard['overdueTransactions'] === []) : ?>
        <div class="overdue-clear">
            <span class="overdue-clear-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
            </span>
            <div>
                <strong>Circulation is on track</strong>
                <p>No loans are currently past their return date.</p>
            </div>
        </div>
    <?php else : ?>
        <div class="overdue-list">
            <?php foreach ($dashboard['overdueTransactions'] as $overdue) : ?>
                <?php
                $daysOverdue = max(1, (int) $overdue['days_overdue']);
                $severity = $daysOverdue >= 14 ? 'critical' : ($daysOverdue >= 7 ? 'high' : 'watch');
                $memberName = trim((string) $overdue['member_name']);
                $initials = implode('', array_map(static function (string $part): string {
                    return strtoupper(substr($part, 0, 1));
                }, array_slice(preg_split('/\s+/', $memberName, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2)));
                $dueTimestamp = strtotime((string) $overdue['due_at']);
                ?>
                <article class="overdue-item overdue-<?= $severity ?>">
                    <span class="member-avatar" aria-hidden="true"><?= htmlspecialchars($initials !== '' ? $initials : 'M', ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="overdue-book">
                        <strong><?= htmlspecialchars((string) $overdue['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $overdue['author'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="overdue-member">
                        <span>Borrower</span>
                        <strong><?= htmlspecialchars($memberName, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="overdue-due">
                        <span>Due date</span>
                        <strong><?= $dueTimestamp ? date(DISPLAY_DATE_FORMAT, $dueTimestamp) : 'Unknown' ?></strong>
                    </div>
                    <div class="overdue-delay">
                        <strong><?= $daysOverdue ?> <?= $daysOverdue === 1 ? 'day' : 'days' ?></strong>
                        <span><?= (float) $overdue['fine_amount'] > 0 ? '$' . number_format((float) $overdue['fine_amount'], 2) . ' fine' : 'Review now' ?></span>
                    </div>
                    <a class="table-action" href="<?= url('admin/transactions/' . (int) $overdue['id']) ?>" aria-label="Review overdue transaction <?= (int) $overdue['id'] ?>">→</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="glass-panel dashboard-panel recent-panel">
    <header class="panel-heading">
        <div>
            <span class="section-kicker">Latest movement</span>
            <h2>Recent transactions</h2>
        </div>
        <a class="text-link" href="<?= url('admin/transactions') ?>">View all <span aria-hidden="true">→</span></a>
    </header>
    <?php if ($dashboard['recentTransactions'] === []) : ?>
        <div class="empty-state compact">
            <div class="empty-state-icon">↗</div>
            <h3>No circulation yet</h3>
            <p>Issued and returned loans will appear here.</p>
        </div>
    <?php else : ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Book</th>
                        <th scope="col">Member</th>
                        <th scope="col">Issued</th>
                        <th scope="col">Due</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboard['recentTransactions'] as $transaction) : ?>
                        <?php
                        $issuedAt = strtotime((string) $transaction['issued_at']);
                        $dueAt = strtotime((string) $transaction['due_at']);
                        $status = (string) $transaction['status'];
                        ?>
                        <tr>
                            <td>
                                <div class="table-primary">
                                    <strong><?= htmlspecialchars((string) $transaction['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars((string) $transaction['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars((string) $transaction['member_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $issuedAt ? date(DISPLAY_DATE_FORMAT, $issuedAt) : '—' ?></td>
                            <td><?= $dueAt ? date(DISPLAY_DATE_FORMAT, $dueAt) : '—' ?></td>
                            <td><span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= ucfirst(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')) ?></span></td>
                            <td><a class="table-action" href="<?= url('admin/transactions/' . (int) $transaction['id']) ?>" aria-label="View transaction <?= (int) $transaction['id'] ?>">→</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
