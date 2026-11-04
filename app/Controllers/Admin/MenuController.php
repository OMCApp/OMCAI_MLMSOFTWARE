<?php
/**
 * เมนูของเว็บ (CMS) — header / footer แยกตามภาษา · รายการซ้อนได้ 2 ชั้น · เรียงขึ้น/ลง
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Menu;
use App\Services\MenuService;
use App\Services\PageService;
use App\Services\TranslationService;

final class MenuController extends AdminController
{
    private MenuService $menus;

    public function __construct()
    {
        parent::__construct();
        $this->menus = new MenuService();
    }

    public function index(): void
    {
        [$location, $locale] = $this->scope();
        $rows = $this->menus->flat($location, $locale);

        $this->view('admin/menus', [
            'title'        => __('cms.menus.title'),
            'pageLead'     => __('cms.menus.lead'),
            'pageWidth'    => 'wide',
            'menuLocation' => $location,
            'menuLocale'   => $locale,
            'menuRows'     => $rows,
            'menuParents'  => ['' => __('cms.menus.top_level')] + $this->parentOptions($rows),
            'menuPages'    => ['' => '—'] + (new PageService())->options(null, TranslationService::enabled('pages') ? $locale : null),
            'menuLocales'  => PageController::localeOptions(),
        ]);
    }

    public function store(): void
    {
        [$location, $locale] = $this->scope();
        $data   = $this->input();
        $errors = $this->menus->validateItem($data, $location, $locale);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $menuId = $this->menus->ensureMenu($location, $location, $locale);
        $this->menus->createItem($data + ['menu_id' => $menuId, 'sort' => $this->menus->nextSort($menuId, $data['parent_id'])]);

        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/menus?' . http_build_query(['location' => $location, 'locale' => $locale]));
    }

    public function edit(string $id): void
    {
        $item = $this->menus->findItem((int) $id);

        if ($item === null) {
            $this->notFound();
        }

        [$location, $locale] = $this->scopeOf($item);
        $rows = $this->menus->flat($location, $locale);

        $this->view('admin/crud/form', [
            'title'          => __('cms.menus.edit_item'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.menus.title'), 'url' => '/admin/menus?location=' . $location . '&locale=' . $locale],
                                 ['label' => (string) $item['label']]],
            'formAction'     => '/admin/menus/items/' . (int) $id,
            'formMethod'     => 'PATCH',
            'formSections'   => [['fields' => $this->fields($item, $rows, $locale, (int) $id)]],
            'formCancel'     => '/admin/menus?location=' . $location . '&locale=' . $locale,
            'formDelete'     => ['url' => '/admin/menus/items/' . (int) $id, 'name' => (string) $item['label']],
        ]);
    }

    public function update(string $id): void
    {
        $item = $this->menus->findItem((int) $id);

        if ($item === null) {
            $this->notFound();
        }

        [$location, $locale] = $this->scopeOf($item);
        $data   = $this->input();
        $errors = $this->menus->validateItem($data, $location, $locale, (int) $id);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->menus->updateItem((int) $id, $data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/menus?' . http_build_query(['location' => $location, 'locale' => $locale]));
    }

    public function destroy(string $id): void
    {
        $item = $this->menus->findItem((int) $id);

        if ($item === null) {
            $this->notFound();
        }

        [$location, $locale] = $this->scopeOf($item);
        $this->menus->deleteItem((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/menus?' . http_build_query(['location' => $location, 'locale' => $locale]));
    }

    public function move(string $id): void
    {
        $item = $this->menus->findItem((int) $id);

        if ($item === null) {
            $this->notFound();
        }

        [$location, $locale] = $this->scopeOf($item);
        $this->menus->move((int) $id, $this->request->pick('direction', ['up', 'down'], 'up'));
        $this->redirect('/admin/menus?' . http_build_query(['location' => $location, 'locale' => $locale]));
    }

    public function import(): void
    {
        [$location, $locale] = $this->scope();
        $added = $this->menus->importDefaults($location, $locale);
        $this->flash($added > 0 ? 'success' : 'info', __('cms.menus.imported', ['count' => (string) $added]));
        $this->redirect('/admin/menus?' . http_build_query(['location' => $location, 'locale' => $locale]));
    }

    // ------------------------------------------------------------------

    /** @return array{0:string, 1:string} */
    private function scope(): array
    {
        return [
            $this->request->pick('location', Menu::LOCATIONS, 'header'),
            $this->request->pick('locale', TranslationService::locales(), TranslationService::default()),
        ];
    }

    /**
     * ตำแหน่ง/ภาษาของรายการ — อ่านจากเมนูของรายการเอง ไม่เชื่อ query string
     *
     * @param  array<string, mixed> $item
     * @return array{0:string, 1:string}
     */
    private function scopeOf(array $item): array
    {
        return $this->menus->scopeOf($item);
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return [
            'label'       => $this->str('label', 150),
            'description' => $this->nullable('description', 255),
            'icon'        => $this->nullable('icon', 50),
            'url'         => $this->nullable('url', 500),
            'page_id'     => $this->intOrNull('page_id'),
            'parent_id'   => $this->intOrNull('parent_id'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function parentOptions(array $rows, ?int $except = null): array
    {
        $out = [];

        foreach ($rows as $row) {
            if ((int) $row['depth'] === 0 && (int) $row['id'] !== $except) {
                $out[(int) $row['id']] = (string) $row['label'];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>             $item
     * @param  array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function fields(array $item, array $rows, string $locale, int $id): array
    {
        return [
            ['name' => 'label', 'label' => __('cms.menus.label'), 'value' => $item['label'] ?? '', 'required' => true],
            ['name' => 'page_id', 'label' => __('cms.menus.page'), 'type' => 'select', 'value' => (string) ($item['page_id'] ?? ''),
             'options' => ['' => '—'] + (new PageService())->options(null, TranslationService::enabled('pages') ? $locale : null)],
            ['name' => 'url', 'label' => __('cms.menus.url'), 'value' => $item['url'] ?? '', 'hint' => __('cms.menus.url_hint')],
            ['name' => 'parent_id', 'label' => __('cms.menus.parent'), 'type' => 'select', 'value' => (string) ($item['parent_id'] ?? ''),
             'options' => ['' => __('cms.menus.top_level')] + $this->parentOptions($rows, $id)],
            ['name' => 'description', 'label' => __('cms.menus.description'), 'value' => $item['description'] ?? '', 'hint' => __('cms.menus.description_hint')],
            ['name' => 'icon', 'label' => __('cms.menus.icon'), 'value' => $item['icon'] ?? '', 'width' => 'medium'],
        ];
    }
}
