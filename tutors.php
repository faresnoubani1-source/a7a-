<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$subject = trim((string) ($_GET['subject'] ?? ''));

$subjects = $pdo->query('SELECT DISTINCT subject FROM tutors ORDER BY subject')->fetchAll(PDO::FETCH_COLUMN);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(name LIKE ? OR subject LIKE ? OR bio LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

if ($subject !== '') {
    $where[] = 'subject = ?';
    $params[] = $subject;
}

$sql = 'SELECT id, name, subject, hourly_rate, rating, bio, accent_color FROM tutors';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY rating DESC, hourly_rate ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutors = $stmt->fetchAll();

$pageTitle = 'Tutors';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-heading">
    <div>
        <p class="eyebrow">Tutor directory</p>
        <h1>Find a tutor for your next session</h1>
    </div>
</section>

<form class="filter-bar" action="<?= e(url_for('tutors.php')) ?>" method="get">
    <label>
        Search
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Tutor, subject, or skill">
    </label>
    <label>
        Subject
        <select name="subject">
            <option value="">All subjects</option>
            <?php foreach ($subjects as $subjectOption): ?>
                <option value="<?= e($subjectOption) ?>"<?= selected($subject, $subjectOption) ?>>
                    <?= e($subjectOption) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button secondary" type="submit">Filter</button>
    <a class="button ghost" href="<?= e(url_for('tutors.php')) ?>">Reset</a>
</form>

<?php if (!$tutors): ?>
    <p class="empty-state">No tutors matched your search.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($tutors as $tutor): ?>
            <article class="tutor-card">
                <div class="avatar-ring" style="--accent: <?= e($tutor['accent_color']) ?>">
                    <?= e(substr($tutor['name'], 0, 1)) ?>
                </div>
                <div class="card-body">
                    <p class="soft-label"><?= e($tutor['subject']) ?></p>
                    <h2><?= e($tutor['name']) ?></h2>
                    <p><?= e($tutor['bio']) ?></p>
                    <div class="card-meta">
                        <span><?= format_money($tutor['hourly_rate']) ?>/hour</span>
                        <span><?= e($tutor['rating']) ?> rating</span>
                    </div>
                    <a class="button small primary" href="<?= e(url_for('book.php?tutor_id=' . $tutor['id'])) ?>">Book session</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
