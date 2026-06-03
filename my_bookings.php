<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT b.*, t.name AS tutor_name, t.subject, p.status AS payment_status
     FROM bookings b
     INNER JOIN tutors t ON t.id = b.tutor_id
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.user_id = ?
     ORDER BY b.session_date DESC, b.session_time DESC"
);
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My bookings';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-heading">
    <div>
        <p class="eyebrow">My sessions</p>
        <h1>Bookings and receipts</h1>
    </div>
    <a class="button primary" href="<?= e(url_for('tutors.php')) ?>">New booking</a>
</section>

<?php if (!$bookings): ?>
    <p class="empty-state">You have not booked any tutoring sessions yet.</p>
<?php else: ?>
    <div class="booking-list">
        <?php foreach ($bookings as $booking): ?>
            <article class="booking-row">
                <div class="booking-main">
                    <span class="badge <?= e(status_class($booking['status'])) ?>">
                        <?= e(status_label($booking['status'])) ?>
                    </span>
                    <h2><?= e($booking['subject']) ?> with <?= e($booking['tutor_name']) ?></h2>
                    <p><?= e($booking['session_date']) ?> at <?= e(substr($booking['session_time'], 0, 5)) ?>, <?= e($booking['duration_minutes']) ?> minutes</p>
                    <p class="muted-text"><?= e($booking['focus_area']) ?></p>
                </div>
                <div class="booking-side">
                    <strong><?= format_money($booking['price']) ?></strong>
                    <div class="action-row">
                        <?php if ($booking['status'] === 'pending'): ?>
                            <a class="button small primary" href="<?= e(url_for('payment.php?booking_id=' . $booking['id'])) ?>">Pay</a>
                        <?php endif; ?>

                        <?php if (in_array($booking['status'], ['pending', 'confirmed'], true)): ?>
                            <a class="button small secondary" href="<?= e(url_for('change_booking.php?booking_id=' . $booking['id'])) ?>">Change date</a>
                            <form action="<?= e(url_for('cancel_booking.php')) ?>" method="post" data-confirm="Cancel this booking?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                                <button class="button small danger" type="submit">Cancel</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($booking['payment_status'] === 'paid' || $booking['payment_status'] === 'refunded'): ?>
                            <a class="button small ghost" href="<?= e(url_for('receipt.php?booking_id=' . $booking['id'])) ?>">Receipt</a>
                        <?php endif; ?>

                        <a class="button small ghost" href="<?= e(url_for('resources.php?booking_id=' . $booking['id'])) ?>">Materials</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
