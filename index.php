<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$featuredTutors = $pdo
    ->query('SELECT id, name, subject, hourly_rate, rating, bio, accent_color FROM tutors ORDER BY rating DESC LIMIT 3')
    ->fetchAll();

$pageTitle = 'Book tutoring sessions';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-grid">
    <div class="hero-copy">
        <p class="eyebrow">CS355 project demo</p>
        <h1>Book tutoring sessions, pay online, and share study materials securely.</h1>
        <p class="hero-text">
            TutorConnect gives students a complete workflow: create an account, choose a tutor,
            reserve a time, pay for the session, change the date if needed, and upload files
            that help the tutor prepare.
        </p>
        <div class="hero-actions">
            <a class="button primary" href="<?= e(url_for('tutors.php')) ?>">Browse tutors</a>
            <?php if (!is_logged_in()): ?>
                <a class="button secondary" href="<?= e(url_for('signup.php')) ?>">Create account</a>
            <?php else: ?>
                <a class="button secondary" href="<?= e(url_for('dashboard.php')) ?>">Open dashboard</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-panel" aria-label="Booking workflow summary">
        <div class="session-card large">
            <div>
                <span class="soft-label">Next available</span>
                <h2>Web Technologies</h2>
            </div>
            <p>Upload your assignment brief, book a PHP/MySQL session, and download your payment receipt after checkout.</p>
            <div class="metric-row">
                <span><strong>5</strong> tutors</span>
                <span><strong>5 MB</strong> upload limit</span>
                <span><strong>Secure</strong> forms</span>
            </div>
        </div>
    </div>
</section>

<section class="section-heading">
    <div>
        <p class="eyebrow">Featured tutors</p>
        <h2>Start with the strongest matches</h2>
    </div>
    <a class="text-link" href="<?= e(url_for('tutors.php')) ?>">View all tutors</a>
</section>

<div class="card-grid">
    <?php foreach ($featuredTutors as $tutor): ?>
        <article class="tutor-card">
            <div class="avatar-ring" style="--accent: <?= e($tutor['accent_color']) ?>">
                <?= e(substr($tutor['name'], 0, 1)) ?>
            </div>
            <div class="card-body">
                <p class="soft-label"><?= e($tutor['subject']) ?></p>
                <h3><?= e($tutor['name']) ?></h3>
                <p><?= e($tutor['bio']) ?></p>
                <div class="card-meta">
                    <span><?= format_money($tutor['hourly_rate']) ?>/hour</span>
                    <span><?= e($tutor['rating']) ?> rating</span>
                </div>
                <a class="button small" href="<?= e(url_for('book.php?tutor_id=' . $tutor['id'])) ?>">Book session</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<section class="feature-band">
    <div>
        <h2>Why file upload/download belongs in this idea</h2>
        <p>
            Students often need to send assignment PDFs, screenshots, or notes before tutoring.
            TutorConnect stores those files privately and only lets the signed-in owner download
            or delete them later.
        </p>
    </div>
    <a class="button secondary" href="<?= e(url_for(is_logged_in() ? 'resources.php' : 'signin.php')) ?>">Open materials</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
