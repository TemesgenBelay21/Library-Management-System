<?php

declare(strict_types=1);
?>
<section class="page-header">
    <div><span class="eyebrow">Circulation control</span>
        <h1 class="page-title">Overdue watch</h1>
        <p class="page-subtitle">Prioritize late returns and keep inventory moving.</p>
    </div>
    <div class="page-actions"><a class="button button-secondary" href="<?= url('admin/transactions') ?>">All
            transactions</a></div>
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
                    <th scope="col">Member</th>
                    <th scope="col">Due</th>
                    <th scope="col">Late</th>
                    <th scope="col">Fine</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><strong>
                                <?= htmlspecialchars((string) $transaction['title'], ENT_QUOTES, 'UTF-8') ?>
                            </strong><small>
                                <?= htmlspecialchars((string) $transaction['author'], ENT_QUOTES, 'UTF-8') ?>
                            </small></td>
                        <td>
                            <?= htmlspecialchars((string) $transaction['member_name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(date(DISPLAY_DATE_FORMAT, strtotime((string) $transaction['due_at'])), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><span class="status-badge status-overdue">
                                <?= (int) $transaction['days_overdue'] ?> days
                            </span></td>
                        <td>$
                            <?= number_format((float) $transaction['fine_amount'], 2) ?>
                        </td>
                        <td><a class="table-action" href="<?= url('admin/transactions/' . (int) $transaction['id']) ?>"
                                aria-label="Review overdue transaction">→</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($transactions === []): ?>
        <div class="empty-state compact">
            <h3>Nothing overdue</h3>
            <p>Every active loan is currently within its due date.</p>
        </div>
    <?php endif; ?>
</section>