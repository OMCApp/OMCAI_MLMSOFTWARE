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
        'steps', 'comparison_table', 'lead_form',
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
        $out = [];

        foreach ($blocks as $block) {
            if (!is_array($block) || !in_array($block['type'] ?? '', self::TYPES, true)) {
                continue;
            }

            if ($block['type'] === 'lead_form') {
                $block['options']     = self::leadOptions();
                $block['privacy_url'] = SiteService::privacyUrl();
            }

            /* ‼️ ตัวเลข / โลโก้ลูกค้า / รีวิว มาจากแหล่งที่ "ยืนยันแล้ว" เท่านั้น (Phase 3 · BRIEF ห้ามของปลอม)
                  บล็อกที่บันทึกจาก CMS ไม่มีรายการเหล่านี้ — เติมที่นี่ · ไม่มีของจริง = partial ไม่วาดอะไร */
            if ($block['type'] === 'stats' && empty($block['items'])) {
                $block['items'] = SettingService::verifiedStats();
            }
            if ($block['type'] === 'trust_bar' && empty($block['logos'])) {
                $block['logos'] = SettingService::verifiedLogos();
            }
            if ($block['type'] === 'testimonials' && empty($block['items'])) {
                $block['items'] = (new SocialProofService())->testimonialItems();
            }

            $out[] = $block;
        }

        return $out;
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
