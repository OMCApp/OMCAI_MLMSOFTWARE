<?php
/**
 * Selects the URLs to migrate from the SEO baseline (pages.csv):
 * every 200 post (/YYYY/MM/DD/slug/) plus the real pages kept in the URL map.
 */

declare(strict_types=1);

const MIGRATION_REAL_PAGES = [
    '/', '/mlm-software-omc/', '/services/', '/ocpb-mlm/', '/ocpb-mlm2/', '/contact/',
    '/blog/', '/portfolio/', '/นโยบายความเป็นส่วนตัวข/', '/นโยบายการใช้คุกกี้-cookies-policy/',
    '/category/ข่าวขายตรง/', '/category/ประกัน/',
];

/**
 * @return list<array{url:string, path_decoded:string, type:string}>
 */
function migration_targets(string $pagesCsv): array
{
    $fh = fopen($pagesCsv, 'r');
    if ($fh === false) {
        throw new RuntimeException("Cannot open $pagesCsv");
    }
    $head = fgetcsv($fh);
    $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);

    $out = [];
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) !== count($head)) {
            continue;
        }
        $r = array_combine($head, $row);
        if ($r['status'] !== '200' || $r['url'] !== $r['final_url']) {
            continue;   // skip redirect sources (/index.php/, /mlm-software/, http://, www.)
        }
        $path = $r['path_decoded'];
        if (str_contains($r['url'], '?')) {
            continue;   // /?s=mlm
        }
        if (preg_match('~^/\d{4}/\d{2}/\d{2}/[^/]+/$~u', $path)) {
            $type = 'post';
        } elseif (in_array($path, MIGRATION_REAL_PAGES, true)) {
            $type = str_starts_with($path, '/category/') ? 'category' : 'page';
        } else {
            continue;
        }
        // The baseline lists categories twice (percent-encoded + raw Thai); keep the encoded one.
        if (isset($out[$path]) && str_contains($out[$path]['url'], '%')) {
            continue;
        }
        $out[$path] = ['url' => $r['url'], 'path_decoded' => $path, 'type' => $type];
    }
    fclose($fh);
    return array_values($out);
}
