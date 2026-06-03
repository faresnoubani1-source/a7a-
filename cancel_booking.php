<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_bookings.php');
}

verify_csrf();
$user = current_user();
$bookingId = (int) ($_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    flash('warning', 'Booking not found.');
    redirect('my_bookings.php');
}

if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
    flash('warning', 'This booking cannot be cancelled.');
    redirect('my_bookings.php');
}

$pdo->beginTransaction();

try {
    $updateBooking = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $updateBooking->execute([$bookingId, $user['id']]);

    $updatePayment = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE booking_id = ? AND user_id = ? AND status = 'paid'");
    $updatePayment->execute([$bookingId, $user['id']]);

    $pdo->commit();
    flash('success', 'Booking cancelled. Paid bookings are marked as refunded in this demo.');
} catch (Throwable $exception) {
    $pdo->rollBack();
    flash('danger', 'Booking could not be cancelled.');
}

redirect('my_bookings.php');
