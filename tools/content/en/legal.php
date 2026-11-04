<?php
/**
 * OCPB guide + policy pages (English). Keys = Thai page path; `path` = /en URL.
 *
 * ‼️ /ocpb-mlm/ is general information about Thailand, not legal advice. Unverified figures from the old Thai page
 *    (capital amounts, thresholds, timelines) are not published — readers are pointed to the OCPB.
 * ‼️ /ocpb-mlm2/ is a Thai-only legacy URL and is intentionally not translated.
 */

declare(strict_types=1);

$policyContact = '<h3>Contact us about personal data</h3>'
    . '<p>If you have suggestions or questions about how we collect, use or disclose personal data, or wish to exercise your rights under this policy, please contact:</p>'
    . '<ul><li>Online Marketing Communication Co., Ltd., Khajornprasart Building, 3/3 Soi Liang Mueang Nonthaburi 14, Tha Sai, Mueang Nonthaburi, Nonthaburi 11000, Thailand</li>'
    . '<li>Email <a href="mailto:info@omc.co.th">info@omc.co.th</a></li>'
    . '<li>Phone <a href="tel:+66991095544">+66 99 109 5544</a></li>'
    . '<li>Facebook: <a href="https://www.facebook.com/mlmsoftwarebyomc">MLM Software by OMC</a></li></ul>';

return [

    '/ocpb-mlm/' => [
        'path'        => '/en/ocpb-mlm/',
        'title'       => "OCPB Direct Selling Registration in Thailand",
        'seo_title'   => "OCPB Direct Selling Registration in Thailand | OMC",
        'description' => "An overview of registering a direct selling or direct marketing business with Thailand's OCPB: who can apply, disqualifications and steps. Not legal advice.",
        'blocks'      => [
            [
                'type'    => 'hero',
                'variant' => 'page',
                'eyebrow' => "OCPB registration (Thailand)",
                'title'   => "Registering a direct selling business with Thailand's OCPB",
                'lead'    => "An introduction for companies planning to run a direct selling or direct marketing business in Thailand: why registration with the Office of the Consumer Protection Board (OCPB, known in Thai as สคบ.) is required, who can apply and how the process generally works.",
                'actions' => [
                    ['label' => "Talk to our team", 'url' => '#lead-form', 'style' => 'primary'],
                    ['label' => "See the direct selling system", 'url' => '/mlm-software-omc/', 'style' => 'secondary'],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Why direct selling businesses in Thailand must register",
                'paragraphs' => [
                    "This page applies to Thailand only. Direct selling and direct marketing in Thailand are governed by the Direct Sales and Direct Marketing Act B.E. 2545 (2002), as amended. Operators must register with the registrar, the OCPB, before starting business.",
                    "Under Thai law, \"direct selling\" means selling goods or services to consumers through independent distributors or agents, including multi-level marketing (MLM). \"Direct marketing\" means offering goods or services directly to consumers through media so that consumers order them. Each has its own registration criteria.",
                    "Registration lets the business operate lawfully, gives independent distributors and consumers confidence, and means the company appears in the list of registered operators that the OCPB publishes.",
                ],
            ],
            [
                'type'    => 'feature_grid',
                'eyebrow' => "Basic criteria",
                'title'   => "What applicants should know",
                'lead'    => "A summary of the general criteria. Always check the current details and figures in the OCPB's announcements before applying.",
                'columns' => 3,
                'items'   => [
                    ['icon' => 'users', 'title' => "Individual applicants (direct marketing)", 'text' => "A valid Thai national ID card, Thai nationality, at least 18 years of age, and other conditions set by the OCPB."],
                    ['icon' => 'store', 'title' => "Juristic person applicants", 'text' => "A partnership or company with paid-up registered capital at or above the legal minimum, and possibly a security deposit depending on the type of business."],
                    ['icon' => 'shield', 'title' => "No previous revocation", 'text' => "The applicant must not have had a direct selling or direct marketing registration revoked within the period set by law (five years before the application date)."],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "Disqualifications for executives",
                'paragraphs' => ["The managing partner, managing director, manager or person responsible for the applicant's operations must not be disqualified, for example by being:"],
                'list'       => [
                    "Bankrupt",
                    "Legally incompetent or quasi-incompetent",
                    "Previously sentenced to imprisonment by a final judgment, except for offences committed through negligence or petty offences",
                    "An executive of another partnership or company currently registered for direct selling or direct marketing",
                    "A former executive of a partnership or company whose registration was revoked within five years before the application date",
                ],
            ],
            [
                'type'    => 'steps',
                'title'   => "The general process",
                'lead'    => "How long it takes depends on the completeness of your documents and the OCPB's review.",
                'items'   => [
                    ['title' => "Prepare the entity and documents", 'text' => "Company certificate, registered capital, executive details and product information."],
                    ['title' => "Prepare the compensation plan", 'text' => "A plan explaining how independent distributors' rewards are calculated."],
                    ['title' => "Submit and receive a reference number", 'text' => "Track the status using the submission and application numbers."],
                    ['title' => "Receive the registration number", 'text' => "Once approved, you receive a registration number and can begin operating."],
                ],
            ],
            [
                'type'       => 'rich_text',
                'title'      => "How a direct selling system helps",
                'paragraphs' => [
                    "Registration is the operating company's responsibility. A well-run direct selling system helps by giving the company verifiable records: independent distributor details, sales, and reward payments calculated in line with the plan that was submitted.",
                    "OMC can offer initial guidance and help prepare the system and compensation-plan side of things. Ask our team directly about the scope of what we can help with.",
                ],
            ],
            [
                'type'  => 'faq',
                'title' => "OCPB registration questions",
                'items' => [
                    ['question' => "What is the difference between direct selling and direct marketing?", 'answer' => "Direct selling reaches consumers through independent distributors or agents. Direct marketing offers goods directly to consumers through media, and consumers place orders themselves."],
                    ['question' => "Where can I check which companies are registered?", 'answer' => "The OCPB publishes a list of registered operators, which you can check on the OCPB website."],
                    ['question' => "What is the minimum registered capital?", 'answer' => "The law sets a minimum paid-up capital depending on the type of entity and business, and it has been amended over time. Check the current figure with the OCPB before applying."],
                    ['question' => "Does this apply to companies outside Thailand?", 'answer' => "This guide covers Thai law only. If you sell in other countries, check the rules in each market with local advisers."],
                ],
            ],
            [
                'type'  => 'rich_text',
                'title' => "Read more",
                'html'  => '<ul>'
                    . '<li><a href="/compensation-plans/">MLM compensation plans</a></li>'
                    . '<li><a href="/direct-sales-system/">Direct selling system for Thailand</a></li>'
                    . '<li><a href="/mlm-software-thailand/">MLM software in Thailand</a></li>'
                    . '</ul>'
                    . '<p><strong>General information, not legal advice.</strong> Requirements may change. Please check with the OCPB or a legal adviser before taking action.</p>',
            ],
            [
                'type'     => 'lead_form',
                'form_key' => 'consult',
                'eyebrow'  => "Free consultation",
                'title'    => "Talk to us about starting direct selling in Thailand",
                'lead'     => "Tell us where your business stands today. Our team will get back to you with guidance on the system and compensation-plan steps.",
            ],
        ],
    ],

    '/นโยบายความเป็นส่วนตัวข/' => [
        'path'        => '/en/privacy-policy/',
        'title'       => "Privacy Policy",
        'seo_title'   => "Privacy Policy | OMC",
        'description' => "Privacy policy for mlmsoftware.omc.co.th by Online Marketing Communication Co., Ltd.: what data we collect, why we collect it and your rights as a data subject.",
        'blocks'      => [
            [
                'type' => 'rich_text',
                'html' => '<p>The website mlmsoftware.omc.co.th is operated by Online Marketing Communication Co., Ltd. ("OMC", "we" or "us"). We respect the privacy of everyone who visits the website, and this policy explains how we handle your personal data in line with Thailand\'s Personal Data Protection Act B.E. 2562 (2019).</p>'
                    . '<h3>Who this policy applies to</h3>'
                    . '<ul><li>The service provider and website owner: OMC</li><li>Website visitors: anyone who opens or reads any page on mlmsoftware.omc.co.th</li><li>People who submit information through forms on the website</li></ul>'
                    . '<h3>Data we collect through forms</h3>'
                    . '<p>When you submit a form to request a demo, a quote or a consultation, or to contact our team, we store the information you enter: your name, company, type of business, phone number, email, LINE ID and any details you provide. We also store technical information such as the page the form was sent from, the source of your visit and your IP address. We use this to contact you, provide the service you asked for and prevent automated submissions (spam). We do not sell your data to anyone.</p>'
                    . '<h3>Why we collect data</h3>'
                    . '<ul><li>To contact you and provide the services you request</li><li>For online marketing and advertising activities</li><li>To analyse website visitors and for market research</li><li>To improve the website and the user experience</li></ul>'
                    . '<h3>Use of cookies</h3>'
                    . '<p>The website uses cookies to function. Find out more in our <a href="/นโยบายการใช้คุกกี้-cookies-policy/">Cookie Policy</a>.</p>'
                    . '<h3>Data collected by third parties</h3>'
                    . '<p>The website may use third-party software and services to improve how it works, such as Google Analytics, Facebook Pixel/Conversion API, Google Maps and YouTube. Where the website connects to third-party platforms, some cookies and traffic data may be handled by those third parties. Please review their policies as well.</p>'
                    . '<h3>Your rights</h3>'
                    . '<ul><li>Request access to, correction of or deletion of the data you submitted through our forms, using the contact details below</li><li>Refuse or delete cookies through your web browser settings</li></ul>'
                    . '<h3>Security and external links</h3>'
                    . '<p>The website may link to other websites. We are not responsible for the privacy policies of those websites, so please review the policy of each site you visit.</p>'
                    . $policyContact,
            ],
        ],
    ],

    '/นโยบายการใช้คุกกี้-cookies-policy/' => [
        'path'        => '/en/cookie-policy/',
        'title'       => "Cookie Policy",
        'seo_title'   => "Cookie Policy | OMC",
        'description' => "Cookie policy for mlmsoftware.omc.co.th: what cookies are, the necessary, analytics and marketing cookies we use, and how to manage or refuse them.",
        'blocks'      => [
            [
                'type' => 'rich_text',
                'html' => '<p>The website mlmsoftware.omc.co.th, operated by Online Marketing Communication Co., Ltd., uses cookies and similar technologies to give visitors a good experience and to help us improve the website to better meet their needs.</p>'
                    . '<h3>Definitions</h3>'
                    . '<ul><li>Website visitor: anyone who opens or reads any page on the website</li><li>Website system: the systems that run mlmsoftware.omc.co.th</li></ul>'
                    . '<h3>What are cookies?</h3>'
                    . '<p>Cookies are small text files stored on a visitor\'s device when they visit a website. The information in a cookie is sent back to the website system for processing, or to other websites that recognise that cookie. Cookies help a website remember your device and the choices you have made.</p>'
                    . '<h3>Types of cookies we use</h3>'
                    . '<ul><li><strong>Necessary cookies</strong>: allow the website and its functions to work securely, for example cookies that protect forms against forgery. Without them, the website will not work properly.</li>'
                    . '<li><strong>Statistics and analytics cookies</strong>: count visitors and the pages they view so we can improve the website.</li>'
                    . '<li><strong>Marketing cookies</strong>: record usage, links visited and the source of the visit (such as campaign UTM parameters) so we can present relevant information and advertising.</li></ul>'
                    . '<h3>Managing and refusing cookies</h3>'
                    . '<p>You can refuse cookies through your web browser settings or your device\'s privacy settings. Refusing all cookies may stop some functions, such as submitting forms, from working.</p>'
                    . '<h3>Terms of use</h3>'
                    . '<p>By using this website, you acknowledge the use of cookies for the purposes above and the collection, use and disclosure of data under our <a href="/นโยบายความเป็นส่วนตัวข/">Privacy Policy</a>. If the website links to third-party websites, those parties may have their own cookie policies.</p>'
                    . $policyContact,
            ],
        ],
    ],
];
