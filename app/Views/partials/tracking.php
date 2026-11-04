<?php
/**
 * รหัสติดตามของเว็บสาธารณะ — วาดเฉพาะ production ที่ตั้งรหัสไว้ใน CMS (App\Services\TrackingService)
 *
 * ‼️ ไม่มี inline script — tracking.js อ่าน data-* แล้วโหลดสคริปต์ของ Google เอง (CSP)
 */

declare(strict_types=1);

$trackingIds = \App\Services\TrackingService::active();

if ($trackingIds === null) {
    return;
}
?>
<script src="<?= e(asset('/assets/js/tracking.js')) ?>" defer<?php if (csp_nonce() !== ''): ?> nonce="<?= e(csp_nonce()) ?>"<?php endif; ?>
        data-ga4="<?= e($trackingIds['ga4']) ?>" data-gtm="<?= e($trackingIds['gtm']) ?>"></script>
