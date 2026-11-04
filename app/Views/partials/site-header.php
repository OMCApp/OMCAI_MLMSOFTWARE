<?php
/**
 * header ของเว็บสาธารณะ — โลโก้ · เมนูหลัก (mega menu) · เม็ดเบอร์โทร · ปุ่มขอ Demo
 *
 * ‼️ ข้อมูลทั้งหมดมาจาก view-model $site (App\Services\SiteService::layout()) — ห้ามเขียนเมนูในไฟล์นี้
 *
 * ‼️ ทำงานได้โดยไม่มี JS (design.md 5.11)
 *    ‣ เมนูกางเปิดด้วย :hover และ :focus-within — Tab เดินเข้าเมนูย่อยได้เลย
 *    ‣ ปุ่มเปิดเมนูมือถือมี hidden — app.js เป็นคนเอาออก เพราะมันทำงานได้ด้วย JS เท่านั้น
 *      มือถือที่ไม่มี JS ยังไปทุกหน้าได้จากลิงก์ใน footer
 *    ‣ เมื่อ JS ทำงาน app.js ใส่ data-enhanced แล้วคุมเมนูด้วย aria-expanded (Esc ปิด · ลูกศรเดิน)
 *
 * ‼️ หัวเมนูที่กางได้เป็น <button> ไม่ใช่ลิงก์ — ลิงก์ที่กางเมนูด้วยทำให้คีย์บอร์ดเปิดเมนูไม่ได้
 *    หน้ารวมของหัวข้อจึงต้องเป็นรายการแรกในเมนูย่อยเสมอ (config/site.php)
 *
 * ตัวแปรที่รับ: $site (จาก layout)
 */

declare(strict_types=1);

$siteHeaderBrand   = (array) ($site['brand'] ?? []);
$siteHeaderContact = (array) ($site['contact'] ?? []);
$siteHeaderNav     = (array) ($site['nav'] ?? []);
$siteHeaderCta     = ['label' => __('site.cta_demo'), 'url' => (string) ($site['cta']['url'] ?? '/demo/'), 'style' => 'primary'];
$siteHeaderLogo    = (array) ($siteHeaderBrand['logo_size'] ?? ['width' => 760, 'height' => 100]);
?>
<header class="site-header" data-site-header>
    <div class="site-header-inner container-wide">
        <a class="site-logo" href="<?= e(url((string) ($site['home'] ?? '/'))) ?>">
            <img src="<?= e(asset((string) ($siteHeaderBrand['logo'] ?? '/assets/images/brand/omc-logo-horizontal.svg'))) ?>"
                 alt="<?= e(__('site.home_link')) ?>"
                 width="<?= e((string) (int) ($siteHeaderLogo['width'] ?? 760)) ?>"
                 height="<?= e((string) (int) ($siteHeaderLogo['height'] ?? 100)) ?>">
        </a>

        <nav class="site-nav" id="site-nav" aria-label="<?= e(__('site.nav_label')) ?>" data-site-nav>
            <div class="site-nav-head">
                <img src="<?= e(asset('/assets/images/brand/omc-mark.svg')) ?>" alt="" width="300" height="100">
                <button type="button" class="btn btn-icon site-nav-close" data-site-nav-close hidden
                        aria-label="<?= e(__('site.menu_close')) ?>" title="<?= e(__('site.menu_close')) ?>">
                    <?= icon('close') ?>
                </button>
            </div>

            <ul class="site-nav-list">
                <?php foreach ($siteHeaderNav as $siteHeaderIndex => $siteHeaderEntry): ?>
                    <?php
                    $siteHeaderChildren = (array) ($siteHeaderEntry['children'] ?? []);
                    $siteHeaderMenuId   = 'site-menu-' . $siteHeaderIndex;
                    $siteHeaderItemClass = 'site-nav-item' . ($siteHeaderChildren !== [] ? ' has-menu' : '')
                        . (!empty($siteHeaderEntry['active']) ? ' is-active' : '');
                    ?>
                    <li class="<?= e($siteHeaderItemClass) ?>"<?php if ($siteHeaderChildren !== []): ?> data-menu<?php endif; ?>>
                        <?php if ($siteHeaderChildren === []): ?>
                            <a class="site-nav-link" href="<?= e(url((string) ($siteHeaderEntry['url'] ?? '/'))) ?>"
                               <?php if (!empty($siteHeaderEntry['current'])): ?>aria-current="page"<?php endif; ?>>
                                <?= e((string) ($siteHeaderEntry['label'] ?? '')) ?>
                            </a>
                        <?php else: ?>
                            <button type="button" class="site-nav-link" aria-expanded="false"
                                    aria-controls="<?= e($siteHeaderMenuId) ?>" data-menu-toggle>
                                <span><?= e((string) ($siteHeaderEntry['label'] ?? '')) ?></span>
                                <?= icon('chevron-down', 'site-nav-caret') ?>
                            </button>
                            <ul class="site-menu" id="<?= e($siteHeaderMenuId) ?>" data-menu-panel>
                                <?php foreach ($siteHeaderChildren as $siteHeaderChild): ?>
                                    <li>
                                        <a class="site-menu-link" href="<?= e(url((string) ($siteHeaderChild['url'] ?? '/'))) ?>"
                                           <?php if (!empty($siteHeaderChild['current'])): ?>aria-current="page"<?php endif; ?>>
                                            <span class="site-menu-icon" aria-hidden="true"><?= icon((string) ($siteHeaderChild['icon'] ?? 'arrow-right')) ?></span>
                                            <span class="site-menu-text">
                                                <span class="site-menu-label"><?= e((string) ($siteHeaderChild['label'] ?? '')) ?></span>
                                                <?php if ((string) ($siteHeaderChild['desc'] ?? '') !== ''): ?>
                                                    <span class="site-menu-desc"><?= e((string) $siteHeaderChild['desc']) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php /* ของที่อยู่ใน drawer มือถือเท่านั้น — จอใหญ่อยู่บนแถบ header แล้ว */ ?>
            <div class="site-nav-extra">
                <?php if (($siteHeaderContact['phone'] ?? '') !== ''): ?>
                    <a class="site-phone" href="<?= e(url('tel:' . ($siteHeaderContact['phone_e164'] ?? $siteHeaderContact['phone']))) ?>">
                        <?= icon('phone') ?>
                        <span><span class="visually-hidden"><?= e(__('site.call_us')) ?> </span><?= e($siteHeaderContact['phone']) ?></span>
                    </a>
                <?php endif; ?>
                <?php $siteButton = $siteHeaderCta; include __DIR__ . '/site-button.php'; ?>
                <?php $langSwitchItems = (array) ($site['languages'] ?? []); $langSwitchClass = 'is-drawer'; include __DIR__ . '/lang-switch.php'; ?>
            </div>
        </nav>

        <div class="site-actions">
            <?php $langSwitchItems = (array) ($site['languages'] ?? []); $langSwitchClass = 'is-header'; include __DIR__ . '/lang-switch.php'; ?>
            <?php if (($siteHeaderContact['phone'] ?? '') !== ''): ?>
                <a class="site-phone" href="<?= e(url('tel:' . ($siteHeaderContact['phone_e164'] ?? $siteHeaderContact['phone']))) ?>">
                    <?= icon('phone') ?>
                    <span><span class="visually-hidden"><?= e(__('site.call_us')) ?> </span><?= e($siteHeaderContact['phone']) ?></span>
                </a>
            <?php endif; ?>
            <div class="site-cta">
                <?php $siteButton = $siteHeaderCta; include __DIR__ . '/site-button.php'; ?>
            </div>
            <button type="button" class="btn btn-icon site-nav-toggle" data-site-nav-open hidden
                    aria-controls="site-nav" aria-expanded="false"
                    aria-label="<?= e(__('site.menu_open')) ?>" title="<?= e(__('site.menu_open')) ?>">
                <?= icon('menu') ?>
            </button>
        </div>
    </div>
    <div class="site-backdrop" data-site-nav-backdrop></div>
</header>
