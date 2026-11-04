<?php
/**
 * รูปของหน้าสาธารณะ — width/height กัน CLS · srcset จากรูปย่อของคลังสื่อ · lazy เว้นแต่เป็นรูปส่วนเปิด (LCP)
 *
 * ตัวแปรที่รับ:
 *   $siteImage      = ['src','alt','width','height','srcset' => [ความกว้าง => url]]   (MediaService::imageData)
 *   $siteImageClass = string            class ของ <img>
 *   $siteImageSizes = string            ค่า sizes เช่น '(min-width: 960px) 40vw, 100vw' — ไม่บังคับ
 *   $siteImageEager = bool              true = รูปส่วนเปิด: fetchpriority="high" ไม่ lazy
 */

declare(strict_types=1);

$siteImage      = (array) ($siteImage ?? []);
$siteImageSet   = [];
foreach ((array) ($siteImage['srcset'] ?? []) as $siteImageWidth => $siteImageUrl) {
    if ((int) $siteImageWidth > 0 && is_string($siteImageUrl) && $siteImageUrl !== '') {
        $siteImageSet[] = asset($siteImageUrl) . ' ' . (int) $siteImageWidth . 'w';
    }
}

if ((string) ($siteImage['src'] ?? '') !== ''): ?>
<img class="<?= e((string) ($siteImageClass ?? '')) ?>" src="<?= e(asset((string) $siteImage['src'])) ?>"
     <?php if ($siteImageSet !== []): ?>srcset="<?= e(implode(', ', $siteImageSet)) ?>" sizes="<?= e((string) (($siteImageSizes ?? '') !== '' ? $siteImageSizes : '100vw')) ?>"<?php endif; ?>
     alt="<?= e((string) ($siteImage['alt'] ?? '')) ?>"
     width="<?= e((string) (int) ($siteImage['width'] ?? 1200)) ?>"
     height="<?= e((string) (int) ($siteImage['height'] ?? 800)) ?>"
     <?php if (!empty($siteImageEager)): ?>fetchpriority="high"<?php else: ?>loading="lazy"<?php endif; ?> decoding="async">
<?php endif;

$siteImage = []; $siteImageClass = ''; $siteImageSizes = ''; $siteImageEager = false;
