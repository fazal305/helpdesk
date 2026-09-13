<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$errors = $_SESSION['login_errors'] ?? [];
$old = $_SESSION['login_old'] ?? [];
unset($_SESSION['login_errors'], $_SESSION['login_old']);

$pageTitle = 'Log in — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="card">
        <h1>Log in</h1>
        <p class="auth-page__sub">Welcome back. Enter your details to continue.</p>
        <form class="form" action="/actions/login.php" method="post" novalidate>
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>"
                       class="<?= isset($errors['general']) ? 'field-error' : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="<?= isset($errors['general']) ? 'field-error' : '' ?>" required>
                <?php if (isset($errors['general'])): ?><div class="field-error-message"><?= e($errors['general']) ?></div><?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Log in</button>
            </div>
        </form>
        <div class="auth-page__switch">Don't have an account? <a href="/register.php">Sign up</a></div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
