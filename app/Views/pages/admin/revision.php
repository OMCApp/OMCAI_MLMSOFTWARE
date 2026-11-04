<?php
/**
 * ดู revision เทียบกับปัจจุบัน + ปุ่มย้อนกลับ (ต้องยืนยัน)
 *
 * ตัวแปร: $revisionFields [['key','now','then','changed']] · $revisionRestore URL (POST)
 * ‼️ ค่าทั้งหมดมาจากผู้ใช้ — ผ่าน e() ทุกตัว แสดงเป็นข้อความ ไม่ render HTML
 */

declare(strict_types=1);
?>
<div class="stack">
    <div class="card stack">
        <p class="hint"><?= e(__('cms.revisions.compare_hint')) ?></p>
        <?php foreach ((array) $revisionFields as $revisionField): ?>
            <section class="stack cms-diff"<?php if ($revisionField['changed']): ?> data-changed="true"<?php endif; ?>>
                <h3><?= e(__('cms.field.' . $revisionField['key'])) ?>
                    <?php if ($revisionField['changed']): ?><span class="badge"><?= e(__('cms.revisions.changed')) ?></span><?php endif; ?>
                </h3>
                <?php if ($revisionField['changed']): ?>
                    <div class="field-grid">
                        <div class="stack"><span class="hint"><?= e(__('cms.revisions.then')) ?></span><pre class="cms-pre"><?= e(mb_substr($revisionField['then'], 0, 4000)) ?></pre></div>
                        <div class="stack"><span class="hint"><?= e(__('cms.revisions.now')) ?></span><pre class="cms-pre"><?= e(mb_substr($revisionField['now'], 0, 4000)) ?></pre></div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="card row">
        <p><?= e(__('cms.revisions.restore_hint')) ?></p>
        <span class="spacer">
            <?php
            $confirmUrl     = (string) $revisionRestore;
            $confirmTitle   = __('cms.revisions.restore_title');
            $confirmBody    = __('cms.revisions.restore_body');
            $confirmLabel   = __('cms.revisions.restore');
            $confirmTrigger = __('cms.revisions.restore');
            $confirmIcon    = 'history';
            $confirmMethod  = 'POST';
            include __DIR__ . '/../../partials/confirm.php';
            ?>
        </span>
    </div>
</div>
