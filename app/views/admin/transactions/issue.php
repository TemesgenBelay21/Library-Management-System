<?php

declare(strict_types=1);

$old = array_merge(['book_id' => 0, 'member_id' => 0, 'loan_days' => LOAN_DAYS], $old);
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

<?php if ($errors !== []): ?>
    <div class="flash-stack" aria-live="polite">
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
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
    <form method="post" action="<?= url('admin/transactions/issue') ?>" class="stack-form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-field">
            <label class="form-label" for="book_search">Find a book</label>
            <input class="form-control" type="search" id="book_search" data-issue-search
                data-issue-endpoint="<?= url('admin/transactions/issue/books') ?>" data-issue-collection="books"
                data-issue-target="book_id" placeholder="Search by title or author" autocomplete="off">
            <p class="form-hint" data-issue-status role="status" aria-live="polite"></p>
            <label class="form-label" for="book_id">Selected book</label>
            <select class="form-control" name="book_id" id="book_id" data-issue-select required>
                <option value="">Choose an available book</option>
                <?php foreach ($books as $book): ?>
                    <option value="<?= (int) $book['id'] ?>" <?= (int) $old['book_id'] === (int) $book['id'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') ?> ·
                        <?= (int) $book['available_copies'] ?> available
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($books === [] && $hasFilters) : ?>
            <p class="form-hint">No available book matches this filter. Clear it to see the whole catalogue.</p>
        <?php endif; ?>
        <div class="form-field">
            <label class="form-label" for="member_search">Find a member</label>
            <input class="form-control" type="search" id="member_search" data-issue-search
                data-issue-endpoint="<?= url('admin/members/search') ?>" data-issue-collection="members"
                data-issue-target="member_id" placeholder="Search by name or email" autocomplete="off">
            <p class="form-hint" data-issue-status role="status" aria-live="polite"></p>
            <label class="form-label" for="member_id">Selected member</label>
            <select class="form-control" name="member_id" id="member_id" data-issue-select required>
                <option value="">Choose an active member</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?= (int) $member['id'] ?>" <?= (int) $old['member_id'] === (int) $member['id'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?> ·
                        <?= htmlspecialchars((string) $member['email'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($members === [] && $hasFilters) : ?>
            <p class="form-hint">No active member matches this filter. Clear it to see every member.</p>
        <?php endif; ?>
        <label class="form-field"><span>Loan period in days</span><input class="form-control" type="number"
                name="loan_days" min="1" max="365" value="<?= (int) $old['loan_days'] ?>" required></label>
        <div class="form-actions"><button class="button button-primary" type="submit">Confirm issue</button><a
                class="button button-ghost" href="<?= url('admin/transactions') ?>">Cancel</a></div>
    </form>
</section>