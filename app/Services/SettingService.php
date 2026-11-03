<?php
/**
 * ค่าตั้งของเว็บ (ชื่อแบรนด์ ข้อมูลองค์กร จำนวนบทความต่อหน้า ...)
 *
 * ‼️ อ่านทั้งตารางครั้งเดียวต่อ request แล้วเก็บไว้ — หน้าเดียวเรียกหลายสิบครั้ง ห้ามยิง query ทุกครั้ง
 * ‼️ ค่าที่ยังไม่ยืนยัน (เบอร์โทร โลโก้ ตัวเลข) เป็น null — ผู้ใช้ต้องตรวจ null แล้วซ่อน ห้ามเติมค่าสมมติ
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Core\Logger;
use Throwable;

final class SettingService
{
    public const DEFAULT_BRAND = 'OMC MLM Software';

    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * อ่านด้วย key แบบจุด: get('site.brand') · get('blog.per_page', 10)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value    = self::all()[array_shift($segments)] ?? null;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value ?? $default;
    }

    public static function brand(): string
    {
        $brand = self::get('site.brand');

        return is_string($brand) && $brand !== '' ? $brand : self::DEFAULT_BRAND;
    }

    /** ล้าง cache — ใช้หลังบันทึกค่าใหม่ และในชุดทดสอบ */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @param array<string, mixed>|null $value */
    public function save(string $key, ?array $value): void
    {
        $model    = new Setting();
        $existing = $model->findByKey($key);
        $json     = $value === null ? null : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($existing === null) {
            $model->create(['setting_key' => $key, 'value' => $json]);
        } else {
            $model->update((int) $existing['id'], ['value' => $json]);
        }

        self::flush();
    }

    /** @return array<string, mixed> */
    private static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        try {
            foreach ((new Setting())->allLive() as $row) {
                $decoded = json_decode((string) $row['value'], true);
                self::$cache[(string) $row['setting_key']] = $decoded;
            }
        } catch (Throwable $e) {
            // ค่าตั้งอ่านไม่ได้ต้องไม่ทำให้ทั้งเว็บล่ม — ใช้ค่าตั้งต้นแล้วบันทึกไว้
            Logger::exception($e);
        }

        return self::$cache;
    }
}
