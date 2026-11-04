<?php
/**
 * หมวดบทความ (CMS) — /category/{slug}/ · มี SEO ของหมวด · ลบได้เฉพาะหมวดที่ไม่มีบทความ
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Services\CategoryService;
use App\Services\TranslationService;
use Core\Path;

final class CategoryController extends AdminController
{
    private CategoryService $categories;

    public function __construct()
    {
        parent::__construct();
        $this->categories = new CategoryService();
    }

    public function index(): void
    {
        $p       = $this->listParams(Category::SORTABLE, 'sort', 'asc');
        $locales = TranslationService::enabled('categories');
        $filters = $this->filters($locales ? ['locale' => TranslationService::locales()] : []);
        $result  = $this->categories->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);
        $missing = TranslationService::missing('categories', $result['rows']);
        $result['rows'] = PageController::withLocaleLabels($result['rows']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['missing'] = PageController::missingLabel($missing[(int) $row['id']] ?? []);
        }

        $columns = [
            ['key' => 'name', 'label' => __('cms.field.name'), 'sort' => 'name', 'primary' => true],
            ['key' => 'slug', 'label' => __('cms.field.slug'), 'sort' => 'slug'],
            ['key' => 'robots', 'label' => __('cms.seo.robots'), 'format' => 'badge', 'lang' => 'cms.seo.robots_'],
        ];

        if ($locales) {
            $columns[] = ['key' => 'locale_label', 'label' => __('cms.field.locale'), 'format' => 'badge', 'badgeKind' => 'type'];
            $columns[] = ['key' => 'missing', 'label' => __('cms.translation.missing_col')];
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.categories.title'),
            'pageLead'    => __('cms.categories.lead'),
            'pageActions' => [['label' => __('cms.categories.add'), 'url' => '/admin/categories/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => $locales ? [PageController::localeFilter($filters['locale'])] : [],
            'listColumns' => $columns,
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/categories/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/categories/{id}', 'confirm' => 'name'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['robots' => 'index', 'sort' => 0, 'locale' => TranslationService::default()]);
    }

    public function edit(string $id): void
    {
        $category = $this->categories->find((int) $id);

        if ($category === null) {
            $this->notFound();
        }

        $this->form((int) $id, $category);
    }

    public function store(): void
    {
        $data   = $this->input();
        $errors = $this->categories->validateForCms($data, null);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $id = $this->categories->create($data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/categories/' . $id);
    }

    public function update(string $id): void
    {
        if ($this->categories->find((int) $id) === null) {
            $this->notFound();
        }

        $data   = $this->input();
        $errors = $this->categories->validateForCms($data, (int) $id);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->categories->update((int) $id, $data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/categories/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->categories->find((int) $id) === null) {
            $this->notFound();
        }

        if ($this->categories->postCount((int) $id) > 0) {
            $this->flash('danger', __('cms.categories.in_use'));
            $this->redirect('/admin/categories/' . (int) $id);
        }

        $this->categories->delete((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/categories');
    }

    public function translate(string $id): void
    {
        $result = $this->categories->translate((int) $id, (string) $this->request->input('locale', ''));

        if ($result['error'] !== null) {
            $this->flash('warning', __($result['error']));
            $this->redirect('/admin/categories/' . ($result['id'] > 0 ? $result['id'] : (int) $id));
        }

        $this->flash('success', __('cms.translation.created'));
        $this->redirect('/admin/categories/' . $result['id']);
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        $data = [
            'slug'             => rawurldecode($this->str('slug', 200)),
            'name'             => $this->str('name', 255),
            'description'      => $this->nullable('description', 5000),
            'seo_title'        => $this->nullable('seo_title', 255),
            'meta_description' => $this->nullable('meta_description', 500),
            'robots'           => $this->str('robots', 20) ?: 'index',
            'sort'             => (int) $this->request->input('sort', 0),
        ];

        if (TranslationService::enabled('categories')) {
            $data['locale'] = TranslationService::normalize((string) $this->request->input('locale', ''));
        }

        return $data;
    }

    /** @param array<string, mixed> $category */
    private function form(?int $id, array $category): void
    {
        $fields = [
            ['name' => 'name', 'label' => __('cms.field.name'), 'value' => $category['name'] ?? '', 'required' => true],
            ['name' => 'slug', 'label' => __('cms.field.slug'), 'value' => $category['slug'] ?? '', 'required' => true, 'hint' => __('cms.categories.slug_hint')],
            ['name' => 'description', 'label' => __('cms.field.description'), 'type' => 'textarea', 'value' => $category['description'] ?? ''],
            ['name' => 'sort', 'label' => __('cms.field.sort'), 'type' => 'number', 'value' => (string) ($category['sort'] ?? 0), 'width' => 'short'],
        ];

        if (TranslationService::enabled('categories')) {
            $fields[] = ['name' => 'locale', 'label' => __('cms.field.locale'), 'type' => 'select', 'value' => (string) ($category['locale'] ?? TranslationService::default()),
                         'options' => PageController::localeOptions(), 'width' => 'medium'];
        }

        $path = '/category/' . ($category['slug'] ?? '') . '/';

        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.categories.add') : __('cms.categories.edit'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.categories.title'), 'url' => '/admin/categories'],
                                 ['label' => $id === null ? __('cms.categories.add') : (string) $category['name']]],
            'formAction'     => $id === null ? '/admin/categories' : '/admin/categories/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [['fields' => $fields]],
            'formPanels'     => ['seo-panel'],
            'formAside'      => $id === null ? [] : ['translations'],
            'formCancel'     => '/admin/categories',
            'formDelete'     => $id === null ? null : ['url' => '/admin/categories/' . $id, 'name' => (string) $category['name']],
            'seoValues'      => $category,
            'seoOg'          => false,
            'seoUrl'         => rtrim((string) config('seo.canonical_url'), '/') . Path::encode($path),
            'seoFallback'    => (string) ($category['name'] ?? ''),
            'cmsTranslations' => $id !== null && TranslationService::enabled('categories') ? [
                'id' => $id, 'current' => (string) ($category['locale'] ?? TranslationService::default()), 'base' => '/admin/categories',
                'rows' => TranslationService::siblings('categories', $category['translation_group'] ?? null),
            ] : null,
        ]);
    }
}
