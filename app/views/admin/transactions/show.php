<?php

declare(strict_types=1);

$status = (string) $transaction['status'];
$dueAt = strtotime((string) $transaction['due_at']);
?>
<section class="page-header">
    <div><span class="eyebrow">Circulation record</span>
        <h1 class="page-title">Transaction #<?= (int) $transaction['id'] ?></h1>
        <p class="page-subtitle">Review the loan and complete its return.</p>
    </div>
    <div class="page-actions"><a class="button button-secondary" href="<?= url('admin/transactions') ?>">All
            transactions</a></div>
</section>
<?php if ($flashMessages !== []): ?>
    <div class="flash-stack" aria-live="polite"><?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?= htmlspecialchars((string) $message['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message['message'], ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?>
    </div><?php endif; ?>
<section class="glass-panel detail-panel">
    <div class="detail-heading">
        <div><span class="section-kicker">Book</span>
            <h2><?= htmlspecialchars((string) $transaction['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars((string) $transaction['author'], ENT_QUOTES, 'UTF-8') ?></p>
        </div><span
            class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <dl class="detail-grid">
        <div>
            <dt>Member</dt>
            <dd><?= htmlspecialchars((string) $transaction['member_name'], ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
            <dt>Email</dt>
            <dd><?= htmlspecialchars((string) $transaction['member_email'], ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
            <dt>Issued</dt>
            <dd><?= htmlspecialchars(date(DISPLAY_DATETIME_FORMAT, strtotime((string) $transaction['issued_at'])), ENT_QUOTES, 'UTF-8') ?>
            </dd>
        </div>
        <div>
            <dt>Due</dt>
            <dd><?= $dueAt ? htmlspecialchars(date(DISPLAY_DATETIME_FORMAT, $dueAt), ENT_QUOTES, 'UTF-8') : 'Unknown' ?>
            </dd>
        </div>
        <div>
            <dt>Fine</dt>
            <dd>$<?= number_format((float) $transaction['fine_amount'], 2) ?></dd>
        </div>
        <div>
            <dt>Issued by</dt>
            <dd><?= htmlspecialchars((string) $transaction['issued_by_name'], ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
    </dl>
    <?php if ($status !== 'returned'): ?>
        <form method="post" action="<?= url('admin/transactions/' . (int) $transaction['id'] . '/return') ?>"><input
                type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button
                class="button button-primary" type="submit">Mark as returned</button></form><?php else: ?>
        <p class="text-success">Returned
            <?= htmlspecialchars(date(DISPLAY_DATETIME_FORMAT, strtotime((string) $transaction['returned_at'])), ENT_QUOTES, 'UTF-8') ?>.
        </p><?php endif; ?>
</section>