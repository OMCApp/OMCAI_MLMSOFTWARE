<?php
/**
 * Converts theme/page-builder HTML into clean, semantic content HTML.
 *
 * Kept: h2–h6, p, ul/ol/li, strong/em, a[href], img[src alt width height], br,
 *       figure/figcaption, table (thead/tbody/tfoot/tr/th/td), blockquote.
 * H1 inside content becomes H2. Everything else is unwrapped (layout wrappers)
 * or dropped (scripts, styles, forms, embeds). Loose inline runs between blocks
 * are wrapped in <p>. Classes, ids and inline styles never survive.
 */

declare(strict_types=1);

const SAN_SITE_HOSTS = ['mlmsoftware.omc.co.th', 'www.mlmsoftware.omc.co.th'];

const SAN_DROP = [
    'script', 'style', 'noscript', 'svg', 'form', 'button', 'input', 'select', 'textarea', 'label',
    'iframe', 'object', 'embed', 'video', 'audio', 'canvas', 'nav', 'link', 'meta', 'template', 'source', 'picture-source',
];
const SAN_BLOCK_KEEP = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'figure', 'figcaption', 'table',
    'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'blockquote'];
const SAN_INLINE_KEEP = ['strong', 'b', 'em', 'i', 'a', 'img', 'br'];
const SAN_BLOCK_UNWRAP = ['div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'center', 'dl', 'dd', 'dt',
    'address', 'hr', 'pre', 'caption', 'picture'];

final class ContentSanitizer
{
    private DOMDocument $out;
    /** @var list<array{src:string, alt:string, width:?int, height:?int}> */
    public array $images = [];
    /** @var list<array{href:string, href_decoded:string, text:string}> */
    public array $internalLinks = [];
    /** @var list<string> */
    public array $externalLinks = [];

    public function __construct()
    {
        $this->out = new DOMDocument('1.0', 'UTF-8');
    }

    public function sanitize(DOMNode $root): string
    {
        $wrap = $this->out->createElement('div');
        $this->out->appendChild($wrap);
        $this->appendBlockChildren($wrap, $this->convertChildren($root));
        $this->cleanup($wrap);

        $html = '';
        foreach ($wrap->childNodes as $c) {
            $html .= $this->out->saveHTML($c) . "\n";
        }
        $this->out->removeChild($wrap);
        return trim($html);
    }

    /** @return list<DOMNode|string> nodes in the output doc, or the string "BREAK" */
    private function convertChildren(DOMNode $node): array
    {
        $items = [];
        foreach ($node->childNodes as $c) {
            array_push($items, ...$this->convert($c));
        }
        return $items;
    }

    /** @return list<DOMNode|string> */
    private function convert(DOMNode $n): array
    {
        if ($n instanceof DOMText) {
            $t = self::stripShortcodes($n->data);
            return $t === '' ? [] : [$this->out->createTextNode($t)];
        }
        if (!$n instanceof DOMElement) {
            return [];
        }
        $tag = strtolower($n->tagName);
        $class = ' ' . $n->getAttribute('class') . ' ';

        if (in_array($tag, SAN_DROP, true) || $n->getAttribute('aria-hidden') === 'true' && $tag !== 'img') {
            return [];
        }
        // Back face of a flip box repeats the front face.
        if (str_contains($class, ' gdlr-core-flipbox-back ') || str_contains($class, ' screen-reader-text ')
            || str_contains($class, ' sharedaddy ') || str_contains($class, ' seocrawler-single-social-share ')) {
            return [];
        }

        // Animated counters render "0" in HTML; the real figure is in data-counter-end.
        if ($n->hasAttribute('data-counter-end')) {
            return [$this->out->createTextNode($n->getAttribute('data-counter-end'))];
        }
        if ($tag === 'img') {
            $img = $this->image($n);
            return $img ? [$img] : [];
        }
        if ($tag === 'br') {
            return [$this->out->createElement('br')];
        }
        if ($tag === 'hr') {
            return ['BREAK'];
        }

        if (in_array($tag, ['strong', 'b', 'em', 'i'], true)) {
            $el = $this->out->createElement(in_array($tag, ['strong', 'b'], true) ? 'strong' : 'em');
            $this->appendInline($el, $this->convertChildren($n));
            return [$el];
        }
        if ($tag === 'a') {
            $href = $this->rewriteHref(trim($n->getAttribute('href')));
            $children = $this->convertChildren($n);
            if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
                return $children;
            }
            $el = $this->out->createElement('a');
            $el->setAttribute('href', $href);
            $this->appendInline($el, $children);
            $text = trim(preg_replace('/\s+/u', ' ', $el->textContent));
            if (str_starts_with($href, '/')) {
                $this->internalLinks[] = ['href' => $href, 'href_decoded' => rawurldecode($href), 'text' => $text];
            } elseif (preg_match('~^https?://~i', $href)) {
                $this->externalLinks[] = $href;
            }
            return [$el];
        }

        if (in_array($tag, SAN_BLOCK_KEEP, true)) {
            $outTag = $tag === 'h1' ? 'h2' : $tag;
            $el = $this->out->createElement($outTag);
            $children = $this->convertChildren($n);
            if (in_array($tag, ['ul', 'ol', 'table', 'thead', 'tbody', 'tfoot', 'tr'], true)) {
                foreach ($children as $c) {
                    if ($c instanceof DOMElement) {
                        $el->appendChild($c);
                    }
                }
            } elseif (in_array($tag, ['li', 'td', 'th', 'blockquote', 'figure'], true)) {
                $this->appendMixed($el, $children);
            } else {
                $this->appendInline($el, $children);
            }
            if ($tag === 'th' || $tag === 'td') {
                foreach (['colspan', 'rowspan'] as $a) {
                    if ($n->hasAttribute($a)) {
                        $el->setAttribute($a, $n->getAttribute($a));
                    }
                }
            }
            return [$el];
        }

        $children = $this->convertChildren($n);
        $isBlock = in_array($tag, SAN_BLOCK_UNWRAP, true) || str_starts_with($tag, 'rs-');
        return $isBlock ? ['BREAK', ...$children, 'BREAK'] : $children;
    }

    private function image(DOMElement $n): ?DOMElement
    {
        $src = '';
        foreach (['data-lazyload', 'data-lazy-src', 'data-src', 'src'] as $a) {
            $v = trim($n->getAttribute($a));
            if ($v !== '' && !str_starts_with($v, 'data:') && !str_contains($v, 'dummy.png')) {
                $src = $v;
                break;
            }
        }
        if ($src === '') {
            return null;
        }
        $src = $this->rewriteHref($src);
        $el = $this->out->createElement('img');
        $el->setAttribute('src', $src);
        $alt = trim(preg_replace('/\s+/u', ' ', $n->getAttribute('alt')));
        $el->setAttribute('alt', $alt);
        $w = ctype_digit($n->getAttribute('width')) ? (int) $n->getAttribute('width') : null;
        $h = ctype_digit($n->getAttribute('height')) ? (int) $n->getAttribute('height') : null;
        if ($w && $h) {
            $el->setAttribute('width', (string) $w);
            $el->setAttribute('height', (string) $h);
        }
        $this->images[] = ['src' => $src, 'alt' => $alt, 'width' => $w, 'height' => $h];
        return $el;
    }

    public function rewriteHref(string $href): string
    {
        $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~^(?:https?:)?//([^/]+)(/.*)?$~i', $href, $m) && in_array(strtolower($m[1]), SAN_SITE_HOSTS, true)) {
            return ($m[2] ?? '') === '' ? '/' : $m[2];
        }
        return $href;
    }

    /** Inline-only container: nested blocks are flattened, breaks become <br>. */
    private function appendInline(DOMElement $el, array $items): void
    {
        foreach ($items as $c) {
            if ($c === 'BREAK') {
                if ($el->lastChild !== null && !($el->lastChild instanceof DOMElement && $el->lastChild->tagName === 'br')) {
                    $el->appendChild($this->out->createElement('br'));
                }
                continue;
            }
            if ($c instanceof DOMElement && in_array($c->tagName, SAN_BLOCK_KEEP, true)) {
                if ($el->hasChildNodes()) {
                    $el->appendChild($this->out->createElement('br'));
                }
                while ($c->firstChild) {
                    $el->appendChild($c->firstChild);
                }
                continue;
            }
            $el->appendChild($c);
        }
    }

    /** Container that accepts blocks and inline content as-is (li, td, blockquote, figure). */
    private function appendMixed(DOMElement $el, array $items): void
    {
        foreach ($items as $c) {
            if ($c === 'BREAK') {
                continue;
            }
            $el->appendChild($c);
        }
    }

    /** Block context: group runs of inline nodes into <p>. */
    private function appendBlockChildren(DOMElement $parent, array $items): void
    {
        $run = [];
        $flush = function () use (&$run, $parent) {
            // split the run on <br><br>
            $p = $this->out->createElement('p');
            $prevBr = false;
            foreach ($run as $c) {
                $isBr = $c instanceof DOMElement && $c->tagName === 'br';
                if ($isBr && $prevBr) {
                    if ($p->lastChild instanceof DOMElement && $p->lastChild->tagName === 'br') {
                        $p->removeChild($p->lastChild);
                    }
                    $parent->appendChild($p);
                    $p = $this->out->createElement('p');
                    $prevBr = false;
                    continue;
                }
                $p->appendChild($c);
                $prevBr = $isBr;
            }
            $parent->appendChild($p);
            $run = [];
        };
        foreach ($items as $c) {
            if ($c === 'BREAK' || ($c instanceof DOMElement && in_array($c->tagName, SAN_BLOCK_KEEP, true))) {
                if ($run !== []) {
                    $flush();
                }
                if ($c !== 'BREAK') {
                    $parent->appendChild($c);
                }
                continue;
            }
            $run[] = $c;
        }
        if ($run !== []) {
            $flush();
        }
    }

    private function cleanup(DOMElement $root): void
    {
        // normalise whitespace in text nodes
        $xp = new DOMXPath($this->out);
        foreach (iterator_to_array($xp->query('.//text()', $root)) as $t) {
            $t->data = preg_replace('/[ \t\r\n\x{00A0}]+/u', ' ', $t->data);
        }
        // trim leading/trailing <br> and whitespace in blocks; drop empty elements (repeat until stable)
        do {
            $changed = false;
            foreach (iterator_to_array($xp->query('.//*', $root)) as $el) {
                /** @var DOMElement $el */
                if (!$el->parentNode) {
                    continue;
                }
                foreach (['firstChild', 'lastChild'] as $edge) {
                    while ($el->$edge !== null && (
                        ($el->$edge instanceof DOMElement && $el->$edge->tagName === 'br')
                        || ($el->$edge instanceof DOMText && trim($el->$edge->data) === '')
                    ) && $el->tagName !== 'br') {
                        $el->removeChild($el->$edge);
                        $changed = true;
                    }
                }
                if ($el->firstChild instanceof DOMText) {
                    $el->firstChild->data = ltrim($el->firstChild->data);
                }
                if ($el->lastChild instanceof DOMText) {
                    $el->lastChild->data = rtrim($el->lastChild->data);
                }
                if (in_array($el->tagName, ['img', 'br'], true)) {
                    continue;
                }
                $hasImg = $xp->query('.//img', $el)->length > 0;
                if (!$hasImg && trim(str_replace("\u{00A0}", ' ', $el->textContent)) === '') {
                    $el->parentNode->removeChild($el);
                    $changed = true;
                }
            }
        } while ($changed);
        // Remove loose whitespace text directly under root.
        foreach (iterator_to_array($root->childNodes) as $c) {
            if ($c instanceof DOMText && trim($c->data) === '') {
                $root->removeChild($c);
            }
        }
    }

    public static function stripShortcodes(string $t): string
    {
        // WordPress / page-builder shortcode leftovers like [vc_row ...], [/gdlr_core_column], [caption id="..."]
        return (string) preg_replace(
            '~\[/?(?:vc_|et_pb_|gdlr|rev_slider|contact-form|caption|embed|gallery|av_|fusion_|tm_|wpforms|elementor)[^\]]*\]~i',
            '',
            $t
        );
    }
}

/** Drop exact repeats of top-level blocks (slider / flip-box duplicates on builder pages). */
function san_dedupe_blocks(string $html): string
{
    $seen = [];
    $out = [];
    foreach (preg_split("/\n/", $html) as $line) {
        $key = trim(preg_replace('/\s+/u', ' ', strip_tags($line)));
        if ($key !== '' && !str_contains($line, '<img') && isset($seen[$key]) && mb_strlen($key) > 3) {
            continue;
        }
        $seen[$key] = true;
        $out[] = $line;
    }
    return implode("\n", $out);
}
