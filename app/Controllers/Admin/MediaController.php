<?php
/**
 * คลังสื่อ (CMS) — อัปโหลด (alt บังคับ · WebP + ขนาดย่อย) · ค้นหา · แก้ alt/title · ลบ (ถูกใช้อยู่ = ไม่ลบ)
 * · ลงทะเบียนรูปเดิมใน /wp-content/uploads/ โดยไม่ย้ายไฟล์
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\MediaService;

final class MediaController extends AdminController
{
    private MediaService $media;

    public function __construct()
    {
        parent::__construct();
        $this->media = new MediaService();
    }

    public function index(): void
    {
        $q      = mb_substr((string) $this->request->input('q', ''), 0, 100);
        $page   = $this->request->page();
        $result = $this->media->search($q, $page, 48);

        $this->view('admin/media', [
            'title'       => __('cms.media.title'),
            'pageLead'    => __('cms.media.lead'),
            'pageWidth'   => 'wide',
            'mediaRows'   => $result['rows'],
            'mediaTotal'  => $result['total'],
            'mediaPage'   => $page,
            'mediaQ'      => $q,
            'mediaNoAlt'  => $this->media->missingAltCount(),
            'mediaTypes'  => MediaService::EXTENSIONS,
            'mediaMax'    => (int) config('upload.max_bytes', 5_242_880),
        ]);
    }

    public function store(): void
    {
        $result = $this->media->upload($this->request->file('file'), $this->str('alt', 500), $this->str('title', 255));

        if (!$result['ok']) {
            $this->fail($result['errors']);
        }

        $this->flash('success', __('cms.media.uploaded'));
        $this->redirect('/admin/media/' . $result['id']);
    }

    public function edit(string $id): void
    {
        $row = $this->media->find((int) $id);

        if ($row === null) {
            $this->notFound();
        }

        $sizes = json_decode((string) ($row['sizes'] ?? ''), true);

        $this->view('admin/crud/form', [
            'title'          => __('cms.media.edit'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.media.title'), 'url' => '/admin/media'], ['label' => basename((string) $row['path'])]],
            'pageMedia'      => ['src' => (string) $row['path'], 'alt' => (string) $row['alt'],
                                 'width' => (int) ($row['width'] ?? 0), 'height' => (int) ($row['height'] ?? 0)],
            'formAction'     => '/admin/media/' . (int) $id,
            'formMethod'     => 'PATCH',
            'formSections'   => [[
                'hint'   => __('cms.media.info', [
                    'path' => (string) $row['path'], 'size' => (string) ($row['width'] ?? '?') . '×' . (string) ($row['height'] ?? '?'),
                    'variants' => is_array($sizes) ? (string) count($sizes) : '0',
                ]),
                'fields' => [
                    ['name' => 'alt', 'label' => __('cms.media.alt'), 'value' => $row['alt'] ?? '', 'required' => true, 'hint' => __('cms.media.alt_hint')],
                    ['name' => 'title', 'label' => __('cms.field.title'), 'value' => $row['title'] ?? ''],
                ],
            ]],
            'formAside'      => ['media-usage'],
            'mediaUsage'     => $this->media->usage((int) $id),
            'formCancel'     => '/admin/media',
            'formDelete'     => ['url' => '/admin/media/' . (int) $id, 'name' => basename((string) $row['path'])],
        ]);
    }

    public function update(string $id): void
    {
        if ($this->media->find((int) $id) === null) {
            $this->notFound();
        }

        $errors = $this->media->updateMeta((int) $id, $this->str('alt', 500), $this->str('title', 255));

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/media/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->media->find((int) $id) === null) {
            $this->notFound();
        }

        $result = $this->media->remove((int) $id);

        if (!$result['ok']) {
            $this->flash('danger', __('cms.media.in_use', ['count' => (string) count($result['usage'])]));
            $this->redirect('/admin/media/' . (int) $id);
        }

        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/media');
    }

    public function import(): void
    {
        $added = $this->media->importLegacy();
        $this->flash('success', __('cms.media.imported', ['count' => (string) $added]));
        $this->redirect('/admin/media');
    }
}
