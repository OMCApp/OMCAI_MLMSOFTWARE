<?php
/**
 * OCPB guide + policy pages (Simplified Chinese). Keys = Thai page path; 'path' = /zh translated URL.
 *
 * ‼️ /ocpb-mlm/ is general information about Thailand only, not legal advice. Unverified figures are not published.
 * ‼️ /ocpb-mlm2/ (legacy duplicate) is intentionally not translated.
 * ‼️ Use 直销 / 多层次直销 / MLM only.
 */

declare(strict_types=1);

$ocpbBlocks = [
    [
        'type'    => 'hero',
        'variant' => 'page',
        'eyebrow' => '泰国OCPB登记',
        'title'   => '在泰国经营直销：OCPB登记基本资讯',
        'lead'    => '为计划在泰国开展直销或直接营销业务的企业，整理向泰国消费者保护委员会办公室（OCPB，泰文简称 สคบ.）办理登记的基本资讯。本页仅适用于泰国，仅供一般参考，不构成法律意见。',
        'actions' => [
            ['label' => '咨询团队', 'url' => '#lead-form', 'style' => 'primary'],
            ['label' => '了解直销系统', 'url' => '/mlm-software-omc/', 'style' => 'secondary'],
        ],
    ],
    [
        'type'       => 'rich_text',
        'title'      => '为什么在泰国经营直销需要向OCPB登记',
        'paragraphs' => [
            '在泰国，直销与直接营销业务受泰国《直销与直接营销法》（B.E. 2545，即2002年，及其后修订）规范。经营者须在开展业务之前，向登记机关——泰国消费者保护委员会办公室（OCPB / สคบ.）——办理登记。',
            '按泰国法律的概念，“直销”是指通过独立经销商或代理人，将商品或服务直接销售给消费者，包括多层次直销（MLM）；“直接营销”则是通过各种媒体直接向消费者发出销售要约，再由消费者回头下单。两者各有登记规定。',
            '完成登记有助于企业合法经营，增强独立经销商与消费者的信心。已登记的经营者名单可在OCPB公布的资料中查询。',
        ],
    ],
    [
        'type'    => 'feature_grid',
        'eyebrow' => '基本条件',
        'title'   => '申请人应了解的基本条件',
        'lead'    => '以下根据一般规定整理，最新细节与金额请在申请前以OCPB公告为准。',
        'columns' => 3,
        'items'   => [
            ['icon' => 'users', 'title' => '个人申请人（直接营销）', 'text' => '持有效泰国身份证、具泰国国籍、年满18周岁，并符合OCPB规定的其他条件'],
            ['icon' => 'store', 'title' => '法人申请人', 'text' => '已缴注册资本符合法律最低标准的合伙企业或公司，并可能需按业务类型提供担保'],
            ['icon' => 'shield', 'title' => '未曾被撤销登记', 'text' => '在法律规定的期间内（申请日前5年内）未曾被撤销直销或直接营销登记'],
        ],
    ],
    [
        'type'       => 'rich_text',
        'title'      => '管理人员的禁止情形',
        'paragraphs' => ['申请人的执行合伙人、董事总经理、经理或业务负责人，不得有禁止情形，例如：'],
        'list'       => [
            '为破产人',
            '为无行为能力人或准无行为能力人',
            '曾经终审判决被判处监禁，但过失犯罪或轻罪除外',
            '为其他正在登记经营直销或直接营销业务的合伙企业或公司的管理人员',
            '在申请日前5年内，曾任被撤销登记的合伙企业或公司的管理人员',
        ],
    ],
    [
        'type'  => 'steps',
        'title' => '一般流程',
        'lead'  => '所需时间取决于文件是否齐全以及OCPB的审核。',
        'items' => [
            ['title' => '准备法人与文件', 'text' => '公司注册证明、注册资本、管理人员资料与产品说明'],
            ['title' => '制定回报分配方案', 'text' => '说明独立经销商回报计算方式的奖金制度'],
            ['title' => '提交申请并取得受理编号', 'text' => '凭受理编号与申请编号跟踪进度'],
            ['title' => '取得登记编号', 'text' => '获批后取得登记编号，即可开展业务'],
        ],
    ],
    [
        'type'       => 'rich_text',
        'title'      => '直销系统能起到什么作用',
        'paragraphs' => [
            '办理登记是经营企业的责任。一套完善的直销系统，能帮助企业保存可核查的资料，包括独立经销商资料、销售额，以及与申报制度一致的回报计算与发放记录。',
            'OMC可提供初步咨询，并协助准备系统与奖金制度方面的资料。服务范围请直接向团队咨询。',
        ],
    ],
    [
        'type'  => 'faq',
        'title' => '关于泰国OCPB登记的常见问题',
        'items' => [
            ['question' => '直销与直接营销有什么不同？', 'answer' => '直销是通过独立经销商或代理人销售给消费者；直接营销是通过媒体直接向消费者发出销售要约，再由消费者下单。'],
            ['question' => '在哪里查询已登记的企业名单？', 'answer' => 'OCPB会公布已登记的经营者名单，可在OCPB网站查询。'],
            ['question' => '最低注册资本是多少？', 'answer' => '法律按法人类型与业务类型规定最低已缴注册资本，且会不时修订。申请前请向OCPB确认最新数字。'],
            ['question' => '这些规定适用于泰国以外的国家吗？', 'answer' => '不适用。本页只介绍泰国的规定。在其他国家或地区经营，请以当地法律与主管机关的要求为准。'],
        ],
    ],
    [
        'type'  => 'rich_text',
        'title' => '延伸阅读',
        'html'  => '<ul>'
            . '<li><a href="/compensation-plans/">MLM奖金制度介绍</a></li>'
            . '<li><a href="/direct-sales-system/">在泰国经营的直销系统</a></li>'
            . '<li><a href="/mlm-software-thailand/">泰国MLM软件</a></li>'
            . '</ul>'
            . '<p><strong>仅供一般参考，不构成法律意见</strong> — 相关规定可能变更，办理前请向OCPB或法律顾问确认。</p>',
    ],
    [
        'type'     => 'lead_form',
        'form_key' => 'consult',
        'eyebrow'  => '免费咨询',
        'title'    => '咨询在泰国开展直销业务',
        'lead'     => '告诉我们您的业务现况，团队会与您联系，就系统与奖金制度方面的步骤提供建议。',
    ],
];

$policyContact = '<h3>个人资料相关联系方式</h3>'
    . '<p>如对个人资料的收集、使用或披露有任何建议或疑问，或希望依本政策行使权利，请联系：</p>'
    . '<ul><li>OMC（Online Marketing Communication Co., Ltd.），Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand</li>'
    . '<li>电子邮件 <a href="mailto:info@omc.co.th">info@omc.co.th</a></li>'
    . '<li>电话 <a href="tel:+66991095544">+66 99 109 5544</a></li>'
    . '<li>Facebook：<a href="https://www.facebook.com/mlmsoftwarebyomc">MLM Software by OMC</a></li></ul>';

return [

    '/ocpb-mlm/' => [
        'path'        => '/zh/ocpb-mlm/',
        'title'       => '泰国OCPB直销登记',
        'seo_title'   => '泰国OCPB直销登记指南 | OMC',
        'description' => '在泰国经营直销与直接营销须向OCPB（สคบ.）登记：申请条件、管理人员禁止情形与一般流程。仅供一般参考，不构成法律意见。',
        'blocks'      => $ocpbBlocks,
    ],

    '/นโยบายความเป็นส่วนตัวข/' => [
        'path'        => '/zh/privacy-policy/',
        'title'       => '隐私政策（Privacy Policy）',
        'seo_title'   => '隐私政策（Privacy Policy） | OMC',
        'description' => 'mlmsoftware.omc.co.th 网站隐私政策，由OMC（Online Marketing Communication Co., Ltd.）说明收集的资料、使用目的及资料主体的权利。',
        'blocks'      => [
            [
                'type' => 'rich_text',
                'html' => '<p>mlmsoftware.omc.co.th 网站由 OMC（Online Marketing Communication Co., Ltd.，以下简称“OMC”或“我们”）运营。我们尊重每一位访问者的隐私，特此说明我们如何使用您的个人资料，以符合泰国《个人资料保护法》（B.E. 2562，即2019年）的要求。</p>'
                    . '<h3>本政策适用对象</h3>'
                    . '<ul><li>服务提供者或网站所有者，即OMC</li><li>网站访问者，即打开或阅读 mlmsoftware.omc.co.th 任何页面的人</li><li>通过网站表单提交资料的人</li></ul>'
                    . '<h3>我们通过表单收集的资料</h3>'
                    . '<p>当您提交预约演示、申请报价、咨询或联系团队的表单时，我们会收集您填写的资料，包括姓名、公司、业务类型、电话、电子邮件、LINE ID 以及您说明的内容，并收集技术资料，例如提交表单的页面、访问来源与IP地址，用于与您联系、提供您所要求的服务，以及防止自动提交（垃圾信息）。我们不会将您的资料出售给他人。</p>'
                    . '<h3>收集资料的目的</h3>'
                    . '<ul><li>与您联系并提供您所要求的服务</li><li>用于线上营销活动与广告</li><li>分析网站访问者资料并进行市场研究</li><li>改进网站与使用体验</li></ul>'
                    . '<h3>Cookie 的使用</h3>'
                    . '<p>本网站使用 Cookie 以维持网站运作，详情请参阅 <a href="/นโยบายการใช้คุกกี้-cookies-policy/">Cookie 政策</a>。</p>'
                    . '<h3>由第三方收集的资料</h3>'
                    . '<p>本网站可能使用第三方软件与服务来提升运作效率，例如 Google Analytics、Facebook Pixel/Conversion API、Google Maps 与 YouTube。与第三方平台连接时，部分 Cookie 与计算机流量资料可能由第三方处理，建议您进一步阅读这些服务提供者的政策。</p>'
                    . '<h3>资料主体的权利</h3>'
                    . '<ul><li>可通过下方联系方式，要求查阅、更正或删除您通过表单提交的资料</li><li>可通过所用浏览器的设定拒绝或删除 Cookie</li></ul>'
                    . '<h3>安全与外部链接</h3>'
                    . '<p>本网站可能包含指向其他网站的链接，我们无法对这些网站的隐私政策负责，请阅读您所访问网站的相关政策。</p>'
                    . $policyContact,
            ],
        ],
    ],

    '/นโยบายการใช้คุกกี้-cookies-policy/' => [
        'path'        => '/zh/cookie-policy/',
        'title'       => 'Cookie 政策（Cookies Policy）',
        'seo_title'   => 'Cookie政策（Cookies Policy） | OMC',
        'description' => 'mlmsoftware.omc.co.th 网站 Cookie 政策：Cookie 是什么、我们使用的必要 Cookie、统计 Cookie 与营销 Cookie，以及如何设定或拒绝 Cookie。',
        'blocks'      => [
            [
                'type' => 'rich_text',
                'html' => '<p>OMC（Online Marketing Communication Co., Ltd.）的 mlmsoftware.omc.co.th 网站使用 Cookie 或类似技术，为访问者提供良好的体验，并帮助我们改进网站，更好地满足需求。</p>'
                    . '<h3>定义</h3>'
                    . '<ul><li>网站访问者：打开或阅读本网站任何页面的人</li><li>网站系统：mlmsoftware.omc.co.th 网站的运作系统</li></ul>'
                    . '<h3>Cookie 是什么</h3>'
                    . '<p>Cookie 是访问者浏览网站时存储在其设备上的小型文本文件。Cookie 中的资料会传回网站系统进行处理，或传送到支持该 Cookie 的其他网站。Cookie 让网站能够记住设备以及访问者所做的选择。</p>'
                    . '<h3>我们使用的 Cookie 类型</h3>'
                    . '<ul><li><strong>必要 Cookie</strong> — 让网站及各项功能安全运作，例如防止表单伪造的 Cookie。缺少这类 Cookie，网站将无法正常运作。</li>'
                    . '<li><strong>统计与分析 Cookie</strong> — 统计访问人数与浏览的页面，用于改进网站。</li>'
                    . '<li><strong>营销 Cookie</strong> — 收集使用情况与访问的链接，包括访问来源（例如营销活动的 UTM 参数），以便提供合适的资讯与广告。</li></ul>'
                    . '<h3>设定与拒绝 Cookie</h3>'
                    . '<p>访问者可以通过浏览器设定或设备的隐私设定拒绝 Cookie，但拒绝全部 Cookie 可能导致部分功能（例如提交表单）无法使用。</p>'
                    . '<h3>使用条款</h3>'
                    . '<p>使用本网站即表示您知悉我们按上述目的使用 Cookie，并按 <a href="/นโยบายความเป็นส่วนตัวข/">隐私政策</a> 收集、使用或披露资料。若本网站链接至第三方网站，该第三方可能有其自己的 Cookie 政策。</p>'
                    . $policyContact,
            ],
        ],
    ],
];
