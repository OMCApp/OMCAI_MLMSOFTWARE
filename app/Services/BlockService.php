<?php
/**
 * เตรียมรายการบล็อกของหน้า (page builder แบบมีชนิด) ก่อนส่งให้ partials/blocks.php
 *
 * ‼️ ชนิดบล็อกมีชุดเดียวทั้งระบบ — ตรงกับ PLAN.md "บล็อกของหน้า" และไฟล์ใน app/Views/partials/blocks/
 *    ชนิดที่ไม่รู้จักถูกตัดทิ้งที่นี่ ไม่ปล่อยให้ view ไปหาไฟล์จากค่าที่มาจาก DB
 *
 * ‼️ ทุกที่ที่แสดงหน้าจากบล็อก (preview ตอนนี้ · ตัวแสดงหน้าของ Phase 2 · preview ใน CMS Phase 3)
 *    ต้องผ่าน prepare() ตัวนี้ตัวเดียว — ตัวเลือกของฟอร์ม lead และ CSRF อยู่ที่นี่จุดเดียว
 */

declare(strict_types=1);

namespace App\Services;

final class BlockService
{
    public const TYPES = [
        'hero', 'trust_bar', 'stats', 'feature_grid', 'split', 'plan_cards', 'industry_grid',
        'dashboard_showcase', 'about_teaser', 'testimonials', 'faq', 'cta_band', 'rich_text',
        'steps', 'comparison_table', 'lead_form', 'case_studies',
    ];

    /**
     * ‼️ ค่าที่ฟอร์ม lead ส่งได้ — เก็บเป็น key ภาษาอังกฤษ (กฎเหล็กข้อ 11) แปลตอนแสดง
     *    ตัวรับฟอร์ม (Phase 4) ต้อง validate กับรายการนี้ชุดเดียวกัน — ห้ามประกาศซ้ำที่อื่น
     */
    public const LEAD_OPTIONS = [
        'business_type'   => ['supplements', 'cosmetics', 'health_wellness', 'distributor', 'franchise', 'international', 'startup', 'other'],
        'has_system'      => ['no', 'yes'],
        'need'            => ['ready', 'custom', 'migration', 'consult'],
        'member_estimate' => ['under_1k', '1k_10k', '10k_50k', 'over_50k', 'unknown'],
    ];

    /** ชื่อกลุ่มใน lang/ ของแต่ละช่อง */
    private const LEAD_LANG = [
        'business_type'   => 'lead.business_types',
        'has_system'      => 'lead.has_system_options',
        'need'            => 'lead.needs',
        'member_estimate' => 'lead.member_ranges',
    ];

    /**
     * @param  list<array<string, mixed>> $blocks
     * @return list<array<string, mixed>>
     */
    public static function prepare(array $blocks): array
    {
        $out   = [];
        $years = self::yearsInBusiness();

        foreach ($blocks as $block) {
            if (!is_array($block) || !in_array($block['type'] ?? '', self::TYPES, true)) {
                continue;
            }

            // {years} = จำนวนปีที่ดำเนินธุรกิจ คำนวณจากวันจดทะเบียน — ไม่เขียนตัวเลขตายตัวในเนื้อหา (QUESTIONS.md)
            $block = self::fillYears($block, $years);

            if ($block['type'] === 'lead_form') {
                $block['options']     = self::leadOptions();
                $block['privacy_url'] = SiteService::privacyUrl();
                $block['tracking']    = LeadTrackingService::formFields($_GET, isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : null);
            }

            /* ‼️ ตัวเลข / โลโก้ลูกค้า / รีวิว มาจากแหล่งที่ "ยืนยันแล้ว" เท่านั้น (Phase 3 · BRIEF ห้ามของปลอม)
                  บล็อกที่บันทึกจาก CMS ไม่มีรายการเหล่านี้ — เติมที่นี่ · ไม่มีของจริง = partial ไม่วาดอะไร */
            // ‼️ ตัวเลขของบริษัทมาจาก Settings ที่ติ๊ก "ยืนยันแล้ว" เท่านั้น — รายการที่ฝังในบล็อก (เนื้อหาเก่า/config) ถูกทิ้งเสมอ
            if ($block['type'] === 'stats') {
                $block['items'] = SettingService::verifiedStats();
            }
            if ($block['type'] === 'trust_bar' && empty($block['logos'])) {
                $block['logos'] = SettingService::verifiedLogos();
            }
            // ผลงานลูกค้า: เฉพาะที่เผยแพร่ + ได้รับอนุญาต (Phase 3) · ไม่มี = บล็อกไม่วาดอะไร (หน้ามีข้อความ "กำลังรวบรวม" อยู่แล้ว)
            if ($block['type'] === 'case_studies') {
                $block['items'] = (new SocialProofService())->caseStudyItems();
            }
            if ($block['type'] === 'testimonials' && empty($block['items'])) {
                $block['items'] = (new SocialProofService())->testimonialItems();
            }

            $out[] = $block;
        }

        return $out;
    }

    /**
     * จำนวนปีเต็มนับจากวันจดทะเบียนบริษัท (3 เม.ย. 2552 ผู้ใช้ยืนยัน — QUESTIONS.md) · ค่าอยู่ที่ config/site.php brand.founded
     */
    public static function yearsInBusiness(?\DateTimeImmutable $today = null): int
    {
        $founded = new \DateTimeImmutable(SiteService::founded());

        return $founded->diff($today ?? new \DateTimeImmutable('today'))->y;
    }

    /**
     * @param  array<string|int, mixed> $value
     * @return array<string|int, mixed>
     */
    private static function fillYears(array $value, int $years): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::fillYears($item, $years);
            } elseif (is_string($item) && str_contains($item, '{years}')) {
                $value[$key] = $item === '{years}' ? $years : str_replace('{years}', (string) $years, $item);
            }
        }

        return $value;
    }

    /** @return array<string, array<string, string>> key => ข้อความที่แปลแล้ว */
    public static function leadOptions(): array
    {
        $options = [];

        foreach (self::LEAD_OPTIONS as $field => $keys) {
            foreach ($keys as $key) {
                $options[$field][$key] = __(self::LEAD_LANG[$field] . '.' . $key);
            }
        }

        return $options;
    }
}
