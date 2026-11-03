<?php
/**
 * หัวเพจ — ชื่อหน้า คำอธิบาย และปุ่มหลักของหน้า
 *
 * ‼️ ทุกหน้าต้องมีหัวเพจ มาจาก layout กลางเหมือน footer ห้ามเขียน <h1> เองทีละหน้า
 *    ยกเว้นหน้าที่มีส่วนเปิด (partials/hero.php) ซึ่งวาด <h1> ของตัวเองอยู่แล้ว
 *    partial นี้ตรวจให้เองด้วย hero_rendered() ไม่ต้องให้ Controller สั่งปิด
 *
 * ‼️ เหตุผลที่ partial นี้มีอยู่
 *    โปรเจคแรกที่ใช้มาตรฐานนี้ทำไป 10 หน้าโดยไม่มีหัวเพจเลยสักหน้า
 *    ชื่อหน้าไปอยู่ใน topbar ตัวเล็ก คำอธิบายหายไปหมด
 *    ผลคือทุกอย่างในหน้ามีน้ำหนักเท่ากัน ไม่มีจุดเริ่มให้สายตา
 *    ซึ่งเป็นสาเหตุอันดับหนึ่งที่ผู้ใช้บอกว่า "หน้าดูแย่" ทั้งที่ทุกกฎผ่านหมด
 *    ของที่ไม่มีในโครง AI จะไม่สร้างขึ้นมาเอง เพราะมันไม่รู้ว่าขาด — เหมือน footer
 *
 * ‼️ ปุ่มหลักของหน้าอยู่ที่นี่ ห้ามไปปนกับแถบตัวกรอง
 *    ปนกันแล้วเส้นฐานไม่ตรงกัน เพราะช่องกรองมี label อยู่ข้างบน
 *
 * ‼️ $pageActions รับเป็น "ข้อมูลของปุ่ม" ไม่ใช่ HTML
 *    รับ HTML แปลว่ามีจุดที่ output ไม่ผ่าน e() ซึ่งขัดกฎเหล็กข้อ 6 โดยตรง
 *    และเปิดช่องให้หน้าฉีด markup เอง ซึ่งขัดกฎ "component ต้องเป็นของกลาง"
 *
 * ‼️ ค่าทั้งหมดต้องส่งมาจาก Controller ไม่ใช่ตั้งในไฟล์หน้า
 *    Controller::render() เรียก capture() ของหน้ากับของ layout แยกกันคนละครั้ง
 *    ตัวแปรที่ตั้งในไฟล์หน้าจึงไปไม่ถึง layout — ต้องผ่าน $data เท่านั้น
 *    และนี่คือที่ที่ถูกต้องอยู่แล้ว เพราะชื่อหน้ากับสิทธิ์เป็นเรื่องของ Controller
 *
 *    return $this->render('users/index', [
 *        'title'       => __('user.title'),
 *        'pageLead'    => __('user.lead'),
 *        'pageWidth'   => 'list',
 *        'pageActions' => [['label' => __('user.add'), 'url' => '/users/create', 'icon' => 'plus']],
 *    ]);
 *
 * ‼️ ช่องภาพ ($pageMedia) และ breadcrumb ($pageBreadcrumb) มีตั้งแต่ 3.17.0
 *    ก่อนหน้านั้นหัวเพจมีแต่ชื่อ คำอธิบาย และปุ่ม — ผู้ใช้ออกแบบหน้าที่มีภาพมุมขวาบนและเส้นทางนำทางมา
 *    แล้วได้หน้าที่ไม่มีทั้งสองอย่าง เพราะ partial ทำไม่ได้ และ AI เลือกตัดทิ้งแทนการเพิ่มช่อง
 *    ของที่แบบต้องการแต่ partial ทำไม่ได้ ให้ขยาย partial — ห้ามตัดทิ้งเงียบๆ (standard/design.md ข้อ 5.0)
 *
 * ‼️ $pageLead คือประโยคอธิบายหน้า ไม่ใช่ชื่อของข้อมูลที่กำลังแก้
 *    หน้าแก้ไขที่ส่งชื่อรายการมาเป็น lead จะได้บรรทัดใต้หัวข้อที่อ่านว่า "START" แทนคำอธิบาย
 *    ชื่อรายการที่กำลังแก้ให้อยู่ในชื่อหน้า หรือเป็นท้ายของ breadcrumb
 *
 * ตัวแปรที่รับ (ขึ้นต้นด้วย page เสมอ เพื่อไม่ให้ชนกับตัวแปรของหน้า):
 *   $pageTitle       ชื่อหน้า — ไม่ส่งจะใช้ $title ที่ Controller ส่งให้ layout
 *   $pageLead        ประโยคบอกว่าใครใช้ได้และผลของการกระทำ
 *   $pageActions     รายการปุ่ม [['label'=>, 'url'=>, 'icon'=>, 'style'=>'primary|ghost'], ...]
 *   $pageBreadcrumb  เส้นทาง [['label'=>, 'url'=>], ...] — ตัวสุดท้ายคือหน้านี้ ไม่ต้องมี url · ต่ำกว่า 2 ขั้นไม่แสดง
 *   $pageMedia       ภาพหรือไอคอนข้างหัวเพจ
 *                    ภาพ:    ['src' => '/assets/images/x.webp', 'alt' => __(...), 'width' => 480, 'height' => 320]
 *                    ไอคอน: ['icon' => 'book'] — ชื่อจากชุดกลางใน partials/icons.php เท่านั้น
 *                    alt ว่าง = ภาพตกแต่ง · ภาพต้องมาจาก public/assets/ ห้ามอ้าง URL ภายนอก
 */

declare(strict_types=1);

$pageTitle      = $pageTitle      ?? ($title ?? '');
$pageLead       = $pageLead       ?? '';
$pageActions    = $pageActions    ?? [];
$pageBreadcrumb = $pageBreadcrumb ?? [];
$pageMedia      = $pageMedia      ?? [];

/* ‼️ หน้าที่มีส่วนเปิด (partials/hero.php) ไม่ต้องมีหัวเพจ
      ทั้งคู่วาด <h1> มีสองตัวแปลว่าไม่มีตัวไหนเป็นหัวจริง

      เจอตอนทำหน้าแรกของเว็บโชว์เป็นครั้งแรก — กฎในไฟล์นี้ที่ว่า
      "ทุกหน้าต้องมีหัวเพจ" เขียนไว้ตอนที่มีแต่หน้าหลังบ้าน ซึ่งไม่มี hero
      ปล่อยให้ต้องจำว่าต้องส่ง pageTitle ว่าง คือปล่อยให้มีวันที่ลืม */
if (hero_rendered() || $pageTitle === '') {
    return;
}
?>
<header class="<?= e($pageMedia !== [] ? 'page-head has-media' : 'page-head') ?>">
    <div class="page-head-text">
        <?php
        $breadcrumbItems = $pageBreadcrumb;
        include __DIR__ . '/breadcrumb.php';
        ?>
        <h1><?= e($pageTitle) ?></h1>
        <?php if ($pageLead !== ''): ?>
            <p class="page-lead"><?= e($pageLead) ?></p>
        <?php endif; ?>
    </div>

    <?php if (($pageMedia['src'] ?? '') !== ''): ?>
        <figure class="page-media">
            <img src="<?= e(asset((string) $pageMedia['src'])) ?>"
                 alt="<?= e((string) ($pageMedia['alt'] ?? '')) ?>"
                 <?php if (isset($pageMedia['width'], $pageMedia['height'])): ?>
                 width="<?= e((string) (int) $pageMedia['width']) ?>" height="<?= e((string) (int) $pageMedia['height']) ?>"
                 <?php endif; ?>
                 fetchpriority="high">
        </figure>
    <?php elseif (($pageMedia['icon'] ?? '') !== ''): ?>
        <div class="page-media is-icon" aria-hidden="true"><?= icon((string) $pageMedia['icon']) ?></div>
    <?php endif; ?>

    <?php if ($pageActions !== []): ?>
        <div class="page-actions">
            <?php foreach ($pageActions as $action): ?>
                <a class="btn btn-<?= e($action['style'] ?? 'primary') ?>"
                   href="<?= e(url((string) ($action['url'] ?? '/'))) ?>">
                    <?php if (($action['icon'] ?? '') !== ''): ?>
                        <?= icon((string) $action['icon']) ?>
                    <?php endif; ?>
                    <?= e((string) ($action['label'] ?? '')) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>
<?php
/* ‼️ ล้างค่าทุกตัวเมื่อจบ — partial ถูก include ในขอบเขตเดียวกับหน้า
   ไม่ล้างแล้วหน้าที่ include ซ้ำจะได้ค่าของครั้งก่อนค้างมา */
$pageTitle      = '';
$pageLead       = '';
$pageActions    = [];
$pageBreadcrumb = [];
$pageMedia      = [];
