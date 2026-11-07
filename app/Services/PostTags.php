<?php
/**
 * แท็กของบทความ — เก็บที่ posts.tags เป็นข้อความคั่นจุลภาค ("MLM, แผนรายได้") · แก้ใน CMS → บทความ
 * แสดงเป็นชิปบนการ์ด (ลิงก์ไปค้นหา /blog/?q=) และใช้ในการค้นหา
 */

declare(strict_types=1);

namespace App\Services;

final class PostTags
{
    public const MAX_TAGS   = 8;
    public const MAX_LENGTH = 40;

    /** จากช่องในหลังบ้าน "a, b, #c" → "a, b, c" — ตัด # · ไม่ซ้ำ (ไม่สนตัวพิมพ์) · ไม่เกิน 8 แท็ก · แท็กละไม่เกิน 40 ตัว */
    public static function normalize(string $raw): ?string
    {
        $tags = [];

        foreach (preg_split('/[,\n]/u', $raw) ?: [] as $tag) {
            $tag = mb_substr(trim(ltrim(trim($tag), '#')), 0, self::MAX_LENGTH);
            if ($tag !== '' && !in_array(mb_strtolower($tag), array_map('mb_strtolower', $tags), true)) {
                $tags[] = $tag;
            }
        }

        return $tags === [] ? null : implode(', ', array_slice($tags, 0, self::MAX_TAGS));
    }

    /** @return list<string> */
    public static function toList(?string $tags): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $tags)), static fn (string $t): bool => $t !== ''));
    }
}
