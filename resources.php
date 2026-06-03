<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$errors = [];
$title = '';
$description = '';
$selectedBookingId = (string) ($_GET['booking_id'] ?? '');
$bookingOptions = owned_booking_options((int) $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $selectedBookingId = trim((string) ($_POST['booking_id'] ?? ''));
    $bookingId = $selectedBookingId !== '' ? (int) $selectedBookingId : null;

    if (strlen($title) < 3 || strlen($title) > 140) {
        $errors[] = 'Title must be between 3 and 140 characters.';
    }

    if (strlen($description) > 255) {
        $errors[] = 'Description must be 255 characters or fewer.';
    }

    if ($bookingId !== null) {
        $ownedBookingIds = array_map(static fn (array $booking): int => (int) $booking['id'], $bookingOptions);

        if (!in_array($bookingId, $ownedBookingIds, true)) {
            $errors[] = 'Choose one of your active bookings or leave the booking field empty.';
        }
    }

    if (!$errors) {
        try {
            $storedFile = store_uploaded_resource($_FILES['material'] ?? []);
            $stmt = $pdo->prepare(
                'INSERT INTO resources (user_id, booking_id, title, description, original_name, stored_name, mime_type, file_size)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $user['id'],
                $bookingId,
                $title,
                $description !== '' ? $description : null,
                $storedFile['original_name'],
                $storedFile['stored_name'],
                $storedFile['mime_type'],
                $storedFile['file_size'],
            ]);

            flash('success', 'Study material uploaded.');
            redirect('resources.php');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}

$resourceStmt = $pdo->prepare(
    "SELECT r.*, b.session_date, t.name AS tutor_name, t.subject
     FROM resources r
     LEFT JOIN bookings b ON b.id = r.booking_id
     LEFT JOIN tutors t ON t.id = b.tutor_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);
$resourceStmt->execute([$user['id']]);
$resources = $resourceStmt->fetchAll();

$pageTitle = 'Study materials';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-heading">
    <div>
        <p class="eyebrow">File handling</p>
        <h1>Study materials</h1>
        <p>Upload assignment briefs, screenshots, or notes for tutor preparation. Files stay private to your account.</p>
    </div>
</section>

<section class="resource-layout">
    <form class="form-card" action="<?= e(url_for('resources.php')) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_UPLOAD_SIZE ?>">

        <div class="form-heading">
            <p class="eyebrow">Upload</p>
            <h2>Add material</h2>
        </div>

        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label>
            Title
            <input type="text" name="title" value="<?= e($title) ?>" maxlength="140" required>
        </label>

        <label>
            Related booking
            <select name="booking_id">
                <option value="">General material</option>
                <?php foreach ($bookingOptions as $booking): ?>
                    <?php $optionLabel = '#' . $booking['id'] . ' - ' . $booking['subject'] . ' with ' . $booking['tutor_name'] . ' on ' . $booking['session_date']; ?>
                    <option value="<?= e($booking['id']) ?>"<?= selected($selectedBookingId, (string) $booking['id']) ?>>
                        <?= e($optionLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Description
            <textarea name="description" rows="3" maxlength="255"><?= e($description) ?></textarea>
        </label>

        <label>
            File
            <input type="file" name="material" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.txt" required>
        </label>

        <p class="form-note">Allowed: PDF, Word, PowerPoint, JPG, PNG, TXT. Maximum file size is 5 MB.</p>
        <button class="button primary full" type="submit">Upload material</button>
    </form>

    <div class="content-panel">
        <div class="panel-heading">
            <h2>Your files</h2>
            <span class="pill-count"><?= count($resources) ?> saved</span>
        </div>

        <?php if (!$resources): ?>
            <p class="empty-state">No uploaded materials yet.</p>
        <?php else: ?>
            <div class="stack-list">
                <?php foreach ($resources as $resource): ?>
                    <article class="list-item resource-item">
                        <div>
                            <p class="soft-label">
                                <?= $resource['booking_id'] ? e($resource['subject'] . ' booking #' . $resource['booking_id']) : 'General material' ?>
                            </p>
                            <h3><?= e($resource['title']) ?></h3>
                            <p><?= e($resource['description'] ?: $resource['original_name']) ?></p>
                            <p class="muted-text">
                                <?= e($resource['original_name']) ?>, <?= human_file_size((int) $resource['file_size']) ?>
                            </p>
                        </div>
                        <div class="action-row">
                            <a class="button small secondary" href="<?= e(url_for('download.php?id=' . $resource['id'])) ?>">Download</a>
                            <form action="<?= e(url_for('delete_resource.php')) ?>" method="post" data-confirm="Delete this material?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="resource_id" value="<?= e($resource['id']) ?>">
                                <button class="button small danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
