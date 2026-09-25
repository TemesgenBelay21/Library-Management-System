<?php

declare(strict_types=1);

$filtersActive = $catalog['query'] !== ''
    || $catalog['category'] !== ''
    || $catalog['status'] !== ''
    || $catalog['availability'] !== ''
    || $catalog['sort'] !== 'newest'
    || $perPage !== DEFAULT_PER_PAGE;
$page = (int) $catalog['page'];
$lastPage = (int) $catalog['last_page'];
$windowStart = max(1, $page - 2);
$windowEnd = min($lastPage, $windowStart + 4);
$windowStart = max(1, $windowEnd - 4);
$availableInResults = 0;
$copiesInResults = 0;

foreach ($catalog['items'] as $catalogBook) {
    $availableInResults += (int) $catalogBook['available_copies'];
    $copiesInResults += (int) $catalogBook['total_copies'];
}

$catalogUrl = function (array $overrides = []) use ($catalog, $perPage): string {
    $query = [
        'q' => $catalog['query'],
        'category' => $catalog['category'],
        'status' => $catalog['status'],
        'availability' => $catalog['availability'],
        'sort' => $catalog['sort'],
        'per_page' => $perPage,
        'page' => (int) $catalog['page'],
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return url('admin/books') . '?' . http_build_query($query);
};
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
    <div class="catalog-summary-card"><span>Matching titles</span><strong><?= number_format((int) $catalog['total']) ?></strong></div>
    <div class="catalog-summary-card"><span>Copies in results</span><strong><?= number_format($copiesInResults) ?></strong></div>
    <div class="catalog-summary-card"><span>Available in results</span><strong class="text-success"><?= number_format($availableInResults) ?></strong></div>
    <div class="catalog-summary-card"><span>Categories</span><strong><?= count($categories) ?></strong></div>
</section>

<section class="glass-panel catalog-filter-panel">
    <form class="catalog-filter-form" method="get" action="<?= url('admin/books') ?>">
        <label class="filter-search">
            <span class="sr-only">Search catalog</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars((string) $catalog['query'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Title, author, or ISBN">
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
    </form>
</section>

<section class="catalog-results-heading">
    <div>
        <span class="section-kicker">Results</span>
        <p>
            <?php if ((int) $catalog['total'] === 0) : ?>
                No titles found
            <?php else : ?>
                Showing <?= number_format((int) $catalog['from']) ?>–<?= number_format((int) $catalog['to']) ?> of <?= number_format((int) $catalog['total']) ?>
            <?php endif; ?>
        </p>
    </div>
    <?php if ($filtersActive) : ?>
        <span class="filter-indicator">Filtered view</span>
    <?php endif; ?>
</section>

<?php if ($catalog['items'] === []) : ?>
    <section class="glass-panel catalog-empty-state">
        <span class="catalog-empty-icon">
            <?php if ($filtersActive) : ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4M8 8l6 6M14 8l-6 6"></path></svg>
            <?php else : ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path><path d="M9 7h7M9 11h5"></path></svg>
            <?php endif; ?>
        </span>
        <?php if ($filtersActive) : ?>
            <h2>No matching books</h2>
            <p>Try a broader search or clear the active filters to view the full collection.</p>
            <a class="button button-secondary" href="<?= url('admin/books') ?>">Clear all filters</a>
        <?php else : ?>
            <h2>Build your first shelf</h2>
            <p>Add a title to begin tracking copies, circulation, and availability.</p>
            <a class="button button-primary" href="<?= url('admin/books/create') ?>">Add the first book</a>
        <?php endif; ?>
    </section>
<?php else : ?>
    <section class="book-admin-grid">
        <?php foreach ($catalog['items'] as $catalogBook) : ?>
            <?php
            $bookId = (int) $catalogBook['id'];
            $hasCover = isset($catalogBook['cover_image']) && is_string($catalogBook['cover_image']) && $catalogBook['cover_image'] !== '';
            $bookStatus = (string) $catalogBook['status'];
            ?>
            <article class="glass-panel book-admin-card">
                <a class="book-admin-cover" href="<?= url('admin/books/' . $bookId) ?>" tabindex="-1" aria-hidden="true">
                    <?php if ($hasCover) : ?>
                        <img src="<?= url('catalog/' . $bookId . '/cover') ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="book-admin-content">
                    <div class="book-admin-meta">
                        <span><?= htmlspecialchars((string) $catalogBook['category'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="status-badge status-<?= htmlspecialchars($bookStatus, ENT_QUOTES, 'UTF-8') ?>"><?= $bookStatus === 'checked_out' ? 'Checked out' : 'Available' ?></span>
                    </div>
                    <h2><a href="<?= url('admin/books/' . $bookId) ?>"><?= htmlspecialchars((string) $catalogBook['title'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                    <p class="book-admin-author"><?= htmlspecialchars((string) $catalogBook['author'], ENT_QUOTES, 'UTF-8') ?></p>
                    <dl class="book-admin-facts">
                        <div><dt>ISBN</dt><dd><?= htmlspecialchars((string) ($catalogBook['isbn'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Shelf</dt><dd><?= htmlspecialchars((string) ($catalogBook['shelf_location'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    </dl>
                    <div class="book-card-availability">
                        <div><span>Available</span><strong><?= (int) $catalogBook['available_copies'] ?> / <?= (int) $catalogBook['total_copies'] ?></strong></div>
                        <div class="progress-track"><span style="width: <?= (int) $catalogBook['total_copies'] > 0 ? (int) round((int) $catalogBook['available_copies'] / (int) $catalogBook['total_copies'] * 100) : 0 ?>%"></span></div>
                    </div>
                    <div class="book-admin-actions">
                        <a class="button button-secondary" href="<?= url('admin/books/' . $bookId) ?>">View</a>
                        <a class="button button-ghost" href="<?= url('admin/books/' . $bookId . '/edit') ?>">Edit</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if ($lastPage > 1) : ?>
        <nav class="pagination-wrap" aria-label="Catalog pages">
            <?php if ($page > 1) : ?>
                <a class="pagination-link" href="<?= htmlspecialchars($catalogUrl(['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Previous page">←</a>
            <?php else : ?>
                <span class="pagination-link is-disabled" aria-disabled="true">←</span>
            <?php endif; ?>
            <?php for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++) : ?>
                <?php if ($pageNumber === $page) : ?>
                    <span class="pagination-link is-current" aria-current="page"><?= $pageNumber ?></span>
                <?php else : ?>
                    <a class="pagination-link" href="<?= htmlspecialchars($catalogUrl(['page' => $pageNumber]), ENT_QUOTES, 'UTF-8') ?>"><?= $pageNumber ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $lastPage) : ?>
                <a class="pagination-link" href="<?= htmlspecialchars($catalogUrl(['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Next page">→</a>
            <?php else : ?>
                <span class="pagination-link is-disabled" aria-disabled="true">→</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
