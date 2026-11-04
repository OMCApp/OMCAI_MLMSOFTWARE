<?php
/**
 * footer ของเว็บสาธารณะ — โลโก้ · เกี่ยวกับบริษัท · คอลัมน์ลิงก์ · ติดต่อ · นโยบาย · ลิขสิทธิ์
 *
 * ‼️ ทุกหน้าต้องมี footer จาก layout กลาง (กฎเหล็กข้อ 20) — layouts/site.php include ไฟล์นี้
 * ‼️ ข้อมูลมาจาก view-model $site เท่านั้น
 * ‼️ พื้น ink (น้ำเงินหมึก) — แบบอ้างอิงไม่มี footer · ใช้สีเข้มเป็น accent ปิดท้ายตาม brief
 *    ("สีเข้มเป็น accent ได้แต่ไม่มืดทั้งหน้า") และใช้โลโก้รุ่นพื้นเข้ม
 *
 * ตัวแปรที่รับ: $site (จาก layout)
 */

declare(strict_types=1);

$siteFooterBrand   = (array) ($site['brand'] ?? []);
$siteFooterContact = (array) ($site['contact'] ?? []);
$siteFooterColumns = (array) ($site['footer']['columns'] ?? []);
$siteFooterLegal   = (array) ($site['footer']['legal'] ?? []);
$siteFooterLogo    = (array) ($siteFooterBrand['logo_dark_size'] ?? ['width' => 300, 'height' => 122]);
?>
<footer class="site-footer-public">
    <div class="container site-footer-grid">
        <div class="site-footer-brand">
            <a class="site-footer-logo" href="<?= e(url((string) ($site['home'] ?? '/'))) ?>">
                <img src="<?= e(asset((string) ($siteFooterBrand['logo_dark'] ?? '/assets/images/brand/omc-logo-dark.svg'))) ?>"
                     alt="<?= e(__('site.home_link')) ?>" loading="lazy"
                     width="<?= e((string) (int) ($siteFooterLogo['width'] ?? 300)) ?>"
                     height="<?= e((string) (int) ($siteFooterLogo['height'] ?? 122)) ?>">
            </a>
            <?php if ((string) ($siteFooterBrand['about'] ?? '') !== ''): ?>
                <p class="site-footer-about"><?= e((string) $siteFooterBrand['about']) ?></p>
            <?php endif; ?>
            <?php $langSwitchItems = (array) ($site['languages'] ?? []); $langSwitchClass = 'is-footer'; include __DIR__ . '/lang-switch.php'; ?>
        </div>

        <?php foreach ($siteFooterColumns as $siteFooterIndex => $siteFooterColumn): ?>
            <nav class="site-footer-col" aria-labelledby="footer-col-<?= e((string) $siteFooterIndex) ?>">
                <h2 class="site-footer-heading" id="footer-col-<?= e((string) $siteFooterIndex) ?>">
                    <?= e((string) ($siteFooterColumn['heading'] ?? '')) ?>
                </h2>
                <ul class="site-footer-links">
                    <?php foreach ((array) ($siteFooterColumn['links'] ?? []) as $siteFooterLink): ?>
                        <li><a href="<?= e(url((string) ($siteFooterLink['url'] ?? '/'))) ?>"><?= e((string) ($siteFooterLink['label'] ?? '')) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endforeach; ?>

        <div class="site-footer-col">
            <h2 class="site-footer-heading"><?= e(__('site.footer_contact')) ?></h2>
            <ul class="site-footer-contact">
                <?php if (($siteFooterContact['phone'] ?? '') !== ''): ?>
                    <li><?= icon('phone') ?>
                        <a href="<?= e(url('tel:' . ($siteFooterContact['phone_e164'] ?? $siteFooterContact['phone']))) ?>">
                            <span class="visually-hidden"><?= e(__('site.phone')) ?> </span><?= e($siteFooterContact['phone']) ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (($siteFooterContact['email'] ?? '') !== ''): ?>
                    <li><?= icon('mail') ?>
                        <a href="<?= e(url('mailto:' . $siteFooterContact['email'])) ?>">
                            <span class="visually-hidden"><?= e(__('site.email')) ?> </span><?= e($siteFooterContact['email']) ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (($siteFooterContact['line_url'] ?? '') !== ''): ?>
                    <li><?= icon('chat') ?>
                        <a href="<?= e(url($siteFooterContact['line_url'])) ?>" rel="noopener">
                            <?= e(__('site.line')) ?>: <?= e((string) ($siteFooterContact['line_id'] ?? '')) ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (($siteFooterContact['facebook'] ?? '') !== ''): ?>
                    <li><?= icon('facebook') ?>
                        <a href="<?= e(url($siteFooterContact['facebook'])) ?>" rel="noopener"><?= e(__('site.facebook')) ?></a>
                    </li>
                <?php endif; ?>
                <?php if (($siteFooterContact['address'] ?? '') !== ''): ?>
                    <li><?= icon('map-pin') ?>
                        <address><span class="visually-hidden"><?= e(__('site.address')) ?> </span><?= e($siteFooterContact['address']) ?></address>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="site-footer-bottom">
        <div class="container site-footer-bottom-inner">
            <p>&copy; <?= e(date('Y')) ?> <?= e((string) ($siteFooterBrand['legal_name'] ?? '')) ?> · <?= e(__('site.copyright')) ?></p>
            <?php if ($siteFooterLegal !== []): ?>
                <nav aria-label="<?= e(__('site.legal_nav')) ?>">
                    <ul class="site-footer-legal">
                        <?php foreach ($siteFooterLegal as $siteFooterLink): ?>
                            <li><a href="<?= e(url((string) ($siteFooterLink['url'] ?? '/'))) ?>"><?= e((string) ($siteFooterLink['label'] ?? '')) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</footer>
