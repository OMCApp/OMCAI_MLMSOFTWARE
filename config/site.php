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
        // วันจดทะเบียนบริษัท (ผู้ใช้ยืนยัน 2026-11-03) — {years} ในเนื้อหาคำนวณจากวันนี้
        'founded'    => '2009-04-03',
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

    // ผู้รับอีเมลแจ้งเตือน lead ใหม่ (ผู้ใช้ยืนยัน 2026-11-03) — settings lead.notify_email ใน CMS ทับค่านี้ได้
    'lead_email' => 'sale@omc.co.th',

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

    /* ‼️ ภาษาอื่น — ทับค่าข้างบนเฉพาะ key ที่ระบุ (brand.about · contact.address · nav · footer)
          URL เขียนเป็น path ภาษาไทยเหมือนข้างบน — App\Services\SiteService แปลงเป็น path ของภาษานั้นให้เอง
          (หน้าที่มีคำแปล → path ของคำแปล · ไม่มี → เติม /en หรือ /zh) จึงไม่ต้องดูแล URL สามชุด
          รายการภาษา (prefix · hreflang · ป้ายตัวสลับภาษา) อยู่ที่ config/locales.php */

    'locales' => [
        'en' => [
            'brand'   => [
                'legal_name' => 'Online Marketing Communication Co., Ltd.',
                'about' => 'OMC (Online Marketing Communication Co., Ltd.) builds MLM and direct selling systems for businesses in Thailand: '
                         . 'members, genealogy, commission calculation, stock and back office, configured to each company\'s plan.',
            ],
            'contact' => [
                'address' => 'Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand',
                'phone'   => '+66 99 109 5544',
            ],
            'nav' => [
                ['label' => 'Home', 'url' => '/'],
                [
                    'label'    => 'MLM Software',
                    'url'      => '/mlm-software-omc/',
                    'children' => [
                        ['label' => 'MLM Software by OMC', 'url' => '/mlm-software-omc/', 'desc' => 'Complete direct selling system overview', 'icon' => 'dashboard'],
                        ['label' => 'All features', 'url' => '/features/', 'desc' => 'Members, genealogy, commissions, back office', 'icon' => 'layers'],
                        ['label' => 'Commission calculation', 'url' => '/commission-system/', 'desc' => 'Bonuses calculated by your plan rules', 'icon' => 'calculator'],
                        ['label' => 'Member management', 'url' => '/member-system/', 'desc' => 'Sign-up, genealogy, member data', 'icon' => 'network'],
                        ['label' => 'MLM system migration', 'url' => '/mlm-migration/', 'desc' => 'Move members and balances from an old system', 'icon' => 'migrate'],
                        ['label' => 'OCPB registration (Thailand)', 'url' => '/ocpb-mlm/', 'desc' => 'Direct selling licensing overview', 'icon' => 'shield'],
                    ],
                ],
                [
                    'label'    => 'Solutions',
                    'url'      => '/services/',
                    'children' => [
                        ['label' => 'All services', 'url' => '/services/', 'desc' => 'Choose by business type', 'icon' => 'grid'],
                        ['label' => 'Supplements', 'url' => '/solutions/supplements/', 'icon' => 'leaf'],
                        ['label' => 'Cosmetics & skincare', 'url' => '/solutions/cosmetics/', 'icon' => 'sparkle'],
                        ['label' => 'Health & wellness', 'url' => '/solutions/health-wellness/', 'icon' => 'heart'],
                        ['label' => 'Distributors', 'url' => '/solutions/distributor/', 'icon' => 'box'],
                        ['label' => 'Franchise', 'url' => '/solutions/franchise/', 'icon' => 'store'],
                        ['label' => 'International', 'url' => '/solutions/international/', 'icon' => 'globe'],
                        ['label' => 'Custom systems', 'url' => '/solutions/custom/', 'icon' => 'sliders'],
                    ],
                ],
                [
                    'label'    => 'Compensation plans',
                    'url'      => '/compensation-plans/',
                    'children' => [
                        ['label' => 'All plans', 'url' => '/compensation-plans/', 'desc' => 'Compare every plan', 'icon' => 'layers'],
                        ['label' => 'Binary', 'url' => '/compensation-plans/binary/', 'icon' => 'plan-binary'],
                        ['label' => 'Unilevel', 'url' => '/compensation-plans/unilevel/', 'icon' => 'plan-unilevel'],
                        ['label' => 'Matrix', 'url' => '/compensation-plans/matrix/', 'icon' => 'plan-matrix'],
                        ['label' => 'Board', 'url' => '/compensation-plans/board/', 'icon' => 'plan-board'],
                        ['label' => 'Stair Step', 'url' => '/compensation-plans/stair-step/', 'icon' => 'plan-stair'],
                        ['label' => 'Hybrid', 'url' => '/compensation-plans/hybrid/', 'icon' => 'plan-hybrid'],
                    ],
                ],
                ['label' => 'Pricing', 'url' => '/pricing/'],
                ['label' => 'About', 'url' => '/about/'],
                ['label' => 'Contact', 'url' => '/contact/'],
            ],
            'footer' => [
                'columns' => [
                    ['heading' => 'Software', 'links' => [
                        ['label' => 'MLM Software by OMC', 'url' => '/mlm-software-omc/'],
                        ['label' => 'Features', 'url' => '/features/'],
                        ['label' => 'Compensation plans', 'url' => '/compensation-plans/'],
                        ['label' => 'Pricing / request a quote', 'url' => '/pricing/'],
                        ['label' => 'Book a demo', 'url' => '/demo/'],
                    ]],
                    ['heading' => 'Solutions', 'links' => [
                        ['label' => 'MLM software Thailand', 'url' => '/mlm-software-thailand/'],
                        ['label' => 'MLM software development', 'url' => '/mlm-development/'],
                        ['label' => 'Direct selling software', 'url' => '/direct-sales-software/'],
                        ['label' => 'Supplements', 'url' => '/solutions/supplements/'],
                        ['label' => 'System migration', 'url' => '/mlm-migration/'],
                    ]],
                    ['heading' => 'Company', 'links' => [
                        ['label' => 'About OMC', 'url' => '/about/'],
                        ['label' => 'Portfolio', 'url' => '/portfolio/'],
                        ['label' => 'Articles (Thai)', 'url' => '/blog/'],
                        ['label' => 'FAQ', 'url' => '/faq/'],
                        ['label' => 'Support', 'url' => '/support/'],
                        ['label' => 'Contact', 'url' => '/contact/'],
                    ]],
                ],
                'legal' => [
                    ['label' => 'Privacy policy', 'url' => '/นโยบายความเป็นส่วนตัวข/'],
                    ['label' => 'Cookie policy', 'url' => '/นโยบายการใช้คุกกี้-cookies-policy/'],
                ],
            ],
        ],
        'zh' => [
            'brand'   => [
                'legal_name' => 'Online Marketing Communication Co., Ltd.',
                'about' => 'OMC（Online Marketing Communication Co., Ltd.）为泰国企业开发MLM与直销系统，涵盖会员、组织网络、奖金计算、库存与后台管理，并按各公司的奖金制度进行配置。',
            ],
            'contact' => [
                'address' => 'Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand',
                'phone'   => '+66 99 109 5544',
            ],
            'nav' => [
                ['label' => '首页', 'url' => '/'],
                [
                    'label'    => 'MLM软件',
                    'url'      => '/mlm-software-omc/',
                    'children' => [
                        ['label' => 'MLM Software by OMC', 'url' => '/mlm-software-omc/', 'desc' => '一套完整的直销系统', 'icon' => 'dashboard'],
                        ['label' => '全部功能', 'url' => '/features/', 'desc' => '会员、组织网络、奖金、后台', 'icon' => 'layers'],
                        ['label' => '奖金计算系统', 'url' => '/commission-system/', 'desc' => '按制度规则自动计算奖金', 'icon' => 'calculator'],
                        ['label' => '会员管理系统', 'url' => '/member-system/', 'desc' => '注册、组织网络、会员资料', 'icon' => 'network'],
                        ['label' => '旧系统迁移', 'url' => '/mlm-migration/', 'desc' => '迁移会员与累计业绩数据', 'icon' => 'migrate'],
                        ['label' => '泰国OCPB直销登记', 'url' => '/ocpb-mlm/', 'desc' => '直销许可登记概览', 'icon' => 'shield'],
                    ],
                ],
                [
                    'label'    => '行业方案',
                    'url'      => '/services/',
                    'children' => [
                        ['label' => '全部服务', 'url' => '/services/', 'desc' => '按行业选择', 'icon' => 'grid'],
                        ['label' => '保健品', 'url' => '/solutions/supplements/', 'icon' => 'leaf'],
                        ['label' => '化妆品与护肤', 'url' => '/solutions/cosmetics/', 'icon' => 'sparkle'],
                        ['label' => '健康与美容', 'url' => '/solutions/health-wellness/', 'icon' => 'heart'],
                        ['label' => '经销商', 'url' => '/solutions/distributor/', 'icon' => 'box'],
                        ['label' => '加盟连锁', 'url' => '/solutions/franchise/', 'icon' => 'store'],
                        ['label' => '跨国业务', 'url' => '/solutions/international/', 'icon' => 'globe'],
                        ['label' => '定制系统', 'url' => '/solutions/custom/', 'icon' => 'sliders'],
                    ],
                ],
                [
                    'label'    => '奖金制度',
                    'url'      => '/compensation-plans/',
                    'children' => [
                        ['label' => '全部制度', 'url' => '/compensation-plans/', 'desc' => '比较各类奖金制度', 'icon' => 'layers'],
                        ['label' => 'Binary 双轨制', 'url' => '/compensation-plans/binary/', 'icon' => 'plan-binary'],
                        ['label' => 'Unilevel 单层制', 'url' => '/compensation-plans/unilevel/', 'icon' => 'plan-unilevel'],
                        ['label' => 'Matrix 矩阵制', 'url' => '/compensation-plans/matrix/', 'icon' => 'plan-matrix'],
                        ['label' => 'Board', 'url' => '/compensation-plans/board/', 'icon' => 'plan-board'],
                        ['label' => 'Stair Step', 'url' => '/compensation-plans/stair-step/', 'icon' => 'plan-stair'],
                        ['label' => 'Hybrid 混合制', 'url' => '/compensation-plans/hybrid/', 'icon' => 'plan-hybrid'],
                    ],
                ],
                ['label' => '价格', 'url' => '/pricing/'],
                ['label' => '关于我们', 'url' => '/about/'],
                ['label' => '联系我们', 'url' => '/contact/'],
            ],
            'footer' => [
                'columns' => [
                    ['heading' => '软件', 'links' => [
                        ['label' => 'MLM Software by OMC', 'url' => '/mlm-software-omc/'],
                        ['label' => '功能', 'url' => '/features/'],
                        ['label' => '奖金制度', 'url' => '/compensation-plans/'],
                        ['label' => '价格与报价', 'url' => '/pricing/'],
                        ['label' => '预约演示', 'url' => '/demo/'],
                    ]],
                    ['heading' => '方案', 'links' => [
                        ['label' => '泰国MLM软件', 'url' => '/mlm-software-thailand/'],
                        ['label' => 'MLM系统开发', 'url' => '/mlm-development/'],
                        ['label' => '直销软件', 'url' => '/direct-sales-software/'],
                        ['label' => '直销系统', 'url' => '/direct-sales-system/'],
                        ['label' => '系统迁移', 'url' => '/mlm-migration/'],
                    ]],
                    ['heading' => '公司', 'links' => [
                        ['label' => '关于OMC', 'url' => '/about/'],
                        ['label' => '项目案例', 'url' => '/portfolio/'],
                        ['label' => '文章（泰文）', 'url' => '/blog/'],
                        ['label' => '常见问题', 'url' => '/faq/'],
                        ['label' => '客户支持', 'url' => '/support/'],
                        ['label' => '联系我们', 'url' => '/contact/'],
                    ]],
                ],
                'legal' => [
                    ['label' => '隐私政策', 'url' => '/นโยบายความเป็นส่วนตัวข/'],
                    ['label' => 'Cookie政策', 'url' => '/นโยบายการใช้คุกกี้-cookies-policy/'],
                ],
            ],
        ],
    ],
];
