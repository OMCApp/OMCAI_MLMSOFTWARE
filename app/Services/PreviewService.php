<?php
/**
 * สิทธิ์ดูหน้า/บทความที่ยังไม่เผยแพร่ใน request นี้
 *
 * ‼️ ตัดสินที่ App\Middlewares\PreviewMiddleware เท่านั้น (กฎเหล็กข้อ 4)
 *    คลาสนี้แค่จำผลการตัดสินไว้ให้ SiteResolver อ่าน — ห้ามเรียก grant() จากที่อื่น
 */

declare(strict_types=1);

namespace App\Services;

final class PreviewService
{
    private static bool $granted = false;

    public static function grant(): void
    {
        self::$granted = true;
    }

    public static function granted(): bool
    {
        return self::$granted;
    }

    /** ใช้ในชุดทดสอบเท่านั้น */
    public static function reset(): void
    {
        self::$granted = false;
    }
}
