<?php

declare(strict_types=1);
?>
<section class="page-header">
    <div><span class="eyebrow"><span class="pulse-dot"></span> Your activity</span>
        <h1 class="page-title">Reading history</h1>
        <p class="page-subtitle">A clear record of everything you have borrowed.</p>
    </div>
    <div class="page-actions"><a class="button button-primary" href="<?= url('member/catalog') ?>">Discover books</a>
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
<section class="glass-panel dashboard-panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Book</th>
                    <th scope="col">Issued</th>
                    <th scope="col">Due</th>
                    <th scope="col">Status</th>
                    <th scope="col">Fine</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td><strong>
                                <?= htmlspecialchars((string) $loan['title'], ENT_QUOTES, 'UTF-8') ?>
                            </strong><small>
                                <?= htmlspecialchars((string) $loan['author'], ENT_QUOTES, 'UTF-8') ?>
                            </small></td>
                        <td>
                            <?= htmlspecialchars(date(DISPLAY_DATE_FORMAT, strtotime((string) $loan['issued_at'])), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(date(DISPLAY_DATE_FORMAT, strtotime((string) $loan['due_at'])), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><span
                                class="status-badge status-<?= htmlspecialchars((string) $loan['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(ucfirst((string) $loan['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span></td>
                        <td>$
                            <?= number_format((float) $loan['fine_amount'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($loans === []): ?>
        <div class="empty-state compact">
            <h3>Your history is empty</h3>
            <p>Issued books will appear here once a librarian checks them out.</p><a class="button button-primary"
                href="<?= url('member/catalog') ?>">Browse the catalog</a>
        </div>
    <?php endif; ?>
</section>