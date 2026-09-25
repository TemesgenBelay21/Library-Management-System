<?php

declare(strict_types=1);
?>
<section class="page-header page-heading">
    <div>
        <span class="eyebrow">Catalog expansion</span>
        <h1 class="page-title">Add a new book</h1>
        <p class="page-subtitle">Create a complete catalog record and track its first available copy.</p>
    </div>
    <a class="button button-secondary" href="<?= url('admin/books') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        Back to catalog
    </a>
</section>

<?php if ($errors !== []) : ?>
    <div class="alert alert-danger form-alert" role="alert">
        <strong>Review the highlighted fields.</strong>
        <ul>
            <?php foreach ($errors as $error) : ?>
                <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($flashMessages !== []) : ?>
    <div class="flash-stack" aria-live="polite">
        <?php foreach ($flashMessages as $message) : ?>
            <div class="alert alert-<?= htmlspecialchars((string) $message['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="glass-panel entity-form" method="post" action="<?= url('admin/books') ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="entity-form-heading">
        <div>
            <span class="section-kicker">Bibliographic record</span>
            <h2>Book identity</h2>
            <p>Core details used throughout search and circulation.</p>
        </div>
        <span class="entity-form-step">01</span>
    </div>

    <div class="form-grid">
        <div class="form-group form-group-full">
            <label class="form-label" for="book-title">Title <span aria-hidden="true">*</span></label>
            <input class="form-control" id="book-title" name="title" type="text" maxlength="220" required value="<?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?>" placeholder="The Left Hand of Darkness">
        </div>
        <div class="form-group">
            <label class="form-label" for="book-author">Author <span aria-hidden="true">*</span></label>
            <input class="form-control" id="book-author" name="author" type="text" maxlength="180" required value="<?= htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Ursula K. Le Guin">
        </div>
        <div class="form-group">
            <label class="form-label" for="book-isbn">ISBN</label>
            <input class="form-control" id="book-isbn" name="isbn" type="text" maxlength="20" inputmode="numeric" value="<?= htmlspecialchars((string) $book['isbn'], ENT_QUOTES, 'UTF-8') ?>" placeholder="9780441478125">
        </div>
        <div class="form-group">
            <label class="form-label" for="book-category">Category <span aria-hidden="true">*</span></label>
            <input class="form-control" id="book-category" name="category" type="text" maxlength="100" required list="catalog-categories" value="<?= htmlspecialchars((string) $book['category'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Science fiction">
            <datalist id="catalog-categories">
                <?php foreach ($categories as $category) : ?>
                    <option value="<?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8') ?>"><?= (int) $category['title_count'] ?> titles</option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="form-group">
            <label class="form-label" for="book-year">Published year</label>
            <input class="form-control" id="book-year" name="published_year" type="number" min="1000" max="<?= date('Y') ?>" value="<?= htmlspecialchars((string) $book['published_year'], ENT_QUOTES, 'UTF-8') ?>" placeholder="1969">
        </div>
        <div class="form-group form-group-full">
            <label class="form-label" for="book-description">Description</label>
            <textarea class="form-control" id="book-description" name="description" maxlength="5000" placeholder="A concise catalog summary."><?= htmlspecialchars((string) $book['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>

    <div class="entity-form-divider"></div>

    <div class="entity-form-heading">
        <div>
            <span class="section-kicker">Inventory</span>
            <h2>Copy and placement</h2>
            <p>Set starting availability and where members can find the title.</p>
        </div>
        <span class="entity-form-step">02</span>
    </div>

    <div class="inventory-grid">
        <div class="form-group">
            <label class="form-label" for="book-copies">Total copies <span aria-hidden="true">*</span></label>
            <input class="form-control" id="book-copies" name="total_copies" type="number" min="1" max="10000" required value="<?= (int) $book['total_copies'] ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="book-shelf">Shelf location</label>
            <input class="form-control" id="book-shelf" name="shelf_location" type="text" maxlength="80" value="<?= htmlspecialchars((string) $book['shelf_location'], ENT_QUOTES, 'UTF-8') ?>" placeholder="SCI-04 / A12">
        </div>
        <div class="form-group form-group-full">
            <label class="form-label" for="book-cover">Cover image</label>
            <label class="cover-upload" for="book-cover">
                <span class="cover-upload-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
                </span>
                <span><strong>Choose a cover image</strong><small>JPEG, PNG, or WebP · minimum 200 × 200 · maximum 5 MB</small></span>
                <input id="book-cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" data-cover-input>
            </label>
        </div>
    </div>

    <div class="entity-form-actions">
        <a class="button button-ghost" href="<?= url('admin/books') ?>">Cancel</a>
        <button class="button button-primary" type="submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
            Add to catalog
        </button>
    </div>
</form>
