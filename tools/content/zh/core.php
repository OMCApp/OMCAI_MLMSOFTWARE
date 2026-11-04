<?php
/**
 * Core pages — Simplified Chinese (home, product, features, services hub, pricing, demo, about, contact, faq, support,
 * portfolio, blog index SEO, thank-you). Keys = Thai page path; 'path' = /zh + Thai path.
 *
 * Rules: no unverified numbers / client logos / testimonials / income promises. Use 直销 / 多层次直销 / MLM only.
 * {years} = years since registration (2009-04-03), replaced at render time.
 */

declare(strict_types=1);

$ctaDemo = [
    'type'    => 'cta_band',
    'title'   => '想先看看系统实际操作再决定？',
    'text'    => '预约OMC团队的系统演示，同时聊聊您的奖金制度。',
    'actions' => [
        ['label' => '预约演示', 'url' => '/demo/', 'style' => 'light', 'icon' => 'calendar'],
        ['label' => '致电 +66 99 109 5544', 'url' => 'tel:+66991095544', 'style' => 'ghost-light', 'icon' => 'phone'],
    ],
];

return [

    // ------------------------------------------------------------------ Home
    '/' => [
        'path'        => '/zh/',
        'title'       => 'MLM Software by OMC',
        'seo_title'   => '直销软件与MLM系统 | OMC',
        'description' => 'MLM Software by OMC：一站式直销系统，涵盖会员、网络结构、奖金计算、库存与会员后台，按您的奖金制度配置。泰国本地团队，成立于2009年。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => 'MLM Software by OMC',
                'title'   => "直销系统（MLM）\n按您的业务模式设计",
                'lead'    => '为在泰国经营的直销企业打造的系统：从会员注册、网络结构、奖金计算，到库存与会员后台，由深耕软件开发{years}年的OMC团队开发与维护。',
                'bullets' => [
                    '可配置多种奖金制度，如Binary、Unilevel、Matrix与混合制',
                    '会员、网络结构管理，奖金按实际订单计算',
                    '多分公司库存、在线订购与会员电子钱包',
                    '协助从旧系统迁移数据，上线后持续支持',
                ],
                'actions' => [
                    ['label' => '免费预约演示', 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => '查看全部功能', 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'about_teaser',
                'eyebrow' => '关于我们',
                'title'   => 'OMC是谁？为什么直销企业愿意找我们谈',
                'text'    => 'OMC（Online Marketing Communication Co., Ltd.）于2009年在泰国暖武里府成立，以“MLM Software by OMC”为品牌，为希望系统贴合自身奖金制度的直销企业开发MLM软件，而不是让企业去迁就现成软件。从需求收集、制度配置、计算测试到正式上线后的支持，都由同一支团队负责。',
                'points'  => [
                    '泰国注册公司，成立于2009年，已经营{years}年',
                    '开发与客服团队均在泰国，熟悉当地团队的工作方式',
                    '按实际奖金制度设计，并协助迁移旧系统数据',
                ],
                'action'  => ['label' => '认识OMC', 'url' => '/about/'],
            ],
            [
                'type'  => 'stats',
                'items' => [
                    ['value' => 2009, 'format' => 'plain', 'label' => '公司成立年份'],
                    ['value' => '{years}', 'suffix' => ' 年', 'label' => '持续经营'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '直销企业每天都在用的核心功能',
                'lead'    => '按业务规模选择启用，并可在项目范围内扩充。',
                'columns' => 8,
                'items'   => [
                    ['icon' => 'users', 'title' => '会员与网络结构', 'url' => '/member-system/'],
                    ['icon' => 'calculator', 'title' => '奖金计算', 'url' => '/commission-system/'],
                    ['icon' => 'cart', 'title' => '在线订购', 'url' => '/features/'],
                    ['icon' => 'wallet', 'title' => '电子钱包 E-Credit', 'url' => '/features/'],
                    ['icon' => 'box', 'title' => '库存与分公司', 'url' => '/features/'],
                    ['icon' => 'chart', 'title' => '报表导出Excel', 'url' => '/features/'],
                    ['icon' => 'lock', 'title' => '员工菜单权限', 'url' => '/features/'],
                    ['icon' => 'sliders', 'title' => '按业务定制', 'url' => '/solutions/custom/'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'band',
                'visual'  => 'bars',
                'eyebrow' => '数据与报表',
                'title'   => '用系统里的数字做决策，而不是靠零散的文件',
                'lead'    => '销售额、积分与奖金在同一个数据库中，管理层与财务、会员服务部门看到的是同一组数字。',
                'bullets' => [
                    '按日、按月、按产品、按分公司查看销售',
                    '每一笔奖金都能追溯到来源订单',
                    '进阶搜索，报表可导出Excel',
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖金制度',
                'title'   => '按公司公布的制度配置奖金',
                'lead'    => '直销企业常用的制度，条件与比例可自行设定；更复杂的制度由团队与您共同设计。',
                'plans'   => [
                    ['key' => 'binary', 'name' => 'Binary', 'text' => '左右两区，按两边业绩配对计算奖金', 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => 'Unilevel', 'text' => '首层可直推多人，按制度规定的层数计算奖金', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => 'Matrix', 'text' => '设定宽度与深度，网络按结构发展', 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid', 'text' => '在同一系统中组合多种制度', 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => '查看全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'industry_grid',
                'title' => '适用于多种类型的直销业务',
                'lead'  => '不同业务的管理重点各有不同，选择与您业务相近的方案了解。',
                'items' => [
                    ['icon' => 'leaf', 'title' => '保健食品', 'subtitle' => 'Supplements', 'url' => '/solutions/supplements/'],
                    ['icon' => 'sparkle', 'title' => '化妆品与护肤', 'subtitle' => 'Cosmetics', 'url' => '/solutions/cosmetics/'],
                    ['icon' => 'heart', 'title' => '健康与美容', 'subtitle' => 'Health & Wellness', 'url' => '/solutions/health-wellness/'],
                    ['icon' => 'box', 'title' => '批发 / 经销商', 'subtitle' => 'Distributor', 'url' => '/solutions/distributor/'],
                    ['icon' => 'store', 'title' => '加盟连锁', 'subtitle' => 'Franchise', 'url' => '/solutions/franchise/'],
                    ['icon' => 'globe', 'title' => '跨国业务', 'subtitle' => 'International', 'url' => '/solutions/international/'],
                ],
            ],
            [
                'type'    => 'dashboard_showcase',
                'eyebrow' => '系统界面',
                'title'   => '管理层看全局，会员看自己的业绩与奖金',
                'lead'    => '公司团队使用管理后台，会员使用会员后台，电脑与手机均可使用。',
                'bullets' => [
                    '管理层仪表板：销售额、新会员、待发奖金',
                    '可逐层展开的网络结构图',
                    '每位会员的奖金记录与发放状态',
                ],
                'action'  => ['label' => '查看全部功能', 'url' => '/features/'],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => '如何开始',
                'title'   => '与OMC合作上线系统的步骤',
                'lead'    => '所需时间视各企业的项目范围而定，团队会在收集需求后提供工作计划。',
                'items'   => [
                    ['title' => '沟通业务规划', 'text' => '奖金制度、产品、分公司与销售团队的工作方式'],
                    ['title' => '演示并确认范围', 'text' => '比对系统现有功能与需要增加的部分，并提供报价'],
                    ['title' => '配置、迁移、测试', 'text' => '配置奖金制度，导入旧会员资料，一起测试计算结果'],
                    ['title' => '上线与后续支持', 'text' => '正式启用系统，并提供联系支持团队的渠道'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'demo',
                'eyebrow'  => '免费预约演示',
                'title'    => '请OMC团队与您联系',
                'lead'     => '简单填写资料，团队会与您联系，安排查看示范系统，并讨论您的业务规划。',
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Product hub
    '/mlm-software-omc/' => [
        'path'        => '/zh/mlm-software-omc/',
        'title'       => 'MLM Software by OMC',
        'seo_title'   => 'MLM Software by OMC 直销系统 | OMC',
        'description' => 'MLM Software by OMC 将会员、网络结构、产品、库存、电子商务、电子钱包、奖金与报表整合在同一系统中，并按您的奖金制度进行配置。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => 'OMC出品的直销软件',
                'title'   => "MLM Software by OMC\n一套系统，管好整个直销业务",
                'lead'    => '把直销公司的日常工作集中在一个地方：会员注册、网络安置、销售产品、扣减库存、计算奖金，直到发放款项与输出报表，全部按您公司的奖金制度配置。',
                'bullets' => [
                    '公司团队用的管理后台，以及会员用的会员后台',
                    '按公司公布的制度设定奖金规则与PV/BV积分',
                    '可在项目范围内调整或扩充开发',
                ],
                'actions' => [
                    ['label' => '预约演示', 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => '获取报价', 'url' => '/pricing/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '什么是MLM软件？直销公司为什么需要它',
                'paragraphs' => [
                    'MLM软件（直销软件）是记录会员资料与网络关系，并依据实际销售额按公司奖金制度计算回报的系统。仍在使用多个Excel文件的企业，往往会遇到同样的问题：计算慢、难以回溯核查，会员对数字也缺乏信心。',
                    'MLM Software by OMC把这些工作整合在同一个数据库中：订单产生积分，积分转化为奖金，奖金再进入电子钱包或发放周期。每一步都可以回溯，财务、会员服务与管理层看到的是同一组数字。',
                    'OMC持续为泰国的直销企业开发这套系统，始终以客户实际的奖金制度为出发点，不要求企业为了配合软件而修改制度。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '系统模块',
                'title'   => 'MLM Software by OMC 功能模块',
                'lead'    => '直销公司常用的核心模块，可按项目范围选择启用并扩充。',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => '会员管理（Member Management）', 'text' => '会员注册、安置到网络结构、修改结构与会员资料', 'url' => '/member-system/'],
                    ['icon' => 'sparkle', 'title' => '产品与促销', 'text' => '设定产品、促销、价格与积分，控制产品显示或隐藏'],
                    ['icon' => 'box', 'title' => '库存管理（Stock）', 'text' => '扣减库存、开立销售单、商品入库、分公司库存、领料单与收货单'],
                    ['icon' => 'cart', 'title' => '在线订购（E-Commerce）', 'text' => '会员自行下单，以E-Credit或支付网关付款'],
                    ['icon' => 'wallet', 'title' => '电子钱包（E-Credit）', 'text' => '按公司规定的条件在系统内充值、转账与提现'],
                    ['icon' => 'plug', 'title' => '支付对接（API）', 'text' => '对接公司所选支付服务商的收款渠道'],
                    ['icon' => 'history', 'title' => '账单与配送跟踪', 'text' => '配送、分公司收货，以及待发货与待收货清单'],
                    ['icon' => 'layers', 'title' => 'Hold PV 积分分配', 'text' => '从订单分配积分、积分分配单与剩余待分配余额'],
                    ['icon' => 'store', 'title' => '分公司管理', 'text' => '新增泰国国内与海外分公司，分开管理库存与销售'],
                    ['icon' => 'calculator', 'title' => '奖金计算', 'text' => '按奖金制度每日、每期或每月计算', 'url' => '/commission-system/'],
                    ['icon' => 'chart', 'title' => '报表', 'text' => '多套标准报表、进阶搜索、导出Excel'],
                    ['icon' => 'lock', 'title' => '员工权限', 'text' => '按菜单设定查看、新增、修改、删除权限'],
                ],
            ],
            [
                'type'      => 'comparison_table',
                'title'     => '可配置的标准系统，还是专门定制的系统',
                'lead'      => '按奖金制度的复杂度与预算选择，团队会在了解需求后协助评估。',
                'columns'   => ['标准系统（可配置）', '专属定制系统（Custom）'],
                'highlight' => 1,
                'rows'      => [
                    ['label' => '配置奖金制度与积分', 'values' => [true, true]],
                    ['label' => '品牌标志与配色', 'values' => [true, true]],
                    ['label' => '标准系统中没有的特殊奖金条件', 'values' => [false, true]],
                    ['label' => '对接公司的外部系统', 'values' => ['视项目范围', true]],
                    ['label' => '上线所需时间', 'values' => ['较短', '视项目范围']],
                ],
            ],
            [
                'type'    => 'dashboard_showcase',
                'eyebrow' => '系统界面',
                'title'   => '简单好用的管理后台与会员后台',
                'lead'    => '公司团队管理会员、产品与付款；会员可随时查看自己的团队、积分与奖金。',
                'bullets' => [
                    '可逐层展开的网络结构图',
                    '本期积分以及尚未达到的条件',
                    '奖金记录与发放状态',
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于MLM Software by OMC的常见问题',
                'items' => [
                    ['question' => '系统支持哪些奖金制度？', 'answer' => '可配置多种制度，如Binary、Unilevel、Matrix与混合制（Hybrid）。Board和Stair Step可按需求定制，请咨询我们的团队，并提供您的制度说明。'],
                    ['question' => '可以在手机上使用吗？', 'answer' => '会员后台适合在手机上使用，会员可通过浏览器查看团队、积分与奖金。'],
                    ['question' => '我们已有旧系统，可以迁移过来吗？', 'answer' => '可以。团队会评估旧数据，再与您一起规划会员、网络结构与累计业绩的迁移方式。详情请见MLM系统迁移页面。'],
                    ['question' => '价格是多少？', 'answer' => '取决于奖金制度、模块数量以及需要定制的工作。您可以在报价页面提交需求，团队会先了解更多信息再报价。'],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => '延伸阅读',
                'html'  => '<ul>'
                    . '<li><a href="/features/">系统全部功能</a></li>'
                    . '<li><a href="/compensation-plans/">奖金制度比较</a></li>'
                    . '<li><a href="/mlm-migration/">迁移现有MLM系统</a></li>'
                    . '<li><a href="/mlm-program/">如何选择MLM软件</a></li>'
                    . '</ul>',
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Features
    '/features/' => [
        'path'        => '/zh/features/',
        'title'       => '系统功能',
        'seo_title'   => 'MLM软件功能：会员、奖金与库存 | OMC',
        'description' => 'MLM Software by OMC 全部功能：会员与网络结构、产品与积分、多分公司库存、电子商务、电子钱包、奖金计算、报表与员工权限。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Features',
                'title'   => 'MLM Software by OMC 系统功能',
                'lead'    => '按各部门的工作分组，概览直销公司使用的系统模块。所有模块按业务规划配置，并可在项目范围内扩充。',
                'actions' => [
                    ['label' => '预约演示', 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => '查看奖金制度', 'url' => '/compensation-plans/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '会员与网络',
                'title'   => '会员管理',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => '注册与安置', 'text' => '注册新会员、指定推荐人，并按制度规则安置到网络中', 'url' => '/member-system/'],
                    ['icon' => 'network', 'title' => '网络结构图', 'text' => '逐层展开查看团队，在网络中搜索会员，由授权人员修改结构'],
                    ['icon' => 'dashboard', 'title' => '会员后台', 'text' => '会员查看本人资料、团队、积分与奖金，支持手机使用'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '销售与产品',
                'title'   => '销售、产品与库存',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'sparkle', 'title' => '产品与促销', 'text' => '设定会员价、PV/BV积分、促销套组，以及产品的显示或隐藏'],
                    ['icon' => 'cart', 'title' => '在线订购', 'text' => '会员通过会员后台下单，以E-Credit或支付网关付款'],
                    ['icon' => 'box', 'title' => '库存与分公司', 'text' => '按订单扣减库存、商品入库、领料单与收货单，以及分公司库存'],
                    ['icon' => 'history', 'title' => '账单与配送跟踪', 'text' => '配送状态、分公司收货、待发货与待收货清单'],
                    ['icon' => 'layers', 'title' => 'Hold PV', 'text' => '将订单积分分配给会员，并显示剩余待分配余额'],
                    ['icon' => 'store', 'title' => '多分公司', 'text' => '新增泰国国内与海外分公司，分别统计各分公司的销售与库存'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '财务与回报',
                'title'   => '奖金与财务',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'calculator', 'title' => '奖金计算', 'text' => '按制度条件每日、每期或每月计算，可追溯到来源订单', 'url' => '/commission-system/'],
                    ['icon' => 'wallet', 'title' => '电子钱包 E-Credit', 'text' => '按公司规定在系统内充值、转账与提现'],
                    ['icon' => 'plug', 'title' => '支付对接', 'text' => '对接公司所选支付服务商的API'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '管理与安全',
                'title'   => '报表与管控',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'chart', 'title' => '报表', 'text' => '多套标准报表、进阶搜索，可下载为Excel'],
                    ['icon' => 'lock', 'title' => '员工权限', 'text' => '设定每位员工可以查看、新增、修改或删除哪些菜单'],
                    ['icon' => 'shield', 'title' => '操作记录', 'text' => '记录重要资料的修改，可查到谁在何时做了什么'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'band',
                'visual'  => 'bars',
                'eyebrow' => '可定制',
                'title'   => '需要清单以外的功能？',
                'lead'    => 'OMC为有特殊需求的企业开发专属功能，例如新的奖金形式，或对接现有的会计、仓储系统。团队会先评估范围并说明做法，再开始工作。',
                'action'  => ['label' => '了解定制系统', 'url' => '/solutions/custom/', 'style' => 'outline'],
            ],
            [
                'type'  => 'faq',
                'title' => '关于功能的常见问题',
                'items' => [
                    ['question' => '必须使用所有模块吗？', 'answer' => '不必。刚起步的公司通常先使用会员、产品、订购与奖金模块，之后再增加分公司库存或电子钱包。'],
                    ['question' => '数据可以导出给会计软件使用吗？', 'answer' => '报表可下载为Excel；与会计软件直接对接则属于项目范围内的定制工作。'],
                    ['question' => '会员能看到其他人的资料吗？', 'answer' => '会员只能看到公司规定范围内的本人与团队资料；员工也只能看到已获授权的菜单。'],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => '延伸阅读',
                'html'  => '<ul>'
                    . '<li><a href="/mlm-software-omc/">MLM Software by OMC 概览</a></li>'
                    . '<li><a href="/member-system/">直销会员管理系统</a></li>'
                    . '<li><a href="/commission-system/">直销奖金计算系统</a></li>'
                    . '</ul>',
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Services hub
    '/services/' => [
        'path'        => '/zh/services/',
        'title'       => '服务与解决方案',
        'seo_title'   => '直销系统服务与行业方案 | OMC',
        'description' => 'OMC为直销企业提供的服务：建立MLM系统、配置奖金制度、定制开发、旧系统数据迁移与上线后支持，并按业务类型提供解决方案。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Services',
                'title'   => '覆盖直销业务各个阶段的系统服务',
                'lead'    => '无论是新开展直销业务、替换旧系统，还是开发专属功能，OMC都能负责系统建设与奖金制度配置。',
                'actions' => [
                    ['label' => '咨询团队', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => 'OMC的主要服务',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'dashboard', 'title' => '建立MLM系统', 'text' => '按公司奖金制度安装并配置标准系统', 'url' => '/mlm-software-omc/'],
                    ['icon' => 'code', 'title' => 'MLM系统定制开发', 'text' => '开发一般系统没有的功能与条件', 'url' => '/mlm-development/'],
                    ['icon' => 'migrate', 'title' => '旧MLM系统迁移', 'text' => '从旧系统迁移会员、网络结构与累计业绩', 'url' => '/mlm-migration/'],
                    ['icon' => 'calculator', 'title' => '奖金计算设计', 'text' => '把奖金制度文件转化为可计算、可测试的规则', 'url' => '/commission-system/'],
                    ['icon' => 'shield', 'title' => '泰国OCPB登记资讯', 'text' => '在泰国经营直销业务的基础参考', 'url' => '/ocpb-mlm/'],
                    ['icon' => 'headset', 'title' => '上线后支持', 'text' => '问题反馈渠道与泰国本地支持团队', 'url' => '/support/'],
                ],
            ],
            [
                'type'    => 'industry_grid',
                'eyebrow' => '按业务选择',
                'title'   => '按业务类型的解决方案',
                'lead'    => '了解与您类似的企业通常使用哪些模块与奖金制度。',
                'items'   => [
                    ['icon' => 'leaf', 'title' => '保健食品', 'subtitle' => 'Supplements', 'url' => '/solutions/supplements/'],
                    ['icon' => 'sparkle', 'title' => '化妆品与护肤', 'subtitle' => 'Cosmetics', 'url' => '/solutions/cosmetics/'],
                    ['icon' => 'heart', 'title' => '健康与美容', 'subtitle' => 'Health & Wellness', 'url' => '/solutions/health-wellness/'],
                    ['icon' => 'box', 'title' => '批发 / 经销商', 'subtitle' => 'Distributor', 'url' => '/solutions/distributor/'],
                    ['icon' => 'store', 'title' => '加盟连锁', 'subtitle' => 'Franchise', 'url' => '/solutions/franchise/'],
                    ['icon' => 'globe', 'title' => '跨国业务', 'subtitle' => 'International', 'url' => '/solutions/international/'],
                    ['icon' => 'sliders', 'title' => '定制系统', 'subtitle' => 'Custom', 'url' => '/solutions/custom/'],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '如何与OMC合作',
                'items' => [
                    ['title' => '沟通需求', 'text' => '奖金制度、产品、分公司以及旧系统的问题'],
                    ['title' => '演示与工作范围', 'text' => '整理可直接使用的部分与需要额外开发的部分'],
                    ['title' => '配置与测试', 'text' => '用您的示例数据测试计算结果'],
                    ['title' => '上线与支持', 'text' => '培训后台团队，并在上线后持续支持'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'title'    => '咨询适合您业务的系统',
                'lead'     => '告诉我们您的业务类型与需求，团队会与您联系并建议合适的做法。',
            ],
        ],
    ],

    // ------------------------------------------------------------------ Pricing
    '/pricing/' => [
        'path'        => '/zh/pricing/',
        'title'       => '价格与报价',
        'seo_title'   => 'MLM软件价格与报价申请 | OMC',
        'description' => 'MLM软件价格取决于奖金制度、模块数量、定制开发与数据迁移。了解影响价格的因素，并直接向OMC团队申请符合您业务需求的报价。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Pricing',
                'title'   => 'MLM软件价格与报价申请',
                'lead'    => '每家公司的奖金制度与工作范围都不同，因此OMC按实际需求报价，而不是提供未必适合您业务的固定套餐。',
                'actions' => [
                    ['label' => '申请报价', 'url' => '#lead-form', 'style' => 'primary'],
                    ['label' => '先看演示', 'url' => '/demo/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '影响价格的因素',
                'lead'    => '提供这些信息，团队首次评估就能更接近实际报价。',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'plan-hybrid', 'title' => '奖金制度的复杂度', 'text' => '奖金种类数量、职级条件，以及跨周期计算'],
                    ['icon' => 'layers', 'title' => '启用的模块', 'text' => '例如多分公司库存、电子商务、电子钱包或支付对接'],
                    ['icon' => 'code', 'title' => '专属开发', 'text' => '标准系统中没有的功能或页面'],
                    ['icon' => 'migrate', 'title' => '旧数据迁移', 'text' => '会员、网络结构与累计业绩数据的数量与格式'],
                    ['icon' => 'users', 'title' => '网络规模', 'text' => '预估的会员数量与订单量，会影响服务器的准备'],
                    ['icon' => 'headset', 'title' => '上线后支持', 'text' => '所需的支持方式与系统改进安排'],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '报价流程',
                'items' => [
                    ['title' => '提交基本信息', 'text' => '填写下方表单，或致电团队'],
                    ['title' => '讨论制度细节', 'text' => '团队索取奖金制度文件，并进一步了解需求'],
                    ['title' => '收到报价单', 'text' => '列明工作范围、交付内容与支持条件'],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于价格的常见问题',
                'items' => [
                    ['question' => '为什么网站上没有套餐价格？', 'answer' => '主要成本来自奖金制度与定制工作，而各企业之间差异很大。根据实际信息评估，报价才能与真正需要的工作相符。'],
                    ['question' => '申请报价前需要准备什么？', 'answer' => '奖金制度文件（如有）、大致的产品清单、分公司数量，如需迁移数据，再加上旧系统的资料。'],
                    ['question' => '可以先看系统吗？', 'answer' => '可以。请在预约演示页面预约，团队会按您的业务类型展示系统。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'quote',
                'eyebrow'  => '申请报价',
                'title'    => '申请直销系统报价',
                'lead'     => '提供的信息越详细，团队评估越准确。您的资料仅用于与您联系。',
                'submit'   => '提交报价申请',
            ],
        ],
    ],

    // ------------------------------------------------------------------ Demo
    '/demo/' => [
        'path'        => '/zh/demo/',
        'title'       => '预约MLM系统演示',
        'seo_title'   => '免费预约MLM系统演示 | OMC',
        'description' => '与OMC团队预约MLM Software演示，查看管理后台、会员后台、网络结构与奖金计算，并一起讨论您的奖金制度，演示不收取费用。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Demo',
                'title'   => '预约直销系统演示',
                'lead'    => '与OMC团队在线上或面对面查看实际系统。团队会展示与您业务相关的界面，并同时解答奖金制度方面的问题。',
                'bullets' => [
                    '预约演示不收取费用',
                    '同时看到公司管理后台与会员后台',
                    '定制与数据迁移问题可一次讨论',
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '演示中您会看到什么',
                'items' => [
                    ['title' => '会员注册与网络结构', 'text' => '注册、安置以及逐层查看团队'],
                    ['title' => '产品销售与积分', 'text' => '开单、扣减库存，以及产生的PV/BV积分'],
                    ['title' => '奖金计算', 'text' => '计算周期、奖金报表与回溯核查'],
                    ['title' => '会员后台', 'text' => '会员在手机上使用的界面'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '演示前的准备',
                'list'       => [
                    '正在使用或正在规划的奖金制度文件或说明',
                    '旧系统遇到的问题，或希望新系统能做到的事',
                    '分公司数量与销售方式（门店、线上或两者兼有）',
                ],
                'paragraphs' => ['不必每一项都准备齐全，团队会在会谈中协助提问与整理。'],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'demo',
                'eyebrow'  => '预约演示',
                'title'    => '填写资料预约演示',
                'lead'     => '团队会与您联系，约定方便的日期与时间。',
                'submit'   => '预约演示',
            ],
        ],
    ],

    // ------------------------------------------------------------------ About
    '/about/' => [
        'path'        => '/zh/about/',
        'title'       => '关于OMC',
        'seo_title'   => '关于OMC：泰国MLM软件开发商 | OMC',
        'description' => '认识OMC（Online Marketing Communication Co., Ltd.），MLM Software by OMC 的开发商，2009年成立于泰国暖武里府，按业务规划设计直销系统。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'About OMC',
                'title'   => '关于OMC',
                'lead'    => 'OMC（Online Marketing Communication Co., Ltd.）是一家泰国软件公司，成立于2009年，是MLM Software by OMC的开发商。',
            ],
            [
                'type'  => 'stats',
                'items' => [
                    ['value' => 2009, 'format' => 'plain', 'label' => '公司注册年份'],
                    ['value' => '{years}', 'suffix' => ' 年', 'label' => '持续经营'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '我们是谁',
                'paragraphs' => [
                    'OMC于2009年4月3日注册成立，办公室位于泰国暖武里府（曼谷都会区）。我们的主要工作是为通过会员网络销售产品的企业开发直销系统与MLM软件。在过去{years}年里，我们服务过奖金制度、产品与工作方式各不相同的企业，因此把系统设计成随业务调整，而不是让业务迁就软件。',
                    '我们的开发团队与客户服务团队都在泰国，能够深入讨论奖金制度、泰文文件以及后台团队的工作流程，从收集需求一直到系统上线之后。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '我们坚持的工作原则',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'calculator', 'title' => '数字必须可回溯', 'text' => '每一笔奖金都能说明来自哪张订单、依据哪条规则'],
                    ['icon' => 'sliders', 'title' => '系统配合业务', 'text' => '按客户的实际制度配置与扩充开发'],
                    ['icon' => 'chat', 'title' => '范围说清楚', 'text' => '明确告知哪些可以立即使用，哪些需要额外开发'],
                ],
            ],
            [
                'type'    => 'about_teaser',
                'title'   => '公司资料',
                'text'    => 'OMC（Online Marketing Communication Co., Ltd.）· Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand',
                'points'  => [
                    '电话 +66 99 109 5544 · LINE omc54',
                    '电子邮件 sale@omc.co.th · info@omc.co.th',
                    'Facebook：MLM Software by OMC',
                ],
                'action'  => ['label' => '联系我们', 'url' => '/contact/'],
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Contact
    '/contact/' => [
        'path'        => '/zh/contact/',
        'title'       => '联系我们',
        'seo_title'   => '联系OMC：直销系统咨询 | OMC',
        'description' => '就MLM Software与直销系统联系OMC团队：电话 +66 99 109 5544、LINE omc54、邮箱 sale@omc.co.th，或填写表单，我们会主动与您联系。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Contact',
                'title'   => '联系OMC团队',
                'lead'    => '咨询直销系统、预约演示、申请报价或讨论系统迁移，请选择您方便的联系方式。',
                'actions' => [
                    ['label' => '致电 +66 99 109 5544', 'url' => 'tel:+66991095544', 'style' => 'primary'],
                    ['label' => 'LINE omc54', 'url' => 'https://line.me/ti/p/~omc54', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '联系方式',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'phone', 'title' => '+66 99 109 5544', 'text' => '与销售团队及客户服务团队通话', 'url' => 'tel:+66991095544'],
                    ['icon' => 'chat', 'title' => 'LINE：omc54', 'text' => '发送消息或奖金制度文件', 'url' => 'https://line.me/ti/p/~omc54'],
                    ['icon' => 'mail', 'title' => 'sale@omc.co.th', 'text' => '联系销售部门、申请报价', 'url' => 'mailto:sale@omc.co.th'],
                    ['icon' => 'mail', 'title' => 'info@omc.co.th', 'text' => '一般咨询', 'url' => 'mailto:info@omc.co.th'],
                    ['icon' => 'mail', 'title' => 'projectyak@hotmail.com', 'text' => '项目团队联系邮箱', 'url' => 'mailto:projectyak@hotmail.com'],
                    ['icon' => 'facebook', 'title' => 'Facebook', 'text' => 'MLM Software by OMC', 'url' => 'https://www.facebook.com/mlmsoftwarebyomc'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'eyebrow' => '办公室',
                'title'   => '公司地址',
                'lead'    => 'OMC（Online Marketing Communication Co., Ltd.），Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand（泰国暖武里府）',
                'bullets' => ['来访前请提前预约'],
                'action'  => ['label' => '打开地图', 'url' => 'https://www.google.com/maps/search/?api=1&query=%E0%B8%AD%E0%B8%B2%E0%B8%84%E0%B8%B2%E0%B8%A3%E0%B8%82%E0%B8%88%E0%B8%A3%E0%B8%9B%E0%B8%A3%E0%B8%B0%E0%B8%A8%E0%B8%B2%E0%B8%AA%E0%B8%99%E0%B9%8C+%E0%B8%99%E0%B8%99%E0%B8%97%E0%B8%9A%E0%B8%B8%E0%B8%A3%E0%B8%B5', 'style' => 'outline'],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'contact',
                'title'    => '给团队留言',
                'lead'     => '团队会通过您填写的联系方式与您联系。',
                'submit'   => '发送留言',
            ],
        ],
    ],

    // ------------------------------------------------------------------ FAQ
    '/faq/' => [
        'path'        => '/zh/faq/',
        'title'       => '常见问题',
        'seo_title'   => 'MLM软件常见问题 | OMC',
        'description' => '关于MLM Software by OMC的解答：支持的奖金制度、定制、旧系统迁移、价格、上线时间、上线后支持，以及泰国OCPB直销登记。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'FAQ',
                'title'   => '常见问题',
                'lead'    => '汇总直销企业主经常问我们的问题。如果找不到答案，欢迎通过任何渠道联系团队。',
            ],
            [
                'type'  => 'faq',
                'title' => '关于OMC',
                'items' => [
                    ['question' => 'OMC是什么公司？', 'answer' => 'OMC（Online Marketing Communication Co., Ltd.）是一家于2009年注册的泰国软件公司，办公室位于暖武里府，为直销企业开发MLM Software by OMC。'],
                    ['question' => '曾为哪些类型的企业做过系统？', 'answer' => '系统为通过会员网络销售产品的直销企业设计，例如保健食品、化妆品、健康产品与经销商。预约演示时，可以询问与您业务相近的案例做法。'],
                    ['question' => '团队在哪里？如何联系？', 'answer' => '团队在泰国暖武里府，可通过电话 +66 99 109 5544、LINE omc54 或电子邮件 sale@omc.co.th 联系。'],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于系统',
                'items' => [
                    ['question' => '系统支持哪些奖金制度？', 'answer' => '可配置多种制度，如Binary、Unilevel、Matrix与Hybrid。Board和Stair Step可按需求定制，请咨询我们的团队，并提供您的制度文件。'],
                    ['question' => '系统可以定制到什么程度？', 'answer' => '奖金制度、积分、产品与权限可以在系统中设定；标准系统中没有的条件或页面，可按项目范围扩充开发。'],
                    ['question' => '会员可以在手机上使用吗？', 'answer' => '可以。会员后台可通过手机浏览器使用，查看团队、积分与奖金。'],
                    ['question' => '数据安全吗？', 'answer' => '系统按菜单区分员工权限，会员只能看到本人与团队的资料，重要资料的修改会被记录以便核查。服务器与数据备份的细节在每个项目中约定。'],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '起步与价格',
                'items' => [
                    ['question' => '起步价格是多少？', 'answer' => '取决于奖金制度、使用的模块、定制工作与数据迁移。请在价格页面申请报价。'],
                    ['question' => '多久可以上线？', 'answer' => '取决于工作范围。以标准系统配置的项目比专属开发所需时间更短，团队会在整理需求后提供工作计划。'],
                    ['question' => '可以从旧系统迁移数据吗？', 'answer' => '可以。团队会检查旧数据格式，规划会员、网络结构与累计业绩的迁移，并在上线前测试。'],
                    ['question' => '使用系统前需要先办理泰国OCPB登记吗？', 'answer' => '在泰国向OCPB（สคบ.）办理直销登记，是经营企业依法应尽的责任，与使用哪套系统无关。基本资讯请参阅我们的OCPB登记参考页面。'],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '上线之后',
                'items' => [
                    ['question' => '上线后有团队支持吗？', 'answer' => '有。可通过电话、LINE或电子邮件反馈问题，支持方式与系统改进安排在每个项目的合约中约定。'],
                    ['question' => '以后可以修改奖金制度吗？', 'answer' => '可以。系统支持的条件调整可在设定中完成；若要改变制度结构，可能需要额外开发并在正式使用前测试。'],
                ],
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Support
    '/support/' => [
        'path'        => '/zh/support/',
        'title'       => '客户支持',
        'seo_title'   => 'MLM Software客户支持与问题反馈 | OMC',
        'description' => 'MLM Software by OMC 客户的支持渠道：通过电话、LINE与电子邮件反馈使用问题、申请调整系统或咨询设定，并了解反馈前应准备的资料。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Support',
                'title'   => '客户支持',
                'lead'    => '使用OMC系统的客户，可通过以下渠道反馈问题或寻求协助。支持团队在泰国，熟悉您的系统。',
                'actions' => [
                    ['label' => '致电 +66 99 109 5544', 'url' => 'tel:+66991095544', 'style' => 'primary'],
                    ['label' => 'LINE omc54', 'url' => 'https://line.me/ti/p/~omc54', 'style' => 'secondary'],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '这样反馈问题，处理更快',
                'lead'  => '提供这些信息，团队首次就能找到原因。',
                'items' => [
                    ['title' => '说明公司名称与联系人', 'text' => '方便团队打开正确的系统'],
                    ['title' => '描述发生的情况', 'text' => '哪个页面、正在做什么，结果与预期有何不同'],
                    ['title' => '附上截图或参考编号', 'text' => '例如订单编号、会员编号或计算周期'],
                    ['title' => '等待团队确认', 'text' => '团队会回复处理状态与解决方式'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '支持团队可以协助的事项',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'help', 'title' => '系统操作方法', 'text' => '为新员工讲解后台菜单的使用'],
                    ['icon' => 'calculator', 'title' => '核查计算结果', 'text' => '会员询问时，协助核查奖金来源'],
                    ['icon' => 'sliders', 'title' => '调整设定', 'text' => '调整系统支持的产品、积分或条件'],
                    ['icon' => 'bug', 'title' => '反馈系统错误', 'text' => '系统运行异常时进行检查与修正'],
                    ['icon' => 'code', 'title' => '申请扩充开发', 'text' => '评估新功能并提出工作范围'],
                    ['icon' => 'book', 'title' => '直销知识', 'text' => '阅读我们的文章（泰文）', 'url' => '/blog/'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'contact',
                'title'    => '向支持团队反馈',
                'lead'     => '请在详情栏填写公司名称与问题说明，团队会与您联系。',
                'submit'   => '提交反馈',
            ],
        ],
    ],

    // ------------------------------------------------------------------ Portfolio
    '/portfolio/' => [
        'path'        => '/zh/portfolio/',
        'title'       => '项目与案例',
        'seo_title'   => '直销系统项目与案例 | OMC',
        'description' => 'OMC直销系统项目与案例：我们正在整理已获客户授权公开的案例。在此期间，预约系统演示时即可查看与您业务类型相近的系统示例。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Portfolio',
                'title'   => '项目与案例',
                'lead'    => '我们只公开已获客户授权的案例，目前正在整理中。',
            ],
            [
                'type'       => 'rich_text',
                'title'      => '案例整理中',
                'paragraphs' => [
                    '直销系统中保存着客户的会员与销售数据，因此我们不会在未经授权的情况下展示客户名称或系统画面。客户同意后，案例会在本页公开。',
                    '在此期间，团队乐意在演示中展示与您业务类型相近的系统示例，并说明解决同类问题的做法，同时不会透露其他客户的资料。',
                ],
            ],
            [
                'type'  => 'industry_grid',
                'title' => '按业务类型查看方案',
                'items' => [
                    ['icon' => 'leaf', 'title' => '保健食品', 'subtitle' => 'Supplements', 'url' => '/solutions/supplements/'],
                    ['icon' => 'sparkle', 'title' => '化妆品', 'subtitle' => 'Cosmetics', 'url' => '/solutions/cosmetics/'],
                    ['icon' => 'box', 'title' => '经销商', 'subtitle' => 'Distributor', 'url' => '/solutions/distributor/'],
                ],
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Blog index (listing is generated; this row holds SEO)
    '/blog/' => [
        'path'        => '/zh/blog/',
        'title'       => '文章',
        'seo_title'   => '直销与MLM软件文章 | OMC',
        'description' => 'OMC团队撰写的直销与MLM知识文章：奖金制度、奖金计算、会员管理与如何选择MLM软件，帮助企业主更好地规划直销网络业务。',
        'blocks'      => [],
    ],

    // ------------------------------------------------------------------ Thank-you (noindex)
    '/thank-you/' => [
        'path'        => '/zh/thank-you/',
        'title'       => '感谢您联系OMC',
        'seo_title'   => '感谢您联系OMC | OMC',
        'description' => 'OMC团队已收到您的资料，将尽快与您联系。在此期间，可先了解系统功能与奖金制度，或致电 +66 99 109 5544、LINE omc54 联系团队。',
        'robots'      => 'noindex',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'title'   => '感谢您联系OMC',
                'lead'    => '我们已收到您的资料，团队会通过您填写的联系方式与您联系。',
                'actions' => [
                    ['label' => '致电 +66 99 109 5544', 'url' => 'tel:+66991095544', 'style' => 'primary'],
                    ['label' => 'LINE omc54', 'url' => 'https://line.me/ti/p/~omc54', 'style' => 'secondary'],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '接下来',
                'items' => [
                    ['title' => '团队查看资料', 'text' => '了解您的业务类型与需求'],
                    ['title' => '与您联系', 'text' => '在方便的时间约谈或预约演示'],
                    ['title' => '整理方案', 'text' => '准备就绪时提供工作范围与报价'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '等待期间',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'layers', 'title' => '系统功能', 'url' => '/features/'],
                    ['icon' => 'plan-hybrid', 'title' => '奖金制度', 'url' => '/compensation-plans/'],
                    ['icon' => 'book', 'title' => '文章', 'url' => '/blog/'],
                ],
            ],
        ],
    ],
];
