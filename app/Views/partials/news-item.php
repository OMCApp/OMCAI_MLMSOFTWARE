<?php
/**
 * การ์ดบทความแนวนอน — ภาพซ้าย · วันที่ + เวลาอ่าน · หัวข้อ · ย่อหน้านำ · ชิปหมวด/แท็ก · "อ่านต่อ"
 * ใช้ที่หน้ารวมบทความ (/blog/ · หมวด · ผลค้นหา) และ "บทความที่เกี่ยวข้อง" ท้ายบทความ
 *
 * ‼️ ทั้งการ์ดกดได้ด้วยลิงก์เดียว (หัวข้อ — ::after ขยายเต็มการ์ด) · ชิปหมวด/แท็กเป็นลิงก์ของตัวเองซ้อนด้านบน
 *    "อ่านต่อ" เป็นแค่ภาพ (aria-hidden) — ไม่ให้ screen reader ได้ยินลิงก์ "อ่านต่อ" ซ้ำ 10 ครั้งที่ไม่บอกว่าไปไหน
 * ‼️ วันที่ต้องอยู่ในรูปที่ i18n แปลงแล้ว (format_date) · datetime เป็น ISO
 *
 * ตัวแปรที่รับ
 *   $newsItems           [['url','title','lead','date','datetime','minutes','category' => ['name','url']|null,'tags' => list<string>,
 *                          'cover' => [src, srcset, alt, width, height]|null], ...]   (SiteViewService::cards)
 *   $newsItemsEagerFirst bool — รูปการ์ดแรกโหลดทันที + fetchpriority="high" (หน้ารวมที่การ์ดแรกอยู่จอแรก = LCP)
 *                        ค่าเริ่ม false — "บทความที่เกี่ยวข้อง" ท้ายบทความอยู่นอกจอแรก ต้อง lazy
 *   $newsItemsHeading    'h2' (หน้ารวม — ใต้ h1) | 'h3' (ใต้หัวข้อ h2 เช่นบทความที่เกี่ยวข้อง) · ค่าเริ่ม h2
 *   $newsItemsSearchUrl  path ของหน้าค้นหา (แท็ก → ?q=แท็ก) · ค่าเริ่ม /blog/ ของภาษาปัจจุบัน
 *   $filtered            bool — รายการว่างเพราะค้นหา/กรอง (empty-state บอกให้ล้าง)
 */

declare(strict_types=1);

use Core\Path;

$newsItems           = (array) ($newsItems ?? []);
$newsItemsEagerFirst = (bool) ($newsItemsEagerFirst ?? false);
$newsItemsHeading    = in_array($newsItemsHeading ?? 'h2', ['h2', 'h3'], true) ? (string) ($newsItemsHeading ?? 'h2') : 'h2';
$newsItemsSearchUrl  = (string) ($newsItemsSearchUrl ?? \App\Services\LocaleService::localize('/blog/', \App\Services\LocaleService::current()));
$newsItemsFirstKey   = array_key_first($newsItems);
?>
<?php if ($newsItems === []): ?>
    <?php include __DIR__ . '/empty-state.php'; ?>
<?php else: ?>
    <ul class="news-list">
        <?php foreach ($newsItems as $newsItemKey => $newsItem): ?>
            <li class="reveal">
                <article class="news-item">
                    <div class="news-item-media">
                        <?php if (is_array($newsItem['cover'] ?? null)): ?>
                            <?php $siteImage = (array) $newsItem['cover']; $siteImageClass = 'news-item-image';
                                  $siteImageEager = $newsItemsEagerFirst && $newsItemKey === $newsItemsFirstKey;
                                  $siteImageSizes = '(min-width: 760px) 340px, 100vw'; include __DIR__ . '/site-image.php'; ?>
                        <?php else: ?>
                            <?php /* ภาพสำรอง: บทความที่ไม่มีภาพปก — แบรนด์บนพื้นน้ำเงินหมึก (ไม่ใช่ภาพ stock) */ ?>
                            <span class="news-item-fallback" aria-hidden="true">
                                <img src="<?= e(asset('/assets/images/brand/omc-mark.svg')) ?>" alt="" width="300" height="100" loading="lazy">
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="news-item-body">
                        <p class="news-item-meta">
                            <?php if ((string) ($newsItem['date'] ?? '') !== ''): ?>
                                <span><?= icon('calendar', 'icon-sm') ?><time datetime="<?= e((string) ($newsItem['datetime'] ?? '')) ?>"><?= e((string) $newsItem['date']) ?></time></span>
                            <?php endif; ?>
                            <?php if ((int) ($newsItem['minutes'] ?? 0) > 0): ?>
                                <span><?= icon('clock', 'icon-sm') ?><?= e(__('site.read_minutes', ['min' => (string) (int) $newsItem['minutes']])) ?></span>
                            <?php endif; ?>
                        </p>

                        <<?= e($newsItemsHeading) ?> class="news-item-title">
                            <a class="news-item-link" href="<?= e(url((string) $newsItem['url'])) ?>"><?= e((string) $newsItem['title']) ?></a>
                        </<?= e($newsItemsHeading) ?>>

                        <?php if ((string) ($newsItem['lead'] ?? '') !== ''): ?>
                            <p class="news-item-lead"><?= e((string) $newsItem['lead']) ?></p>
                        <?php endif; ?>

                        <div class="news-item-foot">
                            <?php if (is_array($newsItem['category'] ?? null) || !empty($newsItem['tags'])): ?>
                                <ul class="news-item-chips">
                                    <?php if (is_array($newsItem['category'] ?? null)): ?>
                                        <li><a class="news-chip is-category" href="<?= e(url((string) $newsItem['category']['url'])) ?>"><?= e((string) $newsItem['category']['name']) ?></a></li>
                                    <?php endif; ?>
                                    <?php foreach ((array) ($newsItem['tags'] ?? []) as $newsItemTag): ?>
                                        <li><a class="news-chip" href="<?= e(url(Path::encode($newsItemsSearchUrl)) . '?q=' . rawurlencode((string) $newsItemTag)) ?>" rel="nofollow">#<?= e((string) $newsItemTag) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <span class="news-item-more" aria-hidden="true"><?= e(__('site.read_more')) ?><?= icon('arrow-right', 'icon-sm') ?></span>
                        </div>
                    </div>
                </article>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php $newsItemsEagerFirst = false; $newsItemsHeading = 'h2'; $filtered = false; ?>
