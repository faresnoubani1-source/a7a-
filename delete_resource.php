<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('resources.php');
}

verify_csrf();
$user = current_user();
$resourceId = (int) ($_POST['resource_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, stored_name FROM resources WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$resourceId, $user['id']]);
$resource = $stmt->fetch();

if (!$resource) {
    flash('warning', 'File not found.');
    redirect('resources.php');
}

$deleteStmt = $pdo->prepare('DELETE FROM resources WHERE id = ? AND user_id = ?');
$deleteStmt->execute([$resource['id'], $user['id']]);

$path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $resource['stored_name'];
if (is_file($path)) {
    unlink($path);
}

flash('success', 'Study material deleted.');
redirect('resources.php');
