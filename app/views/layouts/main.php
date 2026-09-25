<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'AuraLib digital library management system';
$bodyClass = $bodyClass ?? 'app-body';
$authUser = $authUser ?? null;
$currentRoute = $currentRoute ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$pageScripts = $pageScripts ?? [];
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#07090f">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> · <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="<?= asset('js/app.js') ?>"></script>
    <?php foreach ($pageScripts as $script): ?>
        <script defer src="<?= asset($script) ?>"></script>
    <?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>" data-role="<?= $authUser !== null ? htmlspecialchars((string) $authUser['role'], ENT_QUOTES, 'UTF-8') : 'guest' ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="ambient ambient-one" aria-hidden="true"></div>
    <div class="ambient ambient-two" aria-hidden="true"></div>
    <div class="app-shell">
        <?php if (is_array($authUser)): ?>
            <?= $this->view('partials/header', ['authUser' => $authUser, 'currentRoute' => $currentRoute]) ?>
            <?= $this->view('partials/sidebar', ['authUser' => $authUser, 'currentRoute' => $currentRoute]) ?>
        <?php endif; ?>
        <main id="main-content" class="main-content" tabindex="-1">
            <?= $content ?>
        </main>
    </div>
    <div class="drawer-scrim" data-drawer-close hidden></div>
    <div id="toast-region" class="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
