<?php
/**
 * IndexNow — แจ้ง Bing (→ Copilot) · Yandex · Seznam · Naver ทันทีที่เผยแพร่/แก้หน้าหรือบทความ
 *
 * ‼️ ทำงานเฉพาะ production + ตั้ง INDEXNOW_KEY แล้ว (env อื่นไม่ส่งอะไรออกไปเลย)
 * ‼️ ส่งแบบ best-effort timeout สั้น — ล้มเหลวแค่บันทึก log ห้ามทำให้การบันทึกใน CMS ช้าหรือพัง
 * ‼️ ส่งเฉพาะ URL ที่เผยแพร่และ index ได้ (+ URL เดิมเมื่อย้าย path เพื่อให้รู้ว่ามี 301)
 * Google ไม่ใช้ IndexNow — Google ใช้ sitemap + Search Console ตามปกติ
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use Core\Logger;
use Core\Path;

final class IndexNowService
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public static function key(): string
    {
        $key = (string) config('indexnow.key', '');

        // ข้อกำหนดของ IndexNow: 8–128 ตัว a-z A-Z 0-9 -
        return preg_match('/^[A-Za-z0-9-]{8,128}$/', $key) === 1 ? $key : '';
    }

    public static function enabled(): bool
    {
        return config('env') === 'production' && self::key() !== '';
    }

    /** หลังบันทึกบทความ — URL ปัจจุบันถ้าเผยแพร่และ index ได้ · บวก URL เดิมถ้าเพิ่งย้าย */
    public function post(int $id, ?string $oldPath = null): void
    {
        $post = (new Post())->find($id);
        $urls = [];

        if ($post !== null && PostService::isLive($post) && ($post['robots'] ?? 'index') === 'index') {
            $urls[] = PostService::permalink($post);
        }

        $this->submit(array_merge($urls, $oldPath !== null ? [$oldPath] : []));
    }

    /** หลังบันทึกหน้า */
    public function page(int $id, ?string $oldPath = null): void
    {
        $page = (new Page())->find($id);
        $urls = [];

        if ($page !== null && PageService::isLive($page) && ($page['robots'] ?? 'index') === 'index') {
            $urls[] = (string) $page['path'];
        }

        $this->submit(array_merge($urls, $oldPath !== null ? [$oldPath] : []));
    }

    /** @param list<string> $paths path ภายในแบบ decode */
    public function submit(array $paths): bool
    {
        if (!self::enabled() || $paths === []) {
            return false;
        }

        $base = SeoService::base();
        $body = json_encode([
            'host'        => (string) parse_url($base, PHP_URL_HOST),
            'key'         => self::key(),
            'keyLocation' => $base . '/' . self::key() . '.txt',
            'urlList'     => array_values(array_unique(array_map(static fn (string $p): string => $base . Path::encode($p), $paths))),
        ], JSON_UNESCAPED_SLASHES);

        $context = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json; charset=utf-8\r\n",
            'content'       => $body,
            'timeout'       => 3,
            'ignore_errors' => true,
        ]]);

        $response = @file_get_contents(self::ENDPOINT, false, $context);
        $status   = isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', (string) $http_response_header[0], $m) === 1 ? (int) $m[1] : 0;

        // 200/202 = รับแล้ว · อื่นๆ บันทึกไว้ดู (ไม่รบกวนผู้ใช้)
        if ($response === false || !in_array($status, [200, 202], true)) {
            Logger::warning('indexnow_failed', ['status' => $status, 'urls' => count($paths)]);

            return false;
        }

        Logger::info('indexnow_ok', ['status' => $status, 'urls' => count($paths)]);

        return true;
    }
}
