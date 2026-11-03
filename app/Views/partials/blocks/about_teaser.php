<?php
/**
 * บล็อก about_teaser — แนะนำบริษัทสั้นๆ พร้อมลิงก์ไปหน้า About
 *
 * $block = [
 *   'eyebrow', 'title' (h2)
 *   'text'   => string        ย่อหน้าแนะนำ
 *   'points' => list<string>  — ไม่บังคับ
 *   'action' => ['label','url'] — ไม่บังคับ (ปุ่มขอบส้ม)
 *   'image'  => ['src','alt','width','height'] — ภาพทีม/ออฟฟิศจริง · ไม่บังคับ · lazy
 * ]
 *
 * ‼️ แบบอ้างอิงมีภาพอาคาร "OMC" ที่ไม่ใช่อาคารจริง — ไม่มีภาพจริงจึงใช้แผงโลโก้แทน (ไม่ใช้กล่องเทา)
 *    ห้ามใส่ภาพสต็อกแอบอ้างเป็นทีมหรือออฟฟิศ (QUESTIONS.md)
 */

declare(strict_types=1);

$aboutBlock = (array) ($block ?? []);
$aboutImage = is_array($aboutBlock['image'] ?? null) && (string) ($aboutBlock['image']['src'] ?? '') !== ''
    ? $aboutBlock['image'] : null;
$aboutUid   = (string) ($aboutBlock['_uid'] ?? 'about');
?>
<section class="section" aria-labelledby="<?= e($aboutUid) ?>-title">
    <div class="container about-teaser">
        <div class="about-media reveal">
            <?php if ($aboutImage !== null): ?>
                <img src="<?= e(asset((string) $aboutImage['src'])) ?>" alt="<?= e((string) ($aboutImage['alt'] ?? '')) ?>"
                     width="<?= e((string) (int) ($aboutImage['width'] ?? 800)) ?>" height="<?= e((string) (int) ($aboutImage['height'] ?? 600)) ?>"
                     loading="lazy" decoding="async">
            <?php else: ?>
                <div class="about-brand-panel" aria-hidden="true" data-parallax="0.04">
                    <img src="<?= e(asset('/assets/images/brand/omc-logo.svg')) ?>" alt="" width="300" height="122" loading="lazy">
                </div>
            <?php endif; ?>
        </div>

        <div class="about-text reveal" data-reveal-delay="1">
            <?php if ((string) ($aboutBlock['eyebrow'] ?? '') !== ''): ?>
                <p class="eyebrow-pill"><?= e((string) $aboutBlock['eyebrow']) ?></p>
            <?php endif; ?>
            <h2 class="section-title" id="<?= e($aboutUid) ?>-title"><?= e((string) ($aboutBlock['title'] ?? '')) ?></h2>
            <?php if ((string) ($aboutBlock['text'] ?? '') !== ''): ?>
                <p class="section-lead"><?= e((string) $aboutBlock['text']) ?></p>
            <?php endif; ?>
            <?php $checkListItems = (array) ($aboutBlock['points'] ?? []); include __DIR__ . '/../check-list.php'; ?>
            <?php if (is_array($aboutBlock['action'] ?? null)): ?>
                <div class="block-actions">
                    <?php $siteButton = (array) $aboutBlock['action'] + ['style' => 'outline', 'arrow' => true]; include __DIR__ . '/../site-button.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
