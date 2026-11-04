<?php
/**
 * รูปในเนื้อหาบทความ (HTML) → WebP + srcset จากคลังสื่อ ตอนแสดงผล
 *
 * ‼️ ทำตอนแสดง ไม่แก้ HTML ที่เก็บในฐานข้อมูล — เนื้อหาเดิมจาก WordPress ยังอ้างไฟล์ต้นฉบับ (PNG/JPEG)
 *    และ URL ของไฟล์ต้นฉบับยังเปิดได้ (backlink / Google Images เดิมไม่เสีย)
 * ‼️ รับ HTML ที่ผ่าน HtmlSanitizer แล้วเท่านั้น — แก้แค่ src/srcset/sizes ของ <img> ที่มีแถวในคลังสื่อ
 * ‼️ query เดียวต่อบทความ (Media::findManyByPath) ไม่ query ทีละรูป
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Core\Path;

final class ContentImages
{
    /** ความกว้างสูงสุดของรูปในเนื้อหา = .post (--layout-form-width 760px) */
    public const SIZES = '(min-width: 800px) 760px, 100vw';

    public function __construct(private readonly Media $media = new Media())
    {
    }

    /**
     * @param bool $firstIsLcp บทความที่ไม่มีรูปเด่น — รูปแรกในเนื้อหาคือ LCP: ห้าม lazy + fetchpriority="high"
     */
    public function apply(string $html, bool $firstIsLcp = false): string
    {
        if ($html === '' || !str_contains($html, '<img')) {
            return $html;
        }

        if ($firstIsLcp) {
            $html = (string) preg_replace_callback('#<img\b[^>]*>#i', static fn (array $m): string => str_replace(
                [' loading="lazy"', '<img '],
                ['', '<img fetchpriority="high" '],
                $m[0]
            ), $html, 1);
        }

        preg_match_all('#<img\b[^>]*\ssrc="(/[^"/][^"]*)"#i', $html, $found);
        $rows = $this->media->findManyByPath(array_map(
            static fn (string $src): string => rawurldecode(html_entity_decode($src, ENT_QUOTES, 'UTF-8')),
            $found[1]
        ));

        if ($rows === []) {
            return $html;
        }

        return (string) preg_replace_callback('#<img\b[^>]*>#i', static function (array $m) use ($rows): string {
            $tag = $m[0];

            if (preg_match('#\ssrc="([^"]*)"#i', $tag, $src) !== 1 || str_contains($tag, ' srcset=')) {
                return $tag;
            }

            $row = $rows[rawurldecode(html_entity_decode($src[1], ENT_QUOTES, 'UTF-8'))] ?? null;

            if ($row === null) {
                return $tag;
            }

            $image = MediaService::imageData($row);
            $set   = [];

            foreach ($image['srcset'] as $width => $url) {
                $set[] = Path::encode($url) . ' ' . $width . 'w';
            }

            $attrs = ' src="' . e(Path::encode($image['src'])) . '"';
            if ($set !== []) {
                $attrs .= ' srcset="' . e(implode(', ', $set)) . '" sizes="' . self::SIZES . '"';
            }

            return str_replace($src[0], $attrs, $tag);
        }, $html);
    }
}
