<?php
/**
 * Parses one old-site (WordPress / SeoCrawler theme / Rank Math) HTML page into a content record.
 */

declare(strict_types=1);

const SITE_ORIGIN = 'https://mlmsoftware.omc.co.th';

function dom_load(string $html): array
{
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
    libxml_clear_errors();
    return [$doc, new DOMXPath($doc)];
}

function xp_class(string $class): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' $class ')";
}

function xp_first(DOMXPath $xp, string $q, ?DOMNode $ctx = null): string
{
    $n = $xp->query($q, $ctx)->item(0);
    return $n ? trim(preg_replace('/\s+/u', ' ', $n->nodeValue)) : '';
}

/**
 * "รายชื่อผู้ประกอบธุรกิจ ตลาดแบบตรง และ ขายตรง - โปรแกรมขายตรง,ระบบขายตรง,..." → "รายชื่อผู้ประกอบธุรกิจ ตลาดแบบตรง และ ขายตรง"
 * Drops every " - " segment that is a keyword list (3+ commas) and the bare site name.
 */
function clean_title(string $title): string
{
    $parts = explode(' - ', $title);
    $keep = [];
    foreach ($parts as $i => $p) {
        if ($i > 0 && substr_count($p, ',') >= 3) {
            break;   // keyword-stuffed tail starts here
        }
        $keep[] = trim($p);
    }
    if (count($keep) > 1 && strcasecmp(end($keep), 'MLM SOFTWARE') === 0) {
        array_pop($keep);
    }
    return trim(implode(' - ', $keep));
}

function to_root_relative(string $url): string
{
    if (preg_match('~^(?:https?:)?//(?:www\.)?mlmsoftware\.omc\.co\.th(/.*)?$~i', $url, $m)) {
        return ($m[1] ?? '') === '' ? '/' : $m[1];
    }
    return $url;
}

/** Collects JSON-LD nodes (flattening @graph). */
function jsonld_nodes(DOMXPath $xp): array
{
    $nodes = [];
    foreach ($xp->query('//script[@type="application/ld+json"]') as $s) {
        $data = json_decode($s->textContent, true);
        if (!is_array($data)) {
            continue;
        }
        $stack = isset($data['@graph']) ? $data['@graph'] : (array_is_list($data) ? $data : [$data]);
        foreach ($stack as $n) {
            if (is_array($n)) {
                $nodes[] = $n;
            }
        }
    }
    return $nodes;
}

/**
 * @return array<string, mixed>
 */
function parse_page(string $html, array $target): array
{
    [$doc, $xp] = dom_load($html);
    $problems = [];

    $rawTitle = xp_first($xp, '//head/title');
    $h1s = [];
    foreach ($xp->query('//h1') as $h) {
        $h1s[] = trim(preg_replace('/\s+/u', ' ', $h->textContent));
    }

    $ld = jsonld_nodes($xp);
    $ldArticle = null;
    $ldPersons = [];
    foreach ($ld as $n) {
        $types = (array) ($n['@type'] ?? []);
        if (array_intersect($types, ['BlogPosting', 'Article', 'NewsArticle']) && $ldArticle === null) {
            $ldArticle = $n;
        }
        if (in_array('Person', $types, true) && isset($n['name'])) {
            $ldPersons[$n['@id'] ?? $n['name']] = $n['name'];
        }
    }

    $published = xp_first($xp, '//meta[@property="article:published_time"]/@content')
        ?: (string) ($ldArticle['datePublished'] ?? '')
        ?: xp_first($xp, '//time/@datetime');
    $modified = xp_first($xp, '//meta[@property="article:modified_time"]/@content')
        ?: (string) ($ldArticle['dateModified'] ?? '')
        ?: xp_first($xp, '//meta[@property="og:updated_time"]/@content');
    if ($published === '' && $target['type'] === 'post' && preg_match('~^/(\d{4})/(\d{2})/(\d{2})/~', $target['path_decoded'], $m)) {
        $published = "$m[1]-$m[2]-$m[3]";
        $problems[] = 'published date taken from URL';
    }

    // Categories / tags / author (posts)
    $categories = [];
    $tags = [];
    $author = '';
    if ($target['type'] === 'post') {
        foreach ($xp->query('//*[' . xp_class('seocrawler-blog-info-category') . ']//a') as $a) {
            $href = to_root_relative($a->getAttribute('href'));
            $slug = preg_match('~^/category/([^/]+)/~', rawurldecode($href), $m) ? $m[1] : '';
            $categories[$slug] = ['name' => trim($a->textContent), 'slug' => $slug, 'href' => $href];
        }
        if ($categories === []) {
            foreach ($xp->query('//meta[@property="article:section"]/@content') as $c) {
                $categories[$c->nodeValue] = ['name' => $c->nodeValue, 'slug' => '', 'href' => ''];
            }
        }
        foreach ($xp->query('//meta[@property="article:tag"]/@content') as $t) {
            $tags[] = $t->nodeValue;
        }
        foreach ($xp->query('//a[contains(@href, "/tag/")]') as $a) {
            $tags[] = trim($a->textContent);
        }
        $author = xp_first($xp, '//*[' . xp_class('seocrawler-blog-info-author') . ']//*[' . xp_class('fn') . ']');
        if ($author === '' && $ldArticle && isset($ldArticle['author'])) {
            $a = $ldArticle['author'];
            $author = is_array($a) ? (string) ($a['name'] ?? ($ldPersons[$a['@id'] ?? ''] ?? '')) : (string) $a;
        }
        if ($author === '') {
            $author = xp_first($xp, '//meta[@name="author"]/@content');
        }
    }

    // ---- main content root
    $root = null;
    $selector = '';
    if ($target['type'] === 'post') {
        $root = $xp->query('//*[' . xp_class('seocrawler-single-article-content') . ']')->item(0);
        $selector = '.seocrawler-single-article-content';
    } else {
        $root = $xp->query('//*[@id="seocrawler-page-wrapper"]')->item(0);
        $selector = '#seocrawler-page-wrapper (minus title bar, sidebars, blog/portfolio listings, pagination)';
    }
    if (!$root) {
        $problems[] = 'content container not found';
        $root = $xp->query('//body')->item(0);
    }

    // Listings on blog / category / portfolio pages are dynamic — keep them as data, not content.
    $listing = [];
    foreach ($xp->query('.//*[' . xp_class('gdlr-core-blog-item-holder') . ']//*[' . xp_class('gdlr-core-blog-title') . ']//a'
        . ' | .//*[' . xp_class('gdlr-core-portfolio-item') . ']//*[' . xp_class('gdlr-core-portfolio-title') . ']//a', $root) as $a) {
        $listing[] = ['title' => trim($a->textContent), 'href' => to_root_relative($a->getAttribute('href'))];
    }

    $removeQ = [
        './/*[' . xp_class('seocrawler-page-title-wrap') . ']',
        './/*[' . xp_class('seocrawler-blog-title-wrap') . ']',
        './/*[' . xp_class('seocrawler-sidebar-left') . ']',
        './/*[' . xp_class('seocrawler-sidebar-right') . ']',
        './/*[' . xp_class('gdlr-core-blog-item') . ']',
        './/*[' . xp_class('gdlr-core-portfolio-item') . ']',
        './/*[' . xp_class('seocrawler-pagination') . ']',
        './/*[' . xp_class('gdlr-core-pagination') . ']',
        './/*[' . xp_class('seocrawler-single-social-share') . ']',
        './/*[' . xp_class('seocrawler-single-nav-area') . ']',
        './/*[' . xp_class('seocrawler-single-related-post-wrap') . ']',
        './/*[@id="comments"]',
        './/*[' . xp_class('cmplz-cookiebanner') . ']',
    ];
    // Work on a clone so the full document stays intact for contact scanning.
    $clone = $root->cloneNode(true);
    $holder = $doc->createElement('div');
    $holder->appendChild($clone);
    foreach ($removeQ as $q) {
        foreach (iterator_to_array($xp->query($q, $holder)) as $n) {
            $n->parentNode?->removeChild($n);
        }
    }

    // Background images inside the content (page-builder sections, slider backgrounds)
    $backgrounds = [];
    foreach ($xp->query('.//*[@style or @data-bg or @data-lazyload]', $holder) as $el) {
        if (preg_match_all('~url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)~', $el->getAttribute('style'), $mm)) {
            foreach ($mm[1] as $u) {
                $backgrounds[] = to_root_relative(html_entity_decode($u));
            }
        }
    }

    $san = new ContentSanitizer();
    $contentHtml = $san->sanitize($clone);
    if ($target['type'] !== 'post') {
        $contentHtml = san_dedupe_blocks($contentHtml);
    }

    // outline + text metrics from the clean HTML
    [$cdoc, $cxp] = dom_load('<div id="c">' . $contentHtml . '</div>');
    $outline = [];
    foreach ($cxp->query('//*[@id="c"]//*[self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $h) {
        $outline[] = ['level' => (int) substr($h->tagName, 1), 'text' => trim($h->textContent)];
    }
    $text = trim(preg_replace('/\s+/u', ' ', $cxp->query('//*[@id="c"]')->item(0)?->textContent ?? ''));
    $wordCount = $text === '' ? 0 : count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    $charCount = mb_strlen(preg_replace('/\s+/u', '', $text));
    $contentH1Demoted = $xp->query('.//h1', $holder)->length;

    // images actually kept in the clean HTML
    $images = [];
    foreach ($cxp->query('//img') as $img) {
        $images[] = ['src' => $img->getAttribute('src'), 'alt' => $img->getAttribute('alt'), 'role' => 'content'];
    }

    $ogImage = to_root_relative(xp_first($xp, '//meta[@property="og:image"]/@content'));
    $headerBg = '';
    $bgEl = $xp->query('//*[' . xp_class('seocrawler-blog-title-wrap') . ']/@style')->item(0);
    if ($bgEl && preg_match('~url\(\s*[\'"]?([^\'")]+)~', $bgEl->nodeValue, $m)) {
        $headerBg = to_root_relative(trim($m[1]));
    }

    if ($contentHtml === '') {
        $problems[] = 'empty content';
    }
    if (count($h1s) === 0) {
        $problems[] = 'no H1 on live page';
    } elseif (count($h1s) > 1) {
        $problems[] = count($h1s) . ' H1s on live page';
    }

    $rawDesc = xp_first($xp, '//meta[@name="description"]/@content');
    $headline = (string) ($ldArticle['headline'] ?? '');

    return [
        'url' => $target['url'],
        'path_decoded' => $target['path_decoded'],
        'type' => $target['type'],
        'title_raw' => $rawTitle,
        'title_clean' => clean_title($rawTitle),
        'og_title' => xp_first($xp, '//meta[@property="og:title"]/@content'),
        'jsonld_headline' => $headline,
        'meta_description' => $rawDesc,
        'canonical' => xp_first($xp, '//link[@rel="canonical"]/@href'),
        'meta_robots' => xp_first($xp, '//meta[@name="robots"]/@content'),
        'og_image' => $ogImage,
        'h1' => $h1s,
        'published_at' => $published,
        'modified_at' => $modified,
        'categories' => array_values($categories),
        'tags' => array_values(array_unique(array_filter($tags))),
        'author' => $author,
        'content_selector' => $selector,
        'content_html' => $contentHtml,
        'content_h1_demoted' => $contentH1Demoted,
        'featured_image' => $ogImage !== '' ? $ogImage : $headerBg,
        'header_background' => $headerBg,
        'images' => $images,
        'background_images' => array_values(array_unique($backgrounds)),
        'word_count' => $wordCount,
        'char_count' => $charCount,
        'outline' => $outline,
        'internal_links' => $san->internalLinks,
        'external_links' => array_values(array_unique($san->externalLinks)),
        'listing' => $listing,
        'problems' => $problems,
        '_doc' => [$doc, $xp],
    ];
}
