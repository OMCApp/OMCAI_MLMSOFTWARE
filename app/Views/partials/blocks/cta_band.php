<?php
/**
 * บล็อก cta_band — แถบชวนติดต่อเต็มความกว้าง ไล่สีน้ำเงินหมึก → ส้มเข้ม (ลายเซ็นของ DESIGN.md)
 *
 * $block = [
 *   'title'   => string   (h2)
 *   'text'    => string   — ไม่บังคับ
 *   'actions' => list<['label','url','style' => light|ghost-light,'icon' => ไม่บังคับ]>
 * ]
 *
 * ‼️ gradient ใช้ ink → primary ไม่ใช่ ink → accent (ส้มสด) — ตัวหนังสือขาวบนส้มสดได้แค่ 2.88:1
 *    ทั้งสองปลายของ gradient ผ่าน 4.5:1 กับตัวหนังสือขาว (DESIGN.md "สิ่งที่จงใจไม่ทำ")
 */

declare(strict_types=1);

$ctaBlock = (array) ($block ?? []);
$ctaUid   = (string) ($ctaBlock['_uid'] ?? 'cta');

if ((string) ($ctaBlock['title'] ?? '') === '') {
    return;
}
?>
<section class="cta-band" aria-labelledby="<?= e($ctaUid) ?>-title">
    <div class="cta-band-glow" aria-hidden="true"></div>
    <div class="container cta-band-inner reveal">
        <div class="cta-band-text">
            <h2 class="cta-band-title" id="<?= e($ctaUid) ?>-title"><?= e((string) $ctaBlock['title']) ?></h2>
            <?php if ((string) ($ctaBlock['text'] ?? '') !== ''): ?>
                <p class="cta-band-lead"><?= e((string) $ctaBlock['text']) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($ctaBlock['actions'])): ?>
            <div class="cta-band-actions">
                <?php foreach ((array) $ctaBlock['actions'] as $ctaAction): ?>
                    <?php $siteButton = (array) $ctaAction + ['size' => 'lg', 'style' => 'light']; include __DIR__ . '/../site-button.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
