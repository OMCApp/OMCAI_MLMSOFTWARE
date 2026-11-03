<?php
/**
 * JSON-LD ของหน้าสาธารณะ — ข้อมูลมาจาก App\Services\SchemaService::graph()
 *
 * ‼️ ห้ามครอบ e() — ข้างใน <script> เบราว์เซอร์ไม่ถอด &quot; กลับ JSON จะพังทั้งก้อน
 *    ความปลอดภัยมาจาก SchemaService::encode() ที่แปลง < > & เป็น < > &
 *    ข้อความจาก CMS จึงปิด </script> ก่อนเวลาไม่ได้
 *
 * ตัวแปรที่รับ: $jsonLdGraph  ['@context' => ..., '@graph' => [...]]
 */

declare(strict_types=1);

use App\Services\SchemaService;

$jsonLdGraph = (array) ($jsonLdGraph ?? []);

if ($jsonLdGraph === []) {
    return;
}
?>
<script type="application/ld+json"><?php echo SchemaService::encode($jsonLdGraph); ?></script>
