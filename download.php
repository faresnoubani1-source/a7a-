<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
$resourceId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM resources WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$resourceId, $user['id']]);
$resource = $stmt->fetch();

if (!$resource) {
    flash('warning', 'File not found.');
    redirect('resources.php');
}

$path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $resource['stored_name'];

if (!is_file($path)) {
    flash('danger', 'The stored file is missing.');
    redirect('resources.php');
}

$downloadName = preg_replace('/[^A-Za-z0-9_. -]/', '_', (string) $resource['original_name']);

header('Content-Type: ' . $resource['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
readfile($path);
exit;
