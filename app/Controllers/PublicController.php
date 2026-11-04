<?php
/**
 * ฐานของ Controller เว็บสาธารณะ — ส่งหน้าเข้า layout ของเว็บพร้อม SEO head และ JSON-LD
 *
 * สัญญาของ layout เว็บสาธารณะ (layouts/site-fallback.php ตอนนี้ · layouts/site.php ของ Phase 1):
 *   $seoHead      string  HTML สำหรับใน <head> — title, meta, canonical, OG/Twitter, feed (escape แล้วทุกค่า)
 *   $jsonLd       string  <script type="application/ld+json">…</script> (encode แบบปลอดภัยแล้ว)
 *   $content      string  HTML ของหน้า
 *   $breadcrumbs  array   [['label' => string, 'url' => ?string], ...] — path ภายใน · ตัวสุดท้ายคือหน้านี้ (url = null)
 *                         ว่างในหน้าแรกและหน้า 404
 *   $title        string  ชื่อหน้า (H1) — layout ส่งต่อให้ partials/page-head.php เป็นคนวาด
 *                         หน้าที่มีส่วนเปิด (hero) วาด H1 เองแล้ว page-head ข้ามให้อัตโนมัติ
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SettingService;
use Core\Controller;
use Core\Path;

abstract class PublicController extends Controller
{
    /**
     * ‼️ layout ของเว็บสาธารณะ — เปลี่ยนที่บรรทัดนี้บรรทัดเดียว
     *    Phase 1 ส่ง layouts/site.php แล้วให้เปลี่ยนเป็น 'site'
     */
    public const LAYOUT = 'site';

    /**
     * @param array{status:int, view:string, title:string, data:array<string,mixed>, seo:array<string,mixed>,
     *              graph:array<string,mixed>, breadcrumbs:array<int,array<string,mixed>>, private:bool} $screen
     */
    protected function renderScreen(array $screen): void
    {
        http_response_code($screen['status']);
        header('Content-Type: text/html; charset=utf-8');

        // รหัสติดตาม (production + ตั้งไว้ใน CMS) ต้องให้ CSP ยอมสคริปต์ของ Google — ไม่มีรหัส = CSP เดิมเข้มเท่าเดิม
        if (!$screen['private'] && \App\Services\TrackingService::active() !== null) {
            \Core\Security::allowTracking();
        }

        if ($screen['private']) {
            // ดูตัวอย่าง draft — ห้ามเก็บ cache และห้าม index ไม่ว่า environment ไหน (ต้องล็อกอินถึงเห็น)
            header('Cache-Control: private, no-store');
            header('X-Robots-Tag: noindex, nofollow', false);
        }

        $this->view($screen['view'], $screen['data'] + [
            'title'       => $screen['title'],
            // layouts/site.php include partial เอง — ส่งเป็นข้อมูล ไม่ใช่ HTML ที่ต้องพิมพ์ดิบ
            'seoHeadMeta' => $screen['seo'],
            'jsonLdGraph' => $screen['graph'],
            'breadcrumbs' => $screen['breadcrumbs'],
            'siteBrand'   => SettingService::brand(),
        ], self::LAYOUT);
    }

    /**
     * redirect ของเว็บสาธารณะ — ปลายทางเป็น path ภายในแบบ decode หรือ URL เต็ม
     * ‼️ path ภาษาไทยต้อง percent-encode ก่อนใส่ Location (header เป็น ASCII เท่านั้น)
     */
    protected function redirectTo(string $location, int $code): never
    {
        if (preg_match('#^https?://#i', $location) !== 1) {
            [$path, $query] = array_pad(explode('?', $location, 2), 2, null);
            $location = Path::url(Path::encode((string) $path)) . ($query !== null ? '?' . $query : '');
        }

        header('Location: ' . $location, true, in_array($code, [301, 302, 307, 308], true) ? $code : 301);
        exit;
    }

    protected function plain(string $body, string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        echo $body;
    }
}
