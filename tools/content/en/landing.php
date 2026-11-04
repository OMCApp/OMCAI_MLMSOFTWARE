<?php
/**
 * English landing pages (SEO) — keys are the Thai paths, `path` is the /en URL.
 * Written natively for English-speaking buyers (foreign companies in Thailand, international MLM owners).
 * Content rules: no unverified numbers, no clients/testimonials, no superlatives, no income promises.
 */

declare(strict_types=1);

return [

    // ------------------------------------------------------------------ 1
    '/mlm-program/' => [
        'path'        => '/en/mlm-program/',
        'title'       => "MLM Software Program",
        'seo_title'   => "MLM Software Program for Network Marketing | OMC",
        'description' => "An MLM software program that runs members, genealogy, orders, PV and commissions in one place. Configured to your plan by OMC's team in Thailand.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "MLM Software Program",
                'title'   => "An MLM software program\nbuilt around your plan",
                'lead'    => "MLM Software by OMC is a complete MLM software program for network marketing companies: member registration, genealogy, orders, points, commissions and a back office for every distributor. We configure it to your compensation plan instead of asking you to change your business to fit the software.",
                'bullets' => [
                    "Binary, Unilevel, Matrix and Hybrid plans, configured to your rules",
                    "Member back office on desktop and mobile",
                    "Orders, stock, e-credit wallet and payment connections",
                    "Thai development team, founded in 2009",
                ],
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See all features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What an MLM software program actually has to do",
                'paragraphs' => [
                    "A spreadsheet can track a few dozen distributors. It stops working the moment your network grows deeper than you can see on one screen, or when two people calculate the same bonus and get two different answers. An MLM software program exists to make one version of the truth: who sponsored whom, who bought what, how many points each order carries, and exactly what every member is owed for a given period.",
                    "That sounds simple, but the details are where network marketing companies get hurt. A plan document says \"10% on level three\"; the software has to know whether that applies to personal volume or group volume, whether inactive members are skipped, whether the rank is checked at the start or the end of the month, and what happens to an order that is refunded after the bonus was paid. A good program turns every one of those rules into something you can see, test and explain to your distributors.",
                    "OMC has built MLM and direct-selling systems in Thailand since 2009. Rather than selling a fixed box, we take a proven core and configure it, or extend it, to match your plan, your product catalogue and the way your team works day to day.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Inside the program",
                'title'   => "The modules most network marketing companies need",
                'lead'    => "Each project starts from the same core modules. The final scope is agreed with you before development begins.",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => "Member management", 'text' => "Registration, placement in the genealogy tree, and controlled editing of structure and member details.", 'url' => '/member-system/'],
                    ['icon' => 'calculator', 'title' => "Commission calculation", 'text' => "Daily, per-round or monthly calculation based on the rules of your plan, with history kept for each member.", 'url' => '/commission-system/'],
                    ['icon' => 'cart', 'title' => "Orders and e-commerce", 'text' => "Members order online and pay by e-credit wallet or a connected payment gateway.", 'url' => '/features/'],
                    ['icon' => 'box', 'title' => "Products, PV and stock", 'text' => "Prices, PV/BV points, promotions, branch stock, goods receipt and issue slips.", 'url' => '/features/'],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'text' => "Top-up, transfer and withdrawal, so bonuses and purchases move through one controlled balance.", 'url' => '/features/'],
                    ['icon' => 'chart', 'title' => "Reports and permissions", 'text' => "Standard reports, advanced search, Excel export, and staff access set per menu.", 'url' => '/features/'],
                ],
            ],
            [
                'type'    => 'comparison_table',
                'eyebrow' => "Choosing a program",
                'title'   => "Off-the-shelf script or a configured program?",
                'lead'    => "Many owners start by comparing a low-cost script with a system configured by a development team. Here is how the two usually differ.",
                'columns' => ["Off-the-shelf script", "MLM Software by OMC"],
                'rows'    => [
                    ['label' => "Your exact compensation rules", 'values' => ["Only if they match the script", "Configured or built to your plan"]],
                    ['label' => "Changes after launch", 'values' => ["Often not possible", "Handled by the same team"]],
                    ['label' => "Migration of existing members", 'values' => [false, true]],
                    ['label' => "Stock and branch operations", 'values' => ["Rarely included", "Available as modules"]],
                    ['label' => "Local support in Thailand", 'values' => [false, true]],
                ],
                'highlight' => 1,
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Questions about our MLM software program",
                'items'   => [
                    ['q' => "Is this a ready-made program or custom software?", 'a' => "Both. There is a working core that covers members, genealogy, orders and commissions, and we configure it to your plan. When your rules need something the core does not do, we build it as part of the project scope."],
                    ['q' => "Which compensation plans can the program handle?", 'a' => "Binary, Unilevel, Matrix and Hybrid plans are configurable. Board and Stair Step plans are available as a custom configuration, so please ask our team to review your plan document."],
                    ['q' => "Can my distributors use it on their phones?", 'a' => "Yes. The member back office works on desktop and mobile browsers, so distributors can check their team, orders and bonus history wherever they are."],
                    ['q' => "How much does an MLM software program cost?", 'a' => "Cost depends on your plan, the modules you need and whether data must be migrated. We quote after reviewing your requirements, and there is no obligation to proceed."],
                    ['q' => "Do you only work with companies in Thailand?", 'a' => "Our team is based in Thailand and most projects run here, but we also talk with companies that operate branches abroad. Tell us where your members are and we will discuss what the project needs."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/mlm-software-omc/">MLM Software by OMC: product overview</a></li><li><a href="/features/">All features and modules</a></li><li><a href="/compensation-plans/">Compensation plans we configure</a></li><li><a href="/pricing/">Request a quote</a></li><li><a href="/about/">About OMC</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Free consultation",
                'title'   => "Tell us about your plan",
                'lead'    => "Share your compensation plan, product range and launch timeline. Our team will explain how the program would be set up for your business.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 2
    '/mlm-system/' => [
        'path'        => '/en/mlm-system/',
        'title'       => "MLM System",
        'seo_title'   => "MLM System: Members, Orders and Commissions | OMC",
        'description' => "How a complete MLM system connects members, orders, PV, commissions, wallets and reports, and how OMC sets one up for network marketing companies.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "MLM System",
                'title'   => "One MLM system,\nfrom sign-up to payout",
                'lead'    => "An MLM system is more than a genealogy viewer. It is the chain that links a new member, their first order, the points it carries, the bonus it creates and the payment that reaches the right person. OMC designs that chain so every step is recorded and every number can be traced.",
                'bullets' => [
                    "Members, orders, points and commissions share one database",
                    "Clear audit trail from order to bonus",
                    "Head office, branch and member views",
                    "Configured for your plan and your operations",
                ],
                'actions' => [
                    ['label' => "Book a consultation", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "Explore the software", 'url' => '/mlm-software-omc/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Why the whole system matters more than any single feature",
                'paragraphs' => [
                    "Most problems in network marketing operations do not come from a missing feature. They come from gaps between tools: orders taken in one place, stock counted in another, and commissions worked out in a spreadsheet that someone exports every month. Each hand-off is a chance for a number to change, and when a distributor questions a bonus, nobody can show where it came from.",
                    "A complete MLM system closes those gaps. When a member places an order, the same record updates stock, assigns PV and BV, feeds the commission run and shows up in reports. Staff work from the same data your distributors see in their back office, so disputes become a matter of looking up the record rather than arguing about it.",
                    "OMC builds MLM systems for companies in Thailand, from new brands preparing to launch to established direct-selling businesses replacing an older platform. We start with how your business actually runs, then decide which modules belong in your system and how they connect.",
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "How the system flows",
                'title'   => "From a new member to a paid bonus",
                'lead'    => "A simplified view of the data path inside an MLM system set up by OMC.",
                'items'   => [
                    ['title' => "Register and place", 'text' => "A new member signs up under a sponsor and is placed in the genealogy tree according to your plan's placement rules."],
                    ['title' => "Order and pay", 'text' => "The member orders products and pays by e-credit wallet or a connected payment gateway. Stock is deducted at the selling branch."],
                    ['title' => "Assign points", 'text' => "Each product carries PV/BV. Points can be applied straight away or held and allocated later through Hold PV."],
                    ['title' => "Calculate commissions", 'text' => "The commission engine runs daily, per round or monthly, applying your qualification and payout rules."],
                    ['title' => "Pay and report", 'text' => "Bonuses are credited, withdrawals are processed, and management reviews the period through reports and Excel exports."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Compensation plans",
                'title'   => "Plans the system can be configured for",
                'lead'    => "Your compensation plan drives how the system is configured. These are the structures we work with most often.",
                'plans'   => [
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Two legs, with bonuses based on matching volume between left and right. Configurable.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "A single front line with bonuses paid down a set number of levels. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => "Matrix", 'text' => "Fixed width and depth that fill the structure in a defined order. Configurable.", 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Several bonus types combined in one plan. Configurable to your rules.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "View all compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "MLM system questions",
                'items'   => [
                    ['q' => "What is the difference between an MLM system and MLM software?", 'a' => "People use the terms loosely. We use \"system\" to mean the full set of modules working together, including stock, branches, wallets and reports, not only the genealogy and commission screens."],
                    ['q' => "Can the system run several branches?", 'a' => "Yes. Branch management covers branches in Thailand and abroad, with branch stock, branch receiving of deliveries and reports per branch."],
                    ['q' => "Who can see what inside the system?", 'a' => "Staff permissions are set per menu, with separate rights to view, add, edit and delete. Members only see their own data and their own team."],
                    ['q' => "Can we start small and add modules later?", 'a' => "Yes. Many companies launch with members, orders and commissions, then add stock, branches or extra reports as they grow. We plan the first scope so later additions fit cleanly."],
                    ['q' => "Our plan includes Board or Stair Step bonuses. Is that possible?", 'a' => "Board and Stair Step structures are available as a custom configuration. Send us the plan and our team will confirm how it would work in your system."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/features/">Features of the MLM system</a></li><li><a href="/compensation-plans/">Compensation plans</a></li><li><a href="/services/">Solutions by business type</a></li><li><a href="/mlm-migration/">Moving from an existing system</a></li><li><a href="/faq/">Frequently asked questions</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Talk to OMC",
                'title'   => "Plan your MLM system with us",
                'lead'    => "Describe your business, your plan and where you are today. We will suggest which modules your system needs first.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 3
    '/direct-sales-software/' => [
        'path'        => '/en/direct-sales-software/',
        'title'       => "Direct Selling Software",
        'seo_title'   => "Direct Selling Software for Your Company | OMC",
        'description' => "Direct selling software for head office, branches and distributors: orders, stock, members, PV and bonuses. See a live demo from OMC in Thailand.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "Direct Selling Software",
                'title'   => "Direct selling software\nyour whole team can use",
                'lead'    => "Head office staff, branch counters and independent distributors all need different things from the same system. MLM Software by OMC gives each of them the screens they need, on top of one shared record of members, orders and bonuses.",
                'bullets' => [
                    "Back office for staff with permissions per menu",
                    "Branch sales, stock and delivery tracking",
                    "Distributor back office on desktop and mobile",
                    "Bonus calculation tied directly to sales",
                ],
                'actions' => [
                    ['label' => "Book a live demo", 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Software for the people who run a direct selling business",
                'paragraphs' => [
                    "Direct selling companies are busy places. Someone at head office is approving a new distributor, a branch is selling over the counter, the warehouse is sending parcels, and finance wants to know what the commission bill looks like before the month closes. If each team uses a different tool, they spend the day reconciling instead of selling.",
                    "Direct selling software should feel like one workplace. In MLM Software by OMC, the order a branch enters is the same order the warehouse ships, the same order that adds points to the distributor's team and the same order finance sees in the sales report. Nobody has to re-key it.",
                    "The fastest way to judge whether software fits is to see it working with realistic data. That is why we run demos: you can walk through registration, ordering, stock and bonuses on screen and ask how each step would work under your own plan.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Who uses it",
                'title'   => "Built for every role in the business",
                'lead'    => "Each role gets its own view. Access is controlled centrally, so people only see what their job requires.",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'dashboard', 'title' => "Management", 'text' => "Sales, new members and commission totals across the business, with reports exportable to Excel."],
                    ['icon' => 'store', 'title' => "Branch staff", 'text' => "Sell to members, issue bills, receive deliveries and manage branch stock."],
                    ['icon' => 'box', 'title' => "Warehouse", 'text' => "Goods receipt, issue and receive slips, and lists of orders waiting to be delivered or collected."],
                    ['icon' => 'calculator', 'title' => "Finance", 'text' => "Commission runs, wallet top-ups, transfers and withdrawal requests in one place."],
                    ['icon' => 'users', 'title' => "Distributors", 'text' => "Their own team, orders, points and bonus history, on desktop or mobile."],
                    ['icon' => 'lock', 'title' => "Administrators", 'text' => "Staff accounts with view, add, edit and delete rights set per menu."],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'band',
                'visual'  => 'bars',
                'eyebrow' => "What you will see in a demo",
                'title'   => "Walk through a real working day",
                'lead'    => "A demo with OMC is a working session, not a slide show. We use sample data to show how your team would handle common tasks.",
                'bullets' => [
                    "Register a distributor and place them in the tree",
                    "Create an order, take payment and deduct stock",
                    "Check PV/BV and run a commission calculation",
                    "Open the distributor back office on a phone",
                ],
                'action'  => ['label' => "Book your demo", 'url' => '/demo/', 'style' => 'primary'],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Direct selling software FAQ",
                'items'   => [
                    ['q' => "Is direct selling software only for MLM companies?", 'a' => "No. Any business that sells through independent distributors and rewards them for sales can use it. Whether you pay on one level or many, the same order, stock and member records apply."],
                    ['q' => "Can branches sell to walk-in members?", 'a' => "Yes. Branch staff can issue sales bills, deduct branch stock and track whether goods were delivered or collected at the branch."],
                    ['q' => "How long is a demo?", 'a' => "It depends on how much you want to see. We agree the topics in advance so the session covers the parts of the software that matter for your business."],
                    ['q' => "Can the software connect to our payment provider?", 'a' => "The system supports payment API connections. Which provider is connected is agreed as part of your project scope."],
                    ['q' => "Do you register our company with the OCPB?", 'a' => "Registration with Thailand's Office of the Consumer Protection Board is the company's own responsibility. We publish a general guide, which is not legal advice, and the system can produce the sales and member records you keep."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/demo/">Book a demo</a></li><li><a href="/features/">Feature overview</a></li><li><a href="/direct-sales-system/">Direct selling system for Thailand</a></li><li><a href="/ocpb-mlm/">OCPB registration guide</a></li><li><a href="/support/">After-sales support</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'demo',
                'eyebrow' => "Live demo",
                'title'   => "See the direct selling software in action",
                'lead'    => "Pick a time and tell us what you would like to see. We will prepare the demo around your business.",
                'submit'  => "Book a demo",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 4
    '/direct-sales-system/' => [
        'path'        => '/en/direct-sales-system/',
        'title'       => "Direct Selling System",
        'seo_title'   => "Direct Selling System for Thailand | OMC",
        'description' => "A direct selling system set up for operating in Thailand: members, branches, stock, bonuses and the records your business keeps. Plan it with OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "Direct Selling System",
                'title'   => "A direct selling system\nset up for Thailand",
                'lead'    => "Running a direct selling business in Thailand means Thai-speaking distributors, branch counters, local payment habits and registration with the Office of the Consumer Protection Board. OMC sets up the system with those realities in mind from the first meeting.",
                'bullets' => [
                    "Thai team that knows how local direct selling works",
                    "Branch, stock and delivery records in one system",
                    "Commission rules configured to your plan",
                    "Help moving data from your current platform",
                ],
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "Read the OCPB guide", 'url' => '/ocpb-mlm/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Operating realities a direct selling system must handle",
                'paragraphs' => [
                    "Direct selling in Thailand is regulated under the Direct Sales and Direct Marketing Act B.E. 2545 and its amendments, and companies register with the OCPB. The registration itself is your company's task, but the way you run the business afterwards depends heavily on your records: who your distributors are, what was sold, at what price, and what was paid out.",
                    "Day-to-day operations add their own demands. Many members still prefer to buy at a branch rather than online. Stock moves between head office and branches. Parcels go out and need to be tracked until they arrive. Promotions change monthly. A direct selling system that only knows about the genealogy tree leaves your staff to handle everything else by hand.",
                    "OMC is a Thai software company, founded in 2009 and based in Nonthaburi, just outside Bangkok. We work with owners and managers in the same time zone and the same language as their teams, which makes requirements, training and support far more direct.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "System coverage",
                'title'   => "What the system keeps track of",
                'lead'    => "Modules are selected per project. These are the areas a Thai direct selling business usually needs covered.",
                'columns' => 4,
                'items'   => [
                    ['icon' => 'users', 'title' => "Distributors", 'text' => "Registration, sponsor, placement and member details, with controlled edits."],
                    ['icon' => 'store', 'title' => "Branches", 'text' => "Branches in Thailand and abroad, each with its own stock and sales."],
                    ['icon' => 'box', 'title' => "Stock", 'text' => "Goods receipt, stock deduction, issue and receive slips per branch."],
                    ['icon' => 'map-pin', 'title' => "Deliveries", 'text' => "Bills awaiting delivery, branch receipt and member pickup."],
                    ['icon' => 'layers', 'title' => "Products and promotions", 'text' => "Prices, PV/BV, promotions and items shown or hidden as needed."],
                    ['icon' => 'calculator', 'title' => "Bonuses", 'text' => "Daily, per-round or monthly calculation with history per member."],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'text' => "Top-up, transfer and withdrawal records for every member."],
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "Standard reports, advanced search and export to Excel."],
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "Getting started",
                'title'   => "How we set up your direct selling system",
                'items'   => [
                    ['title' => "Understand the business", 'text' => "We review your plan, products, branches and how orders reach members today."],
                    ['title' => "Agree the scope", 'text' => "Together we decide which modules launch first and what is configured or built."],
                    ['title' => "Configure and test", 'text' => "We set up the plan and test commission results against examples you provide."],
                    ['title' => "Migrate and train", 'text' => "Existing members and balances are imported where needed, and your staff are trained."],
                    ['title' => "Launch and support", 'text' => "After go-live, our team supports you and handles changes as your business evolves."],
                ],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Direct selling system questions",
                'items'   => [
                    ['q' => "Does the system make us compliant with Thai law?", 'a' => "No software can do that on its own. Compliance depends on how your company registers and operates. The system helps by keeping clear records of members, sales and payouts. Our OCPB page is a general guide, not legal advice."],
                    ['q' => "Can the system handle members who buy at a branch?", 'a' => "Yes. Branch sales, branch stock and branch receipt of deliveries are part of the branch and stock modules."],
                    ['q' => "We are a foreign company opening in Thailand. Can you help?", 'a' => "Yes. We regularly explain local operating practice to new entrants and can scope the system around your Thai entity, branches and plan."],
                    ['q' => "Can the plan change after launch?", 'a' => "Plans do change. Adjustments are handled by our team as change requests, so the commission logic stays consistent and tested."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/ocpb-mlm/">OCPB registration guide</a></li><li><a href="/mlm-software-thailand/">MLM software in Thailand</a></li><li><a href="/features/">Features</a></li><li><a href="/solutions/international/">International businesses</a></li><li><a href="/contact/">Contact OMC</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your direct selling system",
                'lead'    => "Tell us whether you are launching, expanding or replacing a system. We will come back with questions and a suggested approach.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 5
    '/mlm-development/' => [
        'path'        => '/en/mlm-development/',
        'title'       => "MLM Software Development Company",
        'seo_title'   => "MLM Software Development Company in Thailand | OMC",
        'description' => "OMC is an MLM software development company in Thailand, founded in 2009. See how we scope, build, test and support MLM systems, and request a quote.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "MLM Software Development",
                'title'   => "An MLM software development\ncompany you can talk to",
                'lead'    => "OMC has developed MLM and direct-selling software in Thailand since 2009. You work directly with the team that designs, builds and supports your system, from the first plan review to the changes you need years later.",
                'bullets' => [
                    "In business for {years} years, based in Nonthaburi",
                    "Requirements, development and support by one team",
                    "Plans configured or built to your specification",
                    "Data migration from your existing system",
                ],
                'actions' => [
                    ['label' => "Request a quote", 'url' => '/pricing/', 'style' => 'primary'],
                    ['label' => "About OMC", 'url' => '/about/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What to look for in an MLM development partner",
                'paragraphs' => [
                    "Commissioning MLM software is a long relationship, not a one-off purchase. Your compensation plan will be adjusted, new products will need new point values, and a member will eventually dispute a bonus from six months ago. The company that built the system needs to be there, understand the code and be able to explain the result.",
                    "When you compare development companies, ask how they turn a plan document into calculation rules, how they test those rules before launch, who owns support after go-live, and whether they have moved members and balances out of another platform before. Polished screens matter less than these answers.",
                    "OMC is a registered Thai company, Online Marketing Communication Co., Ltd., founded on 3 April 2009. We build MLM systems from a proven core and develop the parts that make your business different. Because the same team handles requirements, development and support, knowledge about your system does not disappear between departments.",
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "Our development process",
                'title'   => "How an MLM project runs with OMC",
                'lead'    => "Every project follows the same basic path. The depth of each step depends on your scope.",
                'items'   => [
                    ['title' => "Discovery", 'text' => "We study your compensation plan, products, branches and operations, and ask the questions that expose edge cases early."],
                    ['title' => "Scope and quotation", 'text' => "You receive a written scope: which modules are configured, what is developed specifically for you, and what migration involves."],
                    ['title' => "Build and configure", 'text' => "We set up the core modules, implement your plan rules and develop the agreed custom parts."],
                    ['title' => "Test against your examples", 'text' => "Commission results are checked against worked examples from your plan before anyone is paid from the new system."],
                    ['title' => "Launch, train and support", 'text' => "We help you go live, train your staff and stay on as your support team after launch."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "What we deliver",
                'title'   => "Scope that can be included in your project",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'network', 'title' => "Genealogy and members", 'text' => "Registration, placement and structure editing for your network.", 'url' => '/member-system/'],
                    ['icon' => 'calculator', 'title' => "Commission engine", 'text' => "Your plan's rules, calculated daily, per round or monthly.", 'url' => '/commission-system/'],
                    ['icon' => 'cart', 'title' => "Orders and payments", 'text' => "E-commerce ordering with e-credit wallet and payment API connections.", 'url' => '/features/'],
                    ['icon' => 'box', 'title' => "Stock and branches", 'text' => "Branch stock, goods receipt, delivery and pickup tracking.", 'url' => '/features/'],
                    ['icon' => 'migrate', 'title' => "Data migration", 'text' => "Members, structure and balances moved from your old platform.", 'url' => '/mlm-migration/'],
                    ['icon' => 'headset', 'title' => "After-sales support", 'text' => "Help for your team after launch, and changes as your plan evolves.", 'url' => '/support/'],
                ],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Working with an MLM development company",
                'items'   => [
                    ['q' => "Do you build everything from scratch?", 'a' => "No, and that is deliberate. We start from a core that already handles members, orders and commissions, then configure and extend it. You pay for what makes your business different rather than for rebuilding the basics."],
                    ['q' => "How long does development take?", 'a' => "It depends on the scope, the complexity of your plan and whether data is migrated. We give you a timeline with the quotation once requirements are clear."],
                    ['q' => "Who supports the system after launch?", 'a' => "OMC does. The same company that developed the system provides after-sales support and handles change requests."],
                    ['q' => "Can you show us previous work?", 'a' => "Client systems are confidential, so we show capability through a live demo of the software and by walking through how your plan would be implemented."],
                    ['q' => "Do you work with companies outside Thailand?", 'a' => "Our team is in Thailand. We talk with overseas companies operating here or with branches abroad; the scope, languages and currencies are agreed per project."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/about/">About OMC</a></li><li><a href="/mlm-programming/">Custom MLM software programming</a></li><li><a href="/solutions/custom/">Custom-designed systems</a></li><li><a href="/portfolio/">Our work</a></li><li><a href="/pricing/">Request a quote</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'quote',
                'eyebrow' => "Request a quote",
                'title'   => "Start your MLM development project",
                'lead'    => "Send us your plan document and a short description of your business. We will review it and prepare a scope and quotation.",
                'submit'  => "Request a quote",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 6
    '/mlm-programming/' => [
        'path'        => '/en/mlm-programming/',
        'title'       => "Custom MLM Software Programming",
        'seo_title'   => "Custom MLM Software Programming | OMC",
        'description' => "Custom MLM software programming for plan rules, bonuses and workflows a standard package cannot handle. Developed and maintained by OMC in Thailand.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "Custom MLM Programming",
                'title'   => "Custom MLM software programming\nfor rules that are yours alone",
                'lead'    => "When your compensation plan or operations do something a standard package cannot, the answer is careful custom programming on a stable core. OMC writes the code for your specific rules and keeps it maintainable for the long term.",
                'bullets' => [
                    "Custom bonus rules and qualification logic",
                    "Workflows that match how your team operates",
                    "Integration with payment and other systems",
                    "Code maintained by the team that wrote it",
                ],
                'actions' => [
                    ['label' => "Request a quote", 'url' => '/pricing/', 'style' => 'primary'],
                    ['label' => "Custom solutions", 'url' => '/solutions/custom/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Configure first, program where it counts",
                'paragraphs' => [
                    "Custom programming is powerful, but it should not be the default. Every line of custom code has to be tested, documented and maintained. So our first question is always whether your requirement can be met by configuring what already exists: plan parameters, product points, promotions, permissions and reports.",
                    "Programming becomes the right choice when the rule is genuinely unique. Typical examples include a bonus that depends on a combination of rank, personal volume and team structure that no standard plan type describes; a qualification period that does not align with calendar months; a special promotion that changes how points are counted; or a workflow between head office and branches that your business relies on.",
                    "In those cases we write the logic specifically for you, inside the same system, so it shares data, permissions and reports with everything else. You avoid a patchwork of separate tools, and your distributors see one consistent back office.",
                    "Integrations follow the same thinking. If your business already relies on an accounting package, a warehouse service or a specific payment provider, we look at what that system can exchange and program the connection to your specification, rather than asking your staff to copy figures between screens at the end of every day.",
                ],
            ],
            [
                'type'    => 'comparison_table',
                'eyebrow' => "Configure or program?",
                'title'   => "How we decide what needs custom code",
                'lead'    => "A rough guide. The real decision is made together during scoping.",
                'columns' => ["Configuration", "Custom programming"],
                'rows'    => [
                    ['label' => "Standard Binary, Unilevel, Matrix or Hybrid rules", 'values' => [true, false]],
                    ['label' => "Product prices, PV/BV and promotions", 'values' => [true, false]],
                    ['label' => "Staff permissions per menu", 'values' => [true, false]],
                    ['label' => "Bonus logic not described by a standard plan", 'values' => [false, true]],
                    ['label' => "Board or Stair Step structures", 'values' => [false, "Custom configuration, ask our team"]],
                    ['label' => "Connections to a specific external system", 'values' => [false, true]],
                ],
                'highlight' => 1,
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "How we program",
                'title'   => "Turning a rule into reliable code",
                'items'   => [
                    ['title' => "Write the rule down", 'text' => "We restate your rule in plain language with worked examples, and you confirm it before any code is written."],
                    ['title' => "Find the edge cases", 'text' => "Refunds, inactive members, rank changes mid-period: we agree how each one should behave."],
                    ['title' => "Develop inside the core", 'text' => "The new logic uses the same member, order and point data as the rest of the system."],
                    ['title' => "Test with your numbers", 'text' => "Results are compared with the examples you approved, and differences are explained."],
                ],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Custom MLM programming questions",
                'items'   => [
                    ['q' => "Will custom code make future updates harder?", 'a' => "It can if it is bolted on. We develop custom logic inside the system's own structure and keep it documented, so later changes are handled by the same team that understands it."],
                    ['q' => "Can you change an existing bonus rule after launch?", 'a' => "Yes. Changes are scoped and tested before release, and we agree from which period the new rule applies, so earlier results stay consistent."],
                    ['q' => "Can you integrate with our accounting or shipping system?", 'a' => "Integrations can be built to your specification. We look at what the other system offers and include the connection in the project scope."],
                    ['q' => "Can you take over custom code another developer wrote?", 'a' => "We assess the existing code first, then recommend whether to extend it or rebuild the logic, using your current rules and data as the reference."],
                    ['q' => "Do we own the custom features?", 'a' => "Ownership and licensing terms are set out in the project agreement. Ask us during quotation and we will explain the options."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/solutions/custom/">Custom-designed MLM systems</a></li><li><a href="/mlm-development/">MLM software development</a></li><li><a href="/compensation-plans/hybrid/">Hybrid compensation plans</a></li><li><a href="/commission-system/">Commission calculation</a></li><li><a href="/pricing/">Request a quote</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'quote',
                'eyebrow' => "Quotation",
                'title'   => "Describe the rule you need built",
                'lead'    => "Explain the bonus, workflow or integration you need, with an example if you have one. We will tell you whether it needs custom programming and what it involves.",
                'submit'  => "Request a quote",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 7
    '/mlm-software-thailand/' => [
        'path'        => '/en/mlm-software-thailand/',
        'title'       => "MLM Software Thailand",
        'seo_title'   => "MLM Software Thailand for Foreign Companies | OMC",
        'description' => "MLM software in Thailand from a local Thai team: plan configuration, branches, Thai-speaking support and help with local operating practice. Talk to OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "MLM Software Thailand",
                'title'   => "MLM software in Thailand,\nfrom a team based here",
                'lead'    => "Entering the Thai market or moving your Thai operation onto a new platform? OMC is a Thai software company that has built MLM and direct-selling systems here since 2009. We bridge your head office's plan and the way business actually runs in Thailand.",
                'bullets' => [
                    "Local team in Nonthaburi, Bangkok metropolitan area",
                    "Thai-speaking support for your local staff",
                    "Branches in Thailand and abroad in one system",
                    "Familiar with OCPB registration context",
                ],
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "International solutions", 'url' => '/solutions/international/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Why a local partner matters in the Thai market",
                'paragraphs' => [
                    "Thailand has an active direct-selling sector, and international brands often arrive with a compensation plan and a global platform already in place. What they discover is that local operations raise questions the global platform was never asked: Thai-language back offices for distributors, branch counters where members buy and collect in person, stock held across several locations, and record-keeping that suits a company registered with the Office of the Consumer Protection Board.",
                    "Working with a Thai development team shortens those conversations. Your local managers can explain requirements in their own language, training happens with your staff in person or online during the same working day, and support is in the same time zone (UTC+7) as your distributors.",
                    "OMC, registered in Thailand as Online Marketing Communication Co., Ltd., has been building MLM systems for {years} years. We can run your Thai operation as a standalone system, or as one branch structure alongside branches in other countries.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "For companies entering Thailand",
                'title'   => "What we help foreign companies with",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'sliders', 'title' => "Adapting your plan", 'text' => "We configure your existing compensation plan and discuss local adjustments with your team."],
                    ['icon' => 'globe', 'title' => "Thai and overseas branches", 'text' => "Branch management covers branches in Thailand and abroad, with stock and sales per branch."],
                    ['icon' => 'chat', 'title' => "Language", 'text' => "Thai-speaking support for local staff. Interface languages and currencies are agreed in the project scope."],
                    ['icon' => 'plug', 'title' => "Payment connections", 'text' => "Payment API connections, with the provider agreed per project, plus an internal e-credit wallet."],
                    ['icon' => 'book', 'title' => "Local operating context", 'text' => "A general guide to OCPB registration, so you know which records your business will keep."],
                    ['icon' => 'migrate', 'title' => "Moving existing members", 'text' => "If you already have Thai members on another platform, we can migrate them."],
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "Working together",
                'title'   => "How a Thailand project usually runs",
                'items'   => [
                    ['title' => "Initial call", 'text' => "We meet your regional or Thai team online and learn about the plan, products and launch goals."],
                    ['title' => "Local scoping", 'text' => "We map your head office plan to Thai operations: branches, stock, payments and reports."],
                    ['title' => "Build and test", 'text' => "The system is configured and tested with commission examples from your plan."],
                    ['title' => "Go live in Thailand", 'text' => "Staff are trained, members are migrated if needed, and our team supports you after launch."],
                ],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "MLM software in Thailand: common questions",
                'items'   => [
                    ['q' => "Do we need a Thai company to use your software?", 'a' => "You can talk to us at any stage. Registering to operate direct selling in Thailand is a separate legal matter for your company and its advisers; our OCPB page is a general guide only."],
                    ['q' => "Can we contact you in English?", 'a' => "Yes. You can contact us in English or Thai by email, phone or LINE."],
                    ['q' => "Can the system show prices in more than one currency?", 'a' => "Multiple currencies and interface languages can be built as part of the project scope. Tell us which markets you operate in and we will scope it."],
                    ['q' => "We already have a global platform. Why add local software?", 'a' => "Some companies keep a global platform and need a local system for Thai branches, stock and distributors. Others move Thailand fully onto OMC. We will discuss which arrangement fits your operation."],
                    ['q' => "Where is OMC located?", 'a' => "Our office is at Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand. You can call +66 99 109 5544."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/solutions/international/">International direct-selling businesses</a></li><li><a href="/ocpb-mlm/">OCPB registration guide</a></li><li><a href="/direct-sales-system/">Direct selling system for Thailand</a></li><li><a href="/about/">About OMC</a></li><li><a href="/contact/">Contact us</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Plan your Thai operation with us",
                'lead'    => "Tell us where your company operates today, your plans for Thailand and your timeline. We will reply with next steps.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 8
    '/mlm-migration/' => [
        'path'        => '/en/mlm-migration/',
        'title'       => "MLM Software Migration",
        'seo_title'   => "MLM Software Migration from Your Old System | OMC",
        'description' => "MLM software migration that moves members, genealogy, wallet balances and history to a new system, with checks before switchover. Planned by OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "MLM Software Migration",
                'title'   => "MLM software migration\nwithout losing your network",
                'lead'    => "Your distributors' sponsors, placements, balances and history are the most valuable data your company holds. OMC plans MLM software migration so that data arrives in the new system intact and checked before anyone relies on it.",
                'bullets' => [
                    "Members, sponsors and genealogy placement",
                    "Wallet balances and point records where available",
                    "Reconciliation checks before switchover",
                    "New system configured to your current plan",
                ],
                'actions' => [
                    ['label' => "Plan your migration", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See the new system", 'url' => '/mlm-software-omc/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "When it is time to leave your current platform",
                'paragraphs' => [
                    "Companies usually start thinking about migration for the same few reasons. The original developer is no longer available. Commission runs take too long or need manual corrections every period. The plan has changed and the old system cannot follow. Or the business has outgrown a platform that was never designed for branches, stock or mobile users.",
                    "Whatever the reason, the fear is the same: what if the move breaks the network? A misplaced member changes who earns what. A missing balance becomes a complaint. That is why migration needs to be treated as a project in its own right, with its own checks, not as a file import at the end of development.",
                    "OMC has helped direct-selling businesses move data out of older systems. We look at what your current system can export, map it to the new structure, import it, and then prove that the numbers match before you switch over.",
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "Migration process",
                'title'   => "How we move your data",
                'lead'    => "The exact steps depend on the condition and format of your current data.",
                'items'   => [
                    ['title' => "Assess the source", 'text' => "We review what your current system holds and how it can be exported: database, spreadsheets or reports."],
                    ['title' => "Map the data", 'text' => "Each field is matched to the new system: member details, sponsor, placement, rank, balances and history."],
                    ['title' => "Trial import", 'text' => "Data is loaded into a test environment so problems surface before they reach your distributors."],
                    ['title' => "Reconcile", 'text' => "Member counts, tree structure and balances are compared between old and new, and differences are resolved."],
                    ['title' => "Switch over", 'text' => "A final import is run at an agreed cut-off, and your team moves to the new system with our support."],
                ],
            ],
            [
                'type'    => 'comparison_table',
                'eyebrow' => "What moves",
                'title'   => "Data typically included in a migration",
                'lead'    => "What can be moved depends on what your current system stores and exports.",
                'columns' => ["Usually migrated", "Depends on source"],
                'rows'    => [
                    ['label' => "Member profiles and contact details", 'values' => [true, false]],
                    ['label' => "Sponsor and genealogy placement", 'values' => [true, false]],
                    ['label' => "Current wallet balances", 'values' => [true, false]],
                    ['label' => "Ranks and qualifications", 'values' => [false, true]],
                    ['label' => "Historical orders and bonus history", 'values' => [false, true]],
                ],
                'highlight' => 0,
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "MLM migration questions",
                'items'   => [
                    ['q' => "Our old developer is gone. Can you still get the data out?", 'a' => "Often, yes. If we can access the database or obtain exports and reports, we can usually reconstruct the member structure and balances. We confirm what is possible after reviewing the source."],
                    ['q' => "Will distributors have to register again?", 'a' => "No. The goal of migration is that members keep their position in the network. How they log in for the first time is agreed as part of the plan."],
                    ['q' => "Can we keep running the old system during migration?", 'a' => "Yes. Most migrations use trial imports while the old system stays live, followed by a final import at an agreed cut-off date."],
                    ['q' => "What if our plan changes at the same time?", 'a' => "That is common. We migrate the data as it is, configure the new plan, and agree from which period the new rules start."],
                    ['q' => "How long does a migration take?", 'a' => "It depends on the volume and quality of the data. We estimate the timeline after assessing your source system."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/mlm-software-omc/">MLM Software by OMC</a></li><li><a href="/member-system/">Member management system</a></li><li><a href="/commission-system/">Commission calculation software</a></li><li><a href="/support/">After-sales support</a></li><li><a href="/faq/">FAQ</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'migration',
                'eyebrow' => "Migration assessment",
                'title'   => "Tell us about your current system",
                'lead'    => "Let us know which platform you use, roughly how your data is stored and why you want to move. We will explain how the migration could work.",
                'submit'  => "Request a migration review",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 9
    '/commission-system/' => [
        'path'        => '/en/commission-system/',
        'title'       => "MLM Commission Calculation Software",
        'seo_title'   => "MLM Commission Calculation Software | OMC",
        'description' => "MLM commission calculation software that applies your plan daily, per round or monthly, with PV/BV, Hold PV and bonus history you can trace. Book a demo.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "Commission Calculation",
                'title'   => "MLM commission calculation\nsoftware you can trace",
                'lead'    => "Commissions are where trust in a network marketing company is won or lost. OMC's commission engine applies your plan's rules to real orders and points, and keeps the history so every bonus can be explained.",
                'bullets' => [
                    "Daily, per-round or monthly calculation",
                    "PV/BV per product and Hold PV allocation",
                    "Binary, Unilevel, Matrix and Hybrid rules",
                    "Bonus history visible to members",
                ],
                'actions' => [
                    ['label' => "Book a demo", 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => "Compensation plans", 'url' => '/compensation-plans/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Why commission calculation goes wrong",
                'paragraphs' => [
                    "Ask any direct-selling finance team what keeps them up at the end of a period and the answer is usually the bonus run. Manual spreadsheets break when someone sorts a column. Older systems recalculate differently after a patch. Points from a refunded order stay in a member's volume. And when a distributor asks why their bonus is lower than expected, there is no simple way to show the working.",
                    "Commission calculation software exists to make the run repeatable. Given the same orders and the same rules, it must produce the same result every time, and it must be able to show which orders, which points and which rule produced each line of a member's bonus.",
                    "In MLM Software by OMC, points come directly from the products on each order. Your plan's rules are configured once and tested against worked examples before launch. Each calculation is stored, so members can see their own bonus history and your staff can answer questions with the record in front of them.",
                    "Timing is part of the plan as well. Some bonuses are paid daily, others per round or at month end, and points do not always belong to the period in which the order was placed. Hold PV lets your team keep points from a PV bill and allocate them later, while the system tracks the remaining balance, so late allocations do not turn into manual adjustments.",
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "How a commission run works",
                'title'   => "From orders to a member's bonus",
                'items'   => [
                    ['title' => "Collect volume", 'text' => "Orders in the period contribute PV/BV based on the points set for each product."],
                    ['title' => "Apply held points", 'text' => "Points kept under Hold PV are allocated to the chosen member or period, and remaining balances are tracked."],
                    ['title' => "Check qualifications", 'text' => "The engine applies your rules for activity, rank and volume before any bonus is paid."],
                    ['title' => "Calculate bonuses", 'text' => "Each bonus type in your plan is calculated: matching, level, matrix or a hybrid mix."],
                    ['title' => "Credit and report", 'text' => "Results are recorded, credited to the e-credit wallet where configured, and summarised in reports."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Supported plans",
                'title'   => "Commission rules we configure",
                'lead'    => "Configurable plan types for the commission engine. Board and Stair Step are available as a custom configuration, so ask our team.",
                'plans'   => [
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Pairing or matching bonuses based on left and right leg volume.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Level bonuses down a defined number of generations.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => "Matrix", 'text' => "Bonuses across a fixed-width structure as positions fill.", 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Several bonus types calculated together under one plan.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Commission software questions",
                'items'   => [
                    ['q' => "How often can commissions be calculated?", 'a' => "Calculation can run daily, per round or monthly, depending on how your plan pays each bonus type."],
                    ['q' => "What happens when an order is refunded?", 'a' => "How refunds affect points and bonuses is a rule in your plan. We agree it during scoping and configure the system to apply it consistently."],
                    ['q' => "Can members see how their bonus was calculated?", 'a' => "Members can see their bonus history and team volume in their back office. The level of detail shown is agreed with you."],
                    ['q' => "Can we test the calculation before going live?", 'a' => "Yes. We test results against worked examples from your plan, and you can review them before the system pays anyone."],
                    ['q' => "Does the software decide how much distributors earn?", 'a' => "No. The software applies the plan your company designs. What a distributor receives depends on your plan and on actual sales."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/compensation-plans/">Compensation plans overview</a></li><li><a href="/compensation-plans/hybrid/">Hybrid plans</a></li><li><a href="/member-system/">Member management system</a></li><li><a href="/features/">All features</a></li><li><a href="/demo/">Book a demo</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'demo',
                'eyebrow' => "Demo",
                'title'   => "See a commission run in the demo",
                'lead'    => "Send us a short description of your plan. We will show how its rules would be configured and calculated.",
                'submit'  => "Book a demo",
            ],
        ],
    ],

    // ------------------------------------------------------------------ 10
    '/member-system/' => [
        'path'        => '/en/member-system/',
        'title'       => "Direct Selling Member Management System",
        'seo_title'   => "Direct Selling Member Management System | OMC",
        'description' => "A direct selling member management system for registration, genealogy placement, member data and back office access on desktop and mobile. Book a demo.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "Member Management",
                'title'   => "Direct selling member\nmanagement system",
                'lead'    => "Your distributors are your business. OMC's member management system keeps every registration, sponsor, placement and profile change in order, and gives each member a clear back office to follow their own team.",
                'bullets' => [
                    "Registration and placement in the genealogy tree",
                    "Controlled edits to structure and member data",
                    "Member back office on desktop and mobile",
                    "Staff access set per menu",
                ],
                'actions' => [
                    ['label' => "Book a demo", 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Member data is the foundation of everything else",
                'paragraphs' => [
                    "Every other part of a direct selling business depends on the member record. Commissions follow the sponsor and placement. Orders are tied to a member ID. Wallet balances belong to a person. If a member is placed in the wrong leg or two records exist for the same person, the error spreads into bonuses, reports and, eventually, distributor relationships.",
                    "A member management system has two jobs. For head office, it must make registration simple while protecting the structure: changes to placement or sponsor should be deliberate, permitted only to the right staff and traceable afterwards. For members, it must make their own business visible: who joined their team, how their volume is building and what they have earned.",
                    "In MLM Software by OMC, member management sits at the centre of the system. Registration, genealogy, orders, points and wallet all refer to the same member record, so your team and your distributors are always looking at the same information.",
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "Member lifecycle",
                'title'   => "How a member moves through the system",
                'lead'    => "The member record stays the same from the first day, so everything a member does builds on one history.",
                'items'   => [
                    ['title' => "Join under a sponsor", 'text' => "The new member registers online or at a branch, and the sponsor relationship is recorded at once."],
                    ['title' => "Take a place in the tree", 'text' => "Placement follows your plan's rules, whether that is a left or right leg, a level or a matrix position."],
                    ['title' => "Start ordering", 'text' => "Orders, points and wallet movements are linked to the member ID, so volume is credited correctly."],
                    ['title' => "Build a team", 'text' => "As the member sponsors others, their back office shows new members and team volume for each period."],
                    ['title' => "Correct with care", 'text' => "If a placement or detail needs fixing, authorised staff make the change through a controlled menu."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Member features",
                'title'   => "What the member system covers",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => "Registration", 'text' => "Sign up new members under a sponsor, online or by staff at a branch."],
                    ['icon' => 'network', 'title' => "Genealogy tree", 'text' => "Placement according to your plan, with the tree viewable level by level."],
                    ['icon' => 'sliders', 'title' => "Structure editing", 'text' => "Authorised staff can correct placement and member data when needed."],
                    ['icon' => 'dashboard', 'title' => "Member back office", 'text' => "Team, orders, points and bonus history on desktop and mobile."],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'text' => "Each member's top-ups, transfers and withdrawals in one balance."],
                    ['icon' => 'shield', 'title' => "Permissions", 'text' => "Staff rights to view, add, edit and delete are set per menu."],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'eyebrow' => "The member's view",
                'title'   => "A back office distributors actually open",
                'lead'    => "Members are more engaged when they can see their progress without calling head office. The back office puts their own information one tap away.",
                'bullets' => [
                    "New members in their team",
                    "Personal and team volume for the period",
                    "Order history and delivery status",
                    "Bonus history and wallet balance",
                ],
                'action'  => ['label' => "See it in a demo", 'url' => '/demo/', 'style' => 'primary'],
                'reverse' => true,
            ],
            [
                'type'    => 'faq',
                'eyebrow' => "FAQ",
                'title'   => "Member management questions",
                'items'   => [
                    ['q' => "Can members register themselves online?", 'a' => "Yes, members can register online under a sponsor. Staff can also register members at head office or a branch."],
                    ['q' => "Who can change a member's placement?", 'a' => "Only staff with permission for that menu. Rights to view, add, edit and delete are set per menu, so structure changes stay controlled."],
                    ['q' => "Can we import members from our current system?", 'a' => "Yes. Members, sponsors and placements can be migrated from your existing platform as part of the project."],
                    ['q' => "Which details are collected at registration?", 'a' => "Registration fields are agreed with you during scoping, based on what your business and its record-keeping require."],
                    ['q' => "Can a member see the whole network?", 'a' => "Members see their own data and their own team. Head office staff with the right permissions can view the wider genealogy."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Related pages",
                'html'  => '<ul><li><a href="/features/">All features</a></li><li><a href="/commission-system/">Commission calculation software</a></li><li><a href="/mlm-migration/">Migrating members from another system</a></li><li><a href="/compensation-plans/binary/">Binary plan placement</a></li><li><a href="/demo/">Book a demo</a></li></ul>',
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'demo',
                'eyebrow' => "Demo",
                'title'   => "See the member system for yourself",
                'lead'    => "We will walk you through registration, the genealogy tree and the member back office using sample data.",
                'submit'  => "Book a demo",
            ],
        ],
    ],
];
