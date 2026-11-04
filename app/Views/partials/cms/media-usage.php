<?php
/**
 * รูปนี้ถูกใช้ที่ไหน — แสดงในหน้าแก้รูป · ถูกใช้อยู่ = ลบไม่ได้ (MediaService::remove)
 *
 * ตัวแปรที่รับ: $mediaUsage [['kind' => page|post|testimonial|case_study|setting, 'id', 'title']]
 */

declare(strict_types=1);

$mediaUsage = (array) ($mediaUsage ?? []);
$mediaLinks = ['page' => '/admin/pages/', 'post' => '/admin/posts/', 'testimonial' => '/admin/testimonials/', 'case_study' => '/admin/case-studies/'];
?>
<section class="card stack" aria-labelledby="cms-usage-title">
    <h2 id="cms-usage-title"><?= e(__('cms.media.usage')) ?></h2>
    <?php if ($mediaUsage === []): ?>
        <p class="muted"><?= e(__('cms.media.unused')) ?></p>
    <?php else: ?>
        <p class="hint"><?= e(__('cms.media.usage_hint')) ?></p>
        <ul class="cms-list">
            <?php foreach ($mediaUsage as $mediaUse): ?>
                <li class="row">
                    <span class="badge badge-type"><?= e(__('cms.media.kind_' . $mediaUse['kind'])) ?></span>
                    <?php if (isset($mediaLinks[$mediaUse['kind']])): ?>
                        <a href="<?= e(url($mediaLinks[$mediaUse['kind']] . (int) $mediaUse['id'])) ?>"><?= e((string) $mediaUse['title']) ?></a>
                    <?php elseif ($mediaUse['kind'] === 'setting'): ?>
                        <a href="<?= e(url('/admin/settings')) ?>"><?= e((string) $mediaUse['title']) ?></a>
                    <?php else: ?>
                        <span><?= e((string) $mediaUse['title']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
