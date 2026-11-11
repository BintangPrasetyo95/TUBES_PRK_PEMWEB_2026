
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/admin/requirements.php');
}

csrf_check();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare(
        'DELETE FROM requirements WHERE id = ?'
    );
    $stmt->execute([$id]);
}

redirect('modules/admin/requirements.php');
