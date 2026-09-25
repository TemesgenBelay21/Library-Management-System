<?php

declare(strict_types=1);

$activeLoanCount = (int) $summary['active_loans'];
$overdueLoanCount = (int) $summary['overdue_loans'];
$returnedLoanCount = (int) $summary['returned_loans'];
$totalLoanCount = (int) $summary['total_loans'];
$outstandingFines = (float) $summary['outstanding_fines'];
$collectionRate = $totalLoanCount > 0 ? (int) round($activeLoanCount / $totalLoanCount * 100) : 0;
$settledRate = $totalLoanCount > 0 ? (int) round($returnedLoanCount / $totalLoanCount * 100) : 0;

$metrics = [
    [
        'key' => 'active_loans',
        'label' => 'On loan',
        'value' => $activeLoanCount,
        'display' => number_format($activeLoanCount),
        'detail' => $activeLoanCount === 1 ? 'title out with you' : 'titles out with you',
        'progress' => $collectionRate,
        'tone' => 'cyan',
        'icon' => 'loans',
    ],
    [
        'key' => 'overdue_loans',
        'label' => 'Overdue',
        'value' => $overdueLoanCount,
        'display' => number_format($overdueLoanCount),
        'detail' => $overdueLoanCount === 0 ? 'nothing past due' : 'need attention',
        'progress' => null,
        'tone' => 'rose',
        'icon' => 'overdue',
    ],
    [
        'key' => 'returned_loans',
        'label' => 'Completed',
        'value' => $returnedLoanCount,
        'display' => number_format($returnedLoanCount),
        'detail' => $settledRate . '% of your loans returned',
        'progress' => $settledRate,
        'tone' => 'emerald',
        'icon' => 'available',
    ],
    [
        'key' => 'outstanding_fines',
        'label' => 'Fines owed',
        'value' => (int) round($outstandingFines * 100),
        'display' => '$' . number_format($outstandingFines, 2),
        'detail' => $outstandingFines > 0 ? 'across overdue titles' : 'clear standing',
        'progress' => null,
        'tone' => 'violet',
        'icon' => 'members',
    ],
];
?>
<section class="page-header page-heading">
    <div>
        <span class="eyebrow"><span class="pulse-dot"></span> Member portal</span>
        <h1>Welcome back, <?= htmlspecialchars($memberFirstName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Track what you are reading, what is due, and what to borrow next.</p>
    </div>
    <div class="page-actions">
        <a class="button button-secondary" href="<?= url('member/history') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"></path><path d="M3 3v5h5M12 7v5l3 2"></path></svg>
            Reading history
        </a>
        <a class="button button-primary" href="<?= url('member/catalog') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            Discover books
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

<section class="metric-grid" aria-label="Your library metrics">
    <?php foreach ($metrics as $metric) : ?>
        <?php require VIEW_PATH . '/partials/metric-card.php'; ?>
    <?php endforeach; ?>
</section>

<?php if ($overdueLoans !== []) : ?>
    <section class="glass-panel dashboard-panel overdue-panel">
        <header class="panel-heading">
            <div>
                <span class="section-kicker danger-kicker">Return these first</span>
                <h2>Overdue titles</h2>
            </div>
            <div class="overdue-heading-actions">
                <span class="overdue-total"><strong><?= number_format(count($overdueLoans)) ?></strong> past due</span>
                <a class="text-link" href="<?= url('member/history') ?>">Full history <span aria-hidden="true">→</span></a>
            </div>
        </header>
        <div class="overdue-list">
            <?php foreach ($overdueLoans as $loan) : ?>
                <?php
                $daysOverdue = max(1, (int) $loan['days_overdue']);
                $severity = $daysOverdue >= 14 ? 'critical' : ($daysOverdue >= 7 ? 'high' : 'watch');
                $dueTimestamp = strtotime((string) $loan['due_at']);
                ?>
                <article class="overdue-item overdue-<?= $severity ?>">
                    <?php if (isset($loan['cover_image']) && is_string($loan['cover_image']) && $loan['cover_image'] !== '') : ?>
                        <img class="loan-thumb" src="<?= url('catalog/' . (int) $loan['book_id'] . '/cover') ?>" alt="" width="46" height="69" loading="lazy">
                    <?php else : ?>
                        <span class="loan-thumb loan-thumb-placeholder" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </span>
                    <?php endif; ?>
                    <div class="overdue-book">
                        <strong><?= htmlspecialchars((string) $loan['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $loan['author'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="overdue-member">
                        <span>Issued</span>
                        <strong><?= $dueTimestamp ? date(DISPLAY_DATE_FORMAT, strtotime((string) $loan['issued_at'])) : 'Unknown' ?></strong>
                    </div>
                    <div class="overdue-due">
                        <span>Was due</span>
                        <strong><?= $dueTimestamp ? date(DISPLAY_DATE_FORMAT, $dueTimestamp) : 'Unknown' ?></strong>
                    </div>
                    <div class="overdue-delay">
                        <strong><?= $daysOverdue ?> <?= $daysOverdue === 1 ? 'day' : 'days' ?></strong>
                        <span><?= (float) $loan['fine_amount'] > 0 ? '$' . number_format((float) $loan['fine_amount'], 2) . ' accrued' : 'Return to clear' ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="glass-panel dashboard-panel">
    <header class="panel-heading">
        <div>
            <span class="section-kicker">Out with you now</span>
            <h2>Currently reading</h2>
        </div>
        <span class="panel-signal"><?= $loanDays ?>-day loans</span>
    </header>
    <?php if ($activeLoans === []) : ?>
        <div class="empty-state compact">
            <div class="empty-state-icon">◇</div>
            <h3>No books on loan</h3>
            <p>Browse the catalog and ask a librarian to check out a title for you.</p>
            <a class="button button-primary" href="<?= url('member/catalog') ?>">Browse the catalog</a>
        </div>
    <?php else : ?>
        <div class="loan-list">
            <?php foreach ($activeLoans as $loan) : ?>
                <?php
                $loanId = (int) $loan['id'];
                $dueTimestamp = strtotime((string) $loan['due_at']);
                $daysLeft = $dueTimestamp ? (int) ceil(($dueTimestamp - time()) / 86400) : 0;
                $isOverdue = $daysLeft < 0;
                $status = (string) $loan['status'];
                $countdownLabel = $isOverdue
                    ? abs($daysLeft) . ' ' . (abs($daysLeft) === 1 ? 'day' : 'days') . ' late'
                    : ($daysLeft === 0 ? 'Due today' : $daysLeft . ' ' . ($daysLeft === 1 ? 'day' : 'days') . ' left');
                ?>
                <article class="loan-row">
                    <?php if (isset($loan['cover_image']) && is_string($loan['cover_image']) && $loan['cover_image'] !== '') : ?>
                        <img class="loan-thumb" src="<?= url('catalog/' . (int) $loan['book_id'] . '/cover') ?>" alt="" width="46" height="69" loading="lazy">
                    <?php else : ?>
                        <span class="loan-thumb loan-thumb-placeholder" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </span>
                    <?php endif; ?>
                    <div class="loan-meta">
                        <strong><?= htmlspecialchars((string) $loan['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $loan['author'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $loan['category'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="loan-due">
                        <span class="status-badge status-<?= $isOverdue ? 'overdue' : 'issued' ?>"><?= htmlspecialchars($countdownLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="loan-due-date">Due <?= $dueTimestamp ? date(DISPLAY_DATE_FORMAT, $dueTimestamp) : 'unknown' ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="glass-panel dashboard-panel">
    <header class="panel-heading">
        <div>
            <span class="section-kicker">Ready to borrow</span>
            <h2>Available to you</h2>
        </div>
        <a class="text-link" href="<?= url('member/catalog') ?>">View full catalog <span aria-hidden="true">→</span></a>
    </header>
    <?php if ($recommendations === []) : ?>
        <div class="empty-state compact">
            <div class="empty-state-icon">◈</div>
            <h3>You have reached the limit</h3>
            <p>Every available title is already on loan to you. Return a book to unlock more.</p>
        </div>
    <?php else : ?>
        <ul class="recommendation-list">
            <?php foreach ($recommendations as $book) : ?>
                <li class="recommendation-row">
                    <div class="recommendation-meta">
                        <strong><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <span class="status-badge status-neutral"><?= htmlspecialchars((string) $book['category'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="recommendation-stock"><?= (int) $book['available_copies'] ?> of <?= (int) $book['total_copies'] ?> free</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
