<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();

$activeStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('pending', 'confirmed')");
$activeStmt->execute([$user['id']]);
$activeBookings = (int) $activeStmt->fetchColumn();

$paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = ? AND status = 'paid'");
$paidStmt->execute([$user['id']]);
$paidTotal = (float) $paidStmt->fetchColumn();

$resourcesStmt = $pdo->prepare('SELECT COUNT(*) FROM resources WHERE user_id = ?');
$resourcesStmt->execute([$user['id']]);
$resourceCount = (int) $resourcesStmt->fetchColumn();

$upcomingStmt = $pdo->prepare(
    "SELECT b.*, t.name AS tutor_name, t.subject
     FROM bookings b
     INNER JOIN tutors t ON t.id = b.tutor_id
     WHERE b.user_id = ? AND b.status IN ('pending', 'confirmed')
     ORDER BY b.session_date, b.session_time
     LIMIT 4"
);
$upcomingStmt->execute([$user['id']]);
$upcomingBookings = $upcomingStmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="dashboard-top">
    <div>
        <p class="eyebrow">Student dashboard</p>
        <h1>Hello, <?= e($user['full_name']) ?></h1>
        <p>Here is your tutoring activity, payments, and study material workspace.</p>
    </div>
    <a class="button primary" href="<?= e(url_for('tutors.php')) ?>">Book a tutor</a>
</section>

<section class="stat-grid">
    <article class="stat-card">
        <span>Active bookings</span>
        <strong><?= $activeBookings ?></strong>
    </article>
    <article class="stat-card">
        <span>Paid total</span>
        <strong><?= format_money($paidTotal) ?></strong>
    </article>
    <article class="stat-card">
        <span>Uploaded materials</span>
        <strong><?= $resourceCount ?></strong>
    </article>
</section>

<section class="split-section">
    <div class="content-panel">
        <div class="panel-heading">
            <h2>Upcoming sessions</h2>
            <a class="text-link" href="<?= e(url_for('my_bookings.php')) ?>">Manage all</a>
        </div>

        <?php if (!$upcomingBookings): ?>
            <p class="empty-state">No upcoming sessions yet. Browse tutors and reserve your first time slot.</p>
        <?php else: ?>
            <div class="stack-list">
                <?php foreach ($upcomingBookings as $booking): ?>
                    <article class="list-item">
                        <div>
                            <p class="soft-label"><?= e($booking['subject']) ?></p>
                            <h3><?= e($booking['tutor_name']) ?></h3>
                            <p><?= e($booking['session_date']) ?> at <?= e(substr($booking['session_time'], 0, 5)) ?></p>
                        </div>
                        <span class="badge <?= e(status_class($booking['status'])) ?>">
                            <?= e(status_label($booking['status'])) ?>
                        </span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="content-panel accent-panel">
        <h2>Demo checklist</h2>
        <ul class="check-list">
            <li>Sign-up and sign-in are stored in MySQL.</li>
            <li>Bookings can be created, paid, changed, and cancelled.</li>
            <li>Uploads are validated by type and stored outside public browsing.</li>
            <li>Receipts and study materials are downloaded through PHP.</li>
        </ul>
    </aside>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
