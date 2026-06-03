<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            flash('success', 'Welcome back.');
            redirect('dashboard.php');
        }

        $errors[] = 'The email or password is incorrect.';
    }
}

$pageTitle = 'Sign in';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-layout compact">
    <div class="auth-panel">
        <p class="eyebrow">Student login</p>
        <h1>Sign in to manage your tutoring sessions</h1>
        <p>Access your bookings, upload materials, change dates, and download receipts.</p>
    </div>
    <form class="form-card" action="<?= e(url_for('signin.php')) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label>
            Email address
            <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
        </label>

        <label>
            Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>

        <button class="button primary full" type="submit">Sign in</button>
        <p class="form-note">Need an account? <a href="<?= e(url_for('signup.php')) ?>">Create one</a>.</p>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
