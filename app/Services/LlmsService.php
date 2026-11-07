<?php
/**
 * /llms.txt — สรุปเว็บแบบ Markdown สำหรับผู้ช่วย AI (ChatGPT Search · Perplexity · Claude · Copilot ฯลฯ)
 *
 * ‼️ สร้างจากข้อมูลจริงทุกครั้ง (หน้า/บทความที่เผยแพร่และ index ได้ + ข้อมูลบริษัทชุดเดียวกับท้ายเว็บ) — ไม่มีข้อความที่ต้องแก้มือ
 * ‼️ ข้อเท็จจริงเท่านั้น: ไม่มีตัวเลขลูกค้า/รางวัล/รีวิว · ลิงก์เป็น canonical ของ production
 * ‼️ ไม่ใช่สิ่งที่ Google ใช้จัดอันดับ — เป็นทางลัดให้ระบบ AI อ่านโครงเว็บได้ถูก ไม่มีผลเสียต่อ SEO
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Page;
use Core\Path;

final class LlmsService
{
    /** หน้าที่ขึ้นก่อน (ตอบคำถามหลักของลูกค้า) — ที่เหลือเรียงตาม sort ของ CMS */
    private const KEY_PATHS = ['/', '/mlm-software-omc/', '/features/', '/compensation-plans/', '/services/', '/pricing/', '/demo/', '/faq/',
        '/about/', '/portfolio/', '/contact/', '/ocpb-mlm/'];

    public function __construct(
        private readonly Page $pages = new Page(),
        private readonly PostService $posts = new PostService(),
    ) {
    }

    public function content(): string
    {
        [$brand, $contact] = SiteService::company(SiteService::configFor(LocaleService::DEFAULT), LocaleService::DEFAULT);
        $founded = SiteService::founded();
        $base    = SeoService::base();
        $link    = static fn (string $path): string => $base . Path::encode($path);

        $out   = [];
        $out[] = '# ' . SettingService::brand();
        $out[] = '';
        $out[] = '> ' . SiteService::about('en');
        $out[] = '';
        $out[] = '## Company facts';
        $out[] = '- Company: ' . (string) ($brand['legal_name'] ?? '') . ' (Online Marketing Communication Co., Ltd., "OMC")';
        $out[] = '- Registered: ' . $founded . ' · Nonthaburi, Thailand';
        $out[] = '- What OMC does: develops and maintains MLM / direct selling software (members, genealogy, commission calculation, orders, stock, back office), configured to each company\'s compensation plan';
        $out[] = '- Languages of the website: Thai (main), English (/en/), Chinese (/zh/)';
        $out[] = '- Contact: ' . (string) ($contact['phone'] ?? '') . ' · ' . SchemaService::entityEmail($contact) . ' · ' . $link('/contact/');
        $out[] = '- Live demo of the system: https://demomlm.omc.co.th/ · book a guided demo: ' . $link('/demo/');
        $out[] = '';

        $rows   = array_filter($this->pages->indexable(), static fn (array $r): bool => (string) ($r['canonical_override'] ?? '') === '');
        $byLang = [];
        foreach ($rows as $row) {
            $byLang[(string) $row['locale']][] = $row;
        }

        foreach (['th' => 'Main pages (Thai)', 'en' => 'English pages', 'zh' => 'Chinese pages'] as $locale => $heading) {
            $list = $byLang[$locale] ?? [];
            if ($list === []) {
                continue;
            }
            $prefix = LocaleService::prefix($locale);
            $rank   = static function (array $r) use ($prefix): int {
                $key = array_search(substr((string) $r['path'], strlen($prefix)) ?: '/', self::KEY_PATHS, true);

                return $key === false ? 100 : (int) $key;
            };
            usort($list, static fn (array $a, array $b): int => $rank($a) <=> $rank($b));

            $out[] = '## ' . $heading;
            foreach ($list as $row) {
                $desc  = trim((string) ($row['meta_description'] ?? ''));
                $out[] = '- [' . trim((string) $row['title']) . '](' . $link((string) $row['path']) . ')' . ($desc !== '' ? ': ' . $desc : '');
            }
            $out[] = '';
        }

        $out[] = '## Articles (Thai)';
        foreach ($this->posts->listing(1, 50)['posts'] as $post) {
            $out[] = '- [' . trim((string) $post['title']) . '](' . $link(PostService::permalink($post)) . ')';
        }
        $out[] = '';
        $out[] = '## More';
        $out[] = '- Sitemap: ' . $base . '/sitemap.xml';
        $out[] = '- RSS: ' . $base . '/feed/';

        return implode("\n", $out) . "\n";
    }
}
