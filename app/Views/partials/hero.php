<?php
/**
 * ส่วนเปิดหน้า — ข้อความคู่กับภาพหลัก
 *
 * ‼️ ใช้ได้หน้าละไม่เกินหนึ่งครั้ง และต้องมี <h1> เพียงตัวเดียวในหน้า
 *    มีสองอันในหน้าเดียวแปลว่าไม่มีอันไหนเป็นส่วนเปิดจริง
 *
 * ‼️ ภาพเป็นของประกอบ ไม่ใช่ของที่สื่อความหมายแทนข้อความ
 *    ข้อความต้องอ่านรู้เรื่องแม้ภาพโหลดไม่ขึ้น
 *
 * ตัวแปรที่รับ (ต้องขึ้นต้นด้วย hero เสมอ ตามกฎตัวแปรของ partial)
 *   $heroEyebrow  ข้อความเล็กเหนือพาดหัว — ไม่บังคับ
 *   $heroTitle    พาดหัว
 *   $heroLead     ย่อหน้าอธิบาย — ไม่บังคับ
 *   $heroImage    ชื่อไฟล์ใน public/assets/images/ — ไม่บังคับ
 *   $heroAlt      คำบรรยายภาพ บังคับถ้ามี $heroImage
 *   $heroActions  [['label' => ..., 'url' => ..., 'primary' => bool], ...]
 *   $heroFullBleed  true = ภาพเต็มจอ ข้อความทับภาพ — เฉพาะหน้าสาธารณะ (design.md 5.16)
 */

declare(strict_types=1);

/* ‼️ บอก page-head.php ว่าหน้านี้มีหัวเรื่องแล้ว — มันจะได้ไม่วาด <h1> ซ้ำ
      ไม่ต้องให้ Controller ส่ง pageTitle ว่างมาเอง เพราะสิ่งที่ต้องจำคือสิ่งที่จะถูกลืม */
hero_rendered(true);

// ‼️ หน้าที่มี hero คือหน้าสาธารณะเสมอ — ทำเครื่องหมายให้เอง ไม่ต้องให้ใครจำ
//    layout เขียนลง data-page เพื่อให้ CSS และชุดตรวจรู้ว่ากฎชุดไหนใช้กับหน้านี้
page_public(true);

$heroEyebrow = (string) ($heroEyebrow ?? '');
$heroTitle   = (string) ($heroTitle ?? '');
$heroLead    = (string) ($heroLead ?? '');
$heroImage   = (string) ($heroImage ?? '');
$heroAlt     = (string) ($heroAlt ?? '');
$heroActions = (array) ($heroActions ?? []);

/* ‼️ แบบเต็มจอ — ภาพชนขอบทั้งสองด้าน ข้อความทับภาพ
      ใช้ได้เฉพาะหน้าสาธารณะ ตาม design.md ข้อ 5.16 ซึ่งยกเว้นกฎ 5.4 กับ 5.5 ให้
      หน้าหลังบ้านห้ามใช้ เพราะที่นั่นต้องการความสม่ำเสมอมากกว่าความประทับใจ */
$heroFullBleed = (bool) ($heroFullBleed ?? false);
$heroClass     = $heroFullBleed ? 'hero hero-full bleed' : 'hero';
?>
<section class="<?= e($heroClass) ?>">
    <?php if ($heroFullBleed && $heroImage !== ''): ?>
        <img class="hero-media"
             src="<?= e(asset('/assets/images/' . $heroImage)) ?>"
             alt="<?= e($heroAlt) ?>" width="1600" height="900" fetchpriority="high">
    <?php endif; ?>

    <div class="hero-body">
        <?php if ($heroEyebrow !== ''): ?>
            <p class="hero-eyebrow"><?= e($heroEyebrow) ?></p>
        <?php endif; ?>

        <h1 class="hero-title"><?= e($heroTitle) ?></h1>

        <?php if ($heroLead !== ''): ?>
            <p class="hero-lead"><?= e($heroLead) ?></p>
        <?php endif; ?>

        <?php if ($heroActions !== []): ?>
            <div class="row">
                <?php foreach ($heroActions as $heroAction): ?>
                    <?php $heroActionClass = !empty($heroAction['primary']) ? 'btn btn-primary' : 'btn btn-secondary'; ?>
                    <a class="<?= e($heroActionClass) ?>"
                       href="<?= e(url((string) $heroAction['url'])) ?>"><?= e((string) $heroAction['label']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$heroFullBleed && $heroImage !== ''): ?>
        <?php /* ‼️ ระบุ width/height เพื่อกันหน้ากระตุกตอนภาพโหลดเสร็จ */ ?>
        <img class="hero-image"
             src="<?= e(asset('/assets/images/' . $heroImage)) ?>"
             alt="<?= e($heroAlt) ?>" width="1200" height="1600" fetchpriority="high">
    <?php endif; ?>
</section>
