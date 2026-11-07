<?php
/**
 * JSON-LD ของหน้าสาธารณะ
 *
 * ‼️ ค่าทุกตัวมาจากข้อมูลจริงในฐานข้อมูล/ค่าตั้งเท่านั้น — ห้ามใส่ rating ราคา หรือจำนวนรีวิวที่ไม่มีจริง
 *    (PLAN.md "schema ต้องตรงเนื้อหาจริง") ค่าที่ไม่มีให้ละไว้ ไม่ใช่เติมค่าสมมติ
 *
 *    Organization + WebSite         ทุกหน้า
 *    BreadcrumbList                 ทุกหน้าที่ไม่ใช่หน้าแรก (มี breadcrumb ≥ 2 ขั้น)
 *    BlogPosting                    บทความ
 *    FAQPage                        หน้าที่มีบล็อก faq ที่มีคำถามจริงอย่างน้อยหนึ่งข้อ
 *    SoftwareApplication            หน้า template product (/mlm-software-omc/)
 */

declare(strict_types=1);

namespace App\Services;

final class SchemaService
{
    /**
     * @param array{
     *   canonical:?string, title:string, description?:string,
     *   breadcrumbs?:array<int, array{label:string, url:?string}>,
     *   post?:?array, category?:?array, image?:?array,
     *   page?:?array, blocks?:array<int, array{type:string, data:array<string,mixed>}>
     * } $context
     * @return array<string, mixed>
     */
    public function graph(array $context): array
    {
        $base  = SeoService::base() . '/';
        $locale = (string) ($context['locale'] ?? LocaleService::DEFAULT);
        $graph  = [$this->organization($base, $locale), $this->website($base, $locale)];

        $crumbs = $context['breadcrumbs'] ?? [];

        if (count($crumbs) >= 2 && $context['canonical'] !== null) {
            $graph[] = $this->breadcrumbList($crumbs, $context['canonical']);
        }

        if (($context['post'] ?? null) !== null && $context['canonical'] !== null) {
            $graph[] = $this->blogPosting($context, $base);
        }

        $page = $context['page'] ?? null;

        if ($page !== null) {
            $faq = $this->faqPage($context['blocks'] ?? []);

            if ($faq !== null) {
                $graph[] = $faq;
            }

            if (($page['template'] ?? '') === 'product' && $context['canonical'] !== null) {
                $graph[] = $this->softwareApplication($context, $base);
            }
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /** JSON ที่วางใน <script type="application/ld+json"> ได้ปลอดภัย — < > & ถูกแปลงเป็น < ฯลฯ */
    public static function encode(array $graph): string
    {
        return (string) json_encode(
            $graph,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    /**
     * อีเมลของบริษัทสำหรับ entity (schema · llms.txt) — อีเมลแรกท้ายเว็บที่อยู่บนโดเมนบริษัท (โดเมนของ canonical host)
     * ‼️ ไม่ใช่กล่องรับแจ้งเตือน lead (ตั้งเป็นอีเมลภายในได้) · อีเมลฟรี (hotmail ฯลฯ) ทำให้ AI ไม่แน่ใจว่าเป็นช่องทางทางการ
     *
     * @param array<string, mixed> $contact จาก SiteService::company()
     */
    public static function entityEmail(array $contact): string
    {
        $emails = array_values(array_filter(array_map('strval', (array) ($contact['emails'] ?? [])), 'strlen'));
        $host   = strtolower((string) parse_url(SeoService::base(), PHP_URL_HOST));

        foreach ($emails as $email) {
            $domain = strtolower(substr((string) strrchr($email, '@'), 1));
            if ($domain !== '' && ($host === $domain || str_ends_with($host, '.' . $domain))) {
                return $email;
            }
        }

        return $emails[0] ?? (string) ($contact['email'] ?? '');
    }

    /** @return array<string, mixed> */
    private function organization(string $base, string $locale): array
    {
        $org = [
            '@type' => 'Organization',
            '@id'   => $base . '#organization',
            'name'  => (string) (SettingService::get('organization.name') ?: SettingService::brand()),
            'url'   => $base,
        ];

        $about = SiteService::about($locale);

        if ($about !== '') {
            $org['description'] = $about;
        }

        $optional = [
            'legalName' => SettingService::get('organization.legal_name'),
            'telephone' => SettingService::get('organization.telephone'),
            'email'     => SettingService::get('organization.email'),
        ];

        foreach ($optional as $key => $value) {
            if (is_string($value) && $value !== '') {
                $org[$key] = $value;
            }
        }

        $logo = SettingService::get('organization.logo');

        if (is_string($logo) && $logo !== '') {
            $org['logo'] = ['@type' => 'ImageObject', 'url' => SeoService::absolute($logo)];
        }

        // ‼️ เฉพาะข้อเท็จจริงที่แสดงบนเว็บอยู่แล้ว/ยืนยันแล้ว (GEO 2026-11-07) — ไม่มี rating/award/จำนวนลูกค้า
        [$brand, $contact] = SiteService::company(SiteService::configFor($locale), $locale);

        $alternate = array_values(array_unique(array_filter([
            'OMC',
            (string) ($brand['legal_name'] ?? ''),
            'Online Marketing Communication Co., Ltd.',
        ], static fn (string $n): bool => $n !== '' && $n !== ($org['legalName'] ?? '') && $n !== $org['name'])));
        if ($alternate !== []) {
            $org['alternateName'] = $alternate;
        }

        $founded = SiteService::founded();
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $founded) === 1) {
            $org['foundingDate'] = $founded; // ยืนยันแล้ว: จดทะเบียน 3 เม.ย. 2552
        }

        if ((string) ($contact['address'] ?? '') !== '') {
            $org['address'] = ['@type' => 'PostalAddress', 'streetAddress' => (string) $contact['address'], 'addressCountry' => 'TH'];
        }

        if ((string) ($contact['phone_e164'] ?? '') !== '') {
            $org['contactPoint'] = [[
                '@type'       => 'ContactPoint',
                'contactType' => 'sales',
                'telephone'   => (string) $contact['phone_e164'],
                'email'       => self::entityEmail($contact),
                'areaServed'  => 'TH',
            ]];
        }

        $org['areaServed'] = 'TH';
        $org['knowsAbout'] = ['MLM software', 'Direct selling software', 'Network marketing compensation plans', 'Commission calculation'];

        $sameAs = array_values(array_filter((array) SettingService::get('organization.same_as', []), 'is_string'));
        foreach (['facebook'] as $field) { // LINE เป็นช่องแชต ไม่ใช่โปรไฟล์ขององค์กร — ไม่ใส่ใน sameAs
            if (str_starts_with((string) ($contact[$field] ?? ''), 'https://')) {
                $sameAs[] = (string) $contact[$field];
            }
        }
        $sameAs = array_values(array_unique($sameAs));

        if ($sameAs !== []) {
            $org['sameAs'] = $sameAs;
        }

        return $org;
    }

    /** @return array<string, mixed> */
    private function website(string $base, string $locale): array
    {
        return [
            '@type'      => 'WebSite',
            '@id'        => $base . '#website',
            'url'        => $base,
            'name'       => SettingService::brand(),
            'inLanguage' => LocaleService::htmlLang($locale),
            'publisher'  => ['@id' => $base . '#organization'],
        ];
    }

    /**
     * @param  array<int, array{label:string, url:?string}> $crumbs
     * @return array<string, mixed>
     */
    private function breadcrumbList(array $crumbs, string $canonical): array
    {
        $items = [];
        $last  = count($crumbs) - 1;

        foreach ($crumbs as $index => $crumb) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $crumb['label'],
                'item'     => $index === $last || $crumb['url'] === null ? $canonical : SeoService::absolute($crumb['url']),
            ];
        }

        return ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $items];
    }

    /**
     * @param  array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function blogPosting(array $context, string $base): array
    {
        $post      = $context['post'];
        $canonical = (string) $context['canonical'];
        $author    = trim((string) ($post['author_name'] ?? ''));

        $article = [
            '@type'            => 'BlogPosting',
            '@id'              => $canonical . '#article',
            'headline'         => mb_substr((string) $post['title'], 0, 110),
            'mainEntityOfPage' => $canonical,
            'inLanguage'       => LocaleService::htmlLang((string) ($post['locale'] ?? LocaleService::DEFAULT)),
            'publisher'        => ['@id' => $base . '#organization'],
            'author'           => $author !== '' ? ['@type' => 'Person', 'name' => $author] : ['@id' => $base . '#organization'],
        ];

        if (($post['published_at'] ?? null) !== null) {
            $article['datePublished'] = SeoService::isoDate((string) $post['published_at']);
        }
        if (($post['updated_at'] ?? null) !== null) {
            $article['dateModified'] = SeoService::isoDate((string) $post['updated_at']);
        }
        if (($context['description'] ?? '') !== '') {
            $article['description'] = $context['description'];
        }
        if (($context['image'] ?? null) !== null) {
            $article['image'] = SeoService::absolute((string) $context['image']['path']);
        }
        if (($context['category'] ?? null) !== null) {
            $article['articleSection'] = (string) $context['category']['name'];
        }

        return $article;
    }

    /**
     * @param  array<int, array{type:string, data:array<string,mixed>}> $blocks
     * @return array<string, mixed>|null
     */
    private function faqPage(array $blocks): ?array
    {
        $questions = [];

        foreach ($blocks as $block) {
            if ($block['type'] !== 'faq' || !is_array($block['data']['items'] ?? null)) {
                continue;
            }

            foreach ($block['data']['items'] as $item) {
                // q/a = คีย์ที่ partial blocks/faq.php แสดงจริง (CMS บันทึกแบบนี้) · question/answer = รูปเดิม
                $question = trim((string) ($item['q'] ?? $item['question'] ?? ''));
                $answer   = trim(strip_tags((string) ($item['a'] ?? $item['answer'] ?? '')));

                if ($question === '' || $answer === '') {
                    continue;
                }

                $questions[] = [
                    '@type'          => 'Question',
                    'name'           => $question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
                ];
            }
        }

        return $questions === [] ? null : ['@type' => 'FAQPage', 'mainEntity' => $questions];
    }

    /**
     * ‼️ ไม่มี offers / aggregateRating โดยตั้งใจ — ยังไม่มีราคาและรีวิวจริง (QUESTIONS.md)
     *
     * @param  array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function softwareApplication(array $context, string $base): array
    {
        $app = [
            '@type'               => 'SoftwareApplication',
            '@id'                 => $context['canonical'] . '#software',
            'name'                => (string) $context['page']['title'],
            'url'                 => $context['canonical'],
            'applicationCategory' => 'BusinessApplication',
            'publisher'           => ['@id' => $base . '#organization'],
        ];

        if (($context['description'] ?? '') !== '') {
            $app['description'] = $context['description'];
        }

        return $app;
    }
}
