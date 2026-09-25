<?php

declare(strict_types=1);

$hasActiveFilters = isset($filtersActive) ? (bool) $filtersActive : $catalog['query'] !== '';
$page = (int) $catalog['page'];
$lastPage = (int) $catalog['last_page'];
$windowStart = max(1, $page - 2);
$windowEnd = min($lastPage, $windowStart + 4);
$windowStart = max(1, $windowEnd - 4);
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
<section class="catalog-results-heading">
    <div>
        <span class="section-kicker">Results</span>
        <p data-catalog-result-status>
            <?php if ((int) $catalog['total'] === 0) : ?>
                No titles found
            <?php else : ?>
                Showing <?= number_format((int) $catalog['from']) ?>–<?= number_format((int) $catalog['to']) ?> of <?= number_format((int) $catalog['total']) ?>
            <?php endif; ?>
        </p>
    </div>
    <?php if ($hasActiveFilters) : ?>
        <span class="filter-indicator"><?= $catalog['query'] !== '' ? 'Live search' : 'Filtered view' ?></span>
    <?php endif; ?>
</section>

<?php if ($catalog['items'] === []) : ?>
    <section class="glass-panel catalog-empty-state">
        <span class="catalog-empty-icon">
            <?php if ($hasActiveFilters) : ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4M8 8l6 6M14 8l-6 6"></path></svg>
            <?php else : ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path><path d="M9 7h7M9 11h5"></path></svg>
            <?php endif; ?>
        </span>
        <?php if ($hasActiveFilters) : ?>
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
