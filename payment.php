<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$bookingId = (int) ($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT b.*, t.name AS tutor_name, t.subject, p.status AS payment_status
     FROM bookings b
     INNER JOIN tutors t ON t.id = b.tutor_id
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.id = ? AND b.user_id = ?
     LIMIT 1"
);
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    flash('warning', 'Booking not found.');
    redirect('my_bookings.php');
}

if ($booking['status'] === 'cancelled') {
    flash('warning', 'Cancelled bookings cannot be paid.');
    redirect('my_bookings.php');
}

if ($booking['payment_status'] === 'paid') {
    flash('success', 'This booking is already paid.');
    redirect('my_bookings.php');
}

$errors = [];
$cardName = $user['full_name'];
$cardNumber = '';
$expiry = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $cardName = trim((string) ($_POST['card_name'] ?? ''));
    $cardNumber = preg_replace('/\D+/', '', (string) ($_POST['card_number'] ?? ''));
    $expiry = trim((string) ($_POST['expiry'] ?? ''));
    $cvv = preg_replace('/\D+/', '', (string) ($_POST['cvv'] ?? ''));

    if (strlen($cardName) < 3) {
        $errors[] = 'Enter the cardholder name.';
    }

    if (strlen($cardNumber) !== 16) {
        $errors[] = 'Use a 16 digit demo card number.';
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiry)) {
        $errors[] = 'Expiry must use MM/YY format.';
    } else {
        [$month, $year] = explode('/', $expiry);
        $expiryDate = DateTime::createFromFormat('Y-m-d H:i:s', '20' . $year . '-' . $month . '-01 23:59:59');
        $expiryDate?->modify('last day of this month');

        if (!$expiryDate || $expiryDate < new DateTime()) {
            $errors[] = 'The demo card expiry date must be in the future.';
        }
    }

    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
        $errors[] = 'CVV must be 3 or 4 digits.';
    }

    if (!$errors) {
        $pdo->beginTransaction();

        try {
            $paymentStmt = $pdo->prepare(
                'INSERT INTO payments (booking_id, user_id, amount, card_last4) VALUES (?, ?, ?, ?)'
            );
            $paymentStmt->execute([
                $booking['id'],
                $user['id'],
                $booking['price'],
                substr($cardNumber, -4),
            ]);

            $bookingStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND user_id = ?");
            $bookingStmt->execute([$booking['id'], $user['id']]);

            $pdo->commit();
            flash('success', 'Payment completed. Your tutoring session is confirmed.');
            redirect('my_bookings.php');
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[] = 'Payment could not be saved. Please try again.';
        }
    }
}

$pageTitle = 'Payment';
require_once __DIR__ . '/includes/header.php';
?>

<section class="payment-layout">
    <aside class="content-panel">
        <p class="eyebrow">Payment summary</p>
        <h1><?= e($booking['subject']) ?> session</h1>
        <dl class="summary-list">
            <div>
                <dt>Tutor</dt>
                <dd><?= e($booking['tutor_name']) ?></dd>
            </div>
            <div>
                <dt>Date</dt>
                <dd><?= e($booking['session_date']) ?> at <?= e(substr($booking['session_time'], 0, 5)) ?></dd>
            </div>
            <div>
                <dt>Duration</dt>
                <dd><?= e($booking['duration_minutes']) ?> minutes</dd>
            </div>
            <div>
                <dt>Total</dt>
                <dd><?= format_money($booking['price']) ?></dd>
            </div>
        </dl>
    </aside>

    <form class="form-card" action="<?= e(url_for('payment.php')) ?>" method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">

        <div class="form-heading">
            <p class="eyebrow">Demo checkout</p>
            <h2>Confirm payment</h2>
        </div>

        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label>
            Cardholder name
            <input type="text" name="card_name" value="<?= e($cardName) ?>" required>
        </label>

        <label>
            Demo card number
            <input type="text" name="card_number" value="<?= e($cardNumber) ?>" inputmode="numeric" maxlength="19" placeholder="4242 4242 4242 4242" required>
        </label>

        <div class="two-column">
            <label>
                Expiry
                <input type="text" name="expiry" value="<?= e($expiry) ?>" placeholder="12/30" maxlength="5" required>
            </label>
            <label>
                CVV
                <input type="password" name="cvv" inputmode="numeric" maxlength="4" required>
            </label>
        </div>

        <p class="form-note">This is a safe classroom demo. The app stores only the last four digits, never the full card number.</p>
        <button class="button primary full" type="submit">Pay <?= format_money($booking['price']) ?></button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
