<?php
/**
 * ทุก URL ของเว็บสาธารณะที่ไม่ใช่ route ระบบ — ผูกเป็น fallback ของ Router
 *
 * ลำดับการจับ URL อยู่ใน App\Services\SiteResolver · การเตรียมหน้าอยู่ใน App\Services\SiteViewService
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NotFoundService;
use App\Services\PreviewService;
use App\Services\SiteResolver;
use App\Services\SiteViewService;
use Core\Path;

final class SiteController extends PublicController
{
    public function resolve(): void
    {
        $path       = Path::requested();
        $query      = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $resolution = (new SiteResolver())->resolve($path, $query, PreviewService::granted());

        if ($resolution['kind'] === 'redirect') {
            $this->redirectTo((string) $resolution['location'], (int) $resolution['code']);
        }

        if (($resolution['system'] ?? false) === true) {
            (new ErrorController())->show(404);

            return;
        }

        /* ‼️ ชั่วคราวเฉพาะ staging: หน้าแรกในฐานข้อมูลยังเป็น draft (Phase 4 กำลังเขียนเนื้อหา)
              ให้ / แสดงหน้าแรกตัวอย่างของ design system แทน 404 — ผู้ใช้ขอเห็นเว็บที่ URL หลักของ staging
              production ไม่เข้าเงื่อนไขนี้เด็ดขาด · หมดผลเองทันทีที่หน้า / ถูก publish */
        if ($resolution['kind'] === 'not_found' && $path === '/' && config('env', 'production') !== 'production') {
            (new PreviewController())->home();

            return;
        }

        if ($resolution['kind'] === 'not_found' && ($resolution['log'] ?? false) === true) {
            (new NotFoundService())->record(
                $path,
                isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : null,
                isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : null
            );
        }

        $this->renderScreen((new SiteViewService())->build($resolution));
    }
}
