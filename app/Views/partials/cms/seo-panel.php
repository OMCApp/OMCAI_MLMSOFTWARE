<?php
/**
 * แผง SEO ของหน้า/บทความ/หมวด — title + description พร้อมตัวนับ · ตัวอย่างผลค้นหาแบบ Google · canonical · robots · OG image
 *
 * ‼️ ตัวนับเป็นคำเตือน ไม่บังคับ (PLAN.md) — ~60 ตัวสำหรับ title · ~160 สำหรับ description
 * ‼️ ตัวอย่างผลค้นหาอัปเดตสดด้วย cms.js · ไม่มี JS = เห็นค่าที่บันทึกไว้
 *
 * ตัวแปรที่รับ:
 *   $seoValues   ['seo_title','meta_description','canonical_override','robots','og_image_id']
 *   $seoUrl      URL เต็มที่จะแสดงในตัวอย่าง
 *   $seoFallback ชื่อที่ใช้เมื่อ seo_title ว่าง (title ของหน้า)
 *   $seoOg       true = มีช่อง OG image (หมวดไม่มี)
 *   $cmsMediaOptions
 */

declare(strict_types=1);

$seoValues   = (array) ($seoValues ?? []);
$seoFallback = (string) ($seoFallback ?? '');
$seoTitle    = (string) ($seoValues['seo_title'] ?? '') !== '' ? (string) $seoValues['seo_title'] : $seoFallback;
?>
<fieldset class="group stack">
    <legend><?= e(__('cms.seo.title')) ?></legend>

    <div class="snippet" data-snippet-preview aria-label="<?= e(__('cms.seo.preview')) ?>">
        <span class="snippet-url"><?= e((string) ($seoUrl ?? '')) ?></span>
        <span class="snippet-title" data-snippet-title data-fallback="<?= e($seoFallback) ?>"><?= e($seoTitle) ?></span>
        <span class="snippet-desc" data-snippet-desc><?= e((string) ($seoValues['meta_description'] ?? '')) ?></span>
    </div>

    <?php
    $cmsField = ['name' => 'seo_title', 'label' => __('cms.seo.seo_title'), 'value' => $seoValues['seo_title'] ?? '',
                 'counter' => 60, 'preview' => 'title', 'hint' => __('cms.seo.seo_title_hint')];
    include __DIR__ . '/form-field.php';

    $cmsField = ['name' => 'meta_description', 'label' => __('cms.seo.meta_description'), 'type' => 'textarea',
                 'value' => $seoValues['meta_description'] ?? '', 'counter' => 160, 'preview' => 'desc'];
    include __DIR__ . '/form-field.php';
    ?>

    <div class="field-grid">
        <?php
        // หมวดไม่มี canonical / OG ของตัวเอง ($seoOg = false)
        if ($seoOg ?? true) {
            $cmsField = ['name' => 'canonical_override', 'label' => __('cms.seo.canonical'), 'value' => $seoValues['canonical_override'] ?? '',
                         'hint' => __('cms.seo.canonical_hint')];
            include __DIR__ . '/form-field.php';
        }

        $cmsField = ['name' => 'robots', 'label' => __('cms.seo.robots'), 'type' => 'select', 'value' => $seoValues['robots'] ?? 'index',
                     'options' => ['index' => __('cms.seo.robots_index'), 'noindex' => __('cms.seo.robots_noindex')]];
        include __DIR__ . '/form-field.php';

        if ($seoOg ?? true) {
            $cmsField = ['name' => 'og_image_id', 'label' => __('cms.seo.og_image'), 'type' => 'media', 'value' => (string) ($seoValues['og_image_id'] ?? '')];
            include __DIR__ . '/form-field.php';
        }
        ?>
    </div>
</fieldset>
