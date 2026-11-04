<?php
/**
 * เมนูของเว็บ — คืนเป็นต้นไม้ให้ layout วาด (Phase 1 วาด · Phase 3 แก้ใน CMS)
 *
 * ‼️ เมนูแยกตามตำแหน่ง (header · footer) และภาษาของเนื้อหา (th · en · zh)
 * ‼️ footer: รายการชั้นบนที่มีลูก = คอลัมน์ (หัวข้อ + ลิงก์) · รายการชั้นบนที่ไม่มีลูก = ลิงก์นโยบายแถวล่าง
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;

final class MenuService
{
    public const MAX_DEPTH = 2;

    public function __construct(
        private readonly Menu $menus = new Menu(),
        private readonly MenuItem $items = new MenuItem(),
    ) {
    }

    /**
     * ‼️ รายการที่ชี้ไปหน้าที่ยังไม่เผยแพร่ถูกซ่อน — ไม่งั้นเมนูพาคนไปเจอ 404
     *
     * @return array<int, array{label:string, url:string, desc:string, icon:string, children:array<int, array<string,mixed>>}>
     */
    public function tree(string $location, string $locale = ''): array
    {
        if (!in_array($location, Menu::LOCATIONS, true)) {
            return [];
        }

        $menu = $this->menus->findByLocation($location, TranslationService::normalize($locale));

        if ($menu === null) {
            return [];
        }

        $nodes = [];

        foreach ($this->items->forMenu((int) $menu['id']) as $row) {
            if ($row['page_id'] !== null && ($row['page_path'] === null || $row['page_status'] !== 'published')) {
                continue;
            }

            $nodes[(int) $row['id']] = [
                'label'     => (string) $row['label'],
                'url'       => (string) ($row['page_path'] ?? $row['url'] ?? '/'),
                'desc'      => (string) ($row['description'] ?? ''),
                'icon'      => (string) ($row['icon'] ?? ''),
                'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            ];
        }

        $childrenOf = [];

        foreach ($nodes as $id => $node) {
            $childrenOf[$node['parent_id'] ?? 0][] = $id;
        }

        // เริ่มจากราก (parent_id = null) — ลูกของรายการที่ถูกซ่อนจะหายไปด้วย ซึ่งถูกต้อง
        return $this->branch(0, $nodes, $childrenOf, 0);
    }

    /**
     * @param  array<int, array<string, mixed>> $nodes
     * @param  array<int, array<int, int>>      $childrenOf
     * @return array<int, array<string, mixed>>
     */
    private function branch(int $parent, array $nodes, array $childrenOf, int $depth): array
    {
        $out = [];

        foreach ($childrenOf[$parent] ?? [] as $id) {
            $out[] = [
                'label'    => $nodes[$id]['label'],
                'url'      => $nodes[$id]['url'],
                'desc'     => $nodes[$id]['desc'],
                'icon'     => $nodes[$id]['icon'],
                'children' => $depth < 3 ? $this->branch($id, $nodes, $childrenOf, $depth + 1) : [],
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /**
     * รายการทั้งหมดของเมนูหนึ่งแบบแบน (เรียงตามต้นไม้) สำหรับหน้าจัดการ — รวมรายการที่ชี้หน้า draft
     *
     * @return array<int, array<string, mixed>> แต่ละแถวมี depth
     */
    public function flat(string $location, string $locale): array
    {
        $menu = $this->menus->findByLocation($location, $locale);

        if ($menu === null) {
            return [];
        }

        $rows       = $this->items->forMenu((int) $menu['id']);
        $childrenOf = [];

        foreach ($rows as $row) {
            $childrenOf[(int) ($row['parent_id'] ?? 0)][] = $row;
        }

        $out  = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$out, $childrenOf): void {
            foreach ($childrenOf[$parent] ?? [] as $row) {
                $row['depth'] = $depth;
                $out[]        = $row;
                $walk((int) $row['id'], $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    /**
     * @param  array<string, mixed> $data label, url, page_id, parent_id, description, icon, sort
     * @return array<string, string> errors
     */
    public function validateItem(array $data, string $location, string $locale, ?int $id = null): array
    {
        $errors = [];

        if (trim((string) ($data['label'] ?? '')) === '') {
            $errors['label'] = 'cms.menus.error_label';
        }

        $url    = trim((string) ($data['url'] ?? ''));
        $pageId = (int) ($data['page_id'] ?? 0);

        // ไม่มีทั้งหน้าและ URL = หัวข้อเฉยๆ (หัวคอลัมน์ของ footer) — ใช้ได้
        if ($pageId > 0 && $url !== '') {
            $errors['url'] = 'cms.menus.error_target';
        }
        if ($url !== '' && preg_match('#^(/|https?://|tel:|mailto:)#i', $url) !== 1) {
            $errors['url'] = 'cms.menus.error_url';
        }

        $parentId = (int) ($data['parent_id'] ?? 0);

        if ($parentId > 0) {
            $parent = $this->items->find($parentId);
            $menu   = $this->menus->findByLocation($location, $locale);

            if ($parent === null || $menu === null || (int) $parent['menu_id'] !== (int) $menu['id'] || $parentId === $id
                || $parent['parent_id'] !== null) {
                // ลึกได้สองชั้น (หัวข้อ → ลิงก์) ตามที่ header/footer วาดได้
                $errors['parent_id'] = 'cms.menus.error_parent';
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function createItem(array $data): int
    {
        return $this->items->create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateItem(int $id, array $data): bool
    {
        return $this->items->update($id, $data);
    }

    /** ลบรายการพร้อมลูก — ลูกที่ไม่มีแม่จะหายจากเมนูอยู่ดี ลบให้ชัดดีกว่าค้างในตาราง */
    public function deleteItem(int $id): bool
    {
        foreach ($this->items->children($id) as $child) {
            $this->items->delete((int) $child['id']);
        }

        return $this->items->delete($id);
    }

    /**
     * ตำแหน่งและภาษาของเมนูที่รายการนี้อยู่
     *
     * @param  array<string, mixed> $item
     * @return array{0:string, 1:string}
     */
    public function scopeOf(array $item): array
    {
        $menu = $this->menus->find((int) $item['menu_id']);

        return [(string) ($menu['location'] ?? 'header'), (string) ($menu['locale'] ?? TranslationService::default())];
    }

    /** @return array<string, mixed>|null */
    public function findItem(int $id): ?array
    {
        return $this->items->find($id);
    }

    /** ย้ายรายการขึ้น/ลงหนึ่งตำแหน่งในพี่น้องเดียวกัน — เขียน sort ใหม่ทั้งชุดให้เป็น 10, 20, 30 ... */
    public function move(int $id, string $direction): bool
    {
        $item = $this->items->find($id);

        if ($item === null) {
            return false;
        }

        $siblings = $this->items->siblings((int) $item['menu_id'], $item['parent_id'] !== null ? (int) $item['parent_id'] : null);
        $ids      = array_map(static fn (array $r): int => (int) $r['id'], $siblings);
        $index    = array_search($id, $ids, true);
        $swap     = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || !isset($ids[$swap])) {
            return false;
        }

        [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];

        foreach ($ids as $position => $siblingId) {
            $this->items->update($siblingId, ['sort' => ($position + 1) * 10]);
        }

        return true;
    }

    public function ensureMenu(string $location, string $name, string $locale = ''): int
    {
        $locale = TranslationService::normalize($locale);
        $menu = $this->menus->findByLocation($location, $locale);

        return $menu !== null
            ? (int) $menu['id']
            : $this->menus->create(['location' => $location, 'locale' => $locale, 'name' => $name]);
    }

    /**
     * คัดลอกเมนูตั้งต้นจาก config/site.php ลงฐานข้อมูล (เฉพาะเมนูที่ยังว่าง) — จุดเริ่มให้แก้ใน CMS
     * ‼️ หลังจากนี้เว็บอ่านเมนูจากฐานข้อมูล (SiteService) · config เป็นแค่ค่าสำรองตอนเมนูว่าง
     *
     * @return int จำนวนรายการที่เพิ่ม
     */
    public function importDefaults(string $location, string $locale): int
    {
        if (!in_array($location, Menu::LOCATIONS, true) || $this->flat($location, $locale) !== []) {
            return 0;
        }

        $site   = (array) require dirname(__DIR__, 2) . '/config/site.php';
        $menuId = $this->ensureMenu($location, $location, $locale);
        $added  = 0;
        $sort   = 0;

        $add = function (array $node, ?int $parent) use ($menuId, &$added, &$sort): int {
            $added++;
            $sort += 10;

            return $this->items->create([
                'menu_id' => $menuId, 'parent_id' => $parent, 'label' => (string) ($node['label'] ?? $node['heading'] ?? ''),
                'description' => ($node['desc'] ?? '') !== '' ? (string) $node['desc'] : null,
                'icon' => ($node['icon'] ?? '') !== '' ? (string) $node['icon'] : null,
                'url' => isset($node['url']) ? (string) $node['url'] : null, 'page_id' => null, 'sort' => $sort,
            ]);
        };

        if ($location === 'header') {
            foreach ((array) ($site['nav'] ?? []) as $node) {
                $id = $add($node, null);
                foreach ((array) ($node['children'] ?? []) as $child) {
                    $add($child, $id);
                }
            }
        } else {
            foreach ((array) ($site['footer']['columns'] ?? []) as $column) {
                $id = $add(['label' => $column['heading'] ?? ''], null);
                foreach ((array) ($column['links'] ?? []) as $link) {
                    $add($link, $id);
                }
            }
            foreach ((array) ($site['footer']['legal'] ?? []) as $link) {
                $add($link, null);
            }
        }

        return $added;
    }

    public function nextSort(int $menuId, ?int $parentId): int
    {
        $siblings = $this->items->siblings($menuId, $parentId);

        return $siblings === [] ? 10 : max(array_map(static fn (array $r): int => (int) $r['sort'], $siblings)) + 10;
    }
}
