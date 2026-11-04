<?php
/**
 * บล็อก steps — ขั้นตอนเรียงลำดับ (เลขในวงกลม + เส้นเชื่อม)
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'items' => list<['title' => string, 'text' => string — ไม่บังคับ, 'icon' => ชื่อไอคอน — ไม่บังคับ]>
 * ]
 *
 * ‼️ เป็น <ol> — ลำดับมีความหมาย screen reader อ่านเลขขั้นให้เอง เลขในวงกลมจึง aria-hidden
 */

declare(strict_types=1);

$stepsBlock = (array) ($block ?? []);
$stepsCards = array_values(array_filter((array) ($stepsBlock['items'] ?? []), 'is_array'));

if ($stepsCards === []) {
    return;
}

$stepsUid = (string) ($stepsBlock['_uid'] ?? 'steps');
?>
<section class="section" data-tone="raised" aria-labelledby="<?= e($stepsUid) ?>-title">
    <div class="container">
        <?php
        $sectionHeadEyebrow = (string) ($stepsBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($stepsBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($stepsBlock['lead'] ?? '');
        $sectionHeadId      = $stepsUid . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <ol class="steps">
            <?php foreach ($stepsCards as $stepsIndex => $stepsCard): ?>
                <li class="step reveal" data-reveal-delay="<?= e((string) ($stepsIndex % 4)) ?>">
                    <span class="step-head">
                        <span class="step-number" aria-hidden="true"><?= e((string) ($stepsIndex + 1)) ?></span>
                        <?php if ((string) ($stepsCard['icon'] ?? '') !== ''): ?>
                            <span class="step-icon" aria-hidden="true"><?= icon((string) $stepsCard['icon']) ?></span>
                        <?php endif; ?>
                    </span>
                    <h3 class="step-title"><?= e((string) ($stepsCard['title'] ?? '')) ?></h3>
                    <?php if ((string) ($stepsCard['text'] ?? '') !== ''): ?>
                        <p class="step-text"><?= e((string) $stepsCard['text']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
