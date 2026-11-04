<?php
/**
 * รายการในเมนู — ชี้หน้า (page_id) หรือ URL
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class MenuItem extends Model
{
    protected string $table = 'menu_items';

    protected array $fillable = ['menu_id', 'parent_id', 'label', 'description', 'icon', 'url', 'page_id', 'sort'];

    /** @return array<int, array<string, mixed>> */
    public function forMenu(int $menuId): array
    {
        $extra = Database::hasColumn('menu_items', 'description') ? 'mi.description, mi.icon,' : '';

        return Database::select(
            'SELECT mi.id, mi.parent_id, mi.label, ' . $extra . ' mi.url, mi.page_id, mi.sort,
                    p.path AS page_path, p.status AS page_status, p.title AS page_title
             FROM menu_items mi
             LEFT JOIN pages p ON p.id = mi.page_id AND p.deleted_at IS NULL
             WHERE mi.menu_id = :menu_id AND mi.deleted_at IS NULL
             ORDER BY mi.sort, mi.id',
            ['menu_id' => $menuId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function siblings(int $menuId, ?int $parentId): array
    {
        if ($parentId === null) {
            return Database::select(
                'SELECT id, sort FROM menu_items WHERE menu_id = :m AND parent_id IS NULL AND deleted_at IS NULL ORDER BY sort, id',
                ['m' => $menuId]
            );
        }

        return Database::select(
            'SELECT id, sort FROM menu_items WHERE menu_id = :m AND parent_id = :p AND deleted_at IS NULL ORDER BY sort, id',
            ['m' => $menuId, 'p' => $parentId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function children(int $id): array
    {
        return Database::select('SELECT id FROM menu_items WHERE parent_id = :id AND deleted_at IS NULL', ['id' => $id]);
    }
}
