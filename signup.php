<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$firstName = '';
$lastName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($firstName) < 2 || strlen($firstName) > 70 || !preg_match("/^[A-Za-z][A-Za-z' -]*$/", $firstName)) {
        $errors[] = 'First name must be 2 to 70 characters and use letters, spaces, hyphens, or apostrophes.';
    }

    if (strlen($lastName) < 2 || strlen($lastName) > 70 || !preg_match("/^[A-Za-z][A-Za-z' -]*$/", $lastName)) {
        $errors[] = 'Last name must be 2 to 70 characters and use letters, spaces, hyphens, or apostrophes.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $errors[] = 'Phone number can contain digits, spaces, +, -, and parentheses.';
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = array_merge($errors, validate_password_strength($password, $firstName, $lastName, $email));
    } else {
        $errors = array_merge($errors, validate_password_strength($password, $firstName, $lastName, ''));
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'An account already exists with that email.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            $phone !== '' ? $phone : null,
            password_hash($password, PASSWORD_DEFAULT),
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        flash('success', 'Account created. You can now book a tutoring session.');
        redirect('dashboard.php');
    }
}

$pageTitle = 'Create account';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-layout">
    <div class="auth-panel">
        <p class="eyebrow">New student account</p>
        <h1>Create your TutorConnect account</h1>
        <p>Use this account to manage bookings, payment receipts, and uploaded study materials.</p>
    </div>
    <form class="form-card" action="<?= e(url_for('signup.php')) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="two-column">
            <label>
                First name
                <input type="text" name="first_name" value="<?= e($firstName) ?>" required minlength="2" maxlength="70" autocomplete="given-name">
            </label>

            <label>
                Last name
                <input type="text" name="last_name" value="<?= e($lastName) ?>" required minlength="2" maxlength="70" autocomplete="family-name">
            </label>
        </div>

        <label>
            Email address
            <input type="email" name="email" value="<?= e($email) ?>" required maxlength="160" autocomplete="email">
        </label>

        <label>
            Phone number
            <input type="tel" name="phone" value="<?= e($phone) ?>" maxlength="30" autocomplete="tel">
        </label>

        <label>
            Password
            <input type="password" name="password" required minlength="10" maxlength="72" autocomplete="new-password">
        </label>
        <ul class="password-rules">
            <li>At least 10 characters</li>
            <li>Uppercase, lowercase, number, and special character</li>
            <li>No spaces, common words, or your name/email username</li>
        </ul>

        <label>
            Confirm password
            <input type="password" name="confirm_password" required minlength="10" maxlength="72" autocomplete="new-password">
        </label>

        <button class="button primary full" type="submit">Create account</button>
        <p class="form-note">Already registered? <a href="<?= e(url_for('signin.php')) ?>">Sign in here</a>.</p>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
