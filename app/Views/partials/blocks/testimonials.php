<?php
/**
 * บล็อก testimonials — เสียงจากลูกค้า (เลื่อนดูได้)
 *
 * $block = [
 *   'eyebrow', 'title' (h2) — ไม่บังคับ
 *   'items' => list<[
 *     'quote'   => string   ข้อความรีวิว
 *     'name'    => string   ชื่อผู้ให้รีวิว
 *     'role'    => string   ตำแหน่ง / บริษัท — ไม่บังคับ
 *     'photo'   => ['src','alt','width','height'] — ไม่บังคับ
 *   ]>
 * ]
 *
 * ‼️ ไม่มีรายการ = ไม่วาดอะไรเลย — CMS ส่งมาเฉพาะ testimonials ที่ status = published
 *    ซึ่ง publish ได้เฉพาะของจริงที่ได้รับอนุญาต (PLAN.md · brief ห้ามรีวิวปลอม)
 * ‼️ เลื่อนด้วย scroll-snap ทำงานได้โดยไม่มี JS · ปุ่มก่อนหน้า/ถัดไปมี hidden จนกว่า app.js จะเปิด
 */

declare(strict_types=1);

$testimonialBlock = (array) ($block ?? []);
$testimonialCards = array_values(array_filter(
    (array) ($testimonialBlock['items'] ?? []),
    static fn ($card): bool => is_array($card) && (string) ($card['quote'] ?? '') !== '' && (string) ($card['name'] ?? '') !== ''
));

if ($testimonialCards === []) {
    return;
}

$testimonialUid = (string) ($testimonialBlock['_uid'] ?? 'testimonials');
?>
<section class="section" aria-labelledby="<?= e($testimonialUid) ?>-title" data-slider>
    <div class="container">
        <?php
        $sectionHeadEyebrow = (string) ($testimonialBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($testimonialBlock['title'] ?? __('site.testimonials_label'));
        $sectionHeadId      = $testimonialUid . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <ul class="testimonial-track" data-slider-track>
            <?php foreach ($testimonialCards as $testimonialIndex => $testimonialCard): ?>
                <li class="testimonial-card" aria-label="<?= e(__('site.slide_of', ['n' => $testimonialIndex + 1, 'total' => count($testimonialCards)])) ?>">
                    <span class="testimonial-mark" aria-hidden="true"><?= icon('quote', 'icon-lg') ?></span>
                    <blockquote class="testimonial-quote"><p><?= e((string) $testimonialCard['quote']) ?></p></blockquote>
                    <div class="testimonial-person">
                        <?php if (is_array($testimonialCard['photo'] ?? null) && (string) ($testimonialCard['photo']['src'] ?? '') !== ''): ?>
                            <img class="testimonial-photo" src="<?= e(asset((string) $testimonialCard['photo']['src'])) ?>"
                                 alt="" width="56" height="56" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <span>
                            <span class="testimonial-name"><?= e((string) $testimonialCard['name']) ?></span>
                            <?php if ((string) ($testimonialCard['role'] ?? '') !== ''): ?>
                                <span class="testimonial-role"><?= e((string) $testimonialCard['role']) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (count($testimonialCards) > 1): ?>
            <div class="slider-controls" data-slider-controls hidden>
                <button type="button" class="btn btn-icon slider-btn" data-slider-prev
                        aria-label="<?= e(__('site.previous')) ?>" title="<?= e(__('site.previous')) ?>"><?= icon('chevron-left') ?></button>
                <button type="button" class="btn btn-icon slider-btn" data-slider-next
                        aria-label="<?= e(__('site.next')) ?>" title="<?= e(__('site.next')) ?>"><?= icon('chevron-right') ?></button>
            </div>
        <?php endif; ?>
    </div>
</section>
