<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

// actions/register.php stashes validation errors + resubmitted (non-password)
// field values in the session before redirecting back here, so the form can
// show inline errors without losing what the user already typed.
$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_old']);

$pageTitle = 'Sign up — HelpDesk';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="card">
        <h1>Create your account</h1>
        <p class="auth-page__sub">Sign up to submit and track support tickets.</p>
        <form class="form" action="/actions/register.php" method="post" novalidate>
            <?= csrfField() ?>
            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>"
                       class="<?= isset($errors['name']) ? 'field-error' : '' ?>" required>
                <?php if (isset($errors['name'])): ?><div class="field-error-message"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>"
                       class="<?= isset($errors['email']) ? 'field-error' : '' ?>" required>
                <?php if (isset($errors['email'])): ?><div class="field-error-message"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="<?= isset($errors['password']) ? 'field-error' : '' ?>" required>
                <div class="form-hint">At least 8 characters.</div>
                <?php if (isset($errors['password'])): ?><div class="field-error-message"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       class="<?= isset($errors['password_confirm']) ? 'field-error' : '' ?>" required>
                <?php if (isset($errors['password_confirm'])): ?><div class="field-error-message"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Create account</button>
            </div>
        </form>
        <div class="auth-page__switch">Already have an account? <a href="/login.php">Log in</a></div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
