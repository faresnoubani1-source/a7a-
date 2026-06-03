<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$bookingId = (int) ($_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT b.*, t.name AS tutor_name, t.subject, p.card_last4, p.status AS payment_status, p.paid_at
     FROM bookings b
     INNER JOIN tutors t ON t.id = b.tutor_id
     INNER JOIN payments p ON p.booking_id = b.id
     WHERE b.id = ? AND b.user_id = ?
     LIMIT 1"
);
$stmt->execute([$bookingId, $user['id']]);
$receipt = $stmt->fetch();

if (!$receipt) {
    flash('warning', 'Receipt not found.');
    redirect('my_bookings.php');
}

$filename = 'tutorconnect-receipt-' . $receipt['id'] . '.txt';

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "TutorConnect Payment Receipt\n";
echo "============================\n\n";
echo "Receipt #: " . $receipt['id'] . "\n";
echo "Student: " . $user['full_name'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Tutor: " . $receipt['tutor_name'] . "\n";
echo "Subject: " . $receipt['subject'] . "\n";
echo "Session: " . $receipt['session_date'] . " at " . substr((string) $receipt['session_time'], 0, 5) . "\n";
echo "Duration: " . $receipt['duration_minutes'] . " minutes\n";
echo "Amount: " . format_money($receipt['price']) . "\n";
echo "Payment status: " . strtoupper((string) $receipt['payment_status']) . "\n";
echo "Card: **** **** **** " . $receipt['card_last4'] . "\n";
echo "Paid at: " . $receipt['paid_at'] . "\n\n";
echo "This generated text file demonstrates secure server-side download functionality.\n";
exit;
