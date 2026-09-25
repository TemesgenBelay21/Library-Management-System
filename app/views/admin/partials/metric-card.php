<?php

declare(strict_types=1);

$progress = isset($metric['progress']) && $metric['progress'] !== null ? max(0, min(100, (int) $metric['progress'])) : null;
$degrees = $progress === null ? 0 : (int) round($progress * 3.6);
$iconPaths = [
    'books' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
    'copies' => '<rect x="7" y="7" width="13" height="13" rx="2"></rect><path d="M4 17V5a2 2 0 0 1 2-2h12"></path>',
    'members' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>',
    'loans' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path><path d="M9 7h7M9 11h5"></path>',
    'available' => '<path d="M20 6 9 17l-5-5"></path><circle cx="12" cy="12" r="10"></circle>',
    'overdue' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
];
?>
<article class="metric-card metric-<?= htmlspecialchars((string) $metric['tone'], ENT_QUOTES, 'UTF-8') ?>" data-metric-card data-metric-key="<?= htmlspecialchars((string) $metric['key'], ENT_QUOTES, 'UTF-8') ?>" data-metric-value="<?= (int) $metric['value'] ?>" data-metric-progress="<?= $progress === null ? '' : $progress ?>">
    <div class="metric-card-top">
        <span class="metric-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><?= $iconPaths[$metric['icon']] ?? $iconPaths['books'] ?></svg>
        </span>
        <span class="metric-live"><i></i>Live</span>
    </div>
    <span class="metric-label"><?= htmlspecialchars((string) $metric['label'], ENT_QUOTES, 'UTF-8') ?></span>
    <strong class="metric-value" data-metric-display><?= number_format((int) $metric['value']) ?></strong>
    <div class="metric-footer">
        <span><?= htmlspecialchars((string) $metric['detail'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($progress !== null) : ?>
            <span class="metric-ring" style="--metric-degrees: <?= $degrees ?>deg" aria-label="<?= $progress ?> percent">
                <i><?= $progress ?>%</i>
            </span>
        <?php else : ?>
            <span class="metric-spark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
        <?php endif; ?>
    </div>
</article>
