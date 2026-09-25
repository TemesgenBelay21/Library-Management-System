<?php

declare(strict_types=1);

$bookQuery = $bookQuery ?? '';
$memberQuery = $memberQuery ?? '';
$hasFilters = $bookQuery !== '' || $memberQuery !== '';
?>
<section class="page-header">
    <div>
        <span class="eyebrow">Circulation control</span>
        <h1 class="page-title">Issue a book</h1>
        <p class="page-subtitle">Assign an available copy to an active member.</p>
    </div>
    <div class="page-actions"><a class="button button-secondary" href="<?= url('admin/transactions') ?>">Back to
            transactions</a></div>
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

<section class="glass-panel catalog-filter-panel">
    <form class="catalog-filter-form" method="get" action="<?= url('admin/transactions/issue') ?>">
        <label class="filter-search">
            <span class="sr-only">Filter available books</span>
            <input class="form-control" type="search" name="book"
                value="<?= htmlspecialchars($bookQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Filter by title or author">
        </label>
        <label class="filter-search">
            <span class="sr-only">Filter active members</span>
            <input class="form-control" type="search" name="member"
                value="<?= htmlspecialchars($memberQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Filter by name or email">
        </label>
        <button class="button button-primary filter-submit" type="submit">Filter</button>
        <?php if ($hasFilters) : ?>
            <a class="button button-ghost" href="<?= url('admin/transactions/issue') ?>">Clear</a>
        <?php endif; ?>
    </form>
</section>

<section class="glass-panel form-panel">
    <?php if ($books === [] && $hasFilters) : ?>
        <p class="form-hint">No available book matches this filter. Clear it to see the whole catalogue.</p>
    <?php endif; ?>
    <?php if ($members === [] && $hasFilters) : ?>
        <p class="form-hint">No active member matches this filter. Clear it to see every member.</p>
    <?php endif; ?>
    <?= $this->view('admin/transactions/_issue-form', [
        'books' => $books,
        'members' => $members,
        'old' => $old,
        'errors' => $errors,
    ]) ?>
</section>
