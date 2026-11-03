<?php
/**
 * layout สำหรับหน้าที่ยังไม่ล็อกอิน
 *
 * ‼️ ยังต้องมี footer, ปุ่มแจ้งปัญหา และสลับภาษา/โหมด
 *    เพราะผู้ใช้ที่เข้าระบบไม่ได้ก็ต้องแจ้งปัญหาและอ่านคู่มือได้
 */

declare(strict_types=1);

use Core\Lang;



$currentTheme = theme();
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

<header class="topbar">
    <div class="brand">
        <?= icon('dashboard') ?>
        <span class="brand-name"><?= e((string) config('name')) ?></span>
    </div>

    <div class="row spacer">
        <form method="POST" action="<?= e(url('/theme')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="theme" value="<?= e($currentTheme === 'dark' ? 'light' : 'dark') ?>">
            <button type="submit" class="btn btn-icon"
                    aria-label="<?= e(__('ui.theme')) ?>" title="<?= e(__('ui.theme')) ?>">
                <?= icon($currentTheme === 'dark' ? 'sun' : 'moon') ?>
            </button>
        </form>

        <form method="POST" action="<?= e(url('/language')) ?>">
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
    </div>
</header>

<main class="centered">
    <?= $content ?? '' ?>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/support.php'; ?>

<?php include __DIR__ . '/../partials/js-lang.php'; ?>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
