<?php
/**
 * บล็อก hero — ส่วนเปิดของหน้าสาธารณะ (พาดหัว h1 + รายการข้อดี + ปุ่ม + ภาพ/mockup)
 *
 * $block = [
 *   'eyebrow' => string        เม็ดข้อความเล็กเหนือพาดหัว — ไม่บังคับ
 *   'title'   => string        พาดหัว (h1) — "\n" = ขึ้นบรรทัดใหม่ · บังคับ
 *   'lead'    => string        ย่อหน้านำ — ไม่บังคับ
 *   'bullets' => list<string>  รายการข้อดีพร้อมเครื่องหมายถูก — ไม่บังคับ
 *   'actions' => list<['label','url','style' => primary|secondary|outline]>
 *   'image'   => ['src','alt','width','height'] | null
 *                ‣ มีภาพ = ใช้ภาพแทน mockup (ภาพทีม/ระบบจริงจาก CMS) · ‼️ ภาพนี้คือ LCP — ห้าม lazy
 *                ‣ ไม่มี = mockup แล็ปท็อป + มือถือ (partials/dashboard-mock.php) พร้อม caption ข้อมูลสมมติ
 *   'person'  => ['src','alt',…] | null   ภาพบุคคลด้านหลัง mockup (แบบ Human Trust) — ใช้เมื่อไม่มี image
 *                ‣ ‼️ ภาพประกอบต้องไม่อ้างว่าเป็นทีม OMC — alt เขียนแบบทั่วไป
 *   บรรทัดที่ 2 เป็นต้นไปของพาดหัวเป็นสีส้ม (แบบ Mockup)
 * ]
 *
 * ‼️ มี h1 ได้ตัวเดียวต่อหน้า — เรียก hero_rendered(true) ให้ page-head.php ไม่วาดซ้ำ
 *    และ page_public(true) ทำเครื่องหมายหน้าสาธารณะ (design.md 5.14 / 5.15ก)
 */

declare(strict_types=1);

hero_rendered(true);
page_public(true);

$heroBlock   = (array) ($block ?? []);
$heroLines   = preg_split('/\R/', (string) ($heroBlock['title'] ?? '')) ?: [];
$heroImage   = is_array($heroBlock['image'] ?? null) && (string) ($heroBlock['image']['src'] ?? '') !== ''
    ? $heroBlock['image'] : null;
$heroPerson  = is_array($heroBlock['person'] ?? null) && (string) ($heroBlock['person']['src'] ?? '') !== ''
    ? $heroBlock['person'] : null;

/* variant 'page' = ส่วนเปิดของหน้าใน (ไม่มี mockup) — breadcrumb อยู่ในนี้ เพราะ page-head ไม่วาดเมื่อมี hero
   ‼️ $breadcrumbs มาจาก Controller (view data) — หน้าแรกไม่มี breadcrumb */
if (($heroBlock['variant'] ?? '') === 'page'):
    $heroUid    = (string) ($heroBlock['_uid'] ?? 'hero');
    // tone 'dark' = พื้นน้ำเงินหมึก (หน้ารวมบทความ) · visual 'network' = แล็ปท็อปหน้าจอสายงาน (ตกแต่ง — ซ่อนบนมือถือ)
    $heroDark   = ($heroBlock['tone'] ?? '') === 'dark';
    $heroVisual = (string) ($heroBlock['visual'] ?? '');
?>
<section class="hero-page<?= e(($heroDark ? ' is-dark' : '') . ($heroVisual !== '' ? ' has-visual' : '')) ?>" aria-labelledby="<?= e($heroUid) ?>-title">
    <div class="hero-page-glow" aria-hidden="true"></div>
    <div class="container hero-page-inner">
        <?php $breadcrumbItems = (array) ($breadcrumbs ?? []); include __DIR__ . '/../breadcrumb.php'; ?>
        <?php if ((string) ($heroBlock['eyebrow'] ?? '') !== ''): ?>
            <p class="eyebrow-pill"><?= e((string) $heroBlock['eyebrow']) ?></p>
        <?php endif; ?>
        <h1 class="hero-page-title" id="<?= e($heroUid) ?>-title">
            <?php foreach ($heroLines as $heroLineIndex => $heroLine): ?>
                <?php if ($heroLineIndex > 0): ?><br><?php endif; ?><?= e($heroLine) ?>
            <?php endforeach; ?>
        </h1>
        <?php if ((string) ($heroBlock['lead'] ?? '') !== ''): ?>
            <p class="hero-page-lead"><?= e((string) $heroBlock['lead']) ?></p>
        <?php endif; ?>
        <?php $checkListItems = (array) ($heroBlock['bullets'] ?? []); include __DIR__ . '/../check-list.php'; ?>
        <?php if (!empty($heroBlock['actions'])): ?>
            <div class="hero-site-actions">
                <?php foreach ((array) $heroBlock['actions'] as $heroAction): ?>
                    <?php $siteButton = (array) $heroAction; include __DIR__ . '/../site-button.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($heroVisual === 'network'): ?>
            <figure class="hero-page-visual">
                <?php $dashboardMockFrame = 'laptop'; $dashboardMockView = 'team'; include __DIR__ . '/../dashboard-mock.php'; ?>
                <figcaption class="mock-caption"><?= e(__('showcase.caption')) ?></figcaption>
            </figure>
        <?php endif; ?>
    </div>
</section>
<?php
    return;
endif;
?>
<section class="hero-site" aria-labelledby="<?= e($heroBlock['_uid'] ?? 'hero') ?>-title">
    <div class="hero-site-glow" aria-hidden="true" data-parallax="0.06"></div>
    <div class="container hero-site-inner">
        <div class="hero-site-text">
            <?php if ((string) ($heroBlock['eyebrow'] ?? '') !== ''): ?>
                <p class="eyebrow-pill"><?= e((string) $heroBlock['eyebrow']) ?></p>
            <?php endif; ?>

            <h1 class="hero-site-title" id="<?= e($heroBlock['_uid'] ?? 'hero') ?>-title">
                <?php foreach ($heroLines as $heroLineIndex => $heroLine): ?>
                    <?php if ($heroLineIndex > 0): ?><br><span class="hero-site-accent"><?= e($heroLine) ?></span><?php else: ?><?= e($heroLine) ?><?php endif; ?>
                <?php endforeach; ?>
            </h1>

            <?php if ((string) ($heroBlock['lead'] ?? '') !== ''): ?>
                <p class="hero-site-lead"><?= e((string) $heroBlock['lead']) ?></p>
            <?php endif; ?>

            <?php $checkListItems = (array) ($heroBlock['bullets'] ?? []); include __DIR__ . '/../check-list.php'; ?>

            <?php if (!empty($heroBlock['actions'])): ?>
                <div class="hero-site-actions">
                    <?php foreach ((array) $heroBlock['actions'] as $heroAction): ?>
                        <?php $siteButton = (array) $heroAction + ['size' => 'lg']; include __DIR__ . '/../site-button.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <figure class="hero-site-visual"<?php if ($heroImage === null && $heroPerson !== null): ?> data-compose="person"<?php endif; ?>>
            <?php if ($heroImage === null && $heroPerson !== null): ?>
                <?php /* ภาพบุคคล = LCP ของหน้าแรก → eager · mockup ซ้อนด้านหน้า (ข้อมูลสมมติ — caption บอกไว้) */ ?>
                <div class="hero-person">
                    <?php $siteImage = $heroPerson; $siteImageClass = 'hero-person-image'; $siteImageEager = true;
                          $siteImageSizes = '(min-width: 960px) 30vw, 70vw'; include __DIR__ . '/../site-image.php'; ?>
                </div>
                <div class="hero-person-devices">
                    <?php $dashboardMockFrame = 'laptop'; $dashboardMockView = 'overview'; include __DIR__ . '/../dashboard-mock.php'; ?>
                </div>
                <figcaption class="mock-caption"><?= e(__('showcase.caption_person')) ?></figcaption>
            <?php elseif ($heroImage !== null): ?>
                <img class="hero-site-image"
                     src="<?= e(asset((string) $heroImage['src'])) ?>"
                     alt="<?= e((string) ($heroImage['alt'] ?? '')) ?>"
                     width="<?= e((string) (int) ($heroImage['width'] ?? 1200)) ?>"
                     height="<?= e((string) (int) ($heroImage['height'] ?? 900)) ?>"
                     fetchpriority="high" decoding="async">
            <?php else: ?>
                <?php $dashboardMockFrame = 'laptop'; $dashboardMockView = 'overview'; include __DIR__ . '/../dashboard-mock.php'; ?>
                <figcaption class="mock-caption"><?= e(__('showcase.caption')) ?></figcaption>
            <?php endif; ?>
        </figure>
    </div>
</section>
