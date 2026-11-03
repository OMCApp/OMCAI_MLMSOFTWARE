<?php
/**
 * รายการในเมนู — ชี้ไปที่หน้า (page_id) หรือ URL ตรงๆ
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class MenuItem extends Model
{
    protected string $table = 'menu_items';

    protected array $fillable = ['menu_id', 'parent_id', 'label', 'url', 'page_id', 'sort'];

    /**
     * รายการทั้งเมนูพร้อม path ของหน้าที่ชี้ไป — JOIN ทีเดียว ไม่ query ทีละรายการ
     * หน้าที่ยังไม่เผยแพร่ไม่ถูกส่งออก (page_status) ให้ Service ตัดสินว่าจะซ่อนไหม
     *
     * @return array<int, array<string, mixed>>
     */
    public function forMenu(int $menuId): array
    {
        return Database::select(
            'SELECT mi.id, mi.parent_id, mi.label, mi.url, mi.page_id, mi.sort,
                    p.path AS page_path, p.status AS page_status
             FROM menu_items mi
             LEFT JOIN pages p ON p.id = mi.page_id AND p.deleted_at IS NULL
             WHERE mi.menu_id = :menu_id AND mi.deleted_at IS NULL
             ORDER BY mi.sort, mi.id',
            ['menu_id' => $menuId]
        );
    }
}
