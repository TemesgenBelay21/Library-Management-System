<?php

declare(strict_types=1);

$authMode = $authMode === 'register' ? 'register' : 'login';
$isRegister = $authMode === 'register';
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
$flashMessages = isset($flashMessages) && is_array($flashMessages) ? $flashMessages : [];
?>
<div class="auth-shell">
    <section class="auth-showcase" aria-labelledby="auth-showcase-title">
        <a class="brand-lockup auth-brand" href="<?= url() ?>">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>
                <span class="brand-name">AuraLib</span>
                <span class="brand-tagline">Digital intelligence</span>
            </span>
        </a>
        <div class="auth-showcase-copy">
            <span class="status-badge status-primary">LibraryOS / 01</span>
            <h1 id="auth-showcase-title">A library that feels <span class="text-gradient">alive.</span></h1>
            <p>Curate every title, member journey, and circulation signal from one luminous command surface.</p>
        </div>
        <div class="auth-artboard" aria-hidden="true">
            <div class="auth-orbit auth-orbit-outer"></div>
            <div class="auth-orbit auth-orbit-inner"></div>
            <div class="auth-art-core">
                <span>A</span>
            </div>
            <div class="auth-node auth-node-one"><span>01</span></div>
            <div class="auth-node auth-node-two"><span>02</span></div>
            <div class="auth-node auth-node-three"><span>03</span></div>
        </div>
        <div class="auth-showcase-metrics">
            <div>
                <strong>Live</strong>
                <span>Availability signals</span>
            </div>
            <div>
                <strong>24/7</strong>
                <span>Collection insight</span>
            </div>
            <div>
                <strong>Secure</strong>
                <span>Role-aware access</span>
            </div>
        </div>
    </section>
    <section class="auth-panel" aria-label="Account access">
        <div class="auth-mobile-brand">
            <a class="brand-lockup" href="<?= url() ?>">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span class="brand-name">AuraLib</span>
            </a>
        </div>
        <div class="auth-card glass-panel">
            <div class="auth-card-heading">
                <span class="eyebrow"><?= $isRegister ? 'New membership' : 'Secure access' ?></span>
                <h2><?= $isRegister ? 'Join the collection.' : 'Welcome back.' ?></h2>
                <p><?= $isRegister ? 'Create a member profile in less than a minute.' : 'Enter your credentials to continue to your library.' ?></p>
            </div>
            <div class="auth-switch" aria-label="Authentication mode">
                <a class="<?= !$isRegister ? 'is-active' : '' ?>" href="<?= url('login') ?>" <?= !$isRegister ? 'aria-current="page"' : '' ?>>Sign in</a>
                <a class="<?= $isRegister ? 'is-active' : '' ?>" href="<?= url('register') ?>" <?= $isRegister ? 'aria-current="page"' : '' ?>>Create account</a>
            </div>
            <?php foreach ($flashMessages as $flash): ?>
                <?php $flashType = in_array($flash['type'] ?? 'info', ['info', 'success', 'danger', 'warning'], true) ? $flash['type'] : 'info'; ?>
                <div hidden data-toast-message="<?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-toast-type="<?= htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8') ?>"></div>
            <?php endforeach; ?>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger auth-alert" role="alert">
                    <strong>Check the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="post" action="<?= $isRegister ? url('register') : url('login') ?>">
                <input type="hidden" name="_token" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isRegister): ?>
                    <div class="form-group">
                        <label class="form-label" for="name">Full name</label>
                        <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" maxlength="120" required autofocus>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" maxlength="190" required <?= !$isRegister ? 'autofocus' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="<?= $isRegister ? 'new-password' : 'current-password' ?>" maxlength="72" required>
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                    <?php if ($isRegister): ?>
                        <span class="form-hint">Use 8–72 characters with upper, lower, and numeric characters.</span>
                    <?php endif; ?>
                </div>
                <?php if ($isRegister): ?>
                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm password</label>
                        <div class="password-field">
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" maxlength="72" required>
                            <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password">Show</button>
                        </div>
                    </div>
                <?php endif; ?>
                <button class="button button-primary auth-submit" type="submit">
                    <span><?= $isRegister ? 'Create membership' : 'Enter AuraLib' ?></span>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"></path>
                    </svg>
                </button>
            </form>
            <p class="auth-switch-prompt">
                <?= $isRegister ? 'Already part of AuraLib?' : 'New to AuraLib?' ?>
                <a href="<?= $isRegister ? url('login') : url('register') ?>"><?= $isRegister ? 'Sign in' : 'Create an account' ?></a>
            </p>
        </div>
        <p class="auth-footer">Protected by encrypted sessions and role-based access.</p>
    </section>
</div>
