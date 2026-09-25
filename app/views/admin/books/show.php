<?php

declare(strict_types=1);

$bookId = (int) $book['id'];
$hasCover = isset($book['cover_image']) && is_string($book['cover_image']) && $book['cover_image'] !== '';
$status = (string) $book['status'];
$updatedAt = strtotime((string) $book['updated_at']);
?>
<section class="breadcrumb-row">
    <a href="<?= url('admin/books') ?>">Catalog</a>
    <span>/</span>
    <span><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></span>
</section>

<section class="page-header page-heading book-show-heading">
    <div>
        <span class="eyebrow">Catalog record #<?= $bookId ?></span>
        <h1 class="page-title"><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="page-subtitle">by <?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="page-actions">
        <a class="button button-secondary" href="<?= url('admin/books/' . $bookId . '/edit') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 12v10H4V2h10"></path><path d="m18 2 4 4-11 11-5 1 1-5Z"></path></svg>
            Edit record
        </a>
        <?php if ((int) $book['active_loans'] === 0) : ?>
            <form method="post" action="<?= url('admin/books/' . $bookId . '/delete') ?>">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button class="button button-danger" type="submit" data-confirm="Delete this book from the catalog? This cannot be undone.">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6"></path><path d="M10 11v6M14 11v6"></path></svg>
                    Delete
                </button>
            </form>
        <?php endif; ?>
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

<section class="book-show-grid">
    <article class="glass-panel book-cover-panel">
        <?php if ($hasCover) : ?>
            <img class="book-cover-image" src="<?= url('catalog/' . $bookId . '/cover') ?>" alt="Cover of <?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>">
        <?php else : ?>
            <div class="book-cover-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span>Cover pending</span>
            </div>
        <?php endif; ?>
        <div class="book-cover-caption">
            <span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= $status === 'checked_out' ? 'Checked out' : 'Available' ?></span>
            <span><?= htmlspecialchars((string) $book['category'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </article>

    <div class="book-show-content">
        <section class="glass-panel book-info-panel">
            <header class="panel-heading">
                <div>
                    <span class="section-kicker">Inventory health</span>
                    <h2>Copy availability</h2>
                </div>
                <span class="panel-signal"><?= $availabilityRate ?>% free</span>
            </header>
            <div class="availability-meter" aria-label="<?= $availabilityRate ?> percent available">
                <span style="width: <?= $availabilityRate ?>%"></span>
            </div>
            <div class="inventory-stat-grid">
                <div><span>Total copies</span><strong><?= number_format((int) $book['total_copies']) ?></strong></div>
                <div><span>Available</span><strong class="text-success"><?= number_format((int) $book['available_copies']) ?></strong></div>
                <div><span>On loan</span><strong class="text-warning"><?= number_format($borrowedCopies) ?></strong></div>
                <div><span>Shelf</span><strong><?= htmlspecialchars((string) ($book['shelf_location'] ?: 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </section>

        <section class="glass-panel book-info-panel">
            <header class="panel-heading">
                <div>
                    <span class="section-kicker">Bibliography</span>
                    <h2>Catalog details</h2>
                </div>
            </header>
            <dl class="detail-list">
                <div><dt>ISBN</dt><dd><?= htmlspecialchars((string) ($book['isbn'] ?: 'Not assigned'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Published</dt><dd><?= $book['published_year'] ? (int) $book['published_year'] : 'Undated' ?></dd></div>
                <div><dt>Category</dt><dd><?= htmlspecialchars((string) $book['category'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Last updated</dt><dd><?= $updatedAt ? date(DISPLAY_DATETIME_FORMAT, $updatedAt) : 'Unknown' ?></dd></div>
            </dl>
            <div class="book-description">
                <span class="section-kicker">Description</span>
                <p><?= $book['description'] ? nl2br(htmlspecialchars((string) $book['description'], ENT_QUOTES, 'UTF-8')) : 'No description has been added for this title.' ?></p>
            </div>
        </section>
    </div>
</section>

<section class="glass-panel book-info-panel borrower-panel">
    <header class="panel-heading">
        <div>
            <span class="section-kicker">Active circulation</span>
            <h2>Current borrowers</h2>
        </div>
        <span class="panel-signal"><?= count($activeBorrowers) ?> active</span>
    </header>
    <?php if ($activeBorrowers === []) : ?>
        <div class="overdue-clear">
            <span class="overdue-clear-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
            </span>
            <div><strong>All copies are accounted for</strong><p>This title has no active loans.</p></div>
        </div>
    <?php else : ?>
        <div class="borrower-list">
            <?php foreach ($activeBorrowers as $loan) : ?>
                <?php $due = strtotime((string) $loan['due_at']); ?>
                <a class="borrower-row" href="<?= url('admin/transactions/' . (int) $loan['id']) ?>">
                    <span class="member-avatar"><?= htmlspecialchars(strtoupper(substr((string) $loan['member_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                    <span><strong><?= htmlspecialchars((string) $loan['member_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $loan['member_email'], ENT_QUOTES, 'UTF-8') ?></small></span>
                    <span><small>Due date</small><strong><?= $due ? date(DISPLAY_DATE_FORMAT, $due) : 'Unknown' ?></strong></span>
                    <span class="status-badge status-<?= htmlspecialchars((string) $loan['status'], ENT_QUOTES, 'UTF-8') ?>"><?= ucfirst(htmlspecialchars((string) $loan['status'], ENT_QUOTES, 'UTF-8')) ?></span>
                    <span class="table-action">→</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
