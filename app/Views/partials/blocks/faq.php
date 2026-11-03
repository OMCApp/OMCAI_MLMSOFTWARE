<?php
/**
 * บล็อก faq — คำถามที่พบบ่อยแบบ accordion
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'items' => list<['q' => คำถาม, 'a' => คำตอบ (ข้อความล้วน — ขึ้นบรรทัดใหม่ได้)]>
 * ]
 *
 * ‼️ ใช้ <details>/<summary> ของเบราว์เซอร์ — เปิดปิดได้ด้วยคีย์บอร์ดและไม่ต้องมี JS
 *    name เดียวกันทั้งกลุ่ม = เปิดได้ทีละข้อ (เบราว์เซอร์ใหม่) · คำตอบอยู่ใน HTML เสมอ Google อ่านได้
 * ‼️ หน้าที่มีบล็อกนี้ต้องได้ JSON-LD FAQPage จากเนื้อหาเดียวกัน (Phase 2 — partial SEO อ่านจาก $block['items'])
 */

declare(strict_types=1);

$faqBlock = (array) ($block ?? []);
$faqPairs = array_values(array_filter(
    (array) ($faqBlock['items'] ?? []),
    static fn ($pair): bool => is_array($pair) && (string) ($pair['q'] ?? '') !== '' && (string) ($pair['a'] ?? '') !== ''
));

if ($faqPairs === []) {
    return;
}

$faqUid = (string) ($faqBlock['_uid'] ?? 'faq');
?>
<section class="section" aria-labelledby="<?= e($faqUid) ?>-title">
    <div class="container faq-wrap">
        <?php
        $sectionHeadEyebrow = (string) ($faqBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($faqBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($faqBlock['lead'] ?? '');
        $sectionHeadId      = $faqUid . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <div class="faq-list reveal">
            <?php foreach ($faqPairs as $faqPair): ?>
                <details class="faq-item" name="<?= e($faqUid) ?>">
                    <summary class="faq-question">
                        <span><?= e((string) $faqPair['q']) ?></span>
                        <?= icon('chevron-down', 'faq-caret') ?>
                    </summary>
                    <div class="faq-answer">
                        <?php foreach (preg_split('/\R{2,}/', (string) $faqPair['a']) ?: [] as $faqParagraph): ?>
                            <p><?php foreach (preg_split('/\R/', $faqParagraph) ?: [] as $faqLineIndex => $faqLine): ?><?php if ($faqLineIndex > 0): ?><br><?php endif; ?><?= e($faqLine) ?><?php endforeach; ?></p>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
