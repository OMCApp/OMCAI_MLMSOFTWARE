<?php
/**
 * ประวัติการแก้ไข (revisions) ของหน้า/บทความ — ดูรุ่นเก่า และย้อนกลับ
 *
 * ‼️ ทุกการบันทึกเก็บสำเนา "ก่อนแก้" ไว้ (PageService/PostService::update) · การย้อนกลับก็เก็บสำเนาก่อนย้อนเช่นกัน
 *
 * ตัวแปรที่รับ: $cmsRevisions (list ของ id, created_at, author) · $cmsRevisionBase ('/admin/pages/12')
 */

declare(strict_types=1);

$cmsRevisions = (array) ($cmsRevisions ?? []);
?>
<section class="card stack" aria-labelledby="cms-rev-title">
    <h2 id="cms-rev-title"><?= e(__('cms.revisions.title')) ?></h2>
    <?php if ($cmsRevisions === []): ?>
        <p class="muted"><?= e(__('cms.revisions.empty')) ?></p>
    <?php else: ?>
        <ul class="cms-list">
            <?php foreach (array_slice($cmsRevisions, 0, 20) as $cmsRevision): ?>
                <li class="row">
                    <a href="<?= e(url((string) $cmsRevisionBase . '/revisions/' . (int) $cmsRevision['id'])) ?>">
                        <?= e(format_date((string) $cmsRevision['created_at'], true)) ?>
                    </a>
                    <span class="muted small"><?= e((string) ($cmsRevision['author'] ?? '—')) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
