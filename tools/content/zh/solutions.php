<?php
/**
 * Chinese (Simplified) solutions pages — 7 industry pages under /solutions/.
 * Keys are the Thai paths; 'path' is the /zh translated URL. Internal links use Thai paths (builder prefixes /zh).
 * Compliance: use 直销 / 多层次直销 / MLM only. No income promises, no unverified numbers.
 */

declare(strict_types=1);

return [

    // ---------------------------------------------------------------- supplements
    '/solutions/supplements/' => [
        'path'        => '/zh/solutions/supplements/',
        'title'       => '保健品直销系统',
        'seo_title'   => '保健品直销系统：复购与积分管理 | OMC',
        'description' => '为保健食品直销企业设计的系统方案：按产品设定PV/BV、管理会员复购订单、分公司库存与奖金计算。OMC按您的产品线与奖金制度配置。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 保健食品',
                'title'   => '保健品直销系统',
                'lead'    => '保健食品的直销业务，依靠的是会员持续复购与清楚的积分规则。OMC帮助保健品企业把产品、订单、库存与奖金放在同一系统中管理。',
                'actions' => [
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '保健品直销企业的系统需求',
                'paragraphs' => [
                    '保健食品的消费周期相对固定，会员往往按月重复购买同一类产品。系统需要让会员方便地再次下单，同时准确记录每笔订单的积分，并在结算周期内计入业绩。定期自动下单（Autoship）等复购机制可作为定制范围，在项目中评估。',
                    '不同产品的利润结构不同，因此每项产品的价格与PV/BV通常需要分别设定；推出新品或促销套组时，也要能快速调整积分与显示状态。产品批次多、分公司多时，库存与出入库单据更需要有系统记录。',
                    '另外请注意，产品标签、成分说明与泰国食品药品管理局（Thai FDA）等相关注册事项，属于品牌方的责任，系统不能代替这些审核。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '相关模块',
                'title'   => '保健品企业常用的系统模块',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'layers', 'title' => '产品与积分', 'text' => '按产品设定价格与PV/BV，促销与套组可设定显示或隐藏。'],
                    ['icon' => 'cart', 'title' => '会员复购下单', 'text' => '会员在后台或网上商城重复下单，以电子钱包或支付网关付款。'],
                    ['icon' => 'box', 'title' => '库存与单据', 'text' => '扣减库存、入库、分公司库存与出入库单据集中管理。'],
                    ['icon' => 'map-pin', 'title' => '配送跟踪', 'text' => '跟踪配送、分公司收货、待发货与待收货状态。'],
                    ['icon' => 'calculator', 'title' => '奖金计算', 'text' => '按日、按期或按月计算奖金，可配合Hold PV分配积分。', 'url' => '/commission-system/'],
                    ['icon' => 'chart', 'title' => '销售报表', 'text' => '按产品、按期间查看销售，报表可导出Excel。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖金制度',
                'title'   => '保健品企业常见的奖金制度',
                'lead'    => '以下制度均可在系统中配置，具体规则按您的设计。',
                'plans'   => [
                    ['key' => 'unilevel', 'name' => 'Unilevel 单层制', 'text' => '以复购业绩为主的企业常用，按层数计算。可配置。', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'binary', 'name' => 'Binary 双轨制', 'text' => '左右两区业绩配对计算。可配置。', 'url' => '/compensation-plans/binary/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '组合多种奖金。可配置。', 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '保健品直销系统常见问题',
                'items' => [
                    ['q' => '系统支持定期自动下单吗？', 'a' => '自动复购（Autoship）可作为定制范围，按您的业务规则评估实现方式。'],
                    ['q' => '每项产品的积分可以不同吗？', 'a' => '可以。每项产品可分别设定价格与PV/BV，促销期间也可以调整。'],
                    ['q' => '系统会检查产品是否通过FDA注册吗？', 'a' => '不会。产品注册与标签内容属于品牌方的责任，系统负责管理产品资料、订单与积分。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '咨询保健品直销系统',
                'lead'     => '告诉我们您的产品线与奖金制度，OMC顾问会与您联系。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

    // ---------------------------------------------------------------- cosmetics
    '/solutions/cosmetics/' => [
        'path'        => '/zh/solutions/cosmetics/',
        'title'       => '化妆品直销系统',
        'seo_title'   => '化妆品直销系统：多品项与促销管理 | OMC',
        'description' => '为化妆品与护肤品直销品牌设计的系统方案：管理多品项、套组与促销积分，会员可用手机下单，分公司库存与奖金计算在同一平台完成，欢迎咨询。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 化妆品与护肤',
                'title'   => '化妆品直销系统',
                'lead'    => '化妆品与护肤品牌品项多、新品快、促销频繁。OMC的直销系统让品牌在调整产品与促销时，积分、库存与奖金同步更新。',
                'actions' => [
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '化妆品与护肤品牌的系统需求',
                'paragraphs' => [
                    '化妆品品牌常见的情况是：同一系列有不同容量和色号，节日推出限定套组，新品上市时搭配促销价格。每一次变化都牵涉价格、PV/BV和库存，如果系统不能灵活设定，营运团队就只能靠人工换算，奖金也容易出错。',
                    '化妆品的会员中，不少人以手机为主要工具，会在社群中分享产品并协助身边的人下单。会员后台在手机上清楚好用，能看到订单、积分与团队情况，对品牌的日常运营帮助很大。',
                    '产品成分标示、广告用语与相关注册，属于品牌方的责任，请依泰国相关法规自行确认。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '相关模块',
                'title'   => '化妆品品牌常用的系统模块',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'layers', 'title' => '品项与促销', 'text' => '管理产品、套组与促销价格，设定PV/BV与显示状态。'],
                    ['icon' => 'phone', 'title' => '手机会员后台', 'text' => '会员在手机上下单、查看积分、订单与奖金明细。'],
                    ['icon' => 'wallet', 'title' => '电子钱包', 'text' => '充值、转账、提现，会员可用余额下单。'],
                    ['icon' => 'box', 'title' => '库存管理', 'text' => '各仓库与分公司的库存、入库与出入库单据。'],
                    ['icon' => 'calculator', 'title' => '奖金计算', 'text' => '按您的奖金制度在每个周期自动计算。', 'url' => '/commission-system/'],
                    ['icon' => 'chart', 'title' => '报表', 'text' => '按产品、促销与期间分析销售，可导出Excel。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖金制度',
                'title'   => '化妆品品牌常见的奖金制度',
                'lead'    => '以下制度均可配置，规则按品牌设计。',
                'plans'   => [
                    ['key' => 'binary', 'name' => 'Binary 双轨制', 'text' => '左右两区业绩配对计算。可配置。', 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => 'Unilevel 单层制', 'text' => '按制度规定的层数计算奖金。可配置。', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '在同一系统中组合多种奖金。可配置。', 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '化妆品直销系统常见问题',
                'items' => [
                    ['q' => '限定套组可以单独设定积分吗？', 'a' => '可以。套组可作为独立产品设定价格与PV/BV，活动结束后可设为隐藏。'],
                    ['q' => '会员能在手机上完成下单吗？', 'a' => '可以。会员后台支持手机浏览，会员可在线下单并以电子钱包或支付网关付款。'],
                    ['q' => '新品上市需要请工程师修改系统吗？', 'a' => '一般的新增产品、调整价格与积分，员工在后台即可完成，不需要修改程序。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '咨询化妆品直销系统',
                'lead'     => '介绍您的品牌、产品系列与奖金制度，OMC顾问会与您联系。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

    // ---------------------------------------------------------------- health-wellness
    '/solutions/health-wellness/' => [
        'path'        => '/zh/solutions/health-wellness/',
        'title'       => '健康美容直销系统',
        'seo_title'   => '健康美容直销系统：会员与产品管理 | OMC',
        'description' => '为健康、美容与养生类直销企业设计的系统方案：会员网络、产品积分、门店与分公司库存、奖金计算统一管理，由OMC按您的业务模式配置。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 健康与美容',
                'title'   => '健康美容直销系统',
                'lead'    => '健康、美容与养生类企业，往往同时经营线上订购与线下门店。OMC帮助企业把会员、产品、库存和奖金整合在同一系统中。',
                'actions' => [
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '健康美容企业的系统需求',
                'paragraphs' => [
                    '这类企业的产品线通常较广，可能包括营养补充、美容护理、健身与养生用品等。会员既会在线上下单，也会到门店或分公司取货，因此系统要能同时追踪配送与门店收货，库存要按门店和分公司分别管理。',
                    '会员对品牌的信任来自持续的服务体验。会员后台若能清楚显示订单状态、积分和奖金明细，客服的负担就会减轻，会员也更愿意长期使用。对于管理层，按门店、按产品类别查看的报表，则有助于安排进货与促销。',
                    '健康与美容产品的功效宣传和注册事项，属于品牌方的责任，请依相关法规自行确认。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '相关模块',
                'title'   => '健康美容企业常用的系统模块',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'store', 'title' => '门店与分公司', 'text' => '管理泰国国内与海外分公司，各自的库存与单据。'],
                    ['icon' => 'map-pin', 'title' => '配送与门店收货', 'text' => '跟踪配送、分公司收货、待发货与待收货。'],
                    ['icon' => 'layers', 'title' => '产品与促销', 'text' => '多类别产品分别设定价格与PV/BV。'],
                    ['icon' => 'users', 'title' => '会员管理', 'text' => '注册、安置到网络，维护会员资料。', 'url' => '/member-system/'],
                    ['icon' => 'calculator', 'title' => '奖金计算', 'text' => '按日、按期或按月计算，规则按您的制度设定。'],
                    ['icon' => 'lock', 'title' => '员工权限', 'text' => '门店与总部员工按菜单分配不同权限。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖金制度',
                'title'   => '可配置的奖金制度',
                'plans'   => [
                    ['key' => 'unilevel', 'name' => 'Unilevel 单层制', 'text' => '按层数计算奖金。可配置。', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => 'Matrix 矩阵制', 'text' => '限定每层宽度的结构。可配置。', 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '组合多种奖金。可配置。', 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '健康美容直销系统常见问题',
                'items' => [
                    ['q' => '线上订单和门店取货可以同时管理吗？', 'a' => '可以。系统跟踪配送与分公司收货状态，库存按分公司分别管理。'],
                    ['q' => '门店员工可以只看到自己门店的资料吗？', 'a' => '员工权限按菜单设定；按门店区分可见范围等细节可按项目范围定制。'],
                    ['q' => '可以先上线部分功能吗？', 'a' => '可以。常见做法是先上线会员、订单与奖金计算，再逐步加入门店与库存模块。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '咨询健康美容直销系统',
                'lead'     => '告诉我们您的产品类别、门店与分公司情况，OMC顾问会与您联系。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

    // ---------------------------------------------------------------- distributor
    '/solutions/distributor/' => [
        'path'        => '/zh/solutions/distributor/',
        'title'       => '经销商与代理商系统',
        'seo_title'   => '经销商管理系统：订单库存与奖励 | OMC',
        'description' => '为批发、经销与代理商网络设计的系统方案：管理经销商资料与推荐关系、订单与分公司库存、电子钱包与奖励计算，按您的渠道规则配置。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 批发与经销',
                'title'   => '经销商与代理商系统',
                'lead'    => '以经销商和代理商为主要渠道的企业，需要同时管理进货订单、库存与渠道奖励。OMC按您的渠道规则配置系统。',
                'actions' => [
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '经销与代理业务的系统需求',
                'paragraphs' => [
                    '经销商和代理商通常是批量进货，再转售给终端客户。企业需要掌握每位经销商的进货记录、所在地区和推荐关系，并按渠道规则计算奖励。订单金额较大时，付款、出货与收货的每一步都需要清楚的单据。',
                    '当经销网络扩展到不同地区，分公司和仓库的库存管理就更加关键：哪个仓库发货、货物是否已送达、分公司还有多少库存，都要能在系统中查到。总部也需要通过报表了解各地区、各经销商的进货情况，以便安排生产与补货。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '相关模块',
                'title'   => '经销企业常用的系统模块',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => '经销商资料', 'text' => '注册、推荐关系与网络位置，资料修改留有记录。'],
                    ['icon' => 'inbox', 'title' => '订单与单据', 'text' => '销售单、入库单、出入库单据与账单管理。'],
                    ['icon' => 'box', 'title' => '分公司库存', 'text' => '各仓库与分公司库存、扣减与入库。'],
                    ['icon' => 'map-pin', 'title' => '配送跟踪', 'text' => '待发货、配送中、分公司收货状态。'],
                    ['icon' => 'wallet', 'title' => '电子钱包与付款', 'text' => '电子钱包充值与提现，并可对接支付网关。'],
                    ['icon' => 'chart', 'title' => '渠道报表', 'text' => '按地区、经销商与产品查看进货，可导出Excel。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖励制度',
                'title'   => '可配置的奖励制度',
                'plans'   => [
                    ['key' => 'unilevel', 'name' => 'Unilevel 单层制', 'text' => '按推荐层数计算奖励。可配置。', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '组合渠道奖励与其他奖金。可配置。', 'url' => '/compensation-plans/hybrid/'],
                    ['key' => 'stair_step', 'name' => 'Stair Step', 'text' => '按业绩阶梯的制度可按需求定制，请咨询我们的团队。', 'url' => '/compensation-plans/stair-step/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '经销商系统常见问题',
                'items' => [
                    ['q' => '不同级别的经销商可以有不同价格吗？', 'a' => '价格与积分设定可按您的渠道规则配置，复杂的分级价格可按项目范围定制。'],
                    ['q' => '经销商能自己在线进货吗？', 'a' => '可以。经销商可在后台下单，以电子钱包或支付网关付款，并查看配送状态。'],
                    ['q' => '可以管理多个仓库吗？', 'a' => '可以。系统支持分公司与仓库库存管理，以及出入库单据。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '咨询经销商管理系统',
                'lead'     => '介绍您的渠道结构与奖励规则，OMC顾问会与您联系。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

    // ---------------------------------------------------------------- franchise
    '/solutions/franchise/' => [
        'path'        => '/zh/solutions/franchise/',
        'title'       => '加盟连锁系统',
        'seo_title'   => '加盟连锁系统：门店会员与推荐奖励 | OMC',
        'description' => '为加盟与连锁业务设计的系统方案：加盟店与分公司管理、门店库存与单据、会员推荐关系与奖励计算，由OMC按您的加盟模式配置与定制。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 加盟连锁',
                'title'   => '加盟连锁系统',
                'lead'    => '加盟业务需要总部掌握每家门店的进货与销售，同时管理会员与推荐奖励。OMC按您的加盟模式配置系统。',
                'actions' => [
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '加盟连锁业务的系统需求',
                'paragraphs' => [
                    '加盟体系里，总部、加盟店与会员之间的关系比一般零售复杂：加盟店向总部进货，会员在门店或线上购买，部分企业还会按推荐关系给予奖励。系统需要清楚区分这些角色，并让各方看到自己应看的资料。',
                    '总部最关心的是各门店的库存与单据是否准确，以及整体销售趋势。加盟店则需要方便地下单补货、查看收货状态。若企业结合会员推荐制度，奖励规则也要在系统中明确设定，并能追溯到具体订单。',
                    '加盟体系扩展到新的城市或国家时，系统也要能跟上：新增门店只需在后台建立分公司资料并分配员工权限，库存与单据即可纳入总部的统一管理。具体的加盟规则与报表需求，OMC顾问会在项目开始前与您逐项确认。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '相关模块',
                'title'   => '加盟连锁常用的系统模块',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'store', 'title' => '加盟店与分公司', 'text' => '以分公司模块管理各门店，泰国国内与海外均可。'],
                    ['icon' => 'box', 'title' => '门店库存', 'text' => '门店库存、入库与出入库单据，总部统一查看。'],
                    ['icon' => 'map-pin', 'title' => '补货与收货', 'text' => '跟踪待发货、配送与分公司收货状态。'],
                    ['icon' => 'users', 'title' => '会员与推荐关系', 'text' => '会员注册并记录推荐人，按规则安置到网络。'],
                    ['icon' => 'calculator', 'title' => '奖励计算', 'text' => '按周期计算推荐与销售奖励。'],
                    ['icon' => 'lock', 'title' => '权限分工', 'text' => '总部与门店员工按菜单分配权限。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖励制度',
                'title'   => '可配置的奖励制度',
                'plans'   => [
                    ['key' => 'unilevel', 'name' => 'Unilevel 单层制', 'text' => '按推荐层数计算奖励。可配置。', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '把门店销售与会员推荐奖励组合。可配置。', 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '加盟连锁系统常见问题',
                'items' => [
                    ['q' => '每家加盟店都能有自己的库存吗？', 'a' => '可以。各加盟店以分公司方式管理库存与单据，总部可统一查看。'],
                    ['q' => '加盟费与权利金能在系统中管理吗？', 'a' => '这类功能不在标准模块内，可按项目范围评估定制。'],
                    ['q' => '没有推荐奖励的加盟模式也适用吗？', 'a' => '可以只使用门店、库存与订单相关模块，具体范围由顾问与您确认。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '咨询加盟连锁系统',
                'lead'     => '介绍您的加盟模式与门店规划，OMC顾问会与您联系。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

    // ---------------------------------------------------------------- international
    '/solutions/international/' => [
        'path'        => '/zh/solutions/international/',
        'title'       => '跨国直销系统',
        'seo_title'   => '跨国直销系统：海外分公司统一管理 | OMC',
        'description' => '为在多个国家经营直销业务的企业设计：泰国与海外分公司在同一系统管理库存与单据，多语言、多币种与当地付款方式可按项目范围定制。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 跨国业务',
                'title'   => '跨国直销系统',
                'lead'    => '业务跨越多个国家时，总部需要一张统一的会员网络与清楚的分公司数据。OMC帮助企业在同一系统中管理泰国与海外分公司。',
                'actions' => [
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '跨国直销业务的系统需求',
                'paragraphs' => [
                    '直销会员网络常常跨越国界：一位泰国会员可能推荐了邻国的新会员，而奖金需要按同一套制度计算。若各国使用不同系统，网络与业绩就会被切断，跨国结算变得十分困难。',
                    '因此，跨国企业通常希望会员网络与奖金计算统一，而各国分公司分别管理库存、单据与配送。语言、币种、当地付款方式与各国法规要求，因国家而异，需要在项目开始时逐项确认。OMC的系统支持管理泰国国内与海外分公司；多语言、多币种及当地付款对接，按项目范围定制。',
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => '相关模块',
                'title'   => '跨国企业常用的系统模块',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'globe', 'title' => '海外分公司', 'text' => '泰国国内与海外分公司在同一系统中管理。'],
                    ['icon' => 'network', 'title' => '统一会员网络', 'text' => '跨国推荐关系保持在同一网络中。'],
                    ['icon' => 'box', 'title' => '各国库存与单据', 'text' => '各分公司分别管理库存与出入库单据。'],
                    ['icon' => 'plug', 'title' => '付款对接', 'text' => '通过支付API对接支付网关，当地方式按范围评估。'],
                    ['icon' => 'sliders', 'title' => '语言与币种', 'text' => '多语言界面与多币种按项目范围定制。'],
                    ['icon' => 'chart', 'title' => '跨国报表', 'text' => '按分公司查看销售与库存，可导出Excel。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖金制度',
                'title'   => '可配置的奖金制度',
                'plans'   => [
                    ['key' => 'binary', 'name' => 'Binary 双轨制', 'text' => '左右两区业绩配对。可配置。', 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => 'Unilevel 单层制', 'text' => '按层数计算奖金。可配置。', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '组合多种奖金。可配置。', 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '跨国直销系统常见问题',
                'items' => [
                    ['q' => '系统可以使用中文和英文吗？', 'a' => '多语言界面可按项目范围定制，请说明管理后台与会员后台各自需要的语言。'],
                    ['q' => '不同国家的币种如何处理？', 'a' => '多币种与汇率处理方式需按您的业务规则确认，作为定制范围评估。'],
                    ['q' => '各国的法规要求由系统处理吗？', 'a' => '各国直销法规与登记要求需由企业自行确认，系统负责保存清楚的会员、订单与奖金记录。'],
                    ['q' => '我们总部不在泰国，如何沟通？', 'a' => '可通过电话（+66 99 109 5544）、LINE（ID：omc54）与电子邮件（sale@omc.co.th）联系OMC团队。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '咨询跨国直销系统',
                'lead'     => '告诉我们您目前及计划经营的国家，OMC顾问会与您联系。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

    // ---------------------------------------------------------------- custom
    '/solutions/custom/' => [
        'path'        => '/zh/solutions/custom/',
        'title'       => '定制直销系统',
        'seo_title'   => '定制直销系统：为特殊模式设计 | OMC',
        'description' => '业务模式不同于一般直销？OMC在可配置的标准模块基础上，为特殊的奖金制度、流程与报表需求设计定制直销系统，范围事先书面确认。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => '行业方案 · 定制系统',
                'title'   => '定制直销系统',
                'lead'    => '当您的业务模式无法套用常见方案时，OMC可在标准模块的基础上，按您的规则设计定制直销系统。',
                'actions' => [
                    ['label' => '获取报价', 'url' => '/pricing/', 'style' => 'primary'],
                    ['label' => '预约顾问咨询', 'url' => '/contact/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '哪些企业需要定制直销系统',
                'paragraphs' => [
                    '有些企业结合了直销、联盟推荐与门店销售；有些企业的奖金制度包含独特的晋级条件；还有些企业需要与现有的会计或物流系统交换资料。这些需求无法只靠标准设定完成，就需要定制。',
                    'OMC的做法是先拆解需求：哪些部分可以直接使用会员、产品、库存、电子钱包、奖金与报表等标准模块，哪些部分需要定制编写。确认后把定制范围写成书面内容并报价，开发完成后与您一起测试核对，再安排上线与后续支持。',
                    '提出定制需求时，建议同时说明背后的业务目的，例如希望鼓励哪种行为、需要哪些部门使用某份报表。理解目的之后，团队有时能找到更简单、更容易维护的做法，让系统在业务继续变化时也能顺利调整。',
                ],
            ],
            [
                'type'  => 'steps',
                'eyebrow' => '定制流程',
                'title' => '定制直销系统的进行方式',
                'items' => [
                    ['title' => '了解业务模式', 'text' => '访谈您的流程、奖金规则与特殊需求。'],
                    ['title' => '区分配置与定制', 'text' => '确认哪些用标准模块完成，哪些需要定制。'],
                    ['title' => '书面范围与报价', 'text' => '定制内容写清楚，双方确认后开始。'],
                    ['title' => '开发与测试核对', 'text' => '完成开发后用测试数据核对结果。'],
                    ['title' => '上线与支持', 'text' => '培训员工，上线后持续支持。'],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => '奖金制度',
                'title'   => '可配置与可定制的奖金制度',
                'plans'   => [
                    ['key' => 'hybrid', 'name' => 'Hybrid 混合制', 'text' => '组合多种奖金。可配置。', 'url' => '/compensation-plans/hybrid/'],
                    ['key' => 'board', 'name' => 'Board', 'text' => '可按需求定制，请咨询我们的团队。', 'url' => '/compensation-plans/board/'],
                    ['key' => 'stair_step', 'name' => 'Stair Step', 'text' => '可按需求定制，请咨询我们的团队。', 'url' => '/compensation-plans/stair-step/'],
                ],
                'action'  => ['label' => '了解全部奖金制度', 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => '定制直销系统常见问题',
                'items' => [
                    ['q' => '定制的费用如何计算？', 'a' => '按定制内容评估。OMC会先确认书面范围再报价，范围外的新需求另行评估。'],
                    ['q' => '可以和我们现有的系统对接吗？', 'a' => '系统支持对接支付API；其他系统的对接需评估双方的接口与资料格式后确定。'],
                    ['q' => '定制后还能继续调整吗？', 'a' => '可以。上线后的新需求由团队评估影响与费用，经您确认后进行。'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => '顾问咨询',
                'title'    => '说说您的业务模式',
                'lead'     => '描述您的特殊需求，OMC顾问会与您联系，评估如何配置与定制。',
                'submit'   => '提交咨询',
            ],
        ],
    ],

];
