<?php
/**
 * สำรองฐานข้อมูล (admin) — สร้าง · ดาวน์โหลด · ลบ
 *
 * ‼️ ไม่มีกู้คืนบนเว็บโดยตั้งใจ — ขั้นตอนกู้คืนผ่าน CLI อยู่ใน project/DEPLOY.md และในคู่มือ
 *
 * สิทธิ์: admin เท่านั้น (RoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\BackupService;

final class BackupController extends AdminController
{
    public function index(): void
    {
        $rows = (new BackupService())->list();

        foreach ($rows as $i => $row) {
            $rows[$i]['size'] = number_format($row['bytes'] / 1024, 0) . ' KB';
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.backups.title'),
            'pageLead'    => __('cms.backups.lead'),
            'listNotice'  => __('cms.backups.restore_note'),
            'listSearch'  => false,
            'listColumns' => [
                ['key' => 'name', 'label' => __('cms.backups.file'), 'primary' => true],
                ['key' => 'created_at', 'label' => __('common.created_at'), 'format' => 'datetime'],
                ['key' => 'size', 'label' => __('cms.backups.size')],
            ],
            'listActions' => [
                ['icon' => 'download', 'label' => __('cms.backups.download'), 'url' => '/admin/backups/{name}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/backups/{name}', 'confirm' => 'name'],
            ],
            'listPostActions' => [['label' => __('cms.backups.create'), 'url' => '/admin/backups', 'icon' => 'plus']],
            'rows' => $rows, 'total' => count($rows), 'page' => 1, 'perPage' => max(1, count($rows)),
            'sort' => '', 'dir' => 'desc',
        ]);
    }

    public function store(): void
    {
        try {
            $name = (new BackupService())->create();
        } catch (\Throwable $e) {
            $reference = \Core\Logger::exception($e);
            $this->flash('danger', __('cms.backups.failed'), $reference);
            $this->redirect('/admin/backups');
        }

        $this->flash('success', __('cms.backups.created', ['name' => $name]));
        $this->redirect('/admin/backups');
    }

    public function download(string $name): never
    {
        $file = (new BackupService())->forDownload($name);

        if ($file === null) {
            $this->notFound();
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (string) filesize($file));
        header('Cache-Control: no-store');
        readfile($file);
        exit;
    }

    public function destroy(string $name): void
    {
        if (!(new BackupService())->delete($name)) {
            $this->notFound();
        }

        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/backups');
    }
}
