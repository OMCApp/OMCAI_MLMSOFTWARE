<?php
/**
 * Phase 6 — SEO regression: every baseline URL (95) old → new, judged as production would serve it.
 *
 * Usage (from the project root, against a NON-production database that has run the migrations):
 *   php tools/seo-audit/regression.php
 *
 * Writes docs/seo/regression.md:
 *   Old URL → final URL → HTTP (+ hops) → canonical → indexable → title / description / H1 / hreflang / schema,
 *   compared with docs/seo/baseline-2026-11-03/pages.csv, plus sitemap and hreflang-reciprocity cross-checks.
 *
 * Indexability = page-level signals only (meta robots + status + canonical). The blanket
 * `X-Robots-Tag: noindex` that Core\Staging adds to every non-production response is ignored on
 * purpose — production never sends it (tests/run.php "production" section proves that).
 *
 * Exit 1 when a blocking problem is found (404, redirect chain/loop, canonical not self on an
 * indexable page, lost keyword, indexable page missing from the sitemap, non-reciprocal hreflang).
 *
 * ‼️ Reads the DB settings through config/config.php and never prints them.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__, 2);

require $root . '/core/autoload.php';
require $root . '/core/helpers.php';

/** @var array<string, mixed> $config */
$config = require $root . '/config/config.php';

if ($config['env'] === 'production') {
    fwrite(STDERR, "APP_ENV=production — run this against a local/staging database only\n");
    exit(1);
}

const HOST = 'https://mlmsoftware.omc.co.th';

// ------------------------------------------------------------------ baseline

$urls = array_values(array_filter(array_map('trim', file($root . '/docs/seo/baseline-2026-11-03/url-list.txt') ?: [])));

$baseline = [];
$fh = fopen($root . '/docs/seo/baseline-2026-11-03/pages.csv', 'rb');
$head = fgetcsv($fh, null, ',', '"', '');
$head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]); // UTF-8 BOM
while (($row = fgetcsv($fh, null, ',', '"', '')) !== false) {
    $row = array_combine($head, $row);
    $baseline[$row['url']] = $row;
}
fclose($fh);

// ------------------------------------------------------------------ server

$port   = random_int(20000, 40000);
$base   = "http://127.0.0.1:{$port}";
$log    = $root . '/storage/logs/seo-regression-server.log';
$server = proc_open(
    [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', "127.0.0.1:{$port}", 'server.php'],
    [0 => ['file', 'php://stdin', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
    $pipes,
    $root
);

register_shutdown_function(static function () use ($server): void {
    if (is_resource($server)) {
        proc_terminate($server);
    }
});

/** encode only bytes that are not already URL-safe (raw Thai → %XX, existing %xx kept) */
$safe = static fn (string $target): string => (string) preg_replace_callback(
    '/[^\x21-\x7E]/',
    static fn (array $m): string => rawurlencode($m[0]),
    $target
);

$request = static function (string $target) use ($base, $config, $safe): array {
    $ch      = curl_init($base . $safe($target));
    $headers = [];
    $opts    = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        },
    ];
    if ((string) $config['staging']['pass'] !== '') {
        $opts[CURLOPT_USERPWD] = $config['staging']['user'] . ':' . $config['staging']['pass'];
    }
    curl_setopt_array($ch, $opts);
    $body   = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    return ['status' => $status, 'location' => $headers['location'] ?? '', 'body' => $body, 'type' => strtolower($headers['content-type'] ?? '')];
};

for ($i = 0; $i < 50; $i++) {
    usleep(100000);
    if ($request('/_health')['status'] === 200) {
        break;
    }
}

// ------------------------------------------------------------------ helpers

/** path (+query) of an absolute or root-relative URL, decoded */
$pathOf = static function (string $url): string {
    $p = parse_url($url);

    return rawurldecode(($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : ''));
};

/** @return array{title:string,description:string,h1:list<string>,canonical:string,robots:string,hreflang:array<string,string>,schema:list<string>,lang:string,text:string} */
$parse = static function (string $html): array {
    $out = ['title' => '', 'description' => '', 'h1' => [], 'canonical' => '', 'robots' => '', 'hreflang' => [], 'schema' => [], 'lang' => '', 'text' => ''];
    if ($html === '') {
        return $out;
    }
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    $out['title']       = trim((string) ($xp->query('//title')->item(0)?->textContent ?? ''));
    $out['description'] = trim((string) ($xp->query('//meta[@name="description"]/@content')->item(0)?->nodeValue ?? ''));
    $out['canonical']   = trim((string) ($xp->query('//link[@rel="canonical"]/@href')->item(0)?->nodeValue ?? ''));
    $out['robots']      = strtolower(trim((string) ($xp->query('//meta[@name="robots"]/@content')->item(0)?->nodeValue ?? '')));
    $out['lang']        = (string) ($xp->query('//html/@lang')->item(0)?->nodeValue ?? '');
    foreach ($xp->query('//h1') as $h1) {
        $out['h1'][] = trim(preg_replace('/\s+/u', ' ', $h1->textContent));
    }
    foreach ($xp->query('//link[@rel="alternate"][@hreflang]') as $link) {
        $out['hreflang'][(string) $link->getAttribute('hreflang')] = (string) $link->getAttribute('href');
    }
    foreach ($xp->query('//script[@type="application/ld+json"]') as $script) {
        $data  = json_decode($script->textContent, true);
        $stack = is_array($data) ? [$data] : [];
        while ($stack !== []) {
            $node = array_pop($stack);
            if (isset($node['@type'])) {
                foreach ((array) $node['@type'] as $type) {
                    $out['schema'][] = (string) $type;
                }
            }
            foreach ($node as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }
    }
    $out['schema'] = array_values(array_unique($out['schema']));
    $main          = $xp->query('//main')->item(0) ?? $xp->query('//body')->item(0);
    $out['text']   = $main !== null ? preg_replace('/\s+/u', ' ', $main->textContent) : '';

    return $out;
};

/** compare text ignoring case, whitespace and punctuation */
$norm = static fn (string $s): string => mb_strtolower((string) preg_replace('/[\s\p{P}]+/u', '', html_entity_decode($s, ENT_QUOTES, 'UTF-8')));

/* misspellings of the old site that the new content corrects — the corrected form counts as the same keyword */
const SPELLING_FIXES = ['อนุญาติ' => 'อนุญาต'];
const GENERIC_NAMES  = ['home', 'mlm software'];

/**
 * keywords the old page ranked for: its H1, else the name part of its <title>, plus the first three
 * items of a WordPress keyword list ("Home - MLM SOFTWARE - โปรแกรมขายตรง,ระบบขายตรง,…")
 *
 * @return list<string>
 */
$keywordsOf = static function (array $old, string $path): array {
    $title = trim((string) ($old['title'] ?? ''));
    $parts = array_map('trim', preg_split('/\s[-–|]\s/u', $title) ?: []);
    $out   = [];
    $h1    = trim((string) ($old['h1'] ?? ''));
    $name  = $h1 !== '' ? explode(' | ', $h1)[0] : ($parts[0] ?? '');
    if ($name !== '' && !in_array(mb_strtolower($name), GENERIC_NAMES, true)) {
        $out[] = $name;
    }
    // the comma list was a site-wide WordPress suffix on every title — it is the home page's keyword set only
    $last = (string) end($parts);
    if ($path === '/' && str_contains($last, ',')) {
        $out = array_merge($out, array_slice(array_filter(array_map('trim', explode(',', $last))), 0, 3));
    }

    return array_values(array_map(static fn (string $k): string => strtr($k, SPELLING_FIXES), $out));
};

// ------------------------------------------------------------------ sitemaps

$sitemapLocs = [];
$index       = $request('/sitemap.xml')['body'];
preg_match_all('#<loc>([^<]+)</loc>#', $index, $m);
foreach ($m[1] as $sitemapUrl) {
    $body = $request($pathOf(html_entity_decode($sitemapUrl)))['body'];
    preg_match_all('#<loc>([^<]+)</loc>#', $body, $locs);
    foreach ($locs[1] as $loc) {
        $sitemapLocs[rawurldecode(html_entity_decode($loc))] = true;
    }
}

// ------------------------------------------------------------------ crawl

$rows     = [];
$problems = [];
$pages    = []; // final canonical URL → parsed page (for hreflang reciprocity)

foreach ($urls as $n => $url) {
    $parts  = parse_url($url);
    $target = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $old    = $baseline[$url] ?? [];
    $notes  = [];

    if (($parts['host'] ?? '') !== 'mlmsoftware.omc.co.th' || ($parts['scheme'] ?? '') !== 'https') {
        $notes[] = 'host/scheme variant — server-level 301 (production checklist)';
    }

    $chain   = [];
    $seen    = [$target => true];
    $current = $target;
    $res     = $request($current);
    $loop    = false;
    for ($hop = 0; $hop < 6 && in_array($res['status'], [301, 302, 307, 308], true); $hop++) {
        $chain[] = $res['status'];
        $current = $pathOf($res['location']) === '' ? '/' : $res['location'];
        $key     = $pathOf($current);
        if (isset($seen[$key])) {
            $loop = true;
            break;
        }
        $seen[$key] = true;
        $res        = $request($current);
    }

    $final     = HOST . $pathOf($current);
    $isHtml    = str_starts_with($res['type'], 'text/html');
    $page      = $res['status'] === 200 && $isHtml ? $parse($res['body']) : $parse('');
    $canonical = rawurldecode($page['canonical']);
    $noindex   = str_contains($page['robots'], 'noindex');
    $selfCanon = $canonical === $final;
    $indexable = $res['status'] === 200 && $isHtml && !$noindex && ($selfCanon || $canonical === '');

    // RSS/Atom (/feed/): not a page — only status and redirects apply
    if ($res['status'] === 200 && !$isHtml) {
        $rows[] = [
            'old' => rawurldecode($url), 'oldStatus' => (string) ($old['status'] ?? '—'), 'final' => $final,
            'http' => ($chain === [] ? '' : implode('→', $chain) . '→') . '200', 'canonical' => '—',
            'index' => 'feed (' . strtok($res['type'], ';') . ')', 'title' => '', 'titleOld' => (string) ($old['title'] ?? ''),
            'desc' => 0, 'h1' => 0, 'hreflang' => '', 'schema' => '', 'kept' => '', 'note' => 'not an HTML page',
        ];
        if (count($chain) > 1) {
            $problems[] = '#' . ($n + 1) . ' redirect chain (' . count($chain) . ' hops)';
        }
        continue;
    }

    if ($loop) {
        $problems[] = "#{$n} redirect loop";
    }
    if (count($chain) > 1) {
        $problems[] = '#' . ($n + 1) . ' redirect chain (' . count($chain) . ' hops)';
        $notes[]    = '**redirect chain**';
    }
    if (in_array(302, $chain, true) || in_array(307, $chain, true)) {
        $problems[] = '#' . ($n + 1) . ' temporary redirect';
    }
    if ($res['status'] === 404) {
        $problems[] = '#' . ($n + 1) . ' 404';
        $notes[]    = '**404**';
    }
    if ($res['status'] === 200 && !$noindex && !$selfCanon) {
        $notes[] = 'canonical → ' . ($canonical === '' ? '(none)' : $canonical);
        if ($canonical === '') {
            $problems[] = '#' . ($n + 1) . ' no canonical';
        }
    }
    if ($res['status'] === 200 && count($page['h1']) !== 1) {
        $problems[] = '#' . ($n + 1) . ' H1 count ' . count($page['h1']);
    }
    if ($indexable && $page['title'] === '') {
        $problems[] = '#' . ($n + 1) . ' missing title';
    }
    if ($indexable && $page['description'] === '') {
        $notes[] = 'no meta description';
    }
    // paginated archives (/blog/page/2/) stay indexable + self-canonical but are not sitemap entries
    if ($indexable && !isset($sitemapLocs[$final]) && preg_match('#/page/\d+/$#', $final) !== 1) {
        $problems[] = '#' . ($n + 1) . ' indexable but not in sitemap';
        $notes[]    = '**not in sitemap**';
    }
    if (!$indexable && isset($sitemapLocs[$final])) {
        $problems[] = '#' . ($n + 1) . ' non-indexable URL listed in sitemap';
    }

    /* keyword retention — only where the old URL itself still serves a page (same path, no redirect):
       a redirect is a deliberate consolidation (theme demo pages, author archives, duplicates) and is
       listed as "consolidated" with the old title for review instead */
    $kept = '';
    if (($old['status'] ?? '') === '200' && $res['status'] === 200 && $chain === [] && !$noindex && $selfCanon) {
        $keywords = $keywordsOf($old, $pathOf($current));
        $haystack = $norm($page['title'] . ' ' . implode(' ', $page['h1']) . ' ' . $page['text']);
        $missing  = array_values(array_filter($keywords, static fn (string $k): bool => !str_contains($haystack, $norm($k))));
        $kept     = $keywords === [] ? 'n/a' : ($missing === [] ? 'yes' : 'NO');
        if ($missing !== []) {
            $notes[]    = '**keyword not found: ' . implode(', ', $missing) . '**';
            $problems[] = '#' . ($n + 1) . ' keyword lost: ' . implode(', ', $missing);
        }
    } elseif ($chain !== [] && ($old['status'] ?? '') === '200') {
        $kept    = 'consolidated';
        $notes[] = 'old title: ' . mb_strimwidth((string) ($old['title'] ?? ''), 0, 70, '…');
    } elseif ($noindex || ($res['status'] === 200 && !$selfCanon)) {
        $kept = 'n/a';
    }

    if ($indexable) {
        $pages[$final] = $page;
    }

    $rows[] = [
        'old'       => rawurldecode($url),
        'oldStatus' => (string) ($old['status'] ?? '—'),
        'final'     => $final,
        'http'      => ($chain === [] ? '' : implode('→', $chain) . '→') . $res['status'],
        'canonical' => $res['status'] !== 200 ? '—' : ($selfCanon ? 'self' : ($canonical === '' ? '(none)' : $canonical)),
        'index'     => $res['status'] === 410 ? 'gone (410)' : ($indexable ? 'index' : ($noindex ? 'noindex' : ($res['status'] === 200 ? 'canonicalised' : '—'))),
        'title'     => $page['title'],
        'titleOld'  => (string) ($old['title'] ?? ''),
        'desc'      => mb_strlen($page['description']),
        'h1'        => count($page['h1']),
        'hreflang'  => implode(' ', array_keys($page['hreflang'])),
        'schema'    => implode(', ', $page['schema']),
        'kept'      => $kept,
        'note'      => implode(' · ', $notes),
    ];
}

// ------------------------------------------------------------------ hreflang reciprocity (all localized home + pages that declare alternates)

$hreflangChecked = 0;
foreach (['/', '/en/', '/zh/', '/features/', '/en/features/', '/zh/features/', '/contact/', '/en/contact/', '/zh/contact/'] as $path) {
    $res = $request($path);
    if ($res['status'] !== 200) {
        continue;
    }
    $page = $parse($res['body']);
    $self = HOST . $path;
    foreach ($page['hreflang'] as $lang => $href) {
        if ($lang === 'x-default') {
            continue;
        }
        $other = $parse($request($pathOf($href))['body']);
        $hreflangChecked++;
        if (!in_array($self, array_map('rawurldecode', $other['hreflang']), true)) {
            $problems[] = "hreflang not reciprocal: {$self} → {$href} ({$lang})";
        }
    }
}

// ------------------------------------------------------------------ every sitemap URL (th / en / zh)

$langStats = [];
foreach (array_keys($sitemapLocs) as $loc) {
    $res  = $request($pathOf($loc));
    $page = $parse($res['body']);
    $lang = $page['lang'] !== '' ? $page['lang'] : '?';
    $langStats[$lang] = ($langStats[$lang] ?? 0) + 1;
    $issues = [];
    if ($res['status'] !== 200) {
        $issues[] = 'HTTP ' . $res['status'];
    }
    if (count($page['h1']) !== 1) {
        $issues[] = count($page['h1']) . ' H1';
    }
    if ($page['title'] === '') {
        $issues[] = 'no title';
    }
    if ($page['description'] === '') {
        $issues[] = 'no description';
    }
    if (rawurldecode($page['canonical']) !== $loc) {
        $issues[] = 'canonical ' . ($page['canonical'] === '' ? '(none)' : $page['canonical']);
    }
    if (str_contains($page['robots'], 'noindex')) {
        $issues[] = 'noindex';
    }
    if (preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $res['body'], $ld) > 0) {
        foreach ($ld[1] as $json) {
            if (json_decode($json) === null) {
                $issues[] = 'invalid JSON-LD';
            }
        }
    }
    if ($issues !== []) {
        $problems[] = 'sitemap URL ' . $loc . ': ' . implode(', ', $issues);
    }
}
ksort($langStats);

// ------------------------------------------------------------------ report

$counts = [];
foreach ($rows as $r) {
    $key          = $r['http'] . ' · ' . $r['index'];
    $counts[$key] = ($counts[$key] ?? 0) + 1;
}
ksort($counts);

$cell = static fn (string $s): string => str_replace(['|', "\n"], ['\\|', ' '], $s);
$md   = [];
$md[] = '# SEO regression — 95 baseline URLs, old → new';
$md[] = '';
$md[] = 'Generated by `tools/seo-audit/regression.php` on ' . date('Y-m-d H:i') . ' (Asia/Bangkok) against a local database with migrations up to '
    . htmlspecialchars((string) (new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['name']),
        (string) $config['db']['user'],
        (string) $config['db']['pass']
    ))->query('SELECT MAX(filename) FROM migrations')->fetchColumn()) . '.';
$md[] = '';
$md[] = '- Requests go through the app (`server.php`); redirects followed inside the app. `www` / `http` variants are a server-level 301 in production (see `project/LAUNCH.md`).';
$md[] = '- **Indexable** = 200 + no `noindex` meta + canonical is self. The blanket `X-Robots-Tag: noindex` of non-production environments is ignored — production never sends it.';
$md[] = '- **Keyword kept** = the old page\'s H1 (or the name part of its WordPress `<title>`) still appears in the new title, H1 or main text.';
$md[] = '- Sitemap URLs found: ' . count($sitemapLocs) . ' (by `<html lang>`: '
    . implode(' · ', array_map(static fn (string $l, int $c): string => "{$l} {$c}", array_keys($langStats), $langStats))
    . ') — each one checked for 200, one H1, title, description, self canonical, no noindex, valid JSON-LD.';
$md[] = '- hreflang pairs checked for reciprocity: ' . $hreflangChecked . '.';
$md[] = '';
$md[] = '## Summary';
$md[] = '';
$md[] = '| HTTP · indexability | URLs |';
$md[] = '|---|---|';
foreach ($counts as $k => $v) {
    $md[] = '| ' . $k . ' | ' . $v . ' |';
}
$md[] = '| **total** | **' . count($rows) . '** |';
$md[] = '';
$md[] = $problems === [] ? '**No blocking problems.**' : '**Blocking problems (' . count($problems) . '):**';
foreach ($problems as $p) {
    $md[] = '- ' . $p;
}
$md[] = '';
$md[] = '## All URLs';
$md[] = '';
$md[] = '| # | Old URL | Old | New URL | HTTP | Canonical | Index | Title | Desc | H1 | hreflang | Schema | Keyword | Note |';
$md[] = '|---|---|---|---|---|---|---|---|---|---|---|---|---|---|';
foreach ($rows as $i => $r) {
    $md[] = '| ' . ($i + 1) . ' | `' . $cell($r['old']) . '` | ' . $r['oldStatus'] . ' | ' . ($r['final'] === $r['old'] ? '(same)' : '`' . $cell($r['final']) . '`')
        . ' | ' . $r['http'] . ' | ' . $cell($r['canonical']) . ' | ' . $r['index'] . ' | ' . $cell($r['title']) . ' | ' . $r['desc']
        . ' | ' . $r['h1'] . ' | ' . $r['hreflang'] . ' | ' . $cell($r['schema']) . ' | ' . $r['kept'] . ' | ' . $cell($r['note']) . ' |';
}
$md[] = '';

file_put_contents($root . '/docs/seo/regression.md', implode("\n", $md));

foreach ($counts as $k => $v) {
    echo str_pad($k, 36) . $v . "\n";
}
echo 'problems: ' . count($problems) . "\n";
foreach ($problems as $p) {
    echo '  ' . $p . "\n";
}
echo 'wrote docs/seo/regression.md (' . count($rows) . " URLs)\n";

exit($problems === [] ? 0 : 1);
