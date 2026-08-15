<?php
/**
 * ArchiveController - browse the audit trail of archived records (admin only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Archive.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';

class ArchiveController extends BaseController
{
        public function index(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $this->render('admin/archived', [
            'archives' => Archive::all(),
            'items'    => Item::archived(),
        ]);
    }

    public function deletePermanently(string $id): void
    {
        AuthMiddleware::requireRole(['admin']);
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/archived', 'danger', 'Invalid security token.');
        }
        db()->prepare('DELETE FROM archives WHERE id = ?')->execute([(int)$id]);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'archive_purge');
        $this->redirect('admin/archived', 'warning', 'Archive record removed.');
    }
}
