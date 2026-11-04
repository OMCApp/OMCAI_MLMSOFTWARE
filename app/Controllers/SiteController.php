<?php
/**
 * ทุก URL ของเว็บสาธารณะที่ไม่ใช่ route ระบบ — ผูกเป็น fallback ของ Router
 *
 * ลำดับการจับ URL อยู่ใน App\Services\SiteResolver · การเตรียมหน้าอยู่ใน App\Services\SiteViewService
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LeadTrackingService;
use App\Services\LocaleService;
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

        // ภาษาของหน้ามาจาก prefix ของ URL เท่านั้น (ไม่ดู Accept-Language / IP · ไม่ redirect ตามภาษา)
        LocaleService::use(LocaleService::localeOf($path));

        // UTM ของการเข้าชมครั้งแรก — เก็บใน cookie 30 วัน ให้ lead ที่ส่งจากหน้าอื่นทีหลังยังรู้แหล่งที่มา
        LeadTrackingService::rememberFirstTouch($_GET);

        $resolution = (new SiteResolver())->resolve($path, $query, PreviewService::granted());

        if ($resolution['kind'] === 'redirect') {
            $this->redirectTo((string) $resolution['location'], (int) $resolution['code']);
        }

        if (($resolution['system'] ?? false) === true) {
            (new ErrorController())->show(404);

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
