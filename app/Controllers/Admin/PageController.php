<?php
/**
 * หน้าของเว็บ (CMS) — รายการ · เพิ่ม · แก้ (block builder + SEO) · ลบ (soft) · revisions · คำแปล
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Page;
use App\Services\BlockFormService;
use App\Services\MediaService;
use App\Services\PageService;
use App\Services\RevisionService;
use App\Services\TranslationService;
use Core\Path;

final class PageController extends AdminController
{
    private PageService $pages;

    public function __construct()
    {
        parent::__construct();
        $this->pages = new PageService();
    }

    public function index(): void
    {
        $p       = $this->listParams(Page::SORTABLE, 'updated_at');
        $locales = TranslationService::enabled('pages');
        $filters = $this->filters(['status' => Page::STATUSES] + ($locales ? ['locale' => TranslationService::locales()] : []));
        $result  = $this->pages->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);
        $missing = TranslationService::missing('pages', $result['rows']);
        $result['rows'] = self::withLocaleLabels($result['rows']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['missing'] = self::missingLabel($missing[(int) $row['id']] ?? []);
        }

        $columns = [
            ['key' => 'title', 'label' => __('cms.field.title'), 'sort' => 'title', 'primary' => true, 'limit' => 60],
            ['key' => 'path', 'label' => __('cms.field.path'), 'sort' => 'path', 'limit' => 60],
            ['key' => 'status', 'label' => __('cms.field.status'), 'sort' => 'status', 'format' => 'badge', 'lang' => 'cms.status.'],
        ];

        if ($locales) {
            $columns[] = ['key' => 'locale_label', 'label' => __('cms.field.locale'), 'format' => 'badge', 'badgeKind' => 'type'];
            $columns[] = ['key' => 'missing', 'label' => __('cms.translation.missing_col')];
        }

        $columns[] = ['key' => 'updated_at', 'label' => __('cms.field.updated_at'), 'sort' => 'updated_at', 'format' => 'datetime'];

        $listFilters = [self::statusFilter($filters['status'])];

        if ($locales) {
            $listFilters[] = self::localeFilter($filters['locale']);
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.pages.title'),
            'pageLead'    => __('cms.pages.lead'),
            'pageActions' => [['label' => __('cms.pages.add'), 'url' => '/admin/pages/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => $listFilters,
            'listColumns' => $columns,
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/pages/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/pages/{id}', 'confirm' => 'title'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['path' => '/', 'title' => '', 'template' => 'default', 'status' => 'draft', 'robots' => 'index',
            'blocks' => '[]', 'locale' => TranslationService::normalize((string) $this->request->input('locale', ''))]);
    }

    public function edit(string $id): void
    {
        $page = $this->pages->find((int) $id);

        if ($page === null) {
            $this->notFound();
        }

        $this->form((int) $id, $page);
    }

    public function store(): void
    {
        $data   = $this->input();
        $errors = $this->pages->validateForCms($data, null);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $saved = $this->pages->save(null, $data, false);
        (new \App\Services\IndexNowService())->page((int) $saved['id']);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/pages/' . $saved['id']);
    }

    public function update(string $id): void
    {
        $page = $this->pages->find((int) $id);

        if ($page === null) {
            $this->notFound();
        }

        $data   = $this->input();
        $errors = $this->pages->validateForCms($data, (int) $id);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $saved = $this->pages->save((int) $id, $data, $this->bool('redirect_old_path'));
        (new \App\Services\IndexNowService())->page((int) $id, $saved['redirect']);
        $this->flash('success', $saved['redirect'] !== null
            ? __('cms.common.saved_with_redirect', ['from' => $saved['redirect']])
            : __('cms.common.saved'));
        $this->redirect('/admin/pages/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->pages->find((int) $id) === null) {
            $this->notFound();
        }

        $this->pages->delete((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/pages');
    }

    public function translate(string $id): void
    {
        $result = $this->pages->translate((int) $id, (string) $this->request->input('locale', ''));

        if ($result['error'] !== null) {
            $this->flash('warning', __($result['error']));
            $this->redirect('/admin/pages/' . ($result['id'] > 0 ? $result['id'] : (int) $id));
        }

        $this->flash('success', __('cms.translation.created'));
        $this->redirect('/admin/pages/' . $result['id']);
    }

    public function revision(string $id, string $revisionId): void
    {
        $page     = $this->pages->find((int) $id);
        $snapshot = (new RevisionService())->snapshot((int) $revisionId);

        if ($page === null || $snapshot === null || (int) ($snapshot['id'] ?? 0) !== (int) $id) {
            $this->notFound();
        }

        $this->view('admin/revision', [
            'title'          => __('cms.revisions.view'),
            'pageBreadcrumb' => [['label' => __('cms.pages.title'), 'url' => '/admin/pages'],
                                 ['label' => (string) $page['title'], 'url' => '/admin/pages/' . (int) $id],
                                 ['label' => __('cms.revisions.view')]],
            'revisionFields' => self::diff($page, $snapshot, ['title', 'template', 'seo_title', 'meta_description', 'canonical_override', 'robots', 'blocks']),
            'revisionRestore' => '/admin/pages/' . (int) $id . '/revisions/' . (int) $revisionId . '/restore',
        ]);
    }

    public function restore(string $id, string $revisionId): void
    {
        if (!$this->pages->restore((int) $id, (int) $revisionId)) {
            $this->notFound();
        }

        $this->flash('success', __('cms.revisions.restored'));
        $this->redirect('/admin/pages/' . (int) $id);
    }

    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function input(): array
    {
        $raw    = $this->request->input('blocks', []);
        $blocks = (new BlockFormService())->fromInput(is_array($raw) ? $raw : []);
        $add    = (string) $this->request->input('add_block', '');

        // "บันทึกและเพิ่มบล็อก" (ไม่มี JS) — ต่อท้ายบล็อกว่างของชนิดที่เลือก
        if ($this->bool('_add') && isset(BlockFormService::schema()[$add])) {
            $blocks[] = BlockFormService::blank($add);
        }

        $status = $this->request->pick('status', Page::STATUSES, 'draft');
        $data   = [
            'path'               => self::normalizePath($this->str('path', 500)),
            'title'              => $this->str('title', 255),
            'parent_id'          => $this->intOrNull('parent_id'),
            'template'           => $this->str('template', 50) ?: 'default',
            'blocks'             => (string) json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'seo_title'          => $this->nullable('seo_title', 255),
            'meta_description'   => $this->nullable('meta_description', 500),
            'canonical_override' => $this->nullable('canonical_override', 500),
            'robots'             => $this->str('robots', 20) ?: 'index',
            'og_image_id'        => $this->intOrNull('og_image_id'),
            'status'             => $status,
            'published_at'       => $this->datetime('published_at') ?? ($status === 'published' ? date('Y-m-d H:i:s') : null),
        ];

        if (TranslationService::enabled('pages')) {
            $data['locale'] = TranslationService::normalize((string) $this->request->input('locale', ''));
        }

        return $data;
    }

    /** path ที่ผู้ใช้พิมพ์: decode · ขึ้นต้นและลงท้ายด้วย / */
    private static function normalizePath(string $path): string
    {
        $path = rawurldecode(trim($path));

        if ($path === '') {
            return '';
        }

        return '/' . trim($path, '/') . ($path === '/' ? '' : '/');
    }

    /** @param array<string, mixed> $page */
    private function form(?int $id, array $page): void
    {
        $media  = new MediaService();
        $blocks = json_decode((string) ($page['blocks'] ?? '[]'), true);

        // validate ไม่ผ่าน → แสดงบล็อกตามที่กรอกไว้ ไม่ใช่ของในฐานข้อมูล
        if (has_errors() && is_array(old('blocks', null))) {
            $blocks = (new BlockFormService())->fromInput((array) old('blocks'));
        }

        $locales  = TranslationService::enabled('pages');
        $locale   = (string) ($page['locale'] ?? TranslationService::default());
        $isLive   = $id !== null && PageService::isLive($page);
        $fullUrl  = rtrim((string) config('seo.canonical_url'), '/') . Path::encode((string) $page['path']);

        $main = [
            ['name' => 'title', 'label' => __('cms.field.title'), 'value' => $page['title'] ?? '', 'required' => true],
            ['name' => 'path', 'label' => __('cms.field.path'), 'value' => $page['path'] ?? '', 'required' => true,
             'hint' => __('cms.pages.path_hint')],
        ];

        if ($isLive) {
            $main[] = ['name' => 'redirect_old_path', 'label' => __('cms.pages.redirect_old'), 'type' => 'checkbox', 'value' => true,
                       'hint' => __('cms.pages.redirect_old_hint', ['path' => (string) $page['path']])];
        }

        $settings = [
            ['name' => 'status', 'label' => __('cms.field.status'), 'type' => 'select', 'value' => $page['status'] ?? 'draft',
             'options' => self::statusOptions(), 'width' => 'medium'],
            ['name' => 'published_at', 'label' => __('cms.field.published_at'), 'type' => 'datetime-local', 'width' => 'medium',
             'value' => ($page['published_at'] ?? null) !== null ? date('Y-m-d\TH:i', (int) strtotime((string) $page['published_at'])) : ''],
            ['name' => 'template', 'label' => __('cms.field.template'), 'type' => 'select', 'value' => $page['template'] ?? 'default',
             'options' => array_combine(PageService::TEMPLATES, array_map(static fn (string $t): string => __('cms.template.' . $t), PageService::TEMPLATES)),
             'width' => 'medium'],
            ['name' => 'parent_id', 'label' => __('cms.field.parent'), 'type' => 'select', 'value' => (string) ($page['parent_id'] ?? ''),
             'options' => ['' => '—'] + $this->pages->options($id, $locales ? $locale : null)],
        ];

        if ($locales) {
            $settings[] = ['name' => 'locale', 'label' => __('cms.field.locale'), 'type' => 'select', 'value' => $locale,
                           'options' => self::localeOptions(), 'width' => 'medium', 'hint' => __('cms.translation.locale_hint')];
        }

        $translations = null;

        if ($id !== null && $locales) {
            $translations = [
                'id'      => $id,
                'current' => $locale,
                'base'    => '/admin/pages',
                'rows'    => TranslationService::siblings('pages', $page['translation_group'] ?? null),
            ];
        }

        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.pages.add') : __('cms.pages.edit'),
            'pageWidth'      => 'wide',
            'pageBreadcrumb' => [['label' => __('cms.pages.title'), 'url' => '/admin/pages'],
                                 ['label' => $id === null ? __('cms.pages.add') : (string) $page['title']]],
            'formAction'     => $id === null ? '/admin/pages' : '/admin/pages/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [
                ['title' => __('cms.pages.section_main'), 'fields' => $main],
                ['title' => __('cms.pages.section_publish'), 'fields' => $settings, 'grid' => true],
            ],
            'formPanels'     => ['block-builder', 'seo-panel'],
            'formAside'      => $id === null ? [] : ['translations', 'revisions'],
            'formCancel'     => '/admin/pages',
            'formDelete'     => $id === null ? null : ['url' => '/admin/pages/' . $id, 'name' => (string) $page['title']],
            'formLinks'      => $id === null ? [] : [[
                'label' => __('cms.common.preview'), 'icon' => 'view', 'external' => true,
                'url'   => url(Path::encode((string) $page['path'])) . '?preview=1',
            ]],
            'cmsBlocks'       => is_array($blocks) ? $blocks : [],
            'cmsBlockSchema'  => BlockFormService::schema(),
            'cmsMediaOptions' => $media->pickerOptions(),
            'cmsMediaIdOf'    => static fn (?array $image): int => BlockFormService::mediaIdOf($image),
            'seoValues'       => $page,
            'seoUrl'          => $fullUrl,
            'seoFallback'     => (string) ($page['title'] ?? ''),
            'cmsTranslations' => $translations,
            'cmsRevisions'    => $id === null ? [] : (new RevisionService())->history('page', $id),
            'cmsRevisionBase' => '/admin/pages/' . $id,
        ]);
    }

    // ---------------------------------------------------------- ใช้ร่วมกับ Controller อื่นของเนื้อหา

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return ['draft' => __('cms.status.draft'), 'published' => __('cms.status.published')];
    }

    /** @return array<string, string> */
    public static function localeOptions(): array
    {
        return TranslationService::options();
    }

    /**
     * ป้ายภาษาของแต่ละแถว (คอลัมน์ locale_label ของตาราง)
     *
     * @param  array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public static function withLocaleLabels(array $rows): array
    {
        foreach ($rows as $i => $row) {
            $rows[$i]['locale_label'] = TranslationService::label((string) ($row['locale'] ?? TranslationService::default()));
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public static function statusFilter(string $value): array
    {
        return ['name' => 'status', 'label' => __('cms.field.status'), 'value' => $value, 'options' => ['' => '—'] + self::statusOptions()];
    }

    /** @return array<string, mixed> */
    public static function localeFilter(string $value): array
    {
        return ['name' => 'locale', 'label' => __('cms.field.locale'), 'value' => $value, 'options' => ['' => '—'] + self::localeOptions()];
    }

    /** @param array<int, string> $missing */
    public static function missingLabel(array $missing): string
    {
        return $missing === [] ? '' : '⚠ ' . implode(', ', array_map(static fn (string $l): string => strtoupper($l), $missing));
    }

    /**
     * ช่องที่ต่างระหว่างปัจจุบันกับ revision
     *
     * @param  array<string, mixed> $current
     * @param  array<string, mixed> $snapshot
     * @param  array<int, string>   $keys
     * @return array<int, array{key:string, now:string, then:string, changed:bool}>
     */
    public static function diff(array $current, array $snapshot, array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            $now  = (string) ($current[$key] ?? '');
            $then = (string) ($snapshot[$key] ?? '');
            $out[] = ['key' => $key, 'now' => $now, 'then' => $then, 'changed' => $now !== $then];
        }

        return $out;
    }
}
