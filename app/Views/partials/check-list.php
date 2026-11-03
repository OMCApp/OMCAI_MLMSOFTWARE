<?php
/**
 * รายการข้อดีมีวงเครื่องหมายถูกนำหน้า — ใช้ใน hero · split · dashboard_showcase · about_teaser
 *
 * ‼️ เครื่องหมายถูกเป็นของตกแต่ง (aria-hidden) — ความหมายอยู่ในข้อความ ไม่ใช่ในสี
 *
 * ตัวแปรที่รับ: $checkListItems — list<string>
 */

declare(strict_types=1);

$checkListItems = array_values(array_filter(
    array_map('strval', (array) ($checkListItems ?? [])),
    static fn (string $text): bool => $text !== ''
));

if ($checkListItems !== []): ?>
<ul class="check-list">
    <?php foreach ($checkListItems as $checkListText): ?>
        <li><span class="check-dot" aria-hidden="true"><?= icon('tick', 'icon-sm') ?></span><span><?= e($checkListText) ?></span></li>
    <?php endforeach; ?>
</ul>
<?php endif;

$checkListItems = [];
