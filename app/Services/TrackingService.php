<?php
/**
 * รหัสติดตาม (GA4 / Google Tag Manager) ของเว็บสาธารณะ
 *
 * ‼️ ส่งออกเฉพาะ APP_ENV=production — staging/เครื่อง dev ห้ามส่งข้อมูลปนเข้า Analytics ของจริง
 * ‼️ ไม่มี inline script (CSP ห้าม unsafe-inline) — public/assets/js/tracking.js อ่านรหัสจาก data-* แล้วโหลดสคริปต์ของ Google
 *    CSP ของหน้าที่มีรหัสติดตามขยายเฉพาะโดเมนของ Google (Core\Security::allowTracking)
 * ‼️ รหัสผ่านรูปแบบ G-XXXX / GTM-XXXX มาแล้วตอนบันทึก (SettingService::fromInput) — ตรวจซ้ำที่นี่อีกชั้น
 */

declare(strict_types=1);

namespace App\Services;

final class TrackingService
{
    /** @return array{ga4:string, gtm:string}|null null = ไม่ส่งออก */
    public static function active(?string $env = null): ?array
    {
        if (($env ?? (string) config('env', 'production')) !== 'production') {
            return null;
        }

        $tracking = SettingService::group('tracking');
        $ga4      = (string) ($tracking['ga4_id'] ?? '');
        $gtm      = (string) ($tracking['gtm_id'] ?? '');
        $ga4      = preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4) === 1 ? $ga4 : '';
        $gtm      = preg_match('/^GTM-[A-Z0-9]{4,20}$/', $gtm) === 1 ? $gtm : '';

        return $ga4 === '' && $gtm === '' ? null : ['ga4' => $ga4, 'gtm' => $gtm];
    }
}
