<?php
/**
 * layout กลางของทุกหน้าที่ล็อกอินแล้ว
 *
 * ‼️ ทุกอย่างที่ต้องมีทุกหน้าอยู่ที่นี่จุดเดียว — nav, footer, สลับภาษา, สลับโหมด, ปุ่มแจ้งปัญหา
 *    ห้ามใส่แยกทีละหน้า
 *
 * ‼️ เขียน data-theme ที่ <html> ตั้งแต่ฝั่ง PHP เพื่อไม่ให้หน้ากะพริบตอนโหลด
 *    ถ้ารอ JS ทำงาน ผู้ใช้จะเห็นโหมดสว่างแวบหนึ่งก่อนสลับเป็นมืด
 */

declare(strict_types=1);

use Core\Auth;
use Core\Lang;



$currentTheme = theme();
$collapsed    = 'false';

/* ‼️ รูปแบบเมนูมาจาก DESIGN.md ข้อ layout.nav ที่เดียว ผ่าน config/design.php ที่ generate ไว้
      เคยมี config ตัวนี้อยู่ใน config.php แต่ไม่มีใครอ่านเลย layout จึงวาด sidebar เสมอ
      ผลคือเว็บสาธารณะที่สั่ง header ไว้ ได้ sidebar ของหลังบ้านมาครอบ
      ซึ่งเป็นสิ่งที่ไม่มีใครสั่งให้เกิด และไม่มีอะไรรายงานว่าผิด */
$navStyle = config('ui.nav') === 'header' ? 'header' : 'sidebar';
?>
<!doctype html>
<html lang="<?= e(locale()) ?>" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') . ' · ' . (string) config('name')) ?></title>
    <?php include __DIR__ . '/../partials/favicon.php'; ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body>

<div class="shell" data-nav="<?= e($navStyle) ?>">
    <?php if ($navStyle === 'sidebar'): ?>
        <aside class="sidebar" data-collapsed="<?= e($collapsed) ?>" data-sidebar>
            <div class="brand">
                <?= icon('dashboard') ?>
                <span class="brand-name"><?= e((string) config('name')) ?></span>
            </div>

            <?php include __DIR__ . '/../partials/nav.php'; ?>
        </aside>
    <?php endif; ?>

    <div class="main">
        <header class="topbar">
            <?php if ($navStyle === 'sidebar'): ?>
                <button type="button" class="btn btn-icon" data-sidebar-toggle
                        aria-label="<?= e(__('ui.collapse')) ?>" title="<?= e(__('ui.collapse')) ?>">
                    <?= icon('menu') ?>
                </button>
            <?php else: ?>
                <?php /* ‼️ โหมดแถบบน แบรนด์กับเมนูอยู่บนแถบเดียวกัน ไม่มีปุ่มพับเพราะไม่มีอะไรให้พับ */ ?>
                <a class="brand" href="<?= e(url('/admin')) ?>">
                    <span class="brand-name"><?= e((string) config('name')) ?></span>
                </a>

                <?php include __DIR__ . '/../partials/nav.php'; ?>
            <?php endif; ?>

            <?php
            /* ‼️ ห้ามใส่ชื่อหน้าที่นี่ — ชื่อหน้าอยู่ในหัวเพจ (partials/page-head.php) ที่เดียว
               ใส่ทั้งสองที่แล้วจะซ้ำ และการมีชื่อหน้าตัวเล็กบน topbar
               ทำให้คนคิดว่ามีหัวเพจแล้ว จึงไม่ทำหัวเพจจริง ซึ่งเกิดขึ้นมาแล้ว */
            ?>
            <div class="row spacer">
                <form method="POST" action="<?= e(url('/admin/theme')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="theme" value="<?= e($currentTheme === 'dark' ? 'light' : 'dark') ?>">
                    <button type="submit" class="btn btn-icon"
                            aria-label="<?= e(__('ui.theme')) ?>" title="<?= e(__('ui.theme')) ?>">
                        <?= icon($currentTheme === 'dark' ? 'sun' : 'moon') ?>
                    </button>
                </form>

                <form method="POST" action="<?= e(url('/admin/language')) ?>" class="row">
                    <?= csrf_field() ?>
                    <label class="visually-hidden" for="locale-select"><?= e(__('ui.language')) ?></label>
                    <select id="locale-select" name="locale" data-auto-submit>
                        <?php foreach (Lang::enabled() as $code): ?>
                            <option value="<?= e($code) ?>"<?php if ($code === locale()): ?> selected<?php endif; ?>>
                                <?= e(strtoupper($code)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if (Auth::check()): ?>
                    <form method="POST" action="<?= e(url('/admin/logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-icon"
                                aria-label="<?= e(__('auth.logout')) ?>" title="<?= e(__('auth.logout')) ?>">
                            <?= icon('logout') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <main class="content">
            <?php
            /* ‼️ ความกว้างเป็นเรื่องของ "หน้า" ไม่ใช่ของ component
               หนึ่งหน้ามีคอลัมน์เดียว ความกว้างเดียว อยู่กลางพื้นที่
               Controller ส่งมาเป็นคำเดียว: form | list | wide

               ปล่อยให้แต่ละ component คุมความกว้างตัวเองแล้วจะได้ตารางที่ยืดจนปุ่ม
               ห่างจากข้อมูล 400px หรือได้ที่ว่างโล่งข้างขวาข้างเดียว
               ทั้งสองอาการไม่มีใครสั่งให้เกิด มันเป็นผลข้างเคียงของการไม่มีใครคุมความกว้างของหน้า */
            ?>
            <div class="page" data-width="<?= e($pageWidth ?? 'list') ?>"
                 data-page="<?= e(page_public() ? 'public' : 'app') ?>">
                <?php include __DIR__ . '/../partials/page-head.php'; ?>
                <?= $content ?? '' ?>
            </div>
        </main>

        <?php include __DIR__ . '/../partials/footer.php'; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/support.php'; ?>

<?php include __DIR__ . '/../partials/js-lang.php'; ?>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
