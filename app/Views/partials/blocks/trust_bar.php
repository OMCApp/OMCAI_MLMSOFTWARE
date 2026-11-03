<?php
/**
 * บล็อก trust_bar — แถบโลโก้ลูกค้า
 *
 * $block = [
 *   'title' => string   ข้อความด้านซ้าย เช่น "องค์กรที่ใช้ระบบของ OMC"
 *   'logos' => list<['src','alt','width','height','url' => ไม่บังคับ]>
 * ]
 *
 * ‼️ ไม่มีโลโก้ = ไม่วาดอะไรเลย ไม่ใช่วาดกรอบเปล่า
 *    brief ห้ามโลโก้ลูกค้าที่ไม่ได้รับอนุญาต — CMS ใส่ได้เฉพาะของจริง (QUESTIONS.md)
 */

declare(strict_types=1);

$trustBlock = (array) ($block ?? []);
$trustLogos = array_values(array_filter(
    (array) ($trustBlock['logos'] ?? []),
    static fn ($logo): bool => is_array($logo) && (string) ($logo['src'] ?? '') !== ''
));

if ($trustLogos === []) {
    return;
}
?>
<section class="trust-bar" aria-label="<?= e((string) ($trustBlock['title'] ?? __('site.trust_label'))) ?>">
    <div class="container trust-bar-inner reveal">
        <?php if ((string) ($trustBlock['title'] ?? '') !== ''): ?>
            <p class="trust-bar-title"><?= e((string) $trustBlock['title']) ?></p>
        <?php endif; ?>
        <ul class="trust-bar-logos">
            <?php foreach ($trustLogos as $trustLogo): ?>
                <li>
                    <?php if ((string) ($trustLogo['url'] ?? '') !== ''): ?><a href="<?= e(url((string) $trustLogo['url'])) ?>" rel="noopener"><?php endif; ?>
                    <img src="<?= e(asset((string) $trustLogo['src'])) ?>" alt="<?= e((string) ($trustLogo['alt'] ?? '')) ?>"
                         width="<?= e((string) (int) ($trustLogo['width'] ?? 160)) ?>" height="<?= e((string) (int) ($trustLogo['height'] ?? 64)) ?>"
                         loading="lazy" decoding="async">
                    <?php if ((string) ($trustLogo['url'] ?? '') !== ''): ?></a><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
