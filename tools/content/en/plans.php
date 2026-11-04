<?php
/**
 * Compensation plans — hub + 6 plan pages (English). Keys = Thai page path; `path` = /en URL.
 *
 * ‼️ Board and Stair Step are NOT confirmed as standard features (QUESTIONS.md) —
 *    they are described as "available as a custom configuration — ask our team".
 */

declare(strict_types=1);

$related = static function (string $current): array {
    $all = [
        '/compensation-plans/binary/'     => ['plan-binary', 'Binary'],
        '/compensation-plans/unilevel/'   => ['plan-unilevel', 'Unilevel'],
        '/compensation-plans/matrix/'     => ['plan-matrix', 'Matrix'],
        '/compensation-plans/board/'      => ['plan-board', 'Board'],
        '/compensation-plans/stair-step/' => ['plan-stair', 'Stair Step'],
        '/compensation-plans/hybrid/'     => ['plan-hybrid', 'Hybrid'],
    ];
    $items = [];
    foreach ($all as $url => [$icon, $name]) {
        if ($url !== $current) {
            $items[] = ['icon' => $icon, 'title' => $name . ' plan', 'url' => $url];
        }
    }

    return ['type' => 'feature_grid', 'eyebrow' => 'Other plans', 'title' => 'Compare with other compensation plans', 'columns' => 3, 'items' => $items];
};

$planCta = [
    'type'    => 'cta_band',
    'title'   => "Already have a compensation plan document?",
    'text'    => "Send it to the OMC team. We will tell you which parts can be configured straight away and which need additional design.",
    'actions' => [
        ['label' => "Discuss your plan", 'url' => '/contact/', 'style' => 'light', 'icon' => 'chat'],
        ['label' => "Book a demo", 'url' => '/demo/', 'style' => 'ghost-light', 'icon' => 'calendar'],
    ],
];

$disclaimer = "The examples on this page explain general principles only and are not a promise of income for any member. Each company designs its own plan and is responsible for making sure it complies with direct-selling law.";

return [

    // ------------------------------------------------------------------ hub
    '/compensation-plans/' => [
        'path'        => '/en/compensation-plans/',
        'title'       => "MLM Compensation Plans",
        'seo_title'   => "MLM Compensation Plans: Binary, Unilevel, Matrix | OMC",
        'description' => "Compare MLM compensation plans (Binary, Unilevel, Matrix, Board, Stair Step and Hybrid): strengths, trade-offs and how each is set up in OMC's system.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Compensation Plans",
                'title'   => "Which MLM compensation plan fits your business?",
                'lead'    => "A compensation plan defines how members are rewarded for sales. This page summarises the plans companies use most often, to make conversations with our team and your own decisions easier.",
            ],
            [
                'type'    => 'plan_cards',
                'title'   => "Plans the system can be configured for",
                'lead'    => "Binary, Unilevel, Matrix and Hybrid have configurable rules and rates. Board and Stair Step are available as a custom configuration.",
                'plans'   => [
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Two legs, left and right, with bonuses from matched volume.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "A wide first level, with bonuses paid down a set number of levels.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => "Matrix", 'text' => "A structure with a clearly defined width and depth.", 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'board', 'name' => "Board", 'text' => "Boards that split when full. Custom configuration, ask our team.", 'url' => '/compensation-plans/board/'],
                    ['key' => 'stair_step', 'name' => "Stair Step", 'text' => "Rank steps based on volume. Custom configuration, ask our team.", 'url' => '/compensation-plans/stair-step/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Several bonus types combined in one plan.", 'url' => '/compensation-plans/hybrid/'],
                ],
            ],
            [
                'type'      => 'comparison_table',
                'title'     => "Compensation plans at a glance",
                'lead'      => "A starting point for discussion. The real behaviour depends on the rules your company sets.",
                'columns'   => ["Structure", "Strength", "Watch out for"],
                'rows'      => [
                    ['label' => "Binary", 'values' => ["Two legs per member", "Easy to grasp; the team builds volume together", "Needs clear caps and weak-leg rules"]],
                    ['label' => "Unilevel", 'values' => ["Wide first level", "Rewards direct sponsoring and repeat purchases", "Number of levels and maintenance rules must be set"]],
                    ['label' => "Matrix", 'values' => ["Fixed width × depth", "Clear, predictable structure", "Members may be placed under a team they did not sponsor"]],
                    ['label' => "Board", 'values' => ["Boards that split", "Goals are easy to visualise", "Must be designed to comply with the law; ask our team"]],
                    ['label' => "Stair Step", 'values' => ["Rank steps", "Motivates team volume", "Needs rank maintenance rules; ask our team"]],
                    ['label' => "Hybrid", 'values' => ["Mix of plan types", "Flexible to fit the business", "Rules must be explained clearly to members"]],
                ],
            ],
            [
                'type'    => 'steps',
                'title'   => "From plan document to a system that calculates it",
                'items'   => [
                    ['title' => "Read the plan document", 'text' => "Our team separates each bonus and its conditions."],
                    ['title' => "Set the rules in the system", 'text' => "Points, calculation periods, caps and ranks are defined."],
                    ['title' => "Test with sample data", 'text' => "Results are compared with figures your company calculated itself."],
                    ['title' => "Go live and trace back", 'text' => "Every bonus can be traced to the bill it came from."],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Compensation plan questions",
                'items' => [
                    ['question' => "Which plan should we choose?", 'answer' => "It depends on your products, price points, how often customers reorder and how your sales team behaves. Repeat-purchase businesses often start with Unilevel; businesses that want teams to build volume together often consider Binary or Hybrid. Our team can help you analyse the options."],
                    ['question' => "Can we use several plans at once?", 'answer' => "Yes, as a Hybrid plan, for example a direct sponsor bonus combined with a pairing bonus and level bonuses."],
                    ['question' => "Does the plan need OCPB approval in Thailand?", 'answer' => "Registering a direct-selling business in Thailand includes submitting the compensation plan to the registrar. Check with the OCPB or a legal adviser. The system calculates according to the plan your company defines."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Read more",
                'html'  => '<ul>'
                    . '<li><a href="/commission-system/">MLM commission calculation software</a></li>'
                    . '<li><a href="/solutions/custom/">Custom MLM systems</a></li>'
                    . '</ul>'
                    . '<p>' . $disclaimer . '</p>',
            ],
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Binary
    '/compensation-plans/binary/' => [
        'path'        => '/en/compensation-plans/binary/',
        'title'       => "Binary Plan",
        'seo_title'   => "Binary MLM Plan Explained: Left and Right Legs | OMC",
        'description' => "How a Binary MLM compensation plan works: two legs, points matched between left and right, caps and carry-over, and how OMC's system calculates it.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Binary Plan",
                'title'   => "Binary compensation plan (left and right legs)",
                'lead'    => "Each member can build two legs. The system accumulates points on each leg and calculates bonuses from the volume that can be matched, in the periods your company defines.",
            ],
            [
                'type'  => 'steps',
                'title' => "How Binary works",
                'items' => [
                    ['title' => "Place left or right", 'text' => "A new member is placed under the sponsor in the left or right leg, according to your placement rules."],
                    ['title' => "Accumulate points per leg", 'text' => "Team purchases in each leg become that leg's PV."],
                    ['title' => "Match volume", 'text' => "Each period, the system matches the smaller leg's points against the larger leg."],
                    ['title' => "Calculate and carry over", 'text' => "Bonuses are paid at the set rate and cap; remaining points carry over according to your rules."],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'title'   => "Strengths of Binary",
                'bullets' => [
                    "A simple structure where members clearly see the volume on both legs",
                    "Team members help build up the weaker leg, which encourages teamwork",
                    "Combines well with other bonuses, such as direct sponsor or matching bonuses",
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'reverse' => true,
                'title'   => "Trade-offs and rules to define",
                'bullets' => [
                    "A bonus cap per period to control the overall payout ratio",
                    "Carry-over rules and how long unmatched points are kept",
                    "Qualification rules, such as minimum personal purchases",
                    "Automatic placement method (weaker leg, outer left, or sponsor's choice)",
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Which businesses suit Binary?",
                'paragraphs' => [
                    "Binary suits businesses that want teams to build volume together and have clearly priced sets, such as starter kits or product packages. Many companies pair Binary with a direct sponsor bonus so that sponsors are also rewarded for introducing new members.",
                    "In OMC's system, your company sets the matching rate, the cap per period, the calculation period and the carry-over rules. Administrators can trace each bonus back to the points on the bills that produced it.",
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Binary plan questions",
                'items' => [
                    ['question' => "What happens to the larger leg's points after matching?", 'answer' => "That depends on your rules. Usually the remainder carries over to the next period, sometimes with a cap or an expiry period. Both can be configured in the system."],
                    ['question' => "Can members choose which leg to place someone in?", 'answer' => "Yes, if your rules allow it. Options include letting the sponsor choose or having the system place new members in the weaker leg automatically."],
                    ['question' => "Can Binary be combined with Unilevel?", 'answer' => "Yes, as a Hybrid plan, for example Binary pairing bonuses combined with Unilevel level bonuses on repeat purchases."],
                ],
            ],
            $related('/compensation-plans/binary/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Unilevel
    '/compensation-plans/unilevel/' => [
        'path'        => '/en/compensation-plans/unilevel/',
        'title'       => "Unilevel Plan",
        'seo_title'   => "Unilevel MLM Plan Explained: Level Bonuses | OMC",
        'description' => "How a Unilevel MLM plan works: a wide first level, bonuses as a percentage per level, suited to repeat-purchase products, and its setup in OMC's system.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Unilevel Plan",
                'title'   => "Unilevel compensation plan (level bonuses)",
                'lead'    => "Members can sponsor as many people on their first level as they actually recruit, rather than being limited to two legs. Bonuses are calculated from team volume on each level, using the rates and number of levels your company sets.",
            ],
            [
                'type'  => 'steps',
                'title' => "How Unilevel works",
                'items' => [
                    ['title' => "Direct sponsoring on level 1", 'text' => "Everyone a member sponsors sits on that member's first level."],
                    ['title' => "The team grows into deeper levels", 'text' => "People sponsored by level 1 become level 2, then level 3 and so on."],
                    ['title' => "Count volume per level", 'text' => "The system totals points from members' purchases on each level for the period."],
                    ['title' => "Apply each level's rate", 'text' => "Each level has its own percentage, and levels may unlock based on conditions."],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'title'   => "Strengths of Unilevel",
                'bullets' => [
                    "Easy to explain: members sponsor the people they actually introduce",
                    "Suits repeat-purchase products, tying rewards to ongoing sales",
                    "Rates and levels can be tuned to your cost structure",
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'reverse' => true,
                'title'   => "Rules to define",
                'bullets' => [
                    "How many levels pay a bonus, and the rate for each level",
                    "Minimum purchases required to earn a bonus in the period",
                    "Compression when some members have no volume",
                    "Conditions that unlock levels, based on direct sponsors or rank",
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Which businesses suit Unilevel?",
                'paragraphs' => [
                    "Unilevel is popular with supplement, cosmetics and consumer-goods businesses whose customers reorder every month, because rewards are tied to the team's real sales rather than to a one-off sign-up.",
                    "In OMC's system, your company sets the number of levels, the rate for each level, minimum volume and the calculation period, and can trace which member's bills produced each level bonus.",
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Unilevel plan questions",
                'items' => [
                    ['question' => "How is Unilevel different from Binary?", 'answer' => "Unilevel does not cap the number of people on the first level and pays bonuses by level. Binary has two legs and pays bonuses from matched volume on both sides."],
                    ['question' => "What is compression?", 'answer' => "Skipping members with no volume in the period so the bonus from deeper levels flows up to qualified members. Whether it applies depends on your plan."],
                    ['question' => "Can we change the number of levels later?", 'answer' => "Yes, through the settings. Announce the change to members in advance and test its impact before applying it."],
                ],
            ],
            $related('/compensation-plans/unilevel/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Matrix
    '/compensation-plans/matrix/' => [
        'path'        => '/en/compensation-plans/matrix/',
        'title'       => "Matrix Plan",
        'seo_title'   => "Matrix MLM Plan Explained: Width × Depth | OMC",
        'description' => "How a Matrix MLM plan works: fixed width and depth such as 3x7, spillover into open positions, trade-offs, and how OMC's system is configured for it.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Matrix Plan",
                'title'   => "Matrix compensation plan (width × depth)",
                'lead'    => "The structure has a fixed width and depth, for example three people per level and seven levels deep. Once the first level is full, new members are placed into open positions further down (spillover).",
            ],
            [
                'type'  => 'steps',
                'title' => "How Matrix works",
                'items' => [
                    ['title' => "Set the matrix size", 'text' => "Your company sets the width of each level and how many levels pay a bonus."],
                    ['title' => "Place members in open positions", 'text' => "When the first level is full, new members go to the next level in the defined order."],
                    ['title' => "Count volume in the matrix", 'text' => "The system totals points for members on each level of the matrix."],
                    ['title' => "Pay by level", 'text' => "Each level has its own rate, or a bonus is paid when the matrix is complete."],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'title'   => "Strengths of Matrix",
                'bullets' => [
                    "A clear structure, so payout costs can be estimated in advance",
                    "Members may gain team members through spillover from their upline",
                    "Works well for setting goals one level at a time",
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'reverse' => true,
                'title'   => "Trade-offs",
                'bullets' => [
                    "The placement order must be defined (left to right, or first open level)",
                    "Members need to understand that people below them may not be people they sponsored",
                    "A matrix that is too small fills quickly, so you need a rule for starting a new one",
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Which businesses suit Matrix?",
                'paragraphs' => [
                    "Matrix suits businesses that want tight control over structure and payout costs, as well as monthly membership models. OMC's system lets you configure the width, depth, placement order and rate for each level.",
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Matrix plan questions",
                'items' => [
                    ['question' => "What is spillover?", 'answer' => "Placing a new member into an open position further down when the sponsor's level is already full. The system follows the order your company defines."],
                    ['question' => "What happens when the matrix is full?", 'answer' => "It depends on the plan. Some pay a completion bonus and start a new matrix; others keep paying by level. Our team can help design it around your plan."],
                    ['question' => "How is Matrix different from Unilevel?", 'answer' => "Matrix limits the width of each level, while Unilevel does not cap how many people sit on the first level."],
                ],
            ],
            $related('/compensation-plans/matrix/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Board (not confirmed — custom)
    '/compensation-plans/board/' => [
        'path'        => '/en/compensation-plans/board/',
        'title'       => "Board Plan",
        'seo_title'   => "Board MLM Plan Explained: Boards That Split | OMC",
        'description' => "How a Board MLM plan works, the legal points to check first, and how OMC can support it as a custom configuration. Send your plan to our team for review.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Board Plan",
                'title'   => "Board compensation plan (boards that split)",
                'lead'    => "Members join a board of fixed size. When the board fills, the member at the top is rewarded according to the plan and the board splits into new boards. This plan is available as a custom configuration, so ask our team.",
            ],
            [
                'type'  => 'steps',
                'title' => "How a Board plan works",
                'items' => [
                    ['title' => "Join a board", 'text' => "Members enter a board under the conditions your company sets."],
                    ['title' => "The board fills", 'text' => "Once every position is taken, the board is complete."],
                    ['title' => "The board splits", 'text' => "It divides into new boards and members move up a position."],
                    ['title' => "Rewards per the plan", 'text' => "Calculated according to the rules your company publishes."],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What to check before using a Board plan",
                'paragraphs' => [
                    "Plans where rewards depend more on recruiting new members than on real product sales carry legal risk under direct-selling law. Rewards should be tied to the sale of products or services, and the plan should be checked with the OCPB or a legal adviser before launch.",
                    "OMC does not offer Board as a ready-made setting, but we can design and develop it to the plan your company defines. Our team will assess how each rule can be calculated in the system and which parts need additional development.",
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Board plan questions",
                'items' => [
                    ['question' => "Does OMC's system support Board plans?", 'answer' => "It is available as a custom configuration. Send your plan document so our team can assess the scope first."],
                    ['question' => "Are Board plans legal?", 'answer' => "It depends on the plan's details. If rewards come mainly from new members' joining fees, the risk is high. Consult the OCPB or a legal adviser. This information is not legal advice."],
                ],
            ],
            $related('/compensation-plans/board/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Stair Step (not confirmed — custom)
    '/compensation-plans/stair-step/' => [
        'path'        => '/en/compensation-plans/stair-step/',
        'title'       => "Stair Step Plan",
        'seo_title'   => "Stair Step MLM Plan Explained: Rank Steps | OMC",
        'description' => "How a Stair Step MLM plan works: ranks based on personal and team volume, differentials between steps, breakaway, and OMC support as custom configuration.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Stair Step Plan",
                'title'   => "Stair Step compensation plan (rank steps)",
                'lead'    => "Members move up a rank when their personal and team volume reach set thresholds, and each step has its own reward rate. This plan is available as a custom configuration, so ask our team.",
            ],
            [
                'type'  => 'steps',
                'title' => "How Stair Step works",
                'items' => [
                    ['title' => "Build volume", 'text' => "Personal and team volume within a defined period."],
                    ['title' => "Step up", 'text' => "Reaching the threshold moves the member to a higher rank and rate."],
                    ['title' => "Differentials between steps", 'text' => "Members on a higher step earn the difference on teams at lower steps."],
                    ['title' => "Breakaway", 'text' => "Some plans let teams that reach the top step break away and pay a different bonus."],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What must be designed clearly",
                'list'       => [
                    "Volume thresholds for each step and the period over which volume counts",
                    "Rank maintenance, or stepping down when volume falls short",
                    "How differentials between steps are calculated, and breakaway cases",
                ],
                'paragraphs' => [
                    "OMC does not offer Stair Step as a ready-made setting, but we can design and develop it to your company's plan. Rank and team volume handling in the system provide the foundation to build on. Our team will assess the scope after reviewing your plan document.",
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Stair Step plan questions",
                'items' => [
                    ['question' => "Does OMC's system support Stair Step?", 'answer' => "It is available as a custom configuration. Send your plan document so our team can assess it first."],
                    ['question' => "Can Stair Step be combined with other plans?", 'answer' => "Yes, as a Hybrid plan, for example using rank steps to set the rates of a Unilevel bonus."],
                ],
            ],
            $related('/compensation-plans/stair-step/'),
            $planCta,
        ],
    ],

    // ------------------------------------------------------------------ Hybrid
    '/compensation-plans/hybrid/' => [
        'path'        => '/en/compensation-plans/hybrid/',
        'title'       => "Hybrid Plan",
        'seo_title'   => "Hybrid MLM Plan: Several Plans in One System | OMC",
        'description' => "A Hybrid MLM plan combines bonuses such as direct sponsor, Binary pairing and Unilevel level bonuses. See example mixes, trade-offs and OMC's setup.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Hybrid Plan",
                'title'   => "Hybrid compensation plan (several plans combined)",
                'lead'    => "Most direct-selling businesses do not use a single plan. They combine bonus types to reward sponsoring, team building and repeat purchases. OMC's system is designed to bring these bonuses together in one calculation run.",
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "Bonuses commonly combined",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => "Direct sponsor bonus", 'text' => "The sponsor is rewarded on purchases by the people they introduced."],
                    ['icon' => 'plan-binary', 'title' => "Pairing bonus (Binary)", 'text' => "Matching points between the left and right teams."],
                    ['icon' => 'plan-unilevel', 'title' => "Level bonus (Unilevel)", 'text' => "A percentage of repeat purchases across several levels of the team."],
                    ['icon' => 'network', 'title' => "Matching bonus", 'text' => "A reward based on the bonuses of people you sponsored, as the plan defines."],
                    ['icon' => 'chart', 'title' => "Rank bonus", 'text' => "A reward for reaching a rank based on volume thresholds."],
                    ['icon' => 'sliders', 'title' => "Special conditions", 'text' => "Company-specific rules, developed within the project scope."],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => "How the system calculates a Hybrid plan",
                'items' => [
                    ['title' => "Bills create points", 'text' => "Every bill generates PV/BV based on the products sold."],
                    ['title' => "Points feed each bonus", 'text' => "The same points are used for several bonus types according to the rules."],
                    ['title' => "Check conditions and caps", 'text' => "Minimum volume, rank and the overall cap per period are applied."],
                    ['title' => "Report per member", 'text' => "Members see bonuses broken down by type and can trace them back."],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Trade-offs of a Hybrid plan",
                'paragraphs' => [
                    "The more bonuses you combine, the harder the plan is for members to understand, and the more closely the company must control its total payout ratio. Set an overall cap per period and explain the rules with worked numerical examples. Reports that separate bonuses by type reduce member questions considerably.",
                    $disclaimer,
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Hybrid plan questions",
                'items' => [
                    ['question' => "How many bonuses can be combined?", 'answer' => "It depends on your plan. Bonus types the system supports are configurable; company-specific conditions are developed within the project scope."],
                    ['question' => "How will members understand several bonus types?", 'answer' => "The member back office shows bonuses by type and period, with the source of the points, so members can check for themselves."],
                ],
            ],
            $related('/compensation-plans/hybrid/'),
            $planCta,
        ],
    ],
];
