<?php

declare(strict_types=1);

$bookId = (int) $book['id'];
$hasCover = is_string($book['cover_image']) && $book['cover_image'] !== '';
?>
<section class="breadcrumb-row"><a href="<?= url('member/catalog') ?>">Discover
        books</a><span>/</span><span><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></span>
</section>
<section class="page-header page-heading">
    <div><span class="eyebrow">Book details</span>
        <h1 class="page-title"><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="page-subtitle">by <?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="page-actions"><a class="button button-secondary" href="<?= url('member/catalog') ?>">Back to catalog</a>
    </div>
</section>
<?php if ($flashMessages !== []): ?>
    <div class="flash-stack" aria-live="polite"><?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?= htmlspecialchars((string) $message['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message['message'], ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?>
    </div><?php endif; ?>
<section class="book-show-grid">
    <article class="glass-panel book-cover-panel"><?php if ($hasCover): ?><img class="book-cover-image"
                src="<?= url('catalog/' . $bookId . '/cover') ?>"
                alt="Cover of <?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>"><?php else: ?>
            <div class="book-cover-placeholder"><span>Cover pending</span></div><?php endif; ?>
        <div class="book-cover-caption"><span
                class="status-badge status-<?= htmlspecialchars((string) $book['status'], ENT_QUOTES, 'UTF-8') ?>"><?= (int) $book['available_copies'] > 0 ? 'Available' : 'Checked out' ?></span><span><?= htmlspecialchars((string) $book['category'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </article>
    <div class="book-show-content">
        <section class="glass-panel book-info-panel">
            <header class="panel-heading">
                <div><span class="section-kicker">About this title</span>
                    <h2><?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
            </header>
            <p class="book-description">
                <?= $book['description'] ? nl2br(htmlspecialchars((string) $book['description'], ENT_QUOTES, 'UTF-8')) : 'No description has been added for this title.' ?>
            </p>
            <dl class="detail-list">
                <div>
                    <dt>Author</dt>
                    <dd><?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Published</dt>
                    <dd><?= $book['published_year'] ? (int) $book['published_year'] : 'Undated' ?></dd>
                </div>
                <div>
                    <dt>Available copies</dt>
                    <dd><?= (int) $book['available_copies'] ?> of <?= (int) $book['total_copies'] ?></dd>
                </div>
                <div>
                    <dt>Shelf</dt>
                    <dd><?= htmlspecialchars((string) ($book['shelf_location'] ?: 'Ask a librarian'), ENT_QUOTES, 'UTF-8') ?>
                    </dd>
                </div>
            </dl><?php if ($hasActiveLoan): ?>
                <p class="alert alert-info">You already have this title on loan.</p>
            <?php elseif ((int) $book['available_copies'] > 0): ?>
                <p class="alert alert-info">This title is available. Ask a librarian to issue it to your account.</p>
            <?php else: ?>
                <p class="alert alert-warning">All copies are currently checked out.</p><?php endif; ?>
        </section>
    </div>
</section>