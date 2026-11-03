<?php
/**
 * ปุ่มลิงก์ทรงแคปซูลของหน้าสาธารณะ (ลายเซ็นของ DESIGN.md — ปุ่มหลักส้มเข้ม + ลูกศร →)
 *
 * ‼️ รับเป็น "ข้อมูลของปุ่ม" ไม่ใช่ HTML — ข้อความผ่าน e() ทุกตัว (กฎเหล็กข้อ 6)
 *
 * ตัวแปรที่รับ:
 *   $siteButton = [
 *     'label' => string,
 *     'url'   => string   path ภายในแอป / tel: / mailto: / https:
 *     'style' => 'primary' | 'secondary' | 'outline' | 'light' | 'ghost-light'   (ค่าเริ่มต้น primary)
 *                outline = ขอบส้มบนพื้นขาว · light / ghost-light = บนแถบสีเข้ม (CTA band)
 *     'icon'  => ชื่อไอคอนนำหน้า — ไม่บังคับ
 *     'arrow' => bool  ลูกศรท้ายปุ่ม — ค่าเริ่มต้น true เฉพาะ primary / light
 *     'size'  => 'md' | 'lg'
 *   ]
 */

declare(strict_types=1);

$siteButton      = (array) ($siteButton ?? []);
$siteButtonStyle = (string) ($siteButton['style'] ?? 'primary');
$siteButtonStyle = in_array($siteButtonStyle, ['primary', 'secondary', 'outline', 'light', 'ghost-light'], true)
    ? $siteButtonStyle : 'primary';
$siteButtonArrow = (bool) ($siteButton['arrow'] ?? in_array($siteButtonStyle, ['primary', 'light'], true));
$siteButtonIcon  = (string) ($siteButton['icon'] ?? '');
$siteButtonClass = 'btn btn-pill btn-' . $siteButtonStyle . (($siteButton['size'] ?? 'md') === 'lg' ? ' btn-lg' : '');

if ((string) ($siteButton['label'] ?? '') !== ''): ?>
<a class="<?= e($siteButtonClass) ?>" href="<?= e(url((string) ($siteButton['url'] ?? '/'))) ?>">
    <?php if ($siteButtonIcon !== ''): ?><?= icon($siteButtonIcon) ?><?php endif; ?>
    <span><?= e((string) $siteButton['label']) ?></span>
    <?php if ($siteButtonArrow): ?><?= icon('arrow-right', 'btn-arrow') ?><?php endif; ?>
</a>
<?php endif;

$siteButton = [];
