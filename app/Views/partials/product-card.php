<?php
/**
 * ตะแกรงการ์ดสินค้าที่มีภาพนำ
 *
 * ‼️ ต่างจาก table.php โดยสิ้นเชิง — ตารางเรียงข้อมูลเป็นแถวให้เทียบค่ากันได้
 *    ตะแกรงนี้เรียงของให้ "ดู" ซึ่งภาพเป็นตัวนำ ไม่ใช่ตัวประกอบ
 *    เอาตารางมาทำแทนแล้วจะได้หน้าที่อ่านเหมือนรายงานสต๊อก
 *
 * ‼️ ทุกใบต้องมีภาพในสัดส่วนเดียวกัน ไม่งั้นขอบล่างของชื่อจะไม่ตรงกันทั้งแถว
 *    ซึ่งเป็นสิ่งแรกที่ตาจับได้ว่าหน้าไม่เรียบร้อย
 *
 * ตัวแปรที่รับ
 *   $productCardItems  [['image' => ..., 'alt' => ..., 'name' => ..., 'meta' => ..., 'url' => ...], ...]
 */

declare(strict_types=1);

$productCardItems = (array) ($productCardItems ?? []);
?>
<?php if ($productCardItems === []): ?>
    <?php include __DIR__ . '/empty-state.php'; ?>
<?php else: ?>
    <ul class="product-grid">
        <?php foreach ($productCardItems as $productCardItem): ?>
            <li>
                <a class="product-card" href="<?= e(url((string) $productCardItem['url'])) ?>">
                    <img class="product-card-image"
                         src="<?= e(asset('/assets/images/' . (string) $productCardItem['image'])) ?>"
                         alt="<?= e((string) $productCardItem['alt']) ?>"
                         width="1200" height="1600" loading="lazy">
                    <span class="product-card-name"><?= e((string) $productCardItem['name']) ?></span>
                    <span class="product-card-meta"><?= e((string) $productCardItem['meta']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
