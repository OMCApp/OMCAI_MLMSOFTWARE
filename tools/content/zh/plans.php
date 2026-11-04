<?php
/**
 * Compensation plans — hub + 6 plan pages (Simplified Chinese). Keys = Thai page path; 'path' = /zh + Thai path.
 *
 * ‼️ Board and Stair Step are NOT confirmed as standard features — described as
 *    "可按需求定制，请咨询我们的团队". Use 直销 / 多层次直销 / MLM only.
 */

declare(strict_types=1);

$related = static function (string $current): array {
    $all = [
        '/compensation-plans/binary/'     => ['plan-binary', 'Binary 双轨制'],
        '/compensation-plans/unilevel/'   => ['plan-unilevel', 'Unilevel 单层制'],
        '/compensation-plans/matrix/'     => ['plan-matrix', 'Matrix 矩阵制'],
        '/compensation-plans/board/'      => ['plan-board', 'Board'],
        '/compensation-plans/stair-step/' => ['plan-stair', 'Stair Step'],
        '/compensation-plans/hybrid/'     => ['plan-hybrid', 'Hybrid 混合制'],
    ];
    $items = [];
    foreach ($all as $url => [$icon, $name]) {
        if ($url !== $current) {
            $items[] = ['icon' => $icon, 'title' => $name, 'url' => $url];
        }
    }

    return ['type' => 'feature_grid', 'eyebrow' => '其他制度', 'title' => '与其他奖金制度比较', 'columns' => 3, 'items' => $items];
};

$planCta = [
    'type'    => 'cta_band',
    'title'   => '已经有奖金制度文件了吗？',
    'text'    => '发给OMC团队看看，我们会告诉您哪些部分可以直接配置，哪些部分需要额外设计。',
    'actions' => [
        ['label' => '咨询奖金制度', 'url' => '/contact/', 'style' => 'light', 'icon' => 'chat'],
        ['label' => '预约演示', 'url' => '/demo/', 'style' => 'ghost-light', 'icon' => 'calendar'],
    ],
];

$disclaimer = '本页示例仅用于说明原理，不代表对会员收入的任何保证。奖金制度由经营企业制定，并须符合直销相关法律。';

return [

    // ------------------------------------------------------------------ hub
    '/compensation-plans/' => [
        'path'        => '/zh/compensation-plans/',
        'title'       => 'MLM奖金制度',
        'seo_title'   => 'MLM奖金制度比较：Binary与Unilevel等 | OMC',
        'description' => '比较直销MLM奖金制度：Binary、Unilevel、Matrix、Board、Stair Step与Hybrid的特点、注意事项与适合的业务，以及在OMC系统中的配置方式。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Compensation Plans',
                'title'   => '哪种MLM奖金制度适合您的业务',
                'lead'    => '奖金制度（Compensation Plan）决定会员如何从销售中获得回报。本页整理常见制度的原理，方便您与团队沟通、做出决定。',
            ],
            [
                'type'  => 'plan_cards',
                'title' => '可在系统中配置的奖金制度',
                'lead'  => 'Binary、Unilevel、Matrix与Hybrid可设定条件与比例；Board与Stair Step可按需求定制，请咨询我们的团队。',
                'plans' => [
                    ['key' => 'binary', 'name' => 'Binary', 'text' => '左右两区，按两边业绩配对计算奖金', 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => 'Unilevel', 'text' => '首层可直推多人，按规定层数计算奖金', 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => 'Matrix', 'text' => '明确限定网络的宽度与深度', 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'board', 'name' => 'Board', 'text' => '满盘后分盘 — 可按需求定制，请咨询我们的团队', 'url' => '/compensation-plans/board/'],
                    ['key' => 'stair_step', 'name' => 'Stair Step', 'text' => '按业绩晋升职级 — 可按需求定制，请咨询我们的团队', 'url' => '/compensation-plans/stair-step/'],
                    ['key' => 'hybrid', 'name' => 'Hybrid', 'text' => '在一个制度中组合多种奖金', 'url' => '/compensation-plans/hybrid/'],
                ],
            ],
            [
                'type'    => 'comparison_table',
                'title'   => '奖金制度简要比较',
                'lead'    => '作为讨论的起点，实际细节取决于公司设定的条件。',
                'columns' => ['网络结构', '特点', '注意事项'],
                'rows'    => [
                    ['label' => 'Binary', 'values' => ['每人两个区', '容易理解，团队协作做业绩', '需要明确的封顶与弱区规则']],
                    ['label' => 'Unilevel', 'values' => ['首层较宽', '重视直接推荐与重复消费', '需规定层数与维持业绩的条件']],
                    ['label' => 'Matrix', 'values' => ['宽 × 深 固定', '结构清晰、便于预估', '会员可能被安置在非本人推荐的团队下']],
                    ['label' => 'Board', 'values' => ['满盘分盘', '目标直观', '需按法律要求设计 — 请咨询团队']],
                    ['label' => 'Stair Step', 'values' => ['职级阶梯', '激励团队业绩', '需要职级维持条件 — 请咨询团队']],
                    ['label' => 'Hybrid', 'values' => ['多种组合', '按业务灵活设计', '需要向会员讲清规则']],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '从奖金制度文件到可计算的系统',
                'items' => [
                    ['title' => '解读制度文件', 'text' => '团队逐一拆分每项奖金及其条件'],
                    ['title' => '在系统中设定规则', 'text' => '设定积分、计算周期、封顶与职级'],
                    ['title' => '用示例数据测试', 'text' => '与公司自行计算的数字比对'],
                    ['title' => '上线与回溯核查', 'text' => '每一笔奖金都能查到来自哪张订单'],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于奖金制度的常见问题',
                'items' => [
                    ['question' => '应该选择哪种制度？', 'answer' => '取决于产品、价格、复购频率与销售团队的习惯。以复购为主的企业常从Unilevel开始；希望团队协作做业绩的企业，常考虑Binary或Hybrid。团队可以协助分析。'],
                    ['question' => '可以同时使用多种制度吗？', 'answer' => '可以，以Hybrid的形式实现，例如直推奖加上配对奖与层级奖。'],
                    ['question' => '奖金制度需要经过泰国OCPB审核吗？', 'answer' => '在泰国办理直销登记时，需要向登记机关提交回报分配方案。请向OCPB（สคบ.）或法律顾问确认。系统按公司制定的制度进行计算。'],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => '延伸阅读',
                'html'  => '<ul>'
                    . '<li><a href="/commission-system/">直销奖金计算系统</a></li>'
                    . '<li><a href="/ocpb-mlm/">泰国OCPB直销登记参考</a></li>'
                    . '</ul>'
                    . '<p>' . $disclaimer . '</p>',
            ],
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Binary
    '/compensation-plans/binary/' => [
        'path'        => '/zh/compensation-plans/binary/',
        'title'       => 'Binary 双轨制',
        'seo_title'   => 'Binary双轨制奖金：左右区配对计算 | OMC',
        'description' => '直销Binary双轨制：每位会员有左右两个区，奖金按两区积分配对计算。了解原理、优点、注意事项，以及在OMC系统中的配置方式。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Binary Plan',
                'title'   => 'Binary 双轨制（左右两区）',
                'lead'    => '每位会员可发展左右两个区。系统分别累计两区积分，并按公司规定的周期，以可配对的业绩计算奖金。',
            ],
            [
                'type'  => 'steps',
                'title' => 'Binary如何运作',
                'items' => [
                    ['title' => '安置在左区或右区', 'text' => '新会员按安置规则，被安置在推荐人下方的左区或右区'],
                    ['title' => '累计各区积分', 'text' => '各区团队的消费转化为该区的PV积分'],
                    ['title' => '业绩配对', 'text' => '每个周期，系统以积分较少的一区与较多的一区配对'],
                    ['title' => '计算奖金并结转', 'text' => '按比例与封顶计算奖金，剩余积分按规则结转到下一周期'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'title'   => 'Binary的优点',
                'bullets' => [
                    '结构易懂，会员清楚看到两区业绩',
                    '团队成员一起补强较弱的一区，形成团队合作',
                    '可与其他奖金组合，例如直推奖或对等奖（Matching）',
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'reverse' => true,
                'title'   => '注意事项与需要设定的规则',
                'bullets' => [
                    '每周期奖金封顶，用以控制拨出比例',
                    '积分结转规则，以及长期未配对积分的清除方式',
                    '资格维持条件，例如个人最低消费',
                    '自动安置方式（弱区、最左侧，或由推荐人选择）',
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '哪些业务适合Binary',
                'paragraphs' => [
                    'Binary适合希望团队协作做业绩、产品每套价格明确的业务，例如入会套装或产品配套。许多企业把Binary与直推奖搭配使用，让推荐人也能从推荐中获得回报。',
                    '在OMC系统中，公司可以设定配对比例、每周期封顶、计算周期与结转规则，管理员也能回溯核查每笔奖金来自哪些订单的积分。',
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于Binary的常见问题',
                'items' => [
                    ['question' => '配对后，较多一区的积分去哪里了？', 'answer' => '取决于公司规则。多数会把剩余积分结转到下一周期，也可能设有上限或保留期限，这些都可在系统中设定。'],
                    ['question' => '会员可以选择安置在哪一区吗？', 'answer' => '可以，按公司规定的规则进行，例如由推荐人选择，或由系统自动安置在较弱的一区。'],
                    ['question' => 'Binary可以与Unilevel一起使用吗？', 'answer' => '可以，以Hybrid的形式实现，例如Binary配对奖加上按复购业绩计算的层级奖。'],
                ],
            ],
            $related('/compensation-plans/binary/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Unilevel
    '/compensation-plans/unilevel/' => [
        'path'        => '/zh/compensation-plans/unilevel/',
        'title'       => 'Unilevel 单层制',
        'seo_title'   => 'Unilevel单层制奖金：按层计算 | OMC',
        'description' => '直销Unilevel单层制：首层可直推多位会员，奖金按公司规定的各层比例计算，适合复购型产品。了解原理及在OMC系统中的配置方式。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Unilevel Plan',
                'title'   => 'Unilevel 单层制（按层计奖）',
                'lead'    => '会员按实际情况直推首层会员，不限于两个区。奖金按各层团队业绩，依公司规定的比例与层数计算。',
            ],
            [
                'type'  => 'steps',
                'title' => 'Unilevel如何运作',
                'items' => [
                    ['title' => '直推者在首层', 'text' => '会员推荐的所有人都位于他的首层'],
                    ['title' => '团队向下延伸', 'text' => '首层会员再推荐的人，成为第二层、第三层，依此类推'],
                    ['title' => '统计各层业绩', 'text' => '系统汇总本周期各层会员消费产生的积分'],
                    ['title' => '按各层比例计算奖金', 'text' => '每层有各自的比例，并可设定解锁层级的条件'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'title'   => 'Unilevel的优点',
                'bullets' => [
                    '容易向会员说明，按实际情况推荐',
                    '适合复购型产品，回报与持续销售挂钩',
                    '可按成本结构调整比例与层数',
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'reverse' => true,
                'title'   => '需要设定的规则',
                'bullets' => [
                    '发放奖金的层数，以及各层比例',
                    '当期领取奖金所需的最低消费',
                    '会员无业绩时的网络压缩（Compression）',
                    '按直推人数或职级解锁层级的条件',
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '哪些业务适合Unilevel',
                'paragraphs' => [
                    'Unilevel在保健食品、化妆品与每月复购的日用品行业很常见，因为回报与团队的实际销售挂钩，而不是一次性的入会。',
                    '在OMC系统中，公司可以设定层数、各层比例、最低业绩与计算周期，并能回溯核查某一层的奖金来自哪位会员的订单。',
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于Unilevel的常见问题',
                'items' => [
                    ['question' => 'Unilevel与Binary有什么不同？', 'answer' => 'Unilevel不限制首层人数，按层计算奖金；Binary只有两个区，按两区业绩配对计算奖金。'],
                    ['question' => '什么是Compression（压缩）？', 'answer' => '在计算时跳过本周期没有业绩的会员，让下一层的奖金向上流向有资格的会员。是否采用取决于公司的制度。'],
                    ['question' => '以后可以修改层数吗？', 'answer' => '可以在设定中调整。建议提前通知会员，并在正式使用前测试影响。'],
                ],
            ],
            $related('/compensation-plans/unilevel/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Matrix
    '/compensation-plans/matrix/' => [
        'path'        => '/zh/compensation-plans/matrix/',
        'title'       => 'Matrix 矩阵制',
        'seo_title'   => 'Matrix矩阵制奖金：宽度×深度 | OMC',
        'description' => '直销Matrix矩阵制：网络的宽度与深度固定，例如3×7，首层满后新会员向下安置。了解原理、优点、注意事项与在OMC系统中的配置。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Matrix Plan',
                'title'   => 'Matrix 矩阵制（宽 × 深）',
                'lead'    => '网络的宽度与深度固定，例如每层3人、深7层。首层满额后，新会员会被安置到下方的空位（Spillover，滑落）。',
            ],
            [
                'type'  => 'steps',
                'title' => 'Matrix如何运作',
                'items' => [
                    ['title' => '设定网络规模', 'text' => '公司规定每层宽度与发放奖金的层数'],
                    ['title' => '安置到空位', 'text' => '首层满后，新会员按规定顺序安置到下一层'],
                    ['title' => '统计网络业绩', 'text' => '系统汇总网络中各层会员的积分'],
                    ['title' => '按层计算奖金', 'text' => '各层有自己的比例，或在网络满额时发放奖金'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'title'   => 'Matrix的优点',
                'bullets' => [
                    '结构清晰，可预先估算拨出成本',
                    '会员可能因上方推荐人的滑落安置而获得团队成员',
                    '适合逐层设定目标',
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'reverse' => true,
                'title'   => '注意事项',
                'bullets' => [
                    '需规定安置顺序（由左至右，或先填空缺的层）',
                    '会员需理解下方的人未必是自己推荐的',
                    '网络过小会很快满额，需要有开新网络的方案',
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '哪些业务适合Matrix',
                'paragraphs' => [
                    'Matrix适合希望清楚掌控网络结构与回报成本的企业，以及月费会员制的业务。OMC系统可设定宽度、深度、安置顺序与各层比例。',
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于Matrix的常见问题',
                'items' => [
                    ['question' => '什么是Spillover（滑落）？', 'answer' => '推荐人的层级满额后，新会员被安置到下方空位，系统按公司规定的顺序执行。'],
                    ['question' => '网络满额后会怎样？', 'answer' => '取决于制度。有的在满额时发放奖金并开启新网络，有的持续按层发放。团队可按您的制度协助设计。'],
                    ['question' => 'Matrix与Unilevel有什么不同？', 'answer' => 'Matrix限制每层宽度，Unilevel则不限制首层人数。'],
                ],
            ],
            $related('/compensation-plans/matrix/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Board (not confirmed — custom)
    '/compensation-plans/board/' => [
        'path'        => '/zh/compensation-plans/board/',
        'title'       => 'Board 制度',
        'seo_title'   => 'Board制度：满盘分盘的原理 | OMC',
        'description' => '直销Board制度（满盘后分盘）的原理与法律注意事项。OMC系统可按需求定制Board制度，请携带您的制度说明咨询我们的团队。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Board Plan',
                'title'   => 'Board 制度（满盘分盘）',
                'lead'    => '会员进入固定规模的“盘”，满盘后，位于顶端的会员按制度获得回报，原盘拆分为新的盘。此制度可按需求定制，请咨询我们的团队。',
            ],
            [
                'type'  => 'steps',
                'title' => 'Board制度的原理',
                'items' => [
                    ['title' => '进入盘', 'text' => '会员按公司规定的条件进入盘'],
                    ['title' => '满盘', 'text' => '位置按规模填满后，该盘即告完成'],
                    ['title' => '分盘', 'text' => '原盘拆分为新的盘，会员位置向上移动'],
                    ['title' => '按制度计算回报', 'text' => '按公司公布的规则计算'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '采用Board制度前的注意事项',
                'paragraphs' => [
                    '回报主要来自招募新会员、而非实际产品销售的制度，存在直销法律方面的风险。企业应让回报与产品或服务的销售挂钩，并在启用前向泰国OCPB（สคบ.）或法律顾问确认制度。',
                    'OMC没有把Board制度作为系统的标准设定，但可以按公司制定的制度进行设计与开发。团队会评估每条规则如何在系统中计算，以及哪些部分需要额外开发。',
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于Board制度的常见问题',
                'items' => [
                    ['question' => 'OMC系统支持Board制度吗？', 'answer' => '可按需求定制，请咨询我们的团队。请先发送制度文件，由团队评估工作范围。'],
                    ['question' => 'Board制度合法吗？', 'answer' => '取决于制度细节。如果回报主要来自新会员的入会费，风险较高，应咨询OCPB或法律顾问。本页内容不构成法律意见。'],
                ],
            ],
            $related('/compensation-plans/board/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Stair Step (not confirmed — custom)
    '/compensation-plans/stair-step/' => [
        'path'        => '/zh/compensation-plans/stair-step/',
        'title'       => 'Stair Step 制度',
        'seo_title'   => 'Stair Step制度：按业绩晋升职级 | OMC',
        'description' => '直销Stair Step阶梯制：会员按个人与团队业绩晋升职级，回报比例随职级提高。OMC系统可按需求定制此制度，请咨询我们的团队。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Stair Step Plan',
                'title'   => 'Stair Step 制度（阶梯晋升）',
                'lead'    => '会员的个人与团队业绩达标后晋升职级，不同职级对应不同的回报比例。此制度可按需求定制，请咨询我们的团队。',
            ],
            [
                'type'  => 'steps',
                'title' => 'Stair Step的原理',
                'items' => [
                    ['title' => '累计业绩', 'text' => '在规定期间内累计个人与团队业绩'],
                    ['title' => '晋升职级', 'text' => '达标后晋升，获得更高的比例'],
                    ['title' => '职级差额', 'text' => '较高职级者从较低职级的团队获得差额'],
                    ['title' => 'Breakaway（脱离）', 'text' => '部分制度让达到最高职级的团队独立出来，再以另一种方式计算奖金'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => '需要设计清楚的部分',
                'list'       => [
                    '各职级的业绩门槛，以及业绩的计算期间',
                    '职级维持方式，或业绩不达标时的降级规则',
                    '职级差额的计算方式，以及Breakaway的情况',
                ],
                'paragraphs' => [
                    'OMC没有把Stair Step作为系统的标准设定，但可以按公司的制度设计与开发。系统已有的职级与团队业绩管理可作为基础，团队会在看过制度文件后评估范围。',
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于Stair Step的常见问题',
                'items' => [
                    ['question' => 'OMC系统支持Stair Step吗？', 'answer' => '可按需求定制，请咨询我们的团队。请先发送制度文件供团队评估。'],
                    ['question' => 'Stair Step可以与其他制度一起使用吗？', 'answer' => '可以，以Hybrid的形式实现，例如用职级来决定Unilevel奖金的比例。'],
                ],
            ],
            $related('/compensation-plans/stair-step/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Hybrid
    '/compensation-plans/hybrid/' => [
        'path'        => '/zh/compensation-plans/hybrid/',
        'title'       => 'Hybrid 混合制',
        'seo_title'   => 'Hybrid混合制：多种奖金一套系统 | OMC',
        'description' => '直销Hybrid混合制：在同一制度中组合直推奖、Binary配对奖与Unilevel层级奖等多种奖金。了解组合示例、注意事项与OMC系统配置方式。',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => 'Hybrid Plan',
                'title'   => 'Hybrid 混合制（多制度组合）',
                'lead'    => '多数直销企业不只使用一种制度，而是组合多种奖金，同时鼓励推荐、团队建设与复购。OMC系统把这些奖金整合在同一个计算周期中。',
            ],
            [
                'type'    => 'feature_grid',
                'title'   => '常被组合使用的奖金',
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => '直推奖', 'text' => '推荐人从所推荐会员的消费中获得回报'],
                    ['icon' => 'plan-binary', 'title' => '配对奖（Binary）', 'text' => '左右两区团队积分配对'],
                    ['icon' => 'plan-unilevel', 'title' => '层级奖（Unilevel）', 'text' => '按多层团队复购业绩的百分比计算'],
                    ['icon' => 'network', 'title' => 'Matching Bonus（对等奖）', 'text' => '按制度规定，从所推荐会员的奖金中获得回报'],
                    ['icon' => 'chart', 'title' => '职级奖', 'text' => '达到业绩门槛对应的职级时获得的回报'],
                    ['icon' => 'sliders', 'title' => '特殊条件', 'text' => '公司的特殊规则，可在项目范围内扩充开发'],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => '系统如何计算Hybrid制度',
                'items' => [
                    ['title' => '订单产生积分', 'text' => '每张订单按产品产生PV/BV积分'],
                    ['title' => '分配到各项奖金', 'text' => '同一积分按规则用于计算多种奖金'],
                    ['title' => '检查条件与封顶', 'text' => '最低业绩、职级与每期总封顶'],
                    ['title' => '生成个人报表', 'text' => '会员按奖金类别查看明细，并可回溯核查'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => 'Hybrid的注意事项',
                'paragraphs' => [
                    '组合的奖金越多，会员越难理解，公司也越需要控制总拨出比例。建议设定每期总封顶，并用数字示例说明规则。按类别分开显示奖金的报表，能大幅减少会员的疑问。',
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => '关于Hybrid的常见问题',
                'items' => [
                    ['question' => '可以组合几种奖金？', 'answer' => '取决于公司的制度。系统支持的奖金可直接设定，特殊条件可在项目范围内扩充开发。'],
                    ['question' => '会员如何理解多种奖金？', 'answer' => '会员后台按类别与周期分开显示奖金，并标明积分来源，方便会员自行核对。'],
                ],
            ],
            $related('/compensation-plans/hybrid/'),
            $planCta,
        ],
    ],
];
