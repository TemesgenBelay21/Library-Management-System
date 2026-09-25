<?php

declare(strict_types=1);

$catalog = $catalog;
$catalogUrl = function (array $overrides = []) use ($catalog, $perPage): string {
    $query = ['q' => $catalog['query'], 'category' => $catalog['category'], 'sort' => $catalog['sort'], 'per_page' => $perPage, 'page' => $catalog['page']];
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    return url('member/catalog') . '?' . http_build_query($query);
};
?>
<section class="page-header">
    <div><span class="eyebrow"><span class="pulse-dot"></span> AuraLib collection</span>
        <h1 class="page-title">Discover your next read</h1>
        <p class="page-subtitle">Browse available titles and ask a librarian to issue one for you.</p>
    </div>
    <div class="page-actions"><a class="button button-secondary" href="<?= url('member/history') ?>">Reading history</a>
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
<section class="glass-panel catalog-filter-panel">
    <form class="catalog-filter-form" method="get" action="<?= url('member/catalog') ?>"><label
            class="filter-search"><span class="sr-only">Search books</span><input class="form-control" type="search"
                name="q" value="<?= htmlspecialchars((string) $catalog['query'], ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Title, author, or ISBN"></label><label><span class="sr-only">Category</span><select
                class="form-control" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8') ?>"
                        <?= $catalog['category'] === (string) $category['category'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select></label><button class="button button-primary filter-submit" type="submit">Browse</button></form>
</section>
<section class="catalog-results-heading">
    <div><span class="section-kicker">Available now</span>
        <p>Showing
            <?= number_format((int) $catalog['from']) ?>–
            <?= number_format((int) $catalog['to']) ?> of
            <?= number_format((int) $catalog['total']) ?>
        </p>
    </div>
</section>
<?php if ($catalog['items'] === []): ?>
    <section class="glass-panel catalog-empty-state">
        <h2>No available titles found</h2>
        <p>Try a different search or category.</p><a class="button button-secondary"
            href="<?= url('member/catalog') ?>">Clear filters</a>
    </section>
<?php else: ?>
    <section class="book-admin-grid">
        <?php foreach ($catalog['items'] as $catalogBook): ?>
            <article class="glass-panel book-admin-card"><a class="book-admin-cover"
                    href="<?= url('catalog/' . (int) $catalogBook['id']) ?>" tabindex="-1" aria-hidden="true">
                    <?php if ((string) $catalogBook['cover_image'] !== ''): ?><img
                            src="<?= url('catalog/' . (int) $catalogBook['id'] . '/cover') ?>" alt="" loading="lazy">
                    <?php else: ?><span aria-hidden="true">◇</span>
                    <?php endif; ?>
                </a>
                <div class="book-admin-content">
                    <div class="book-admin-meta"><span>
                            <?= htmlspecialchars((string) $catalogBook['category'], ENT_QUOTES, 'UTF-8') ?>
                        </span><span class="status-badge status-available">Available</span></div>
                    <h2><a href="<?= url('catalog/' . (int) $catalogBook['id']) ?>">
                            <?= htmlspecialchars((string) $catalogBook['title'], ENT_QUOTES, 'UTF-8') ?>
                        </a></h2>
                    <p class="book-admin-author">
                        <?= htmlspecialchars((string) $catalogBook['author'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <div class="book-card-availability">
                        <div><span>Available</span><strong>
                                <?= (int) $catalogBook['available_copies'] ?> /
                                <?= (int) $catalogBook['total_copies'] ?>
                            </strong></div>
                        <div class="progress-track"><span
                                style="width: <?= (int) round((int) $catalogBook['available_copies'] / max(1, (int) $catalogBook['total_copies']) * 100) ?>%"></span>
                        </div>
                    </div>
                    <div class="book-admin-actions"><a class="button button-primary"
                            href="<?= url('catalog/' . (int) $catalogBook['id']) ?>">View details</a></div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>