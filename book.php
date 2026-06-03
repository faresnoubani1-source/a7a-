<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$tutorId = (int) ($_GET['tutor_id'] ?? $_POST['tutor_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, name, subject, hourly_rate, rating, bio, accent_color FROM tutors WHERE id = ? LIMIT 1');
$stmt->execute([$tutorId]);
$tutor = $stmt->fetch();

if (!$tutor) {
    flash('warning', 'Tutor not found.');
    redirect('tutors.php');
}

$errors = [];
$sessionDate = (new DateTime('+1 day'))->format('Y-m-d');
$sessionTime = '16:00';
$duration = '60';
$focusArea = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $sessionDate = trim((string) ($_POST['session_date'] ?? ''));
    $sessionTime = trim((string) ($_POST['session_time'] ?? ''));
    $duration = (string) ($_POST['duration_minutes'] ?? '60');
    $focusArea = trim((string) ($_POST['focus_area'] ?? ''));

    if (!valid_future_session($sessionDate, $sessionTime)) {
        $errors[] = 'Choose a session date and time at least 30 minutes from now.';
    }

    if (!in_array((int) $duration, [60, 90, 120], true)) {
        $errors[] = 'Choose a valid session duration.';
    }

    if (strlen($focusArea) < 5 || strlen($focusArea) > 255) {
        $errors[] = 'Focus area must be between 5 and 255 characters.';
    }

    if (!$errors) {
        $conflictStmt = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE tutor_id = ? AND session_date = ? AND session_time = ?
             AND status IN ('pending', 'confirmed')
             LIMIT 1"
        );
        $conflictStmt->execute([$tutor['id'], $sessionDate, $sessionTime]);

        if ($conflictStmt->fetch()) {
            $errors[] = 'That tutor already has a session at this time.';
        }
    }

    if (!$errors) {
        $price = round((float) $tutor['hourly_rate'] * ((int) $duration / 60), 2);
        $bookingStmt = $pdo->prepare(
            'INSERT INTO bookings (user_id, tutor_id, session_date, session_time, duration_minutes, focus_area, price)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $bookingStmt->execute([
            $user['id'],
            $tutor['id'],
            $sessionDate,
            $sessionTime,
            (int) $duration,
            $focusArea,
            $price,
        ]);

        flash('success', 'Session reserved. Complete the demo payment to confirm it.');
        redirect('payment.php?booking_id=' . $pdo->lastInsertId());
    }
}

$pageTitle = 'Book ' . $tutor['name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="booking-layout">
    <aside class="tutor-summary">
        <div class="avatar-ring large" style="--accent: <?= e($tutor['accent_color']) ?>">
            <?= e(substr($tutor['name'], 0, 1)) ?>
        </div>
        <p class="soft-label"><?= e($tutor['subject']) ?></p>
        <h1><?= e($tutor['name']) ?></h1>
        <p><?= e($tutor['bio']) ?></p>
        <div class="card-meta stacked">
            <span><?= format_money($tutor['hourly_rate']) ?>/hour</span>
            <span><?= e($tutor['rating']) ?> student rating</span>
        </div>
    </aside>

    <form class="form-card" action="<?= e(url_for('book.php')) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="tutor_id" value="<?= e($tutor['id']) ?>">

        <div class="form-heading">
            <p class="eyebrow">Reserve time</p>
            <h2>Session details</h2>
        </div>

        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="two-column">
            <label>
                Date
                <input type="date" name="session_date" value="<?= e($sessionDate) ?>" min="<?= e(date('Y-m-d')) ?>" required>
            </label>
            <label>
                Time
                <input type="time" name="session_time" value="<?= e($sessionTime) ?>" required>
            </label>
        </div>

        <label>
            Duration
            <select name="duration_minutes">
                <option value="60"<?= selected($duration, '60') ?>>60 minutes</option>
                <option value="90"<?= selected($duration, '90') ?>>90 minutes</option>
                <option value="120"<?= selected($duration, '120') ?>>120 minutes</option>
            </select>
        </label>

        <label>
            What should the tutor prepare?
            <textarea name="focus_area" rows="4" maxlength="255" required><?= e($focusArea) ?></textarea>
        </label>

        <button class="button primary full" type="submit">Continue to payment</button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
