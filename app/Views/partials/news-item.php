<?php
/**
 * รายการข่าว — ภาพ วันที่ หัวข้อ ย่อหน้านำ
 *
 * ‼️ ไม่ใช่ตาราง — ข่าวไม่ได้ถูกอ่านเพื่อเทียบค่ากัน แต่ถูกอ่านเพื่อเลือกว่าจะเปิดอันไหน
 *    หัวข้อจึงต้องใหญ่กว่าวันที่ชัดเจน และย่อหน้านำต้องมีที่พอให้อ่านจบประโยค
 *
 * ‼️ วันที่ต้องอยู่ในรูปที่ i18n แปลงแล้ว ห้ามส่งวันดิบจากฐานข้อมูลเข้ามา
 *
 * ตัวแปรที่รับ
 *   $newsItems  [['image' => ..., 'alt' => ..., 'date' => ..., 'title' => ..., 'lead' => ..., 'url' => ...], ...]
 *               'image' ไม่บังคับ — ข่าวที่ไม่มีภาพยังต้องแสดงได้
 *               'cover' ไม่บังคับ — [src (path เต็ม encode แล้ว), alt, width, height] ภาพเด่นของบทความจาก media library
 */

declare(strict_types=1);

$newsItems = (array) ($newsItems ?? []);
?>
<?php if ($newsItems === []): ?>
    <?php include __DIR__ . '/empty-state.php'; ?>
<?php else: ?>
    <ul class="news-list">
        <?php foreach ($newsItems as $newsItem): ?>
            <li>
                <a class="news-item" href="<?= e(url((string) $newsItem['url'])) ?>">
                    <?php if (is_array($newsItem['cover'] ?? null)): ?>
                        <img class="news-item-image" src="<?= e(url((string) $newsItem['cover']['src'])) ?>"
                             alt="<?= e((string) ($newsItem['cover']['alt'] ?? '')) ?>"
                             width="<?= e((string) (int) ($newsItem['cover']['width'] ?? 1200)) ?>" height="<?= e((string) (int) ($newsItem['cover']['height'] ?? 675)) ?>"
                             loading="lazy" decoding="async">
                    <?php elseif (($newsItem['image'] ?? '') !== ''): ?>
                        <img class="news-item-image"
                             src="<?= e(asset('/assets/images/' . (string) $newsItem['image'])) ?>"
                             alt="<?= e((string) ($newsItem['alt'] ?? '')) ?>"
                             width="1600" height="900" loading="lazy">
                    <?php endif; ?>

                    <span class="news-item-body">
                        <span class="news-item-date"><?= e((string) $newsItem['date']) ?></span>
                        <span class="news-item-title"><?= e((string) $newsItem['title']) ?></span>
                        <span class="news-item-lead"><?= e((string) $newsItem['lead']) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
