<?php

declare(strict_types=1);

$filtersActive = $catalog['query'] !== ''
    || $catalog['category'] !== ''
    || $catalog['status'] !== ''
    || $catalog['availability'] !== ''
    || $catalog['sort'] !== 'newest'
    || $perPage !== DEFAULT_PER_PAGE;
$availableInResults = 0;
$copiesInResults = 0;

foreach ($catalog['items'] as $catalogBook) {
    $availableInResults += (int) $catalogBook['available_copies'];
    $copiesInResults += (int) $catalogBook['total_copies'];
}
?>
<section class="page-header">
    <div>
        <span class="eyebrow">Collection management</span>
        <h1 class="page-title">Book catalog</h1>
        <p class="page-subtitle">Curate titles, monitor copy availability, and keep shelving accurate.</p>
    </div>
    <div class="page-actions">
        <a class="button button-primary" href="<?= url('admin/books/create') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
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

<section class="catalog-summary-grid" aria-label="Catalog result summary">
    <div class="catalog-summary-card"><span>Matching titles</span><strong data-catalog-total><?= number_format((int) $catalog['total']) ?></strong></div>
    <div class="catalog-summary-card"><span>Copies in results</span><strong data-catalog-copies><?= number_format($copiesInResults) ?></strong></div>
    <div class="catalog-summary-card"><span>Available in results</span><strong class="text-success" data-catalog-available><?= number_format($availableInResults) ?></strong></div>
    <div class="catalog-summary-card"><span>Categories</span><strong><?= count($categories) ?></strong></div>
</section>

<section class="glass-panel catalog-filter-panel">
    <form class="catalog-filter-form" method="get" action="<?= url('admin/books') ?>" data-catalog-filter data-catalog-endpoint="<?= htmlspecialchars(url('admin/books/search'), ENT_QUOTES, 'UTF-8') ?>">
        <label class="filter-search">
            <span class="sr-only">Search catalog</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars((string) $catalog['query'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Title, author, or ISBN" data-catalog-search autocomplete="off" aria-controls="catalog-results">
        </label>
        <label>
            <span class="sr-only">Category</span>
            <select class="form-control" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8') ?>"<?= $catalog['category'] === (string) $category['category'] ? ' selected' : '' ?>><?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) $category['title_count'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span class="sr-only">Status</span>
            <select class="form-control" name="status">
                <option value="">Any status</option>
                <option value="available"<?= $catalog['status'] === 'available' ? ' selected' : '' ?>>Available</option>
                <option value="checked_out"<?= $catalog['status'] === 'checked_out' ? ' selected' : '' ?>>Checked out</option>
            </select>
        </label>
        <label>
            <span class="sr-only">Availability</span>
            <select class="form-control" name="availability">
                <option value="">Any availability</option>
                <option value="available"<?= $catalog['availability'] === 'available' ? ' selected' : '' ?>>Has copies</option>
                <option value="unavailable"<?= $catalog['availability'] === 'unavailable' ? ' selected' : '' ?>>Unavailable</option>
            </select>
        </label>
        <label>
            <span class="sr-only">Sort catalog</span>
            <select class="form-control" name="sort">
                <option value="newest"<?= $catalog['sort'] === 'newest' ? ' selected' : '' ?>>Newest first</option>
                <option value="title"<?= $catalog['sort'] === 'title' ? ' selected' : '' ?>>Title A–Z</option>
                <option value="author"<?= $catalog['sort'] === 'author' ? ' selected' : '' ?>>Author A–Z</option>
                <option value="category"<?= $catalog['sort'] === 'category' ? ' selected' : '' ?>>Category</option>
                <option value="availability"<?= $catalog['sort'] === 'availability' ? ' selected' : '' ?>>Most available</option>
            </select>
        </label>
        <label>
            <span class="sr-only">Results per page</span>
            <select class="form-control" name="per_page">
                <option value="12"<?= $perPage === 12 ? ' selected' : '' ?>>12 per page</option>
                <option value="24"<?= $perPage === 24 ? ' selected' : '' ?>>24 per page</option>
                <option value="48"<?= $perPage === 48 ? ' selected' : '' ?>>48 per page</option>
            </select>
        </label>
        <button class="button button-primary filter-submit" type="submit">Apply filters</button>
        <?php if ($filtersActive) : ?>
            <a class="button button-ghost filter-reset" href="<?= url('admin/books') ?>">Reset</a>
        <?php endif; ?>
        <span class="sr-only" data-catalog-search-status aria-live="polite"></span>
    </form>
</section>

<div id="catalog-results" data-catalog-results aria-live="polite" aria-busy="false">
    <?php require __DIR__ . '/_results.php'; ?>
</div>
