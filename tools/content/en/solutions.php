<?php
/**
 * English solutions pages (/en/solutions/*) — keys are the Thai paths.
 * Content rules: no unverified numbers, no clients/testimonials, no superlatives, no income promises.
 */

declare(strict_types=1);

return [

    '/solutions/supplements/' => [
        'path'        => '/en/solutions/supplements/',
        'title'       => "Supplements",
        'seo_title'   => "MLM Software for Supplement Brands | OMC",
        'description' => "MLM software for dietary supplement brands: PV per product, repeat orders, promotions, branch stock and commissions, configured by OMC in Thailand.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · Supplements",
                'title'   => "MLM software for supplement brands",
                'lead'    => "Supplement businesses live on repeat purchases and a clear point value for every product. OMC configures the system around your catalogue, your promotions and the way members reorder.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What a supplement business needs from its system",
                'paragraphs' => [
                    "Most supplement members buy the same products every month, so reordering has to be quick and every order has to carry the right PV and BV. When a new formula launches or a bundle goes on promotion, prices and points need to change on a specific date without touching earlier orders.",
                    "Stock matters too. Supplements have batches and expiry dates, and members often collect at a branch. Your system should show what each branch holds and which bills are still waiting to be delivered or picked up. Scheduled repeat orders (autoship) can be built as part of the project scope.",
                    "Product labels, claims and registration with Thailand's Food and Drug Administration remain the brand's responsibility. The software manages sales, members and commissions; it does not approve product content.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Relevant modules",
                'title'   => "Modules supplement brands usually choose",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'layers', 'title' => "Products and PV/BV", 'text' => "Prices, points and promotions per product, shown or hidden as needed."],
                    ['icon' => 'cart', 'title' => "Online ordering", 'text' => "Members reorder online and pay by e-credit wallet or payment gateway."],
                    ['icon' => 'box', 'title' => "Branch stock", 'text' => "Goods receipt, stock deduction and branch stock levels."],
                    ['icon' => 'map-pin', 'title' => "Delivery tracking", 'text' => "Pending delivery, branch receipt and member pickup."],
                    ['icon' => 'calculator', 'title' => "Commissions", 'text' => "Daily, per-round or monthly calculation from actual sales."],
                    ['icon' => 'chart', 'title' => "Sales reports", 'text' => "Reports by product and period, exportable to Excel."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Suggested plans",
                'title'   => "Plans often used for consumable products",
                'lead'    => "All configurable to your rules. We will review your plan document with you.",
                'plans'   => [
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Rewards repeat volume across a set number of levels. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Matching bonuses on two balanced legs. Configurable.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Combine level and matching bonuses in one plan. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "Supplement brand questions",
                'items' => [
                    ['q' => "Can different products carry different points?", 'a' => "Yes. PV and BV are set per product, and promotions can change prices and points for a defined period."],
                    ['q' => "Can members set up automatic monthly orders?", 'a' => "Scheduled repeat orders can be built to your specification as part of the project scope."],
                    ['q' => "Does the system check our product registration?", 'a' => "No. Product registration and label compliance are the brand's responsibility. The system records sales, stock and commissions."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your supplement business",
                'lead'    => "Tell us about your products, your plan and how members buy today.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    '/solutions/cosmetics/' => [
        'path'        => '/en/solutions/cosmetics/',
        'title'       => "Cosmetics and Skincare",
        'seo_title'   => "MLM Software for Cosmetics and Skincare | OMC",
        'description' => "MLM software for cosmetics and skincare brands: large catalogues, shade variants, sets and promotions, PV per item and branch stock. Talk to OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · Cosmetics and Skincare",
                'title'   => "MLM software for cosmetics and skincare",
                'lead'    => "Beauty catalogues change fast: new launches, seasonal sets and frequent promotions. OMC sets up the system so your team can manage products and points without disrupting members or commissions.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What cosmetics and skincare brands need",
                'paragraphs' => [
                    "A beauty brand may sell many items, often in several sizes or shades, plus sets that bundle products together. Each needs its own price and point value, and some should only appear during a campaign. Product management lets you show or hide items and run promotions without editing past orders.",
                    "Distributors in this category often sell to retail customers and demonstrate products in person, so their back office needs to work well on a phone. Branches and stockists need accurate stock, because a popular shade running out at the wrong counter costs sales.",
                    "Cosmetic notifications, ingredient labelling and advertising claims remain the brand's responsibility under Thai regulations; the system supports the commercial side of the business.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Relevant modules",
                'title'   => "Modules beauty brands usually choose",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'sparkle', 'title' => "Product and promotion management", 'text' => "Items, sets, prices, PV/BV and campaign promotions."],
                    ['icon' => 'dashboard', 'title' => "Mobile back office", 'text' => "Distributors check team, orders and bonuses on their phone."],
                    ['icon' => 'box', 'title' => "Branch stock", 'text' => "Stock per branch with receipt and issue slips."],
                    ['icon' => 'cart', 'title' => "Online ordering", 'text' => "Orders paid by e-credit wallet or a connected gateway."],
                    ['icon' => 'calculator', 'title' => "Commissions", 'text' => "Bonuses calculated from points on each order."],
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "Sales by product and campaign, with Excel export."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Suggested plans",
                'title'   => "Plans that suit beauty businesses",
                'lead'    => "All configurable. The right choice depends on how you want to reward retail and team building.",
                'plans'   => [
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Simple level bonuses that are easy for new distributors to understand. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'matrix', 'name' => "Matrix", 'text' => "A structured team with fixed width per level. Configurable.", 'url' => '/compensation-plans/matrix/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Mix retail-focused and team bonuses in one plan. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "Cosmetics and skincare questions",
                'items' => [
                    ['q' => "Can we sell product sets as a single item?", 'a' => "Yes. A set can be managed as its own product with its own price and points. How set stock is deducted is agreed during scoping."],
                    ['q' => "Can we run a promotion for a limited period?", 'a' => "Yes. Promotions and product visibility are managed in the product module, so campaign items can be shown or hidden."],
                    ['q' => "Do distributors need a computer?", 'a' => "No. The member back office works on mobile browsers as well as desktop."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your beauty brand",
                'lead'    => "Tell us about your catalogue, your distributors and your compensation plan.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    '/solutions/health-wellness/' => [
        'path'        => '/en/solutions/health-wellness/',
        'title'       => "Health and Wellness",
        'seo_title'   => "MLM Software for Health and Wellness | OMC",
        'description' => "MLM software for health, wellness and beauty businesses selling products, packages and programmes through distributors. Configured by OMC in Thailand.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · Health and Wellness",
                'title'   => "MLM software for health and wellness",
                'lead'    => "Wellness businesses often combine physical products with packages and memberships. OMC configures one system for members, orders, points and commissions across everything you sell.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What wellness businesses need",
                'paragraphs' => [
                    "A health and wellness company may sell supplements, devices, personal care and starter packages side by side. Each product line can carry its own price and point value, and packages need to be clearly defined so members know what they are buying.",
                    "These businesses also depend on trust. Members want to see their orders, deliveries and bonus history without calling head office, and staff need permissions that keep sensitive member data limited to the people who need it.",
                    "Promotions are frequent in this sector, from seasonal bundles to starter kits for new members. Prices, points and visibility can be changed per product, and reports show which lines and promotions are actually driving sales, so decisions are based on figures from the system rather than impressions.",
                    "Health claims in marketing and product registration remain the brand's responsibility. Items outside the standard modules, such as service bookings, can be built to your specification.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Relevant modules",
                'title'   => "Modules wellness businesses usually choose",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'heart', 'title' => "Products and packages", 'text' => "Prices, PV/BV and promotions for every product line."],
                    ['icon' => 'users', 'title' => "Member management", 'text' => "Registration, genealogy placement and member data."],
                    ['icon' => 'shield', 'title' => "Staff permissions", 'text' => "View, add, edit and delete rights per menu."],
                    ['icon' => 'wallet', 'title' => "E-credit wallet", 'text' => "Top-up, transfer and withdrawal for members."],
                    ['icon' => 'calculator', 'title' => "Commissions", 'text' => "Calculated daily, per round or monthly."],
                    ['icon' => 'map-pin', 'title' => "Delivery tracking", 'text' => "Orders awaiting delivery or branch pickup."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Suggested plans",
                'title'   => "Plans to consider",
                'lead'    => "All configurable to your rules.",
                'plans'   => [
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Two-leg structure with matching bonuses. Configurable.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Level bonuses on repeat purchases. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Different bonus types for products and packages. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "Health and wellness questions",
                'items' => [
                    ['q' => "Can packages and single products have different points?", 'a' => "Yes. Prices and PV/BV are set per item, so a package and a single product can carry different values."],
                    ['q' => "Can the system handle service bookings?", 'a' => "Booking is not one of the standard modules. It can be built to your specification if your business needs it."],
                    ['q' => "How is member data protected?", 'a' => "Staff access is set per menu, and members only see their own data and team."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your wellness business",
                'lead'    => "Tell us what you sell, how members buy and how you reward your distributors.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    '/solutions/distributor/' => [
        'path'        => '/en/solutions/distributor/',
        'title'       => "Distributors and Wholesale",
        'seo_title'   => "Distributor Management Software | OMC",
        'description' => "Distributor management software for wholesale and agent networks: tiered pricing, stock, branches, orders and rewards in one system. Talk to OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · Distributors",
                'title'   => "Software for distributor and agent networks",
                'lead'    => "Not every network is a classic MLM. Brands that sell through dealers, agents and wholesalers need the same discipline around orders, stock and rewards. OMC configures the system for your distribution model.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What distribution networks need",
                'paragraphs' => [
                    "Distributor networks usually have levels: master distributors, dealers and agents, each buying at a different price and often holding their own stock. Head office needs to know who sold what, to whom, and what each level is owed for the period.",
                    "Stock movement is central. Goods are received, issued to branches or agents and delivered to end customers. Issue and receive slips, branch stock and delivery status give you a clear picture of where products are.",
                    "Many distribution businesses start with spreadsheets and messaging apps. That works until two agents claim the same customer or a rebate is disputed. A shared system records each sale against the right agent, so rewards can be checked line by line.",
                    "Tiered pricing and agent-specific rules are agreed in the project scope, and can be configured or built to your specification.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Relevant modules",
                'title'   => "Modules distribution networks usually choose",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'box', 'title' => "Stock control", 'text' => "Goods receipt, stock deduction, issue and receive slips."],
                    ['icon' => 'store', 'title' => "Branch management", 'text' => "Branches in Thailand and abroad with their own stock."],
                    ['icon' => 'cart', 'title' => "Ordering", 'text' => "Orders paid by e-credit wallet or payment gateway."],
                    ['icon' => 'users', 'title' => "Network structure", 'text' => "Dealers and agents placed under their upline."],
                    ['icon' => 'calculator', 'title' => "Rewards", 'text' => "Commissions or rebates calculated from actual sales."],
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "Sales per agent, branch and product, exportable to Excel."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Suggested plans",
                'title'   => "Reward structures to consider",
                'lead'    => "All configurable. Distribution networks often use simple level-based rewards.",
                'plans'   => [
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Rewards based on the sales of agents below, down a set number of levels. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Combine margin-based and team rewards. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "Distributor network questions",
                'items' => [
                    ['q' => "Can each level buy at a different price?", 'a' => "Tiered pricing by level can be configured or built to your specification as part of the project scope."],
                    ['q' => "Can agents hold their own stock?", 'a' => "Stock can be tracked per branch. Whether agents are set up as branches or handled another way is agreed during scoping."],
                    ['q' => "Is this suitable if we are not an MLM company?", 'a' => "Yes. The same order, stock and reward records apply to many distribution models, whether rewards go one level or several."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your distribution network",
                'lead'    => "Describe your levels, pricing and how stock moves today.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    '/solutions/franchise/' => [
        'path'        => '/en/solutions/franchise/',
        'title'       => "Franchise",
        'seo_title'   => "Franchise Network Management Software | OMC",
        'description' => "Software for franchise networks that combine outlets with referral rewards: branches, stock, sales, members and commissions in one system from OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · Franchise",
                'title'   => "Software for franchise networks",
                'lead'    => "Some franchise businesses reward franchisees and partners for growing the network. OMC brings outlets, stock, sales and referral rewards together in one system configured to your model.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "See features", 'url' => '/features/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What franchise networks need",
                'paragraphs' => [
                    "A franchise network has outlets that buy from head office, hold stock and sell to customers. When franchisees are also rewarded for introducing new outlets or partners, the business needs both an operational system and a reward structure that work from the same data.",
                    "Branch management gives each outlet its own stock and sales, while member management records who introduced whom. Head office sees sales per outlet and the rewards they generate in the same reports.",
                    "Supply is the other half of the picture. Outlets order stock from head office, receive goods against issue slips and sell from their own shelves. Keeping those movements in the same system as sales means head office can see which outlets need restocking and which products move fastest, without collecting spreadsheets from each location.",
                    "Franchise agreements, fees and any registration requirements are matters for your business and its advisers. Franchise-specific workflows can be built to your specification.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Relevant modules",
                'title'   => "Modules franchise networks usually choose",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'store', 'title' => "Outlets as branches", 'text' => "Each outlet with its own stock and sales records."],
                    ['icon' => 'box', 'title' => "Stock and supply", 'text' => "Goods receipt, issue slips and stock per outlet."],
                    ['icon' => 'network', 'title' => "Referral structure", 'text' => "Record who introduced each franchisee or partner."],
                    ['icon' => 'calculator', 'title' => "Rewards", 'text' => "Commissions calculated from sales in the network."],
                    ['icon' => 'shield', 'title' => "Permissions", 'text' => "Outlet staff see only the menus they need."],
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "Sales and rewards per outlet, exportable to Excel."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Suggested plans",
                'title'   => "Reward structures to consider",
                'lead'    => "All configurable to your franchise model.",
                'plans'   => [
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Rewards for outlets introduced, down a set number of levels. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Combine outlet sales rewards with referral bonuses. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "Franchise questions",
                'items' => [
                    ['q' => "Can each outlet manage its own stock?", 'a' => "Yes. Outlets can be set up as branches, each with its own stock, sales bills and receipt of deliveries."],
                    ['q' => "Can we reward franchisees for referring new outlets?", 'a' => "Yes. Referral rewards can be configured as part of your plan, based on the structure recorded in the system."],
                    ['q' => "Can outlet staff use the system without seeing head office data?", 'a' => "Yes. Permissions are set per menu, so outlet staff only see what their role requires."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your franchise network",
                'lead'    => "Tell us how your outlets operate and how you reward network growth.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    '/solutions/international/' => [
        'path'        => '/en/solutions/international/',
        'title'       => "International Business",
        'seo_title'   => "MLM Software for International Businesses | OMC",
        'description' => "MLM software for international direct-selling businesses: branches in Thailand and abroad, with currencies and languages scoped per project by OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · International",
                'title'   => "MLM software for international businesses",
                'lead'    => "Running a network across borders, or bringing your plan into Thailand? OMC sets up branches at home and abroad in one system, with currencies and languages agreed as part of your project.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "MLM software in Thailand", 'url' => '/mlm-software-thailand/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "What international networks need",
                'paragraphs' => [
                    "When a network spans countries, members in one market often sponsor members in another. The genealogy has to stay unified while each country keeps its own branches, stock and sales records. Head office needs to see the whole picture and each market separately.",
                    "Branch management already covers branches in Thailand and abroad. Multiple currencies, interface languages and country-specific rules are custom scope: we agree exactly which markets, languages and currencies you need before development starts.",
                    "Each country has its own rules for direct selling. Registration and compliance in every market are your company's responsibility; for Thailand, we publish a general OCPB guide.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Relevant modules",
                'title'   => "Modules international businesses usually choose",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'globe', 'title' => "Branches abroad", 'text' => "Branches in Thailand and other countries in one system."],
                    ['icon' => 'network', 'title' => "Unified genealogy", 'text' => "One network structure across markets."],
                    ['icon' => 'box', 'title' => "Stock per branch", 'text' => "Each market's stock, receipts and deliveries."],
                    ['icon' => 'plug', 'title' => "Payment connections", 'text' => "Payment APIs agreed per market in the project scope."],
                    ['icon' => 'calculator', 'title' => "Commissions", 'text' => "One plan calculated across the whole network."],
                    ['icon' => 'chart', 'title' => "Reports", 'text' => "Sales per branch and country, exportable to Excel."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Suggested plans",
                'title'   => "Plans for cross-border networks",
                'lead'    => "All configurable. Bring your existing plan and we will review it.",
                'plans'   => [
                    ['key' => 'binary', 'name' => "Binary", 'text' => "Two-leg matching that works across markets. Configurable.", 'url' => '/compensation-plans/binary/'],
                    ['key' => 'unilevel', 'name' => "Unilevel", 'text' => "Level bonuses through an international team. Configurable.", 'url' => '/compensation-plans/unilevel/'],
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Your global plan with several bonus types. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "International business questions",
                'items' => [
                    ['q' => "Can the system support several currencies?", 'a' => "Multiple currencies can be built as part of the project scope. Tell us which markets you operate in."],
                    ['q' => "Which languages are available?", 'a' => "Interface languages are agreed per project. You can contact us in English or Thai."],
                    ['q' => "Can members in one country sponsor members in another?", 'a' => "Yes. The genealogy is one structure, while branches keep sales and stock for each market."],
                    ['q' => "Do you handle registration in other countries?", 'a' => "No. Registration and compliance in each market are your company's responsibility."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Discuss your international network",
                'lead'    => "Tell us which countries you operate in and where you plan to grow.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],

    '/solutions/custom/' => [
        'path'        => '/en/solutions/custom/',
        'title'       => "Custom Systems",
        'seo_title'   => "Custom MLM System Design | OMC",
        'description' => "A custom MLM system designed for a business model that does not fit a standard package: your plan, your workflows and integrations, built by OMC.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "Solutions · Custom",
                'title'   => "A custom MLM system for your model",
                'lead'    => "Affiliate programmes, referral schemes and new hybrid models rarely fit a standard package. OMC starts from a proven core and builds the parts that make your business different.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '/contact/', 'style' => 'primary'],
                    ['label' => "Custom programming", 'url' => '/mlm-programming/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "When a custom system makes sense",
                'paragraphs' => [
                    "If your reward rules, workflows or integrations are unusual, forcing them into a fixed package means workarounds that staff and members feel every day. A custom system lets the software follow the business instead.",
                    "Custom does not mean starting from nothing. Members, genealogy, orders, stock, wallets and reports already exist in the core. We configure those and develop only what is genuinely specific to you, inside the same system, so everything shares one database and one set of permissions.",
                    "Board and Stair Step structures, special qualification periods and connections to your other systems are typical examples. Each is scoped, written down with examples and tested before launch.",
                ],
            ],
            [
                'type'    => 'steps',
                'eyebrow' => "How we work",
                'title'   => "From idea to a working system",
                'items'   => [
                    ['title' => "Explain your model", 'text' => "We learn how your business earns, rewards and operates."],
                    ['title' => "Separate configure from build", 'text' => "We identify what the core covers and what must be developed."],
                    ['title' => "Agree scope and examples", 'text' => "Custom rules are written down with worked examples you approve."],
                    ['title' => "Develop, test and support", 'text' => "We build, test against your examples, launch and support the system."],
                ],
            ],
            [
                'type'    => 'plan_cards',
                'eyebrow' => "Plan structures",
                'title'   => "Structures available as custom configuration",
                'lead'    => "Board and Stair Step are available as a custom configuration, so ask our team. Hybrid plans are configurable.",
                'plans'   => [
                    ['key' => 'hybrid', 'name' => "Hybrid", 'text' => "Several bonus types combined in one plan. Configurable.", 'url' => '/compensation-plans/hybrid/'],
                    ['key' => 'board', 'name' => "Board", 'text' => "Available as a custom configuration. Ask our team.", 'url' => '/compensation-plans/board/'],
                    ['key' => 'stair_step', 'name' => "Stair Step", 'text' => "Available as a custom configuration. Ask our team.", 'url' => '/compensation-plans/stair-step/'],
                ],
                'action'  => ['label' => "All compensation plans", 'url' => '/compensation-plans/'],
            ],
            [
                'type'  => 'faq',
                'title' => "Custom system questions",
                'items' => [
                    ['q' => "Is a custom system more expensive?", 'a' => "It depends on how much must be developed rather than configured. We separate the two in the quotation so you can see where the cost comes from."],
                    ['q' => "Can a custom system still be changed later?", 'a' => "Yes. Custom parts are developed inside the core and maintained by our team, so changes are handled as normal change requests."],
                    ['q' => "Can you build an affiliate or referral programme?", 'a' => "Yes, referral and affiliate reward rules can be built to your specification."],
                ],
            ],
            [
                'type'    => 'lead_form',
                'form_key'=> 'consult',
                'eyebrow' => "Consultation",
                'title'   => "Describe your business model",
                'lead'    => "Tell us what makes your model different. We will explain what can be configured and what would be built.",
                'submit'  => "Request a consultation",
            ],
        ],
    ],
];
