<?php
/**
 * บล็อก split — ข้อความคู่กับภาพ / กราฟิก
 *
 * $block = [
 *   'layout'  => 'plain' | 'band'
 *                plain = สองคอลัมน์ ข้อความ + ภาพ
 *                band  = แถบโค้งมนพื้นอุ่นสามช่อง: กราฟิก · หัวข้อ · รายการ (แบบ "AI & Data" ของหน้าแรก)
 *   'visual'  => 'image' | 'bars'   bars = กราฟแท่ง CSS (ไม่ต้องมีภาพ) — ค่าเริ่มต้น image ถ้ามี image
 *   'image'   => ['src','alt','width','height'] — ไม่บังคับ · lazy เสมอ (ไม่ใช่ส่วนเปิด)
 *   'reverse' => bool   สลับภาพไปซ้าย (เฉพาะ plain)
 *   'eyebrow', 'title' (h2), 'lead', 'bullets' => list<string>
 *   'action'  => ['label','url','style'] — ไม่บังคับ
 * ]
 */

declare(strict_types=1);

$splitBlock  = (array) ($block ?? []);
$splitLayout = ($splitBlock['layout'] ?? 'plain') === 'band' ? 'band' : 'plain';
$splitImage  = is_array($splitBlock['image'] ?? null) && (string) ($splitBlock['image']['src'] ?? '') !== ''
    ? $splitBlock['image'] : null;
$splitVisual = $splitImage !== null && ($splitBlock['visual'] ?? 'image') === 'image' ? 'image' : 'bars';
$splitTitleId = ($splitBlock['_uid'] ?? 'split') . '-title';
?>
<section class="section split-section" data-layout="<?= e($splitLayout) ?>" aria-labelledby="<?= e($splitTitleId) ?>">
    <div class="container">
        <div class="split-block reveal" data-layout="<?= e($splitLayout) ?>"<?php if (!empty($splitBlock['reverse'])): ?> data-reverse="true"<?php endif; ?>>
            <div class="split-visual">
                <?php if ($splitVisual === 'image'): ?>
                    <img class="split-image" src="<?= e(asset((string) $splitImage['src'])) ?>"
                         alt="<?= e((string) ($splitImage['alt'] ?? '')) ?>"
                         width="<?= e((string) (int) ($splitImage['width'] ?? 800)) ?>"
                         height="<?= e((string) (int) ($splitImage['height'] ?? 600)) ?>"
                         loading="lazy" decoding="async">
                <?php else: ?>
                    <div class="bars-art" aria-hidden="true">
                        <span class="bars-art-bar" data-h="1"></span>
                        <span class="bars-art-bar" data-h="2"></span>
                        <span class="bars-art-bar" data-h="3"></span>
                        <span class="bars-art-bar" data-h="4"></span>
                        <span class="bars-art-bar" data-h="5"></span>
                        <svg class="bars-art-trend" viewBox="0 0 100 60" preserveAspectRatio="none">
                            <path pathLength="1" d="M2 52 C 25 46, 40 38, 55 30 S 85 12, 98 4"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <div class="split-text">
                <?php if ((string) ($splitBlock['eyebrow'] ?? '') !== ''): ?>
                    <p class="eyebrow-pill"><?= e((string) $splitBlock['eyebrow']) ?></p>
                <?php endif; ?>
                <h2 class="section-title" id="<?= e($splitTitleId) ?>"><?= e((string) ($splitBlock['title'] ?? '')) ?></h2>
                <?php if ((string) ($splitBlock['lead'] ?? '') !== ''): ?>
                    <p class="section-lead"><?= e((string) $splitBlock['lead']) ?></p>
                <?php endif; ?>
                <?php if ($splitLayout === 'plain'): ?>
                    <?php $checkListItems = (array) ($splitBlock['bullets'] ?? []); include __DIR__ . '/../check-list.php'; ?>
                <?php endif; ?>
                <?php if (is_array($splitBlock['action'] ?? null)): ?>
                    <div class="block-actions">
                        <?php $siteButton = (array) $splitBlock['action'] + ['style' => 'outline']; include __DIR__ . '/../site-button.php'; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($splitLayout === 'band'): ?>
                <div class="split-points">
                    <?php $checkListItems = (array) ($splitBlock['bullets'] ?? []); include __DIR__ . '/../check-list.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
