<?php
/**
 * ตรวจหลัง deploy — ยิงจริงที่ URL ที่ให้ (ไม่ต้องใช้ฐานข้อมูล) · ใช้ได้ทั้ง staging และ production
 *
 *   php tools/launch/postcheck.php https://mlmsoftware.omc.co.th            ← หลังขึ้น production (เกณฑ์ production)
 *   php tools/launch/postcheck.php http://203.146.127.221/~omcai/mlmsoftware --staging
 *   … --psi            (PSI_KEY=<Google API key> แนะนำ — ไม่มี key ใช้โควตารวมที่มักเต็ม: error 429) วัด PageSpeed Insights (เซิร์ฟเวอร์ Google · มือถือ + desktop) หน้าสำคัญ 4 หน้า + Field data (CrUX) ถ้ามี
 *
 * ตรวจ:
 *   1. URL เดิม 95 รายการ (docs/seo/baseline-2026-11-03/url-list.txt): ไม่มี 404 · redirect ไม่เกิน 1 ครั้ง และเป็น 301 · ปลายทางอยู่โดเมนเดียวกัน
 *      · canonical ชี้ https://mlmsoftware.omc.co.th · 410 เฉพาะ /wp-login.php /xmlrpc.php
 *   2. production: ไม่มี X-Robots-Tag noindex · robots.txt Allow + Sitemap · HSTS · http→https และ www→non-www 301 ครั้งเดียว
 *      staging:    ต้องมี noindex + robots Disallow (ห้ามหลุดให้ index)
 *   3. sitemap.xml + sitemap ย่อยตอบ 200 · /_health 200 · ไฟล์ลับเปิดไม่ได้
 * ผลเขียนที่ docs/seo/postcheck-<host>-<วันที่>.md · exit 1 เมื่อมีข้อที่ไม่ผ่าน
 *
 * ‼️ ไม่ส่งค่าลับใด ๆ · PSI ส่งแค่ URL สาธารณะไปที่ Google (เหมือนเปิด pagespeed.web.dev)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !isset($argv[1])) {
    fwrite(STDERR, "usage: php tools/launch/postcheck.php <base-url> [--staging] [--psi]\n");
    exit(2);
}

$root    = dirname(__DIR__, 2);
$base    = rtrim($argv[1], '/');
$staging = in_array('--staging', $argv, true);
$psi     = in_array('--psi', $argv, true);
$prodHost = 'mlmsoftware.omc.co.th';
$parts    = parse_url($base);
$prefix   = rtrim((string) ($parts['path'] ?? ''), '/');
$origin   = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

$fails = [];
$notes = [];
$fail  = static function (string $m) use (&$fails): void { $fails[] = $m; };

$enc = static fn (string $t): string => (string) preg_replace_callback('/[^\x21-\x7E]/', static fn (array $m): string => rawurlencode($m[0]), $t);

/** @return array{status:int,headers:array<string,string>,body:string} */
$get = static function (string $url, bool $body = true, int $timeout = 30) use ($enc): array {
    $ch = curl_init($enc($url));
    $headers = [];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => $timeout, CURLOPT_NOBODY => !$body,
        CURLOPT_USERAGENT => 'OMC-postcheck/1.0', CURLOPT_ENCODING => '',
        CURLOPT_HEADERFUNCTION => static function ($c, string $line) use (&$headers): int {
            $p = explode(':', $line, 2);
            if (count($p) === 2) {
                $headers[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($line);
        },
    ]);
    /* ‼️ ตรวจใบรับรองเสมอ (ห้ามปิด) — PHP ของ XAMPP ไม่มี CA bundle ของตัวเอง: ใช้ CURL_CA_BUNDLE (แนะนำ path ที่ไม่มีช่องว่าง)
          ตั้งแยกด้วย curl_setopt — ใส่ในอาร์เรย์ด้วย spread แล้ว key ตัวเลข (ค่าคงที่ CURLOPT_*) ถูกเรียงเลขใหม่ ค่าหายเงียบ */
    $ca = (string) (getenv('CURL_CA_BUNDLE') ?: ini_get('curl.cainfo'));
    if ($ca !== '' && is_file($ca)) {
        curl_setopt($ch, CURLOPT_CAINFO, $ca);
    }
    $b = (string) curl_exec($ch);

    return ['status' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'headers' => $headers, 'body' => $b, 'error' => curl_error($ch)];
};

// ต่อไม่ได้เลย (TLS/DNS/firewall) = หยุดพร้อมสาเหตุจริง — ไม่ใช่รายงาน "ไม่ผ่าน" ทุกข้อแบบชวนเข้าใจผิด
$probe = $get($base . '/', false);
if ($probe['status'] === 0) {
    fwrite(STDERR, "cannot connect to {$base}: {$probe['error']}\n(set CURL_CA_BUNDLE=<path to cacert.pem> if this is a certificate error)\n");
    exit(2);
}

// ------------------------------------------------------------------ 1. 95 baseline URLs
$urls   = array_values(array_filter(array_map('trim', file($root . '/docs/seo/baseline-2026-11-03/url-list.txt') ?: [])));
$counts = [];
$rows   = [];
foreach ($urls as $i => $u) {
    $p      = parse_url($u);
    $target = $base . ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
    $hops   = [];
    $res    = $get($target);
    while (in_array($res['status'], [301, 302, 307, 308], true) && count($hops) < 5) {
        $loc    = $res['headers']['location'] ?? '';
        $loc    = str_starts_with($loc, '/') ? $origin . $loc : $loc;
        $hops[] = $res['status'];
        if (!str_starts_with($loc, $base . '/') && $loc !== $base) {
            $fail('#' . ($i + 1) . ' redirect leaves the site: ' . rawurldecode($loc));
            break;
        }
        $target = $loc;
        $res    = $get($target);
    }
    $canon = preg_match('#<link rel="canonical" href="([^"]+)"#', $res['body'], $m) === 1 ? rawurldecode($m[1]) : '';
    $key   = ($hops === [] ? '' : implode('→', $hops) . '→') . $res['status'];
    $counts[$key] = ($counts[$key] ?? 0) + 1;
    $path = rawurldecode((string) ($p['path'] ?? '/'));
    if ($res['status'] === 404 || $res['status'] >= 500) {
        $fail('#' . ($i + 1) . " {$res['status']} {$path}");
    }
    if (count($hops) > 1) {
        $fail('#' . ($i + 1) . ' redirect chain ' . implode('→', $hops) . " {$path}");
    }
    if (array_intersect($hops, [302, 307]) !== []) {
        $fail('#' . ($i + 1) . " temporary redirect {$path}");
    }
    if ($res['status'] === 410 && !in_array($path, ['/wp-login.php', '/xmlrpc.php'], true)) {
        $fail('#' . ($i + 1) . " unexpected 410 {$path}");
    }
    if ($res['status'] === 200 && $canon !== '' && !str_starts_with($canon, 'https://' . $prodHost . '/')) {
        $fail('#' . ($i + 1) . " canonical not on the production host: {$canon}");
    }
    $rows[] = '| ' . ($i + 1) . ' | `' . str_replace('|', '\\|', rawurldecode($u)) . '` | ' . $key . ' | ' . ($canon === '' ? '—' : '`' . $canon . '`') . ' |';
}
ksort($counts);

// ------------------------------------------------------------------ 2. environment rules
$home   = $get($base . '/');
$robots = $get($base . '/robots.txt');
$xrt    = strtolower($home['headers']['x-robots-tag'] ?? '');
if ($staging) {
    str_contains($xrt, 'noindex') || $fail('staging: X-Robots-Tag noindex missing on /');
    str_contains($robots['body'], 'Disallow: /') || $fail('staging: robots.txt must disallow everything');
} else {
    str_contains($xrt, 'noindex') && $fail('production: X-Robots-Tag noindex on / — the site would drop out of Google');
    preg_match('#<meta name="robots" content="[^"]*noindex#', $home['body']) === 1 && $fail('production: <meta robots noindex> on /');
    (str_contains($robots['body'], 'Allow: /') && str_contains($robots['body'], 'Sitemap: https://' . $prodHost . '/sitemap.xml'))
        || $fail('production: robots.txt must Allow and list https://' . $prodHost . '/sitemap.xml');
    str_starts_with($base, 'https://') || $fail('production: base URL is not https');
    isset($home['headers']['strict-transport-security']) || $fail('production: Strict-Transport-Security header missing');
    foreach (['http://' . $prodHost, 'http://www.' . $prodHost, 'https://www.' . $prodHost] as $variant) {
        $probe = '/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/?utm_source=postcheck';
        $r     = $get($variant . $probe, false);
        $loc   = rawurldecode($r['headers']['location'] ?? '');
        ($r['status'] === 301 && $loc === 'https://' . $prodHost . rawurldecode($probe))
            || $fail("production: {$variant} must 301 once to https://{$prodHost} keeping path + query (got {$r['status']} {$loc})");
        if ($r['status'] === 301) {
            $next = $get($loc, false);
            $next['status'] === 200 || $fail("production: {$variant} target answers {$next['status']} (chain?)");
        }
    }
}

// ------------------------------------------------------------------ 3. sitemaps · health · secrets
$index = $get($base . '/sitemap.xml');
$index['status'] === 200 || $fail('sitemap.xml ' . $index['status']);
preg_match_all('#<loc>([^<]+)</loc>#', $index['body'], $sm);
foreach ($sm[1] as $loc) {
    $path = (string) parse_url(html_entity_decode($loc), PHP_URL_PATH);
    $r    = $get($base . $path, false);
    $r['status'] === 200 || $fail("sitemap {$path} → {$r['status']}");
}
$get($base . '/_health', false)['status'] === 200 || $fail('/_health is not 200');
foreach (['/.env', '/.git/config', '/app/Services/LeadService.php', '/config/config.php', '/database/migrations/049_lead_notify_status.sql',
          '/storage/logs/', '/storage/backups/', '/composer.json', '/tools/launch/postcheck.php', '/project/STATE.md'] as $secret) {
    $s = $get($base . $secret, false)['status'];
    in_array($s, [401, 403, 404, 410], true) || $fail("{$secret} is reachable ({$s})");
}

// ------------------------------------------------------------------ PageSpeed Insights (optional)
$psiRows = [];
if ($psi) {
    $article = '/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/';
    foreach (['/', '/services/', '/blog/', $article] as $page) {
        foreach (['mobile', 'desktop'] as $strategy) {
            $api  = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?category=performance&strategy=' . $strategy . '&url=' . rawurlencode($enc($base . $page))
                . ((string) getenv('PSI_KEY') !== '' ? '&key=' . rawurlencode((string) getenv('PSI_KEY')) : '');
            $json = json_decode($get($api, true, 150)['body'], true);
            $lh   = $json['lighthouseResult'] ?? null;
            if (!is_array($lh)) {
                $psiRows[] = "| `{$page}` | {$strategy} | error: " . str_replace('|', '/', mb_substr((string) ($json['error']['message'] ?? 'no result'), 0, 80)) . ' | | | | |';
                continue;
            }
            $a     = $lh['audits'];
            $field = $json['loadingExperience']['metrics'] ?? [];
            $fv    = static fn (string $k, int $div = 1): string => isset($field[$k]['percentile']) ? (string) round($field[$k]['percentile'] / $div, $div === 1 ? 0 : 2) : '—';
            $psiRows[] = sprintf('| `%s` | %s | %d | %s | %s | %s | LCP %s ms · INP %s ms · CLS %s |', rawurldecode($page), $strategy,
                (int) round(($lh['categories']['performance']['score'] ?? 0) * 100), $a['largest-contentful-paint']['displayValue'] ?? '',
                $a['cumulative-layout-shift']['displayValue'] ?? '', $a['total-blocking-time']['displayValue'] ?? '',
                $fv('LARGEST_CONTENTFUL_PAINT_MS'), $fv('INTERACTION_TO_NEXT_PAINT'), $fv('CUMULATIVE_LAYOUT_SHIFT_SCORE', 100));
        }
    }
}

// ------------------------------------------------------------------ report
$host = (string) $parts['host'];
$md   = ['# Post-deploy check — ' . $base, '', 'Run ' . date('Y-m-d H:i') . ' (Asia/Bangkok) · mode: ' . ($staging ? 'staging' : 'production') . ' · `php tools/launch/postcheck.php`', ''];
$md[] = $fails === [] ? '**PASS — no problems.**' : '**FAIL — ' . count($fails) . ' problem(s):**';
foreach ($fails as $f) {
    $md[] = '- ' . $f;
}
$md[] = '';
$md[] = '## 95 baseline URLs';
$md[] = '';
$md[] = '| HTTP | URLs |';
$md[] = '|---|---|';
foreach ($counts as $k => $v) {
    $md[] = "| {$k} | {$v} |";
}
$md[] = '';
if ($psiRows !== []) {
    $md[] = '## PageSpeed Insights (Google servers)';
    $md[] = '';
    $md[] = '| Page | Form | Perf | LCP (lab) | CLS (lab) | TBT (lab) | Field data p75 (CrUX) |';
    $md[] = '|---|---|---|---|---|---|---|';
    array_push($md, ...$psiRows);
    $md[] = '';
    $md[] = 'Field data is "—" until Google has 28 days of real visits for the URL/origin.';
    $md[] = '';
}
$md[] = '<details><summary>All URLs</summary>';
$md[] = '';
$md[] = '| # | Old URL | HTTP | Canonical |';
$md[] = '|---|---|---|---|';
array_push($md, ...$rows);
$md[] = '';
$md[] = '</details>';

$file = $root . '/docs/seo/postcheck-' . preg_replace('/[^a-z0-9.-]/i', '-', $host) . '-' . date('Ymd-Hi') . '.md';
file_put_contents($file, implode("\n", $md) . "\n");

foreach ($counts as $k => $v) {
    echo str_pad((string) $k, 14) . $v . "\n";
}
foreach ($psiRows as $r) {
    echo $r . "\n";
}
echo ($fails === [] ? 'PASS' : 'FAIL (' . count($fails) . ")\n  " . implode("\n  ", $fails)) . "\nwrote " . substr($file, strlen($root) + 1) . "\n";
exit($fails === [] ? 0 : 1);
