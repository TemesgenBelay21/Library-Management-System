<?php

declare(strict_types=1);

$role = (string) ($authUser['role'] ?? 'member');
$name = (string) ($authUser['name'] ?? 'Aura Member');
$status = (string) ($authUser['status'] ?? 'active');
$nameParts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
}
$initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
$roleHome = $role === 'admin' ? url('admin') : url('member');
$contextUrl = $role === 'admin' ? url('admin/transactions') : url('member/history');
?>
<header class="topbar">
    <div class="topbar-inner">
        <div class="inline-actions">
            <button class="icon-button menu-toggle" type="button" data-drawer-open="sidebar" aria-controls="primary-sidebar" aria-expanded="false" aria-label="Open navigation">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"></path>
                </svg>
            </button>
            <a class="brand-lockup" href="<?= $roleHome ?>" aria-label="AuraLib home">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span>
                    <span class="brand-name">AuraLib</span>
                    <span class="brand-tagline">Digital intelligence</span>
                </span>
            </a>
        </div>
        <div class="header-actions">
            <a class="icon-button" href="<?= $contextUrl ?>" aria-label="<?= $role === 'admin' ? 'Open circulation activity' : 'Open reading history' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                    <path d="M10 21h4"></path>
                </svg>
            </a>
            <div class="profile-trigger" aria-label="Signed in profile">
                <span class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="profile-copy">
                    <span class="profile-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-role"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="status-badge <?= $status === 'active' ? 'status-success' : 'status-danger' ?>">
                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <a class="icon-button" href="<?= url('logout.php') ?>" aria-label="Sign out">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M10 17l5-5-5-5"></path>
                    <path d="M15 12H3"></path>
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                </svg>
            </a>
        </div>
    </div>
</header>
