<?php

declare(strict_types=1);

$role = (string) ($authUser['role'] ?? 'member');
$currentRoute = '/' . trim((string) $currentRoute, '/');
$appBasePath = parse_url(APP_URL, PHP_URL_PATH);
$appBasePath = is_string($appBasePath) ? '/' . trim($appBasePath, '/') : '';

if ($appBasePath !== '/' && strpos($currentRoute, $appBasePath) === 0) {
    $currentRoute = '/' . ltrim(substr($currentRoute, strlen($appBasePath)), '/');
}

$isCurrent = static function (string $path, bool $prefix = false) use ($currentRoute): bool {
    $path = '/' . trim($path, '/');

    return $prefix ? strpos($currentRoute, $path) === 0 : $currentRoute === $path;
};

$icon = static function (string $name): string {
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="3" width="7" height="7" rx="2"></rect><rect x="3" y="14" width="7" height="7" rx="2"></rect><rect x="14" y="14" width="7" height="7" rx="2"></rect>',
        'books' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
        'catalog' => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>',
        'transactions' => '<path d="M7 3h10l4 4v14H3V3h4z"></path><path d="M7 3v5h10V3M7 21v-6h10v6"></path>',
        'overdue' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"></path><path d="M3 3v5h5M12 7v5l3 2"></path>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"></path>',
    ];

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
};

$overdueCount = isset($overdueCount) ? (int) $overdueCount : 0;
?>
<aside id="primary-sidebar" class="sidebar" aria-label="Primary navigation" data-drawer="sidebar">
    <div class="sidebar-mobile-header">
        <a class="sidebar-brand" href="<?= $role === 'admin' ? url('admin') : url('member') ?>">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span class="brand-name">AuraLib</span>
        </a>
        <button class="icon-button sidebar-close" type="button" data-drawer-close aria-label="Close navigation">
            <?= $icon('close') ?>
        </button>
    </div>
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <span class="nav-section-label">Command center</span>
            <a class="nav-link <?= $isCurrent('admin') ? 'is-active' : '' ?>" href="<?= url('admin') ?>" <?= $isCurrent('admin') ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('dashboard') ?></span>
                Dashboard
            </a>
            <span class="nav-section-label">Library</span>
            <a class="nav-link <?= $isCurrent('admin/books', true) ? 'is-active' : '' ?>" href="<?= url('admin/books') ?>" <?= $isCurrent('admin/books', true) ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('books') ?></span>
                Catalog
            </a>
            <span class="nav-section-label">Circulation</span>
            <a class="nav-link <?= $isCurrent('admin/transactions') || ($isCurrent('admin/transactions', true) && !$isCurrent('admin/transactions/overdue', true)) ? 'is-active' : '' ?>" href="<?= url('admin/transactions') ?>" <?= $isCurrent('admin/transactions', true) ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('transactions') ?></span>
                Transactions
            </a>
            <a class="nav-link <?= $isCurrent('admin/transactions/overdue', true) ? 'is-active' : '' ?>" href="<?= url('admin/transactions/overdue') ?>" <?= $isCurrent('admin/transactions/overdue', true) ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('overdue') ?></span>
                Overdue watch
                <?php if ($overdueCount > 0): ?>
                    <span class="nav-badge"><?= $overdueCount > 99 ? '99+' : $overdueCount ?></span>
                <?php endif; ?>
            </a>
        <?php elseif ($role === 'member'): ?>
            <span class="nav-section-label">Your library</span>
            <a class="nav-link <?= $isCurrent('member') ? 'is-active' : '' ?>" href="<?= url('member') ?>" <?= $isCurrent('member') ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('dashboard') ?></span>
                Overview
            </a>
            <a class="nav-link <?= $isCurrent('member/catalog', true) || $isCurrent('catalog', true) ? 'is-active' : '' ?>" href="<?= url('member/catalog') ?>" <?= $isCurrent('member/catalog', true) || $isCurrent('catalog', true) ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('catalog') ?></span>
                Discover books
            </a>
            <a class="nav-link <?= $isCurrent('member/history', true) ? 'is-active' : '' ?>" href="<?= url('member/history') ?>" <?= $isCurrent('member/history', true) ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon"><?= $icon('history') ?></span>
                Reading history
            </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-footer-label">Access level</div>
        <div class="sidebar-footer-value"><?= $role === 'admin' ? 'Administrator console' : 'Member collection' ?></div>
    </div>
</aside>
