<?php
/**
 * แผงคำแปล — ภาษาอื่นของเนื้อหาเดียวกัน (translation_group) + ปุ่มสร้างคำแปล
 *
 * ‼️ แสดงเฉพาะเมื่อตารางมีคอลัมน์ locale/translation_group แล้ว (migration ของ Phase 4) — Controller ส่ง $cmsTranslations = null ถ้ายังไม่มี
 * ‼️ สร้างคำแปล = คัดลอกเป็น draft ในภาษาใหม่ (POST + CSRF) แล้วพาไปหน้าแก้ไขของแถวใหม่
 *
 * ตัวแปรที่รับ:
 *   $cmsTranslations  ['current' => locale, 'rows' => [locale => ['id','title','status']], 'base' => '/admin/pages']
 */

declare(strict_types=1);

if (!is_array($cmsTranslations ?? null)) {
    return;
}

$cmsTrBase    = (string) $cmsTranslations['base'];
$cmsTrRows    = (array) $cmsTranslations['rows'];
$cmsTrCurrent = (string) $cmsTranslations['current'];
$cmsTrSource  = (int) $cmsTranslations['id'];
?>
<section class="card stack" aria-labelledby="cms-tr-title">
    <h2 id="cms-tr-title"><?= e(__('cms.translation.title')) ?></h2>
    <ul class="cms-list">
        <?php foreach (\App\Services\TranslationService::locales() as $cmsTrLocale): ?>
            <li class="row">
                <span class="badge badge-type"><?= e(\App\Services\TranslationService::label($cmsTrLocale)) ?></span>
                <?php if ($cmsTrLocale === $cmsTrCurrent): ?>
                    <span class="muted"><?= e(__('cms.translation.this')) ?></span>
                <?php elseif (isset($cmsTrRows[$cmsTrLocale])): ?>
                    <a href="<?= e(url($cmsTrBase . '/' . (int) $cmsTrRows[$cmsTrLocale]['id'])) ?>"><?= e((string) $cmsTrRows[$cmsTrLocale]['title']) ?></a>
                    <span class="badge"><?= e(__('cms.status.' . (string) ($cmsTrRows[$cmsTrLocale]['status'] ?? 'draft'))) ?></span>
                <?php else: ?>
                    <span class="muted"><?= e(__('cms.translation.missing')) ?></span>
                    <form method="POST" action="<?= e(url($cmsTrBase . '/' . $cmsTrSource . '/translations')) ?>" class="spacer">
                        <?= csrf_field() ?>
                        <input type="hidden" name="locale" value="<?= e($cmsTrLocale) ?>">
                        <button type="submit" class="btn btn-secondary" data-submit-once>
                            <?= icon('plus') ?><?= e(__('cms.translation.create', ['locale' => \App\Services\TranslationService::label($cmsTrLocale)])) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
