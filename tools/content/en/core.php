<?php
/**
 * Core pages — English content (home, product, features, services hub, pricing, demo, about, contact, faq, support,
 * portfolio, blog index SEO, thank-you). Keys = Thai page path; `path` = /en URL.
 *
 * Rules (project/BRIEF.md): no unverified numbers / client logos / testimonials / income promises.
 * {years} = years since registration (2009-04-03), replaced at render time.
 */

declare(strict_types=1);

$ctaDemo = [
    'type'    => 'cta_band',
    'title'   => "Want to see the system before you decide?",
    'text'    => "Book a demo with the OMC team and talk through your compensation plan at the same time.",
    'actions' => [
        ['label' => "Book a demo", 'url' => '/demo/', 'style' => 'light', 'icon' => 'calendar'],
        ['label' => "Call +66 99 109 5544", 'url' => 'tel:+66991095544', 'style' => 'ghost-light', 'icon' => 'phone'],
    ],
];

return [

    // ------------------------------------------------------------------ Home
    '/' => [
        'path'        => '/en/',
        'title'       => "MLM Software by OMC",
        'seo_title'   => "MLM Software for Direct Selling Companies | OMC",
        'description' => "MLM Software by OMC: members, genealogy, commissions, stock and back office in one system, configured to your plan by a Thai team founded in 2009.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "MLM Software by OMC",
                'title'   => "Direct selling (MLM) software\ndesigned around your business plan",
                'lead'    => "Direct selling software for companies operating in Thailand, covering member sign-up, genealogy, commission calculation, stock and the member back office. Developed and supported by OMC, a software company with {years} years in business.",
                'bullets' => [
                    "Configure Binary, Unilevel, Matrix and Hybrid compensation plans",
                    "Members, genealogy and commissions calculated from actual sales bills",
                    "Multi-branch stock, online ordering and member e-credit wallets",
                    "Migration from your existing system and support after launch",
                ],
                'actions' => [
                    ['label' => "Book a free demo", 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => "See all features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'about_teaser',
                'eyebrow' => "Who we are",
                'title'   => "Who is OMC, and why do direct selling companies talk to us?",
                'text'    => "OMC (Online Marketing Communication Co., Ltd.) was founded in 2009 in Nonthaburi, Thailand. We develop \"MLM Software by OMC\" for direct selling companies that want a system matching their own compensation plan, rather than reshaping their business around off-the-shelf software. One team handles everything from gathering requirements, configuring the plan and testing calculations through to support after go-live.",
                'points'  => [
                    "Thai company, registered in 2009, in business for {years} years",
                    "Thai development and customer-care teams who speak your back-office team's language",
                    "Designed around your real plan, with migration from your old system",
                ],
                'action'  => ['label' => "About OMC", 'url' => '/about/'],
            ],
            [
                'type'  => 'stats',
                'items' => [
                    ['value' => 2009, 'format' => 'plain', 'label' => "Year the company was founded"],
                    ['value' => '{years}', 'suffix' => ' years', 'label' => "Continuously in business"],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "Core functions direct selling businesses use every day",
                'lead'    => "Switch modules on to suit the size of your business, and extend them within each project's scope.",
                'columns' => 8,
                'items'   => [
                    ['icon' => 'users', 'title' => "Members and genealogy", 'url' => '/member-system/'],
                    ['icon' => 'calculator', 'title' => "Commission calculation", 'url' => '/commission-system/'],
                    ['icon' => 'cart', 'title' => "Online ordering", 'url' => '/features/'],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'url' => '/features/'],
                    ['icon' => 'box', 'title' => "Stock and branches", 'url' => '/features/'],
                    ['icon' => 'chart', 'title' => "Reports and Excel export", 'url' => '/features/'],
                    ['icon' => 'lock', 'title' => "Staff permissions per menu", 'url' => '/features/'],
                    ['icon' => 'sliders', 'title' => "Customised to your business", 'url' => '/solutions/custom/'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'band',
                'visual'  => 'bars',
                'eyebrow' => "Data and reports",
                'title'   => "Decide from the numbers in the system, not scattered files",
                'lead'    => "Sales, points and commissions live in one database, so management sees the same picture as accounting and member care.",
                'bullets' => [
                    "Sales by day, month, product and branch",
                    "Trace every bonus back to the bill it came from",
                    "Advanced search and report export to Excel",
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Compensation plans",
                'title'   => "Configure the plan exactly as your company announces it",
                'lead'    => "Common direct selling plans have configurable rules and rates. For more complex plans, our team designs the setup with you.",
                'plans'   => [
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Left and right teams, with bonuses from matched volume on both sides.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "A wide first level, with bonuses down the number of levels the plan sets.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => "Matrix", 'text' => "Set width and depth so the team grows in a defined structure.", 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Combine several plans in one system to match your reward model.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "View all compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'industry_grid',
                'title' => "Works for many kinds of direct selling business",
                'lead'  => "Each business has different priorities. Choose the approach closest to yours.",
                'items' => [
                    ['icon' => 'leaf', 'title' => "Supplements", 'subtitle' => "Dietary supplements", 'url' => '/solutions/supplements/'],
                    ['icon' => 'sparkle', 'title' => "Cosmetics and skincare", 'subtitle' => "Beauty", 'url' => '/solutions/cosmetics/'],
                    ['icon' => 'heart', 'title' => "Health and wellness", 'subtitle' => "Wellness", 'url' => '/solutions/health-wellness/'],
                    ['icon' => 'box', 'title' => "Wholesale and distributors", 'subtitle' => "Distribution", 'url' => '/solutions/distributor/'],
                    ['icon' => 'store', 'title' => "Franchise", 'subtitle' => "Franchise networks", 'url' => '/solutions/franchise/'],
                    ['icon' => 'globe', 'title' => "International business", 'subtitle' => "Cross-border", 'url' => '/solutions/international/'],
                ],
            ],
            [
                'type'    => 'dashboard_showcase',
                'eyebrow' => "Inside the system",
                'title'   => "Management sees the big picture; members see their own earnings",
                'lead'    => "A back office for your company's staff and a back office for members, on desktop and mobile.",
                'bullets' => [
                    "Management dashboard: sales, new members, commissions awaiting payment",
                    "A genealogy tree you can expand level by level",
                    "Bonus history and payment status for every member",
                ],
                'action'  => ['label' => "See all features", 'url' => '/features/'],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "Getting started",
                'title'   => "How to get started with OMC",
                'lead'    => "Timelines depend on each business's scope. Our team shares a project plan after gathering requirements.",
                'items'   => [
                    ['title' => "Discuss your business plan", 'text' => "Compensation plan, products, branches and how your sales team works."],
                    ['title' => "Demo and scope", 'text' => "Compare what the system already does with what needs adding, with a quotation."],
                    ['title' => "Configure, migrate, test", 'text' => "Set up the plan, import existing members and test the calculations together."],
                    ['title' => "Go live and ongoing support", 'text' => "Launch the system, with a direct line to our support team."],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'demo',
                'eyebrow'  => "Free demo",
                'title'    => "Have the OMC team contact you",
                'lead'     => "Fill in a few details and our team will get in touch to arrange a demo and discuss your plan.",
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Product hub
    '/mlm-software-omc/' => [
        'path'        => '/en/mlm-software-omc/',
        'title'       => "MLM Software by OMC",
        'seo_title'   => "MLM Software by OMC: Complete Direct Selling System | OMC",
        'description' => "MLM Software by OMC brings members, genealogy, products, stock, e-commerce, wallets, commissions and reports into one system, configured to your plan.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'eyebrow' => "Direct selling software by OMC",
                'title'   => "MLM Software by OMC\nyour whole direct selling operation in one system",
                'lead'    => "Bring a direct selling company's work into one place: member sign-up, genealogy, product sales, stock deduction, commission calculation, payouts and reports, all configured to your company's compensation plan.",
                'bullets' => [
                    "A back office for company staff and a back office for members",
                    "Compensation plan and PV/BV points set up as your company announces them",
                    "Customised or extended within the project scope",
                ],
                'actions' => [
                    ['label' => "Book a demo", 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => "Request a quote", 'url' => '/pricing/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What is MLM software, and why does a direct selling company need it?",
                'paragraphs' => [
                    "MLM software, or direct selling software, stores member data and the relationships within the genealogy, then uses actual sales to calculate rewards according to the plan your company sets. Businesses still running on multiple Excel files tend to hit the same problems: slow calculations, results that are hard to trace, and members who do not trust the numbers.",
                    "MLM Software by OMC brings this work into a single database. Sales on each bill become points, points become commissions, and commissions flow to the wallet or the payout run. Every step can be traced, so accounting, member care and management all see the same set of figures.",
                    "OMC has developed this system for direct selling companies in Thailand on an ongoing basis, starting from each client's real compensation plan rather than forcing the business to change its plan to fit the software.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "System modules",
                'title'   => "Features of MLM Software by OMC",
                'lead'    => "The core modules direct selling companies use. Choose what to switch on and extend within the project scope.",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => "Member management", 'text' => "Member registration, genealogy placement, and editing of structure and member data.", 'url' => '/member-system/'],
                    ['icon' => 'sparkle', 'title' => "Products and promotions", 'text' => "Set up products, promotions, prices and points, and show or hide products."],
                    ['icon' => 'box', 'title' => "Inventory (stock)", 'text' => "Stock deduction, sales bills, goods receipt, branch stock, issue and receive slips."],
                    ['icon' => 'cart', 'title' => "Online ordering (e-commerce)", 'text' => "Members order for themselves and pay by e-credit or payment gateway."],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'text' => "Top up, transfer and withdraw within the system, under rules your company sets."],
                    ['icon' => 'plug', 'title' => "Payment connections (API)", 'text' => "Connect to the payment provider your company chooses."],
                    ['icon' => 'history', 'title' => "Bill and delivery tracking", 'text' => "Deliveries, branch receipt of goods, and items pending delivery or collection."],
                    ['icon' => 'layers', 'title' => "Hold PV allocation", 'text' => "Allocate points from bills, PV bills and remaining unallocated balances."],
                    ['icon' => 'store', 'title' => "Branch management", 'text' => "Add branches in Thailand and abroad, with separate stock and sales."],
                    ['icon' => 'calculator', 'title' => "Commission calculation", 'text' => "Daily, per-round or monthly calculation according to your plan.", 'url' => '/commission-system/'],
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "A range of standard reports, advanced search and Excel download."],
                    ['icon' => 'lock', 'title' => "Staff permissions", 'text' => "Set view, add, edit and delete rights for each menu."],
                ],
            ],
            [
                'type'      => 'comparison_table',
                'title'     => "Configurable core system or custom-designed system?",
                'lead'      => "Choose based on your plan's complexity and your budget. Our team helps you assess the options once we understand your requirements.",
                'columns'   => ["Configurable core system", "Custom-designed system"],
                'highlight' => 1,
                'rows'      => [
                    ['label' => "Compensation plan and points setup", 'values' => [true, true]],
                    ['label' => "Logo and brand colours", 'values' => [true, true]],
                    ['label' => "Bonus conditions not in the core system", 'values' => [false, true]],
                    ['label' => "Connections to your company's other systems", 'values' => ["Within scope", true]],
                    ['label' => "Time to go live", 'values' => ["Shorter", "Depends on scope"]],
                ],
            ],
            [
                'type'    => 'dashboard_showcase',
                'eyebrow' => "Inside the system",
                'title'   => "Back offices that are easy to use",
                'lead'    => "Company staff manage members, products and payouts. Members check their team, points and bonuses whenever they like.",
                'bullets' => [
                    "A genealogy tree that expands level by level",
                    "Points for the period and the conditions still to meet",
                    "Bonus history and payment status",
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Questions about MLM Software by OMC",
                'items' => [
                    ['question' => "Which compensation plans does the system support?", 'answer' => "Several plans are configurable, including Binary, Unilevel, Matrix and Hybrid. Board and Stair Step are available as a custom configuration, so please ask our team and share your plan."],
                    ['question' => "Does it work on mobile?", 'answer' => "The member back office is designed for mobile use. Members can check their team, points and bonuses in a browser."],
                    ['question' => "We already have a system. Can we move to yours?", 'answer' => "Yes. Our team assesses your existing data, then plans the migration of members, genealogy and accumulated balances with you. See the MLM software migration page for details."],
                    ['question' => "How much does it cost?", 'answer' => "It depends on your compensation plan, the number of modules and the customisation required. Request a quote on the pricing page; our team will ask a few more questions before quoting."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Read more",
                'html'  => '<ul>'
                    . '<li><a href="/features/">All system features</a></li>'
                    . '<li><a href="/compensation-plans/">Compare compensation plans</a></li>'
                    . '<li><a href="/mlm-migration/">Migrate from your existing MLM system</a></li>'
                    . '<li><a href="/mlm-software-thailand/">MLM software in Thailand</a></li>'
                    . '</ul>',
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Features
    '/features/' => [
        'path'        => '/en/features/',
        'title'       => "System Features",
        'seo_title'   => "MLM Software Features: Members, Commissions, Stock | OMC",
        'description' => "Features of MLM Software by OMC: members and genealogy, products and points, multi-branch stock, e-commerce, wallets, commissions, reports and permissions.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Features",
                'title'   => "Features of MLM Software by OMC",
                'lead'    => "An overview of the modules direct selling companies use, grouped by the work of each team. Every module is configured to your business plan and can be extended within the project scope.",
                'actions' => [
                    ['label' => "Book a demo", 'url' => '/demo/', 'style' => 'primary'],
                    ['label' => "See compensation plans", 'url' => '/compensation-plans/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Members and network",
                'title'   => "Member management",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => "Sign-up and placement", 'text' => "Register new members, record the sponsor and place them in the tree according to your plan's rules.", 'url' => '/member-system/'],
                    ['icon' => 'network', 'title' => "Genealogy tree", 'text' => "Expand the team level by level, search within the downline, and let authorised staff edit the structure."],
                    ['icon' => 'dashboard', 'title' => "Member back office", 'text' => "Members see their own data, team, points and bonuses, including on mobile."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Sales and products",
                'title'   => "Sales, products and stock",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'sparkle', 'title' => "Products and promotions", 'text' => "Member prices, PV/BV points, promotional sets, and show or hide products."],
                    ['icon' => 'cart', 'title' => "Online ordering", 'text' => "Members order through their back office and pay by e-credit or payment gateway."],
                    ['icon' => 'box', 'title' => "Stock and branches", 'text' => "Deduct stock from bills, receive goods, issue and receive slips, and stock per branch."],
                    ['icon' => 'history', 'title' => "Bill and delivery tracking", 'text' => "Delivery status, branch receipt of goods, and items pending delivery or collection."],
                    ['icon' => 'layers', 'title' => "Hold PV", 'text' => "Allocate points from bills to members, with the remaining balance tracked."],
                    ['icon' => 'store', 'title' => "Multiple branches", 'text' => "Add branches in Thailand and abroad, with separate sales and stock for each."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Finance and rewards",
                'title'   => "Commissions and finance",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'calculator', 'title' => "Commission calculation", 'text' => "Daily, per-round or monthly calculation under your plan's conditions, traceable to the source bill.", 'url' => '/commission-system/'],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'text' => "Top up, transfer and withdraw within the system under rules your company sets."],
                    ['icon' => 'plug', 'title' => "Payment connections", 'text' => "API connection to the payment provider your company chooses."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Management and security",
                'title'   => "Reports and controls",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "A range of standard reports, advanced search and download to Excel."],
                    ['icon' => 'lock', 'title' => "Staff permissions", 'text' => "Decide which menus each staff member can view, add to, edit or delete from."],
                    ['icon' => 'shield', 'title' => "Audit trail", 'text' => "Changes to important data are logged, so you can see who did what."],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'band',
                'visual'  => 'bars',
                'eyebrow' => "Customisable",
                'title'   => "Need a function that isn't listed?",
                'lead'    => "OMC develops custom features for businesses with special requirements, such as new bonus types or connections to an existing accounting or warehouse system. Our team assesses the scope and explains the approach before work begins.",
                'action'  => ['label' => "See custom systems", 'url' => '/solutions/custom/', 'style' => 'outline'],
            ],
            [
                'type'  => 'faq',
                'title' => "Feature questions",
                'items' => [
                    ['question' => "Do we need every module?", 'answer' => "No. New companies often start with members, products, ordering and commissions, then add branch stock or the wallet later."],
                    ['question' => "Can we export data to our accounting software?", 'answer' => "Reports can be downloaded as Excel. A direct connection to accounting software is handled within the project scope."],
                    ['question' => "Can members see other members' data?", 'answer' => "Members only see their own data and their team, as your company defines. Staff only see the menus they have rights to."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Read more",
                'html'  => '<ul>'
                    . '<li><a href="/mlm-software-omc/">MLM Software by OMC overview</a></li>'
                    . '<li><a href="/member-system/">Direct selling member management</a></li>'
                    . '<li><a href="/commission-system/">MLM commission calculation software</a></li>'
                    . '</ul>',
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Services hub
    '/services/' => [
        'path'        => '/en/services/',
        'title'       => "Services and Solutions",
        'seo_title'   => "Direct Selling and MLM Services by Business Type | OMC",
        'description' => "OMC services for direct selling: MLM system setup, compensation plan configuration, custom development, data migration and support, by business type.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Services",
                'title'   => "Direct selling system services for every stage of your business",
                'lead'    => "From launching a new direct selling business or replacing an old system to developing specialised functions, OMC handles both the system and the compensation-plan setup.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "OMC's core services",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'dashboard', 'title' => "MLM software setup", 'text' => "Install and configure the core system to your company's compensation plan.", 'url' => '/mlm-software-omc/'],
                    ['icon' => 'code', 'title' => "Custom MLM development", 'text' => "Develop functions and conditions general-purpose systems do not have.", 'url' => '/mlm-development/'],
                    ['icon' => 'migrate', 'title' => "MLM system migration", 'text' => "Move members, genealogy and accumulated balances from your old system.", 'url' => '/mlm-migration/'],
                    ['icon' => 'calculator', 'title' => "Commission design", 'text' => "Turn your plan document into rules that can be calculated and tested.", 'url' => '/commission-system/'],
                    ['icon' => 'shield', 'title' => "OCPB registration information", 'text' => "An introductory guide for direct selling businesses in Thailand.", 'url' => '/ocpb-mlm/'],
                    ['icon' => 'headset', 'title' => "Post-launch support", 'text' => "Channels for reporting issues and a Thai support team.", 'url' => '/support/'],
                ],
            ],
            [
                'type'    => 'industry_grid',
                'eyebrow' => "By business type",
                'title'   => "Solutions by business type",
                'lead'    => "See which modules and compensation plans businesses like yours tend to use.",
                'items'   => [
                    ['icon' => 'leaf', 'title' => "Supplements", 'subtitle' => "Dietary supplements", 'url' => '/solutions/supplements/'],
                    ['icon' => 'sparkle', 'title' => "Cosmetics and skincare", 'subtitle' => "Beauty", 'url' => '/solutions/cosmetics/'],
                    ['icon' => 'heart', 'title' => "Health and wellness", 'subtitle' => "Wellness", 'url' => '/solutions/health-wellness/'],
                    ['icon' => 'box', 'title' => "Wholesale and distributors", 'subtitle' => "Distribution", 'url' => '/solutions/distributor/'],
                    ['icon' => 'store', 'title' => "Franchise", 'subtitle' => "Franchise networks", 'url' => '/solutions/franchise/'],
                    ['icon' => 'globe', 'title' => "International business", 'subtitle' => "Cross-border", 'url' => '/solutions/international/'],
                    ['icon' => 'sliders', 'title' => "Custom-designed systems", 'subtitle' => "Custom", 'url' => '/solutions/custom/'],
                ],
            ],
            [
                'type'    => 'steps',
                'title'   => "How working with OMC works",
                'items'   => [
                    ['title' => "Discuss requirements", 'text' => "Compensation plan, products, branches and problems with your current system."],
                    ['title' => "Demo and scope", 'text' => "Summarise what works straight away and what needs developing."],
                    ['title' => "Configure and test", 'text' => "Test the calculations with your own sample data."],
                    ['title' => "Go live and support", 'text' => "Train your back-office team and support you after launch."],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'title'    => "Talk to us about a system for your business",
                'lead'     => "Tell us your type of business and what you need. Our team will get back to you with a suitable approach.",
            ],
        ],
    ],

    // ------------------------------------------------------------------ Pricing
    '/pricing/' => [
        'path'        => '/en/pricing/',
        'title'       => "Pricing and Quotation",
        'seo_title'   => "MLM Software Pricing: Request a Quote | OMC",
        'description' => "MLM software pricing depends on your plan, modules, customisation and data migration. See what affects the price, then request a quote from the OMC team.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Pricing",
                'title'   => "MLM software pricing and quotations",
                'lead'    => "Every direct selling company has a different compensation plan and scope, so OMC quotes on your actual requirements instead of fixed packages that may not fit your business.",
                'actions' => [
                    ['label' => "Request a quote", 'url' => '#lead-form', 'style' => 'primary'],
                    ['label' => "See a demo first", 'url' => '/demo/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "What affects the price",
                'lead'    => "This information helps our team give you an accurate estimate from the start.",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'plan-hybrid', 'title' => "Plan complexity", 'text' => "The number of bonuses, rank conditions and cross-period calculations."],
                    ['icon' => 'layers', 'title' => "Modules in use", 'text' => "For example multi-branch stock, e-commerce, wallets or payment connections."],
                    ['icon' => 'code', 'title' => "Custom development", 'text' => "Functions or screens that are not in the core system."],
                    ['icon' => 'migrate', 'title' => "Data migration", 'text' => "The volume and format of member, genealogy and balance data."],
                    ['icon' => 'users', 'title' => "Network size", 'text' => "Approximate member numbers and bill volume, which affect server preparation."],
                    ['icon' => 'headset', 'title' => "Post-launch support", 'text' => "The type of support and system improvements you need."],
                ],
            ],
            [
                'type'    => 'steps',
                'title'   => "How the quotation works",
                'items'   => [
                    ['title' => "Send initial details", 'text' => "Fill in the form below or call our team."],
                    ['title' => "Discuss your plan", 'text' => "Our team asks for your plan document and any further details."],
                    ['title' => "Receive a quotation", 'text' => "Setting out the scope, deliverables and support terms."],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Pricing questions",
                'items' => [
                    ['question' => "Why are there no package prices on the website?", 'answer' => "The main costs come from the compensation plan and customisation, which vary widely between businesses. Assessing your actual details makes the quotation match the work required."],
                    ['question' => "What should we prepare before requesting a quote?", 'answer' => "Your compensation plan document (if you have one), an approximate product list, the number of branches, and details of your current system if data will be migrated."],
                    ['question' => "Can we see the system first?", 'answer' => "Yes. Book a demo on the demo page and our team will show you the system for your type of business."],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'quote',
                'eyebrow'  => "Request a quote",
                'title'    => "Request a quotation for your direct selling system",
                'lead'     => "The more detail you provide, the more accurate our estimate. Your information is only used to contact you.",
                'submit'   => "Send quote request",
            ],
        ],
    ],

    // ------------------------------------------------------------------ Demo
    '/demo/' => [
        'path'        => '/en/demo/',
        'title'       => "Book an MLM Software Demo",
        'seo_title'   => "Book a Free MLM Software Demo | OMC",
        'description' => "Book a demo of MLM Software by OMC: the back office, member back office, genealogy and commission calculation, plus a talk about your plan. Free of charge.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Demo",
                'title'   => "Book a direct selling system demo",
                'lead'    => "See the real system with the OMC team, online or in person. We will show the screens relevant to your business and answer questions about your compensation plan as we go.",
                'bullets' => [
                    "No charge for a demo",
                    "See both the company back office and the member back office",
                    "Discuss customisation and data migration in the same session",
                ],
            ],
            [
                'type'    => 'steps',
                'title'   => "What you will see in the demo",
                'items'   => [
                    ['title' => "Member sign-up and genealogy", 'text' => "Registration, placement and viewing the team level by level."],
                    ['title' => "Product sales and points", 'text' => "Issue a bill, deduct stock and see the PV/BV generated."],
                    ['title' => "Commission calculation", 'text' => "Calculation periods, bonus reports and tracing results back."],
                    ['title' => "Member back office", 'text' => "The screens members use on their phones."],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "How to prepare for the demo",
                'list'       => [
                    "A document or description of the compensation plan you use or are planning",
                    "Problems with your current system, or what you want the new system to do",
                    "Number of branches and how you sell (in store, online or both)",
                ],
                'paragraphs' => ["You do not need to have everything ready. Our team will ask questions and summarise during the session."],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'demo',
                'eyebrow'  => "Book a demo",
                'title'    => "Fill in your details to book a demo",
                'lead'     => "Our team will contact you to arrange a convenient date and time.",
                'submit'   => "Request a demo",
            ],
        ],
    ],

    // ------------------------------------------------------------------ About
    '/about/' => [
        'path'        => '/en/about/',
        'title'       => "About OMC",
        'seo_title'   => "About OMC: MLM Software Developer Since 2009 | OMC",
        'description' => "About OMC (Online Marketing Communication Co., Ltd.), the developer of MLM Software by OMC, founded in 2009 in Nonthaburi, Thailand, with a Thai team.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "About OMC",
                'title'   => "About OMC",
                'lead'    => "Online Marketing Communication Co., Ltd. is a Thai software company founded in 2009 and the developer of MLM Software by OMC.",
            ],
            [
                'type'  => 'stats',
                'items' => [
                    ['value' => 2009, 'format' => 'plain', 'label' => "Year the company was registered"],
                    ['value' => '{years}', 'suffix' => ' years', 'label' => "Continuously in business"],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Who we are",
                'paragraphs' => [
                    "OMC was registered as a company on 3 April 2009 and has its office in Nonthaburi, in the Bangkok metropolitan area. Our main work is developing direct selling systems and MLM software for companies that sell through member networks. Over the past {years} years we have worked with businesses whose plans, products and ways of working all differ, so we design the system to adapt to the business rather than making the business adapt to the software.",
                    "Our development and customer-care teams are Thai, which means we can go into detail on compensation plans, Thai-language documents and how your back-office team works, from the day we gather requirements to long after go-live.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "How we work",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'calculator', 'title' => "Numbers must be traceable", 'text' => "Every bonus can be explained by the bill and the rule it came from."],
                    ['icon' => 'sliders', 'title' => "The system fits the business", 'text' => "Configured and extended to each client's real plan."],
                    ['icon' => 'chat', 'title' => "Straight talk about scope", 'text' => "We say clearly what works straight away and what needs development."],
                ],
            ],
            [
                'type'    => 'about_teaser',
                'title'   => "Company details",
                'text'    => "Online Marketing Communication Co., Ltd. · Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand",
                'points'  => [
                    "Phone +66 99 109 5544 · LINE omc54",
                    "Email sale@omc.co.th · info@omc.co.th",
                    "Facebook: MLM Software by OMC",
                ],
                'action'  => ['label' => "Contact us", 'url' => '/contact/'],
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Contact
    '/contact/' => [
        'path'        => '/en/contact/',
        'title'       => "Contact Us",
        'seo_title'   => "Contact OMC About MLM and Direct Selling Systems | OMC",
        'description' => "Contact OMC about MLM software and direct selling systems: call +66 99 109 5544, LINE omc54, email sale@omc.co.th, or send the form. Office in Nonthaburi.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Contact",
                'title'   => "Contact the OMC team",
                'lead'    => "Ask about direct selling systems, book a demo, request a quote or discuss migrating your system. Choose whichever channel suits you.",
                'actions' => [
                    ['label' => "Call +66 99 109 5544", 'url' => 'tel:+66991095544', 'style' => 'primary'],
                    ['label' => "Chat on LINE: omc54", 'url' => 'https://line.me/ti/p/~omc54', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "Ways to reach us",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'phone', 'title' => "+66 99 109 5544", 'text' => "Talk to our sales and customer-care team.", 'url' => 'tel:+66991095544'],
                    ['icon' => 'chat', 'title' => "LINE: omc54", 'text' => "Send a message or your compensation plan file.", 'url' => 'https://line.me/ti/p/~omc54'],
                    ['icon' => 'mail', 'title' => "sale@omc.co.th", 'text' => "Sales enquiries and quotation requests.", 'url' => 'mailto:sale@omc.co.th'],
                    ['icon' => 'mail', 'title' => "info@omc.co.th", 'text' => "General enquiries.", 'url' => 'mailto:info@omc.co.th'],
                    ['icon' => 'mail', 'title' => "projectyak@hotmail.com", 'text' => "Contact the project team.", 'url' => 'mailto:projectyak@hotmail.com'],
                    ['icon' => 'facebook', 'title' => "Facebook", 'text' => "MLM Software by OMC", 'url' => 'https://www.facebook.com/mlmsoftwarebyomc'],
                ],
            ],
            [
                'type'    => 'split',
                'layout'  => 'plain',
                'visual'  => 'bars',
                'eyebrow' => "Office",
                'title'   => "Company address",
                'lead'    => "Online Marketing Communication Co., Ltd., Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand",
                'bullets' => ["Please make an appointment before visiting."],
                'action'  => ['label' => "Open map", 'url' => 'https://www.google.com/maps/search/?api=1&query=%E0%B8%AD%E0%B8%B2%E0%B8%84%E0%B8%B2%E0%B8%A3%E0%B8%82%E0%B8%88%E0%B8%A3%E0%B8%9B%E0%B8%A3%E0%B8%B0%E0%B8%A8%E0%B8%B2%E0%B8%AA%E0%B8%99%E0%B9%8C+%E0%B8%99%E0%B8%99%E0%B8%97%E0%B8%9A%E0%B8%B8%E0%B8%A3%E0%B8%B5', 'style' => 'outline'],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'contact',
                'title'    => "Send a message to our team",
                'lead'     => "Our team will get back to you through the channel you provide.",
                'submit'   => "Send message",
            ],
        ],
    ],

    // ------------------------------------------------------------------ FAQ
    '/faq/' => [
        'path'        => '/en/faq/',
        'title'       => "Frequently Asked Questions",
        'seo_title'   => "MLM Software FAQ: Direct Selling System Questions | OMC",
        'description' => "Answers about MLM Software by OMC: supported plans, customisation, migrating from an old system, pricing, timelines, support and OCPB registration in Thailand.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "FAQ",
                'title'   => "Frequently asked questions",
                'lead'    => "The questions direct selling business owners ask us most often. If you cannot find your answer, contact our team through any channel.",
            ],
            [
                'type'  => 'faq',
                'title' => "About OMC",
                'items' => [
                    ['question' => "Who is OMC?", 'answer' => "OMC is Online Marketing Communication Co., Ltd., a Thai software company registered in 2009 with its office in Nonthaburi. We develop MLM Software by OMC for direct selling businesses."],
                    ['question' => "What kinds of businesses has the system been built for?", 'answer' => "The system is designed for direct selling businesses that sell through member networks, such as supplements, cosmetics, health products and distributor networks. Ask about examples close to your business during a demo."],
                    ['question' => "Where is the team, and how can we contact you?", 'answer' => "Our team is in Nonthaburi, Thailand. Contact us by phone on +66 99 109 5544, on LINE at omc54, or by email at sale@omc.co.th."],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "About the system",
                'items' => [
                    ['question' => "Which compensation plans does the system support?", 'answer' => "Several are configurable, including Binary, Unilevel, Matrix and Hybrid. Board and Stair Step are available as a custom configuration, so ask our team and share your plan document."],
                    ['question' => "How far can the system be customised?", 'answer' => "Plans, points, products and permissions are configured in the system. Conditions or screens that are not in the core system can be developed within the project scope."],
                    ['question' => "Can members use it on their phones?", 'answer' => "Yes. The member back office works in mobile browsers, so members can check their team, points and bonuses."],
                    ['question' => "How secure is the data?", 'answer' => "Staff permissions are set per menu, members only see their own data and team, and changes to important data are logged for review. Server and backup arrangements are agreed for each project."],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "Getting started and pricing",
                'items' => [
                    ['question' => "What is the starting price?", 'answer' => "It depends on your compensation plan, modules, customisation and data migration. Request a quote on the pricing page."],
                    ['question' => "How long does it take to go live?", 'answer' => "It depends on the scope. A system configured from the core takes less time than custom development. Our team shares a project plan once requirements are agreed."],
                    ['question' => "Can you migrate data from our existing system?", 'answer' => "Yes. Our team reviews the format of your existing data, then plans the migration of members, genealogy and balances, with testing before go-live."],
                    ['question' => "Do we need OCPB registration before using the system?", 'answer' => "In Thailand, registering a direct selling business with the OCPB is the operating company's legal responsibility and does not depend on the system you use. See our OCPB guide for an introduction."],
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "After go-live",
                'items' => [
                    ['question' => "Is there a support team after launch?", 'answer' => "Yes. You can report issues by phone, LINE or email. The type of support and system improvements are agreed in each project's contract."],
                    ['question' => "Can we change our compensation plan later?", 'answer' => "Yes. Adjusting conditions the system supports is done in the settings; changing the plan's structure may need additional development and testing before use."],
                ],
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Support
    '/support/' => [
        'path'        => '/en/support/',
        'title'       => "Help and Customer Support",
        'seo_title'   => "MLM Software Support: Report a System Issue | OMC",
        'description' => "Support channels for MLM Software by OMC customers: report issues, request changes or ask about settings by phone, LINE or email, plus what to prepare.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Support",
                'title'   => "Help and customer support",
                'lead'    => "Customers using OMC's system can report issues or ask for help through the channels below. Our support team is Thai and knows your system.",
                'actions' => [
                    ['label' => "Call +66 99 109 5544", 'url' => 'tel:+66991095544', 'style' => 'primary'],
                    ['label' => "LINE omc54", 'url' => 'https://line.me/ti/p/~omc54', 'style' => 'secondary'],
                ],
            ],
            [
                'type'    => 'steps',
                'title'   => "Report an issue so it can be fixed quickly",
                'lead'    => "This information helps our team find the cause the first time.",
                'items'   => [
                    ['title' => "Give your company and contact name", 'text' => "So the team opens the right system."],
                    ['title' => "Describe what happened", 'text' => "Which screen, what you were doing, and how the result differed from what you expected."],
                    ['title' => "Attach a screenshot or reference number", 'text' => "For example a bill number, member ID or calculation period."],
                    ['title' => "Wait for confirmation from our team", 'text' => "We will let you know the status and how it will be resolved."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "What our support team can help with",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'help', 'title' => "Using the system", 'text' => "Training new staff on the back-office menus."],
                    ['icon' => 'calculator', 'title' => "Checking calculation results", 'text' => "Helping trace where a bonus came from when a member asks."],
                    ['icon' => 'sliders', 'title' => "Adjusting settings", 'text' => "Changing products, points or conditions the system supports."],
                    ['icon' => 'bug', 'title' => "Reporting errors", 'text' => "Investigating and fixing anything that is not working correctly."],
                    ['icon' => 'code', 'title' => "Requesting new development", 'text' => "Assessing new functions and proposing a scope of work."],
                    ['icon' => 'book', 'title' => "Direct selling knowledge", 'text' => "Read articles on our blog (in Thai).", 'url' => '/blog/'],
                ],
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'contact',
                'title'    => "Send a request to our support team",
                'lead'     => "Include your company name and a description of the issue in the details field. Our team will get back to you.",
                'submit'   => "Send request",
            ],
        ],
    ],

    // ------------------------------------------------------------------ Portfolio
    '/portfolio/' => [
        'path'        => '/en/portfolio/',
        'title'       => "Portfolio and Case Studies",
        'seo_title'   => "Direct Selling System Portfolio and Case Studies | OMC",
        'description' => "OMC's direct selling system portfolio. We are gathering case studies approved by clients; meanwhile, see examples close to your business in a demo.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Portfolio",
                'title'   => "Portfolio and case studies",
                'lead'    => "We only publish case studies that clients have approved. We are currently gathering them.",
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Case studies in progress",
                'paragraphs' => [
                    "Direct selling systems hold our clients' member and sales data, so we never show client names or screens without permission. Case studies will appear on this page once clients agree.",
                    "In the meantime, our team is happy to show examples of systems close to your type of business during a demo, and to explain how similar problems were solved, without revealing other clients' information.",
                ],
            ],
            [
                'type'  => 'industry_grid',
                'title' => "Explore approaches by business type",
                'items' => [
                    ['icon' => 'leaf', 'title' => "Supplements", 'subtitle' => "Dietary supplements", 'url' => '/solutions/supplements/'],
                    ['icon' => 'sparkle', 'title' => "Cosmetics", 'subtitle' => "Beauty", 'url' => '/solutions/cosmetics/'],
                    ['icon' => 'box', 'title' => "Distributors", 'subtitle' => "Distribution", 'url' => '/solutions/distributor/'],
                ],
            ],
            $ctaDemo,
        ],
    ],

    // ------------------------------------------------------------------ Blog index (listing is generated; this row holds SEO)
    '/blog/' => [
        'path'        => '/en/blog/',
        'title'       => "Articles",
        'seo_title'   => "Direct Selling and MLM Software Articles | OMC",
        'description' => "Articles from the OMC team on direct selling, MLM compensation plans, commissions, member management and choosing MLM software. Articles are in Thai.",
        'blocks'      => [],
    ],

    // ------------------------------------------------------------------ Thank-you (noindex)
    '/thank-you/' => [
        'path'        => '/en/thank-you/',
        'title'       => "Thank You for Contacting OMC",
        'seo_title'   => "Thank You for Contacting OMC | OMC",
        'description' => "The OMC team has received your details and will be in touch soon. Meanwhile, explore features and plans, or call +66 99 109 5544 or LINE omc54.",
        'robots'      => 'noindex',
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'title'   => "Thank you for contacting OMC",
                'lead'    => "We have received your details. Our team will get back to you through the channel you provided.",
                'actions' => [
                    ['label' => "Call +66 99 109 5544", 'url' => 'tel:+66991095544', 'style' => 'primary'],
                    ['label' => "LINE omc54", 'url' => 'https://line.me/ti/p/~omc54', 'style' => 'secondary'],
                ],
            ],
            [
                'type'  => 'steps',
                'title' => "What happens next",
                'items' => [
                    ['title' => "Our team reviews your details", 'text' => "We look at your type of business and what you need."],
                    ['title' => "We contact you", 'text' => "To arrange a call or a demo at a convenient time."],
                    ['title' => "We agree an approach", 'text' => "Scope of work and a quotation when you are ready."],
                ],
            ],
            [
                'type'    => 'feature_grid',
                'title'   => "While you wait",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'layers', 'title' => "System features", 'url' => '/features/'],
                    ['icon' => 'plan-hybrid', 'title' => "Compensation plans", 'url' => '/compensation-plans/'],
                    ['icon' => 'book', 'title' => "Articles (in Thai)", 'url' => '/blog/'],
                ],
            ],
        ],
    ],
];
