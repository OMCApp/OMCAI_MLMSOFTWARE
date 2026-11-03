<?php
/**
 * เมนูของเว็บ — คืนเป็นต้นไม้ให้ layout วาด (Phase 1 วาด · Phase 3 แก้ใน CMS)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;

final class MenuService
{
    public function __construct(
        private readonly Menu $menus = new Menu(),
        private readonly MenuItem $items = new MenuItem(),
    ) {
    }

    /**
     * ‼️ รายการที่ชี้ไปหน้าที่ยังไม่เผยแพร่ถูกซ่อน — ไม่งั้นเมนูพาคนไปเจอ 404
     *
     * @return array<int, array{label:string, url:string, children:array<int, array<string,mixed>>}>
     */
    public function tree(string $location): array
    {
        if (!in_array($location, Menu::LOCATIONS, true)) {
            return [];
        }

        $menu = $this->menus->findByLocation($location);

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
                'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'children'  => [],
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
     * @return array<int, array{label:string, url:string, children:array<int, array<string,mixed>>}>
     */
    private function branch(int $parent, array $nodes, array $childrenOf, int $depth): array
    {
        $out = [];

        foreach ($childrenOf[$parent] ?? [] as $id) {
            $out[] = [
                'label'    => $nodes[$id]['label'],
                'url'      => $nodes[$id]['url'],
                'children' => $depth < 3 ? $this->branch($id, $nodes, $childrenOf, $depth + 1) : [],
            ];
        }

        return $out;
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

    public function deleteItem(int $id): bool
    {
        return $this->items->delete($id);
    }

    public function ensureMenu(string $location, string $name): int
    {
        $menu = $this->menus->findByLocation($location);

        return $menu !== null ? (int) $menu['id'] : $this->menus->create(['location' => $location, 'name' => $name]);
    }
}
