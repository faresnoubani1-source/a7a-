<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$bookingId = (int) ($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT b.*, t.name AS tutor_name, t.subject
     FROM bookings b
     INNER JOIN tutors t ON t.id = b.tutor_id
     WHERE b.id = ? AND b.user_id = ?
     LIMIT 1"
);
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    flash('warning', 'Booking not found.');
    redirect('my_bookings.php');
}

if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
    flash('warning', 'Only pending or confirmed bookings can be changed.');
    redirect('my_bookings.php');
}

$errors = [];
$sessionDate = (string) $booking['session_date'];
$sessionTime = substr((string) $booking['session_time'], 0, 5);
$focusArea = (string) $booking['focus_area'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $sessionDate = trim((string) ($_POST['session_date'] ?? ''));
    $sessionTime = trim((string) ($_POST['session_time'] ?? ''));
    $focusArea = trim((string) ($_POST['focus_area'] ?? ''));

    if (!valid_future_session($sessionDate, $sessionTime)) {
        $errors[] = 'Choose a future date and time at least 30 minutes from now.';
    }

    if (strlen($focusArea) < 5 || strlen($focusArea) > 255) {
        $errors[] = 'Focus area must be between 5 and 255 characters.';
    }

    if (!$errors) {
        $conflictStmt = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE tutor_id = ? AND session_date = ? AND session_time = ?
             AND id <> ? AND status IN ('pending', 'confirmed')
             LIMIT 1"
        );
        $conflictStmt->execute([$booking['tutor_id'], $sessionDate, $sessionTime, $booking['id']]);

        if ($conflictStmt->fetch()) {
            $errors[] = 'That tutor already has a session at this time.';
        }
    }

    if (!$errors) {
        $updateStmt = $pdo->prepare(
            'UPDATE bookings SET session_date = ?, session_time = ?, focus_area = ? WHERE id = ? AND user_id = ?'
        );
        $updateStmt->execute([$sessionDate, $sessionTime, $focusArea, $booking['id'], $user['id']]);

        flash('success', 'Booking date updated.');
        redirect('my_bookings.php');
    }
}

$pageTitle = 'Change booking';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-layout compact">
    <div class="auth-panel">
        <p class="eyebrow">Change date</p>
        <h1><?= e($booking['subject']) ?> with <?= e($booking['tutor_name']) ?></h1>
        <p>Move the session to another available date and keep the same tutor and duration.</p>
    </div>

    <form class="form-card" action="<?= e(url_for('change_booking.php')) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">

        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="two-column">
            <label>
                New date
                <input type="date" name="session_date" value="<?= e($sessionDate) ?>" min="<?= e(date('Y-m-d')) ?>" required>
            </label>
            <label>
                New time
                <input type="time" name="session_time" value="<?= e($sessionTime) ?>" required>
            </label>
        </div>

        <label>
            Updated preparation notes
            <textarea name="focus_area" rows="4" maxlength="255" required><?= e($focusArea) ?></textarea>
        </label>

        <button class="button primary full" type="submit">Save changes</button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
