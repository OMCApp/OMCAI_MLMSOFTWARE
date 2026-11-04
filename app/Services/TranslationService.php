<?php
/**
 * ภาษาของเนื้อหาเว็บ — คำแปลคือแถวแยกที่ใช้ translation_group เดียวกัน
 *
 * ‼️ รายการภาษา (รหัส · prefix ของ URL · ป้าย) มาจาก config/locales.php ที่เดียว — ห้ามเขียนรหัสภาษาตายตัวใน CMS
 *    ภาษาแรกของไฟล์คือภาษาหลัก (อยู่ที่ราก URL)
 * ‼️ คอลัมน์ locale / translation_group ของ pages · posts · categories มาจาก migration ของ Phase 4
 *    ที่นี่ตรวจว่ามีคอลัมน์ก่อนเสมอ (Database::hasColumn) — ยังไม่มี = ทุกแถวเป็นภาษาหลัก ไม่มีคำแปล
 * ‼️ ชื่อตารางที่รับมาต้องอยู่ใน TABLES — ใช้ประกอบ SQL จึงห้ามรับจากผู้ใช้
 */

declare(strict_types=1);

namespace App\Services;

use Core\Database;

final class TranslationService
{
    private const TABLES = ['pages', 'posts', 'categories'];

    /** @var array<string, array{prefix:string, label:string, hreflang?:string}>|null */
    private static ?array $registry = null;

    /** @return array<string, array{prefix:string, label:string, hreflang?:string}> */
    public static function registry(): array
    {
        if (self::$registry === null) {
            $file = dirname(__DIR__, 2) . '/config/locales.php';
            $data = is_file($file) ? (array) require $file : [];
            self::$registry = [];

            foreach ($data as $code => $info) {
                if (is_string($code) && preg_match('/^[a-z]{2}(-[a-z0-9]+)?$/i', $code) === 1) {
                    self::$registry[$code] = [
                        'prefix' => rtrim((string) ($info['prefix'] ?? ''), '/'),
                        'label'  => (string) ($info['label'] ?? $code),
                    ];
                }
            }

            if (self::$registry === []) {
                self::$registry = ['th' => ['prefix' => '', 'label' => 'th']];
            }
        }

        return self::$registry;
    }

    /** @return array<int, string> รหัสภาษาทั้งหมด (ภาษาหลักก่อน) */
    public static function locales(): array
    {
        return array_keys(self::registry());
    }

    public static function default(): string
    {
        return (string) array_key_first(self::registry());
    }

    public static function label(string $locale): string
    {
        return self::registry()[$locale]['label'] ?? $locale;
    }

    public static function prefix(string $locale): string
    {
        return self::registry()[$locale]['prefix'] ?? '';
    }

    /** @return array<string, string> รหัส => ป้าย สำหรับ select */
    public static function options(): array
    {
        return array_map(static fn (array $info): string => $info['label'], self::registry());
    }

    public static function enabled(string $table): bool
    {
        return in_array($table, self::TABLES, true)
            && Database::hasColumn($table, 'locale')
            && Database::hasColumn($table, 'translation_group');
    }

    public static function normalize(?string $locale): string
    {
        return is_string($locale) && isset(self::registry()[$locale]) ? $locale : self::default();
    }

    /** path ตรงกับภาษาหรือไม่ — ภาษาที่มี prefix ต้องขึ้นต้นด้วย prefix/ · ภาษาหลักห้ามขึ้นต้นด้วย prefix ของภาษาอื่น */
    public static function pathMatchesLocale(string $path, string $locale): bool
    {
        $prefix = self::prefix($locale);

        if ($prefix !== '') {
            return str_starts_with($path, $prefix . '/');
        }

        foreach (self::registry() as $info) {
            if ($info['prefix'] !== '' && str_starts_with($path, $info['prefix'] . '/')) {
                return false;
            }
        }

        return true;
    }

    /** path ของคำแปล: ตัด prefix ภาษาเดิม แล้วเติม prefix ภาษาใหม่ (slug เดิม) */
    public static function translatePath(string $path, string $from, string $to): string
    {
        $prefix = self::prefix($from);
        $bare   = $prefix !== '' && str_starts_with($path, $prefix . '/') ? substr($path, strlen($prefix)) : $path;

        return self::prefix($to) . $bare;
    }

    /**
     * แถวทุกภาษาในกลุ่มเดียวกัน (รวมตัวเอง)
     *
     * @return array<string, array<string, mixed>> locale => แถว
     */
    public static function siblings(string $table, mixed $group): array
    {
        if (!self::enabled($table) || $group === null || $group === '') {
            return [];
        }

        $out = [];

        foreach (Database::select(
            'SELECT * FROM ' . $table . ' WHERE translation_group = :g AND deleted_at IS NULL ORDER BY id',
            ['g' => $group]
        ) as $row) {
            $out[(string) $row['locale']] ??= $row;
        }

        return $out;
    }

    /**
     * ภาษาที่ยังขาดของแต่ละแถว — ใช้แสดงธงในหน้ารายการ
     *
     * @param  array<int, array<string, mixed>> $rows
     * @return array<int, array<int, string>> id => locale ที่ขาด
     */
    public static function missing(string $table, array $rows): array
    {
        if (!self::enabled($table) || $rows === []) {
            return [];
        }

        $groups = [];

        foreach ($rows as $row) {
            if (($row['translation_group'] ?? null) !== null && $row['translation_group'] !== '') {
                $groups[(string) $row['translation_group']] = true;
            }
        }

        $present = [];

        if ($groups !== []) {
            $marks    = [];
            $bindings = [];

            foreach (array_keys($groups) as $index => $group) {
                $marks[]                = ':g' . $index;
                $bindings['g' . $index] = $group;
            }

            foreach (Database::select(
                'SELECT translation_group, locale FROM ' . $table
                . ' WHERE deleted_at IS NULL AND translation_group IN (' . implode(', ', $marks) . ')',
                $bindings
            ) as $row) {
                $present[(string) $row['translation_group']][(string) $row['locale']] = true;
            }
        }

        $out = [];

        foreach ($rows as $row) {
            $group = (string) ($row['translation_group'] ?? '');
            $have  = $group !== '' ? ($present[$group] ?? []) : [(string) ($row['locale'] ?? self::default()) => true];
            $out[(int) $row['id']] = array_values(array_diff(self::locales(), array_keys($have)));
        }

        return $out;
    }

    /** ค่า translation_group ของแถวนี้ — ยังไม่มีให้ใช้ id ของแถวต้นทาง (ผู้เรียกบันทึกกลับ) */
    public static function ensureGroup(string $table, array $row): string
    {
        $group = (string) ($row['translation_group'] ?? '');

        return $group !== '' ? $group : (string) $row['id'];
    }
}
