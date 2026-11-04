<?php
/**
 * ตัวสลับภาษา (TH | EN | 中文) — header (จอใหญ่ + drawer มือถือ) และ footer
 *
 * ‼️ ลิงก์ไปหน้าเดียวกันในภาษานั้นถ้ามีคำแปลจริง ไม่มีไปหน้าแรกของภาษานั้น (App\Services\SiteService::layout)
 *    ไม่มี JS · ไม่ redirect อัตโนมัติตามภาษาเบราว์เซอร์
 * ‼️ ทุกลิงก์มี hreflang + lang (ป้ายเขียนเป็นภาษานั้นเอง screen reader ต้องอ่านด้วยเสียงของภาษานั้น)
 *    และ aria-current บนภาษาปัจจุบัน — กลุ่มลิงก์อยู่ใน <nav> ที่มีชื่อ
 *
 * ตัวแปรที่รับ: $langSwitchItems  list<[code,label,url,current,lang,name]>
 *               $langSwitchClass  class เพิ่ม (เช่น 'is-footer') — ไม่บังคับ
 */

declare(strict_types=1);

$langSwitchItems = (array) ($langSwitchItems ?? []);
$langSwitchClass = trim('lang-switch ' . (string) ($langSwitchClass ?? ''));

if (count($langSwitchItems) < 2) {
    return;
}
?>
<nav class="<?= e($langSwitchClass) ?>" aria-label="<?= e(__('site.lang_switch')) ?>">
    <?= icon('globe', 'lang-switch-icon') ?>
    <ul class="lang-switch-list">
        <?php foreach ($langSwitchItems as $langSwitchItem): ?>
            <li>
                <a class="lang-switch-link" href="<?= e(url((string) $langSwitchItem['url'])) ?>"
                   hreflang="<?= e((string) $langSwitchItem['lang']) ?>" lang="<?= e((string) $langSwitchItem['lang']) ?>"
                   title="<?= e((string) $langSwitchItem['name']) ?>"
                   <?php if (!empty($langSwitchItem['current'])): ?>aria-current="true"<?php endif; ?>>
                    <?= e((string) $langSwitchItem['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php
$langSwitchClass = '';
