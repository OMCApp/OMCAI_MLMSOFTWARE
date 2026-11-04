<?php
/**
 * Phase 5 — old URL → new status report, computed by requesting the app (not by assumption).
 *
 * Usage (from the project root, against a NON-production database that has run the migrations):
 *   php tools/migration/url-mapping.php
 *
 * Starts the PHP built-in server (server.php) on a random port, requests every URL in
 * docs/seo/baseline-2026-11-03/url-list.txt by its path (+ query), follows redirects inside the app
 * (max 5 hops), and writes docs/migration/url-mapping.md.
 *
 * A 404 whose path (or redirect target) is a page row still in draft is reported as
 * "pending Phase 4" — Phase 4 publishes those pages. Any other 404 is a real gap.
 *
 * ‼️ Reads the DB settings through config/config.php (.env or env vars) and never prints them.
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

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['name']),
    (string) $config['db']['user'],
    (string) $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

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
$log    = $root . '/storage/logs/url-mapping-server.log';
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

/** encode only bytes that are not already URL-safe (raw Thai in the list → %XX, existing %xx kept) */
$safe = static fn (string $target): string => (string) preg_replace_callback(
    '/[^\x21-\x7E]/',
    static fn (array $m): string => rawurlencode($m[0]),
    $target
);

$request = static function (string $target) use ($base, $config, $safe): array {
    $ch = curl_init($base . $safe($target));
    $headers = [];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 20,
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
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    return ['status' => $status, 'location' => $headers['location'] ?? ''];
};

for ($i = 0; $i < 50; $i++) {
    usleep(100000);
    if ($request('/_health')['status'] === 200) {
        break;
    }
}

$draftPage = $pdo->prepare("SELECT status FROM pages WHERE path = :p AND deleted_at IS NULL");

$isDraftPage = static function (string $target) use ($draftPage): bool {
    $path = rawurldecode((string) parse_url($target, PHP_URL_PATH));
    $draftPage->execute(['p' => $path]);

    return $draftPage->fetchColumn() === 'draft';
};

// ------------------------------------------------------------------ crawl

$rows   = [];
$counts = [];

foreach ($urls as $url) {
    $parts  = parse_url($url);
    $host   = (string) ($parts['host'] ?? '');
    $scheme = (string) ($parts['scheme'] ?? '');
    $target = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $note   = [];

    if ($host !== 'mlmsoftware.omc.co.th' || $scheme !== 'https') {
        $note[] = 'host/scheme variant — handled by the server (301 to https://mlmsoftware.omc.co.th/), the app sees only the path';
    }

    $chain   = [];
    $current = $target;
    $res     = $request($current);

    for ($hop = 0; $hop < 5 && in_array($res['status'], [301, 302, 307, 308], true); $hop++) {
        $chain[] = $res['status'] . ' → ' . rawurldecode($res['location']);
        $current = $res['location'];
        $res     = $request($current);
    }

    $first = $chain === [] ? $res['status'] : (int) substr($chain[0], 0, 3);

    if ($res['status'] === 200) {
        $status = $chain === [] ? '200' : '301 → 200';
    } elseif ($res['status'] === 410) {
        $status = $chain === [] ? '410' : '301 → 410';
    } elseif ($res['status'] === 404 && $isDraftPage($current)) {
        $status = $chain === [] ? 'pending Phase 4' : '301 → pending Phase 4';
        $note[] = 'page row exists as draft — 200 once Phase 4 publishes it';
    } else {
        $status = $chain === [] ? (string) $res['status'] : '301 → ' . $res['status'];
        if ($res['status'] === 404) {
            $note[] = '**404 — needs a page or a redirect**';
        }
    }
    if (count($chain) > 1) {
        $note[] = 'redirect chain of ' . count($chain) . ' hops';
    }

    $counts[$status] = ($counts[$status] ?? 0) + 1;
    $old             = $baseline[$url] ?? null;

    $rows[] = [
        'url'    => rawurldecode($url),
        'old'    => $old !== null ? $old['status'] . ((string) $old['final_url'] !== '' && $old['final_url'] !== $url ? ' → ' . rawurldecode($old['final_url']) : '') : '—',
        'status' => $status,
        'chain'  => implode(' · ', $chain),
        'note'   => implode(' · ', $note),
        'first'  => $first,
    ];
}

// ------------------------------------------------------------------ report

ksort($counts);

$md   = [];
$md[] = '# URL mapping — old site → new app';
$md[] = '';
$md[] = 'Generated by `tools/migration/url-mapping.php` on ' . date('Y-m-d H:i') . ' (Asia/Bangkok) against a local database with migrations up to ' . htmlspecialchars((string) $pdo->query('SELECT MAX(filename) FROM migrations')->fetchColumn()) . '.';
$md[] = 'Every URL of `docs/seo/baseline-2026-11-03/url-list.txt` (' . count($urls) . ') was requested through the app (`server.php`), following redirects inside the app.';
$md[] = '';
$md[] = '- **pending Phase 4** — the page row exists but is still a draft (404 for the public until Phase 4 publishes it). Not a migration gap.';
$md[] = '- **404** — a real gap: needs a page or a redirect before launch.';
$md[] = '';
$md[] = '## Summary';
$md[] = '';
$md[] = '| New status | URLs |';
$md[] = '|---|---|';
foreach ($counts as $status => $n) {
    $md[] = '| ' . $status . ' | ' . $n . ' |';
}
$md[] = '| **total** | **' . count($rows) . '** |';
$md[] = '';

$real404 = array_filter($rows, static fn (array $r): bool => str_ends_with($r['status'], '404'));
$md[] = $real404 === []
    ? '**No real 404s.** Every old URL answers 200, 301 to a live page, 410 (WordPress endpoints) or is a Phase 4 page that is still a draft.'
    : '**Real 404s: ' . count($real404) . '** — see rows marked 404 below.';
$md[] = '';
$md[] = '## All URLs';
$md[] = '';
$md[] = '| # | Old URL (decoded) | Old status | New status | Redirects | Note |';
$md[] = '|---|---|---|---|---|---|';

$cell = static fn (string $s): string => str_replace('|', '\\|', $s);
foreach ($rows as $i => $r) {
    $md[] = '| ' . ($i + 1) . ' | `' . $cell($r['url']) . '` | ' . $cell($r['old']) . ' | ' . $cell($r['status']) . ' | '
        . $cell($r['chain']) . ' | ' . $cell($r['note']) . ' |';
}
$md[] = '';

file_put_contents($root . '/docs/migration/url-mapping.md', implode("\n", $md));

foreach ($counts as $status => $n) {
    echo str_pad((string) $status, 26) . $n . "\n";
}
echo 'wrote docs/migration/url-mapping.md (' . count($rows) . " URLs)\n";

exit($real404 === [] ? 0 : 1);
