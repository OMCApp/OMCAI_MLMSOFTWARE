<?php
/**
 * ค่าเริ่มต้นของ header / footer / ข้อมูลติดต่อ ของเว็บสาธารณะ
 *
 * ‼️ ไฟล์นี้เป็น "ค่าตั้งต้นที่เดียว" จนกว่า CMS จะมี (Phase 2/3)
 *    เมื่อมีตาราง menus / settings แล้ว App\Services\SiteService จะอ่านจาก DB ก่อน
 *    แล้วตกมาใช้ค่าที่นี่เฉพาะที่ยังไม่ได้ตั้ง — ห้ามคัดลอกค่าเหล่านี้ไปไว้ใน view
 *
 * ‼️ ป้ายเมนูและข้อความเกี่ยวกับบริษัทเป็น "เนื้อหา" ไม่ใช่ข้อความ UI
 *    จึงไม่อยู่ใน lang/ — CMS ต้องแก้ได้ (PLAN.md: เนื้อหาเว็บอยู่ใน DB ไม่ใช่ lang file)
 *
 * ‼️ URL ทุกตัวเป็น path ภายในแอปตาม URL map ใน project/PLAN.md — view ส่งผ่าน url() เสมอ
 *
 * ข้อมูลติดต่อดึงจากหน้า /contact/ ของเว็บเดิม (2026-11-03) — ยืนยันกับผู้ใช้ที่ QUESTIONS.md
 */

declare(strict_types=1);

return [

    'brand' => [
        'name'       => 'OMC MLM Software',
        'legal_name' => 'บริษัท ออนไลน์ มาร์เก็ตติ้ง คอมมิวนิเคชั่น จำกัด',
        'about'      => 'OMC (Online Marketing Communication) พัฒนาระบบขายตรงและ MLM สำหรับธุรกิจไทย '
                      . 'ตั้งแต่ระบบสมาชิก สายงาน คำนวณคอมมิชชัน ไปจนถึงหลังร้านและรายงาน '
                      . 'ปรับแต่งตามแผนธุรกิจของแต่ละบริษัท',
        // โลโก้ทุกแบบอยู่ใน public/assets/images/brand/ — วาดจาก project/design/refs/logo-omc.png
        'logo'       => '/assets/images/brand/omc-logo-horizontal.svg',
        'logo_dark'  => '/assets/images/brand/omc-logo-dark.svg',
        'logo_size'  => ['width' => 760, 'height' => 100],
        'logo_dark_size' => ['width' => 300, 'height' => 122],
    ],

    'contact' => [
        'phone'      => '099-109-5544',
        'phone_e164' => '+66991095544',
        'email'      => 'sale@omc.co.th',
        'line_id'    => 'omc54',
        'line_url'   => 'https://line.me/ti/p/~omc54',
        'facebook'   => 'https://www.facebook.com/mlmsoftwarebyomc',
        'address'    => 'อาคารขจรประศาสน์ เลขที่ 3/3 ซอยเลี่ยงเมืองนนทบุรี 14 ตำบลท่าทราย อำเภอเมืองนนทบุรี จังหวัดนนทบุรี 11000',
    ],

    // หน้านโยบายความเป็นส่วนตัว — ฟอร์ม lead ลิงก์ไปที่นี่ (URL เดิมของเว็บเก่า ห้ามเปลี่ยน)
    'privacy_url' => '/นโยบายความเป็นส่วนตัวข/',

    // ปุ่มหลักบน header — ป้ายมาจาก lang (site.cta_demo) เพราะเป็นข้อความ UI ที่ใช้ทุกหน้า
    'cta' => ['url' => '/demo/'],

    /* เมนูหลัก — children ไม่ว่าง = เมนูกาง (mega menu)
       รายการแรกของ children ควรเป็นหน้ารวมของหัวข้อนั้น เพราะหัวเมนูที่กางได้เป็นปุ่ม ไม่ใช่ลิงก์ */
    'nav' => [
        ['label' => 'หน้าแรก', 'url' => '/'],
        [
            'label'    => 'MLM Software',
            'url'      => '/mlm-software-omc/',
            'children' => [
                ['label' => 'MLM Software by OMC', 'url' => '/mlm-software-omc/', 'desc' => 'ภาพรวมระบบขายตรงครบวงจร', 'icon' => 'dashboard'],
                ['label' => 'ฟีเจอร์ทั้งหมด', 'url' => '/features/', 'desc' => 'สมาชิก สายงาน คอมมิชชัน หลังร้าน', 'icon' => 'layers'],
                ['label' => 'ระบบคำนวณคอมมิชชัน', 'url' => '/commission-system/', 'desc' => 'คำนวณโบนัสอัตโนมัติตามแผน', 'icon' => 'calculator'],
                ['label' => 'ระบบสมาชิกขายตรง', 'url' => '/member-system/', 'desc' => 'สมัคร สายงาน ข้อมูลสมาชิก', 'icon' => 'network'],
                ['label' => 'ย้ายระบบ MLM เดิม', 'url' => '/mlm-migration/', 'desc' => 'ย้ายข้อมูลสมาชิกและยอดจากระบบเก่า', 'icon' => 'migrate'],
                ['label' => 'ขอใบอนุญาต สคบ.', 'url' => '/ocpb-mlm/', 'desc' => 'ระบบที่รองรับเอกสารสำหรับ สคบ.', 'icon' => 'shield'],
            ],
        ],
        [
            'label'    => 'โซลูชันธุรกิจ',
            'url'      => '/services/',
            'children' => [
                ['label' => 'บริการทั้งหมด', 'url' => '/services/', 'desc' => 'เลือกตามประเภทธุรกิจ', 'icon' => 'grid'],
                ['label' => 'อาหารเสริม', 'url' => '/solutions/supplements/', 'icon' => 'leaf'],
                ['label' => 'เครื่องสำอางและสกินแคร์', 'url' => '/solutions/cosmetics/', 'icon' => 'sparkle'],
                ['label' => 'สุขภาพและความงาม', 'url' => '/solutions/health-wellness/', 'icon' => 'heart'],
                ['label' => 'ค้าส่ง / ตัวแทนจำหน่าย', 'url' => '/solutions/distributor/', 'icon' => 'box'],
                ['label' => 'แฟรนไชส์', 'url' => '/solutions/franchise/', 'icon' => 'store'],
                ['label' => 'ธุรกิจต่างประเทศ', 'url' => '/solutions/international/', 'icon' => 'globe'],
                ['label' => 'ระบบออกแบบเฉพาะ (Custom)', 'url' => '/solutions/custom/', 'icon' => 'sliders'],
            ],
        ],
        [
            'label'    => 'แผนรายได้',
            'url'      => '/compensation-plans/',
            'children' => [
                ['label' => 'แผนรายได้ทั้งหมด', 'url' => '/compensation-plans/', 'desc' => 'เปรียบเทียบทุกแผน', 'icon' => 'layers'],
                ['label' => 'Binary', 'url' => '/compensation-plans/binary/', 'icon' => 'plan-binary'],
                ['label' => 'Unilevel', 'url' => '/compensation-plans/unilevel/', 'icon' => 'plan-unilevel'],
                ['label' => 'Matrix', 'url' => '/compensation-plans/matrix/', 'icon' => 'plan-matrix'],
                ['label' => 'Board', 'url' => '/compensation-plans/board/', 'icon' => 'plan-board'],
                ['label' => 'Stair Step', 'url' => '/compensation-plans/stair-step/', 'icon' => 'plan-stair'],
                ['label' => 'Hybrid', 'url' => '/compensation-plans/hybrid/', 'icon' => 'plan-hybrid'],
            ],
        ],
        ['label' => 'ผลงาน / ลูกค้า', 'url' => '/portfolio/'],
        ['label' => 'บทความ', 'url' => '/blog/'],
        ['label' => 'เกี่ยวกับเรา', 'url' => '/about/'],
    ],

    'footer' => [
        'columns' => [
            [
                'heading' => 'ซอฟต์แวร์',
                'links'   => [
                    ['label' => 'MLM Software by OMC', 'url' => '/mlm-software-omc/'],
                    ['label' => 'ฟีเจอร์', 'url' => '/features/'],
                    ['label' => 'แผนรายได้', 'url' => '/compensation-plans/'],
                    ['label' => 'ราคา / ขอใบเสนอราคา', 'url' => '/pricing/'],
                    ['label' => 'ขอ Demo', 'url' => '/demo/'],
                ],
            ],
            [
                'heading' => 'โซลูชัน',
                'links'   => [
                    ['label' => 'อาหารเสริม', 'url' => '/solutions/supplements/'],
                    ['label' => 'เครื่องสำอาง', 'url' => '/solutions/cosmetics/'],
                    ['label' => 'สุขภาพและความงาม', 'url' => '/solutions/health-wellness/'],
                    ['label' => 'ตัวแทนจำหน่าย', 'url' => '/solutions/distributor/'],
                    ['label' => 'ย้ายระบบเดิม', 'url' => '/mlm-migration/'],
                ],
            ],
            [
                'heading' => 'บริษัท',
                'links'   => [
                    ['label' => 'เกี่ยวกับ OMC', 'url' => '/about/'],
                    ['label' => 'ผลงาน', 'url' => '/portfolio/'],
                    ['label' => 'บทความ', 'url' => '/blog/'],
                    ['label' => 'คำถามที่พบบ่อย', 'url' => '/faq/'],
                    ['label' => 'ช่วยเหลือ', 'url' => '/support/'],
                    ['label' => 'ติดต่อเรา', 'url' => '/contact/'],
                ],
            ],
        ],
        'legal' => [
            ['label' => 'นโยบายความเป็นส่วนตัว', 'url' => '/นโยบายความเป็นส่วนตัวข/'],
            ['label' => 'นโยบายการใช้คุกกี้', 'url' => '/นโยบายการใช้คุกกี้-cookies-policy/'],
        ],
    ],
];
