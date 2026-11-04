<?php
/**
 * เมนูตามตำแหน่ง (header · footer) และภาษาของเนื้อหา
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Menu extends Model
{
    protected string $table = 'menus';

    protected array $fillable = ['location', 'locale', 'name'];

    public const LOCATIONS = ['header', 'footer'];

    /** @return array<string, mixed>|null */
    public function findByLocation(string $location, string $locale = 'th'): ?array
    {
        // locale มาจาก migration 012 — ฐานข้อมูลที่ยังไม่ migrate ใช้เมนูเดียวต่อตำแหน่ง
        if (!Database::hasColumn('menus', 'locale')) {
            return Database::selectOne(
                'SELECT * FROM menus WHERE location = :location AND deleted_at IS NULL LIMIT 1',
                ['location' => $location]
            );
        }

        return Database::selectOne(
            'SELECT * FROM menus WHERE location = :location AND locale = :locale AND deleted_at IS NULL LIMIT 1',
            ['location' => $location, 'locale' => $locale]
        );
    }
}
