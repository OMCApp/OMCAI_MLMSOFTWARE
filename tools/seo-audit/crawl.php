<?php
/**
 * SEO baseline crawler.
 *
 * Reads every URL from a site's sitemap index, then records the SEO-relevant
 * state of each page (status, redirects, title, description, canonical, robots,
 * headings, schema types, images, internal links). Run it against the old site
 * before launch (baseline) and against staging/new site after (comparison).
 *
 * Usage: php crawl.php <base-url> <out-dir> [--extra=urls.txt]
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php crawl.php <base-url> <out-dir> [--extra=urls.txt]\n");
    exit(1);
}

$base   = rtrim($argv[1], '/');
$outDir = rtrim($argv[2], '/\\');
$extra  = null;
foreach (array_slice($argv, 3) as $arg) {
    if (str_starts_with($arg, '--extra=')) {
        $extra = substr($arg, 8);
    }
}
$host = parse_url($base, PHP_URL_HOST);

if (!is_dir($outDir) && !mkdir($outDir, 0777, true)) {
    fwrite(STDERR, "Cannot create $outDir\n");
    exit(1);
}

function fetch(string $url, bool $follow = false): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'OMC-SEO-Audit/1.0',
        CURLOPT_ENCODING       => '',
    ]);
    $raw = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['status' => 0, 'error' => $err, 'headers' => [], 'body' => '', 'location' => null, 'ttfb' => 0];
    }
    $headerText = substr($raw, 0, $info['header_size']);
    $headers = [];
    foreach (preg_split('/\r?\n/', $headerText) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = explode(':', $line, 2);
            $headers[strtolower(trim($k))] = trim($v);
        }
    }
    return [
        'status'   => $info['http_code'],
        'error'    => '',
        'headers'  => $headers,
        'body'     => substr($raw, $info['header_size']),
        'location' => $headers['location'] ?? null,
        'ttfb'     => round($info['starttransfer_time'], 3),
    ];
}

/** Follow redirects manually so every hop is recorded (detects chains and loops). */
function resolve(string $url): array
{
    $hops = [];
    $seen = [];
    $current = $url;
    for ($i = 0; $i < 10; $i++) {
        if (isset($seen[$current])) {
            return ['final' => fetch($current), 'hops' => $hops, 'loop' => true];
        }
        $seen[$current] = true;
        $res = fetch($current);
        if ($res['status'] >= 300 && $res['status'] < 400 && $res['location']) {
            $next = $res['location'];
            if (!preg_match('#^https?://#', $next)) {
                $p = parse_url($current);
                $next = $p['scheme'] . '://' . $p['host'] . (str_starts_with($next, '/') ? '' : '/') . $next;
            }
            $hops[] = $res['status'] . ' ' . $next;
            $current = $next;
            continue;
        }
        return ['final' => $res, 'hops' => $hops, 'loop' => false, 'url' => $current];
    }
    return ['final' => $res, 'hops' => $hops, 'loop' => true, 'url' => $current];
}

function sitemapUrls(string $sitemapUrl, int $depth = 0): array
{
    if ($depth > 3) {
        return [];
    }
    $res = fetch($sitemapUrl);
    if ($res['status'] !== 200) {
        return [];
    }
    $xml = @simplexml_load_string($res['body']);
    if (!$xml) {
        return [];
    }
    $urls = [];
    foreach ($xml->sitemap ?? [] as $sm) {
        $urls = array_merge($urls, sitemapUrls((string) $sm->loc, $depth + 1));
    }
    foreach ($xml->url ?? [] as $u) {
        $urls[] = (string) $u->loc;
    }
    return $urls;
}

function text(?DOMNode $n): string
{
    return $n ? trim(preg_replace('/\s+/u', ' ', $n->textContent)) : '';
}

// ---------------------------------------------------------------------------

$urls = sitemapUrls($base . '/wp-sitemap.xml');
if (!$urls) {
    $urls = sitemapUrls($base . '/sitemap.xml');
}
array_unshift($urls, $base . '/');
if ($extra && is_file($extra)) {
    foreach (file($extra, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line !== '' && $line[0] !== '#') {
            $urls[] = str_starts_with($line, 'http') ? $line : $base . '/' . ltrim($line, '/');
        }
    }
}
$urls = array_values(array_unique($urls));

$pagesCsv  = fopen("$outDir/pages.csv", 'w');
$imagesCsv = fopen("$outDir/images.csv", 'w');
$linksCsv  = fopen("$outDir/internal-links.csv", 'w');
fwrite($pagesCsv, "\xEF\xBB\xBF");
fwrite($imagesCsv, "\xEF\xBB\xBF");
fwrite($linksCsv, "\xEF\xBB\xBF");
fputcsv($pagesCsv, ['url', 'path_decoded', 'status', 'redirect_hops', 'redirect_loop', 'final_url', 'ttfb_s', 'html_bytes',
    'title', 'title_len', 'meta_description', 'desc_len', 'canonical', 'meta_robots', 'x_robots_tag',
    'h1_count', 'h1', 'h2_count', 'word_count', 'og_title', 'og_image', 'schema_types', 'lang',
    'img_count', 'img_missing_alt', 'script_count', 'stylesheet_count', 'internal_links', 'external_links']);
fputcsv($imagesCsv, ['page_url', 'src', 'alt']);
fputcsv($linksCsv, ['page_url', 'href', 'anchor']);

$summary = [];
foreach ($urls as $i => $url) {
    fwrite(STDERR, sprintf("[%d/%d] %s\n", $i + 1, count($urls), rawurldecode($url)));
    $r = resolve($url);
    $res = $r['final'];
    $row = [
        'url' => $url,
        'path_decoded' => rawurldecode(parse_url($url, PHP_URL_PATH) ?? '/'),
        'status' => $res['status'],
        'redirect_hops' => implode(' > ', $r['hops']),
        'redirect_loop' => $r['loop'] ? 'yes' : '',
        'final_url' => $r['url'] ?? '',
        'ttfb_s' => $res['ttfb'],
        'html_bytes' => strlen($res['body']),
    ];

    $doc = new DOMDocument();
    if ($res['body'] !== '' && str_contains($res['headers']['content-type'] ?? 'text/html', 'html')) {
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $res['body']);
        libxml_clear_errors();
    }
    $xp = new DOMXPath($doc);

    $title = text($xp->query('//title')->item(0));
    $desc  = $xp->query('//meta[@name="description"]/@content')->item(0)?->nodeValue ?? '';
    $h1s   = [];
    foreach ($xp->query('//h1') as $h) {
        $h1s[] = text($h);
    }
    $schemaTypes = [];
    foreach ($xp->query('//script[@type="application/ld+json"]') as $s) {
        $json = json_decode($s->textContent, true);
        array_walk_recursive($json, function ($v, $k) use (&$schemaTypes) {
            if ($k === '@type') {
                $schemaTypes[] = $v;
            }
        });
    }
    $mainText = text($xp->query('//main')->item(0) ?? $xp->query('//body')->item(0));

    $imgMissingAlt = 0;
    $imgs = $xp->query('//img');
    foreach ($imgs as $img) {
        $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
        $alt = $img->getAttribute('alt');
        if (trim($alt) === '') {
            $imgMissingAlt++;
        }
        fputcsv($imagesCsv, [$url, $src, $alt]);
    }

    $internal = 0;
    $external = 0;
    foreach ($xp->query('//a[@href]') as $a) {
        $href = $a->getAttribute('href');
        $h = parse_url($href, PHP_URL_HOST);
        if ($h === null || $h === $host) {
            if (!str_starts_with($href, '#') && !str_starts_with($href, 'mailto:') && !str_starts_with($href, 'tel:')) {
                $internal++;
                fputcsv($linksCsv, [$url, rawurldecode($href), text($a)]);
            }
        } else {
            $external++;
        }
    }

    $row += [
        'title' => $title,
        'title_len' => mb_strlen($title),
        'meta_description' => $desc,
        'desc_len' => mb_strlen($desc),
        'canonical' => $xp->query('//link[@rel="canonical"]/@href')->item(0)?->nodeValue ?? '',
        'meta_robots' => $xp->query('//meta[@name="robots"]/@content')->item(0)?->nodeValue ?? '',
        'x_robots_tag' => $res['headers']['x-robots-tag'] ?? '',
        'h1_count' => count($h1s),
        'h1' => implode(' | ', $h1s),
        'h2_count' => $xp->query('//h2')->length,
        'word_count' => count(preg_split('/\s+/u', $mainText, -1, PREG_SPLIT_NO_EMPTY)),
        'og_title' => $xp->query('//meta[@property="og:title"]/@content')->item(0)?->nodeValue ?? '',
        'og_image' => $xp->query('//meta[@property="og:image"]/@content')->item(0)?->nodeValue ?? '',
        'schema_types' => implode(',', array_unique($schemaTypes)),
        'lang' => $xp->query('//html/@lang')->item(0)?->nodeValue ?? '',
        'img_count' => $imgs->length,
        'img_missing_alt' => $imgMissingAlt,
        'script_count' => $xp->query('//script[@src]')->length,
        'stylesheet_count' => $xp->query('//link[@rel="stylesheet"]')->length,
        'internal_links' => $internal,
        'external_links' => $external,
    ];
    fputcsv($pagesCsv, array_values($row));
    $summary[] = $row;
}

fclose($pagesCsv);
fclose($imagesCsv);
fclose($linksCsv);

// Robots and sitemap snapshots
file_put_contents("$outDir/robots.txt", fetch($base . '/robots.txt')['body']);
file_put_contents("$outDir/url-list.txt", implode("\n", $urls) . "\n");

$count = fn(callable $f) => count(array_filter($summary, $f));
$report = [
    'crawled_at' => date('c'),
    'base' => $base,
    'urls' => count($summary),
    'status_200' => $count(fn($r) => $r['status'] === 200 && !$r['redirect_hops']),
    'redirected' => $count(fn($r) => $r['redirect_hops'] !== ''),
    'errors_4xx_5xx' => $count(fn($r) => $r['status'] >= 400 || $r['status'] === 0),
    'redirect_loops' => $count(fn($r) => $r['redirect_loop'] === 'yes'),
    'missing_title' => $count(fn($r) => $r['title'] === ''),
    'missing_description' => $count(fn($r) => $r['meta_description'] === ''),
    'missing_or_multiple_h1' => $count(fn($r) => $r['h1_count'] !== 1),
    'canonical_not_self' => $count(fn($r) => $r['canonical'] !== '' && $r['canonical'] !== ($r['final_url'] ?: $r['url'])),
    'noindex' => $count(fn($r) => str_contains($r['meta_robots'] . $r['x_robots_tag'], 'noindex')),
    'images_missing_alt' => array_sum(array_column($summary, 'img_missing_alt')),
];
file_put_contents("$outDir/summary.json", json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
