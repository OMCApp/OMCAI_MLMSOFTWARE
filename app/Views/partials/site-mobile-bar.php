<?php
/**
 * แถบติดต่อติดล่างจอ — เฉพาะจอมือถือ (CSS ซ่อนบนจอกว้าง) · โทร + ทดลอง Demo
 *
 * ‼️ ข้อมูลจาก view-model $site เท่านั้น (เบอร์จริงจากตั้งค่าเว็บ / config/site.php) — ไม่มีเบอร์ = ปุ่มติดต่อแทน
 * ‼️ ต้องเว้นที่ล่างหน้าให้แถบนี้ (.site-body padding) ไม่งั้นบัง footer
 *
 * ตัวแปรที่รับ: $site (จาก layout)
 */

declare(strict_types=1);

$siteMobileContact = (array) ($site['contact'] ?? []);
$siteMobilePhone   = (string) ($siteMobileContact['phone_e164'] ?? ($siteMobileContact['phone'] ?? ''));
?>
<nav class="site-mobile-bar" aria-label="<?= e(__('site.footer_contact')) ?>">
    <?php if ($siteMobilePhone !== ''): ?>
        <a class="btn btn-pill btn-secondary" href="<?= e(url('tel:' . $siteMobilePhone)) ?>">
            <?= icon('phone') ?><span><?= e(__('site.call_us')) ?></span>
        </a>
    <?php else: ?>
        <a class="btn btn-pill btn-secondary" href="<?= e(url((string) ($site['contact_url'] ?? '/contact/'))) ?>">
            <?= icon('chat') ?><span><?= e(__('site.footer_contact')) ?></span>
        </a>
    <?php endif; ?>
    <?php $siteButton = ['label' => __('site.cta_demo'), 'url' => (string) ($site['cta']['url'] ?? '/demo/'), 'style' => 'primary', 'arrow' => false]; include __DIR__ . '/site-button.php'; ?>
</nav>
