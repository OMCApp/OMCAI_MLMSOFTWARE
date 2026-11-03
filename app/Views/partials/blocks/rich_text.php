<?php
/**
 * บล็อก rich_text — เนื้อความยาว (บทความสั้นในหน้า landing)
 *
 * $block = [
 *   'title'      => string   หัวข้อ (h2) — ไม่บังคับ
 *   'paragraphs' => list<string>   ย่อหน้าข้อความล้วน — ผ่าน e() ทุกตัว
 *   'list'       => list<string>   รายการหัวข้อย่อย — ไม่บังคับ
 * ]
 *
 * ‼️ ยังไม่รับ HTML อิสระโดยตั้งใจ — ทุกค่าผ่าน e() (กฎเหล็กข้อ 6)
 *    Phase 3 ถ้าจะให้ editor ใส่ HTML ต้องมี sanitizer (allowlist แท็ก · ตัด on* · ตัด javascript: · ห้าม h1)
 *    ใน Service และส่ง HTML ที่ผ่านแล้วเข้ามาด้วยกลไกที่ชุดตรวจ output-escape รู้จัก — ห้ามพิมพ์ input ดิบที่นี่
 */

declare(strict_types=1);

$richBlock      = (array) ($block ?? []);
$richParagraphs = array_values(array_filter(array_map('strval', (array) ($richBlock['paragraphs'] ?? [])), 'strlen'));
$richList       = array_values(array_filter(array_map('strval', (array) ($richBlock['list'] ?? [])), 'strlen'));
// ‼️ 'html' ต้องผ่าน App\Services\HtmlSanitizer มาแล้วเท่านั้น (SiteViewService::withPartials ทำให้) — เนื้อหาที่ย้ายจากเว็บเดิม
$richHtml       = (string) ($richBlock['html'] ?? '');

if ($richParagraphs === [] && $richList === [] && $richHtml === '') {
    return;
}
?>
<section class="section">
    <div class="container">
        <div class="prose rich-text reveal">
            <?php if ((string) ($richBlock['title'] ?? '') !== ''): ?>
                <h2><?= e((string) $richBlock['title']) ?></h2>
            <?php endif; ?>
            <?php foreach ($richParagraphs as $richParagraph): ?>
                <p><?= e($richParagraph) ?></p>
            <?php endforeach; ?>
            <?php if ($richList !== []): ?>
                <ul>
                    <?php foreach ($richList as $richPoint): ?>
                        <li><?= e($richPoint) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php
            // sanitize แล้วใน Service (allowlist) — ครอบ e() ซ้ำจะได้แท็กดิบขึ้นเป็นข้อความ
            echo $richHtml;
            ?>
        </div>
    </div>
</section>
