<?php
/**
 * Collects contact details (phone, LINE, email, address, company / tax id, social, map) from a full page.
 */

declare(strict_types=1);

final class ContactCollector
{
    /** @var array<string, array{kind:string, value:string, context:string, sources:list<string>}> */
    private array $items = [];

    private function add(string $kind, string $value, string $context, string $source): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $key = $kind . '|' . mb_strtolower($value);
        if (!isset($this->items[$key])) {
            $this->items[$key] = ['kind' => $kind, 'value' => $value, 'context' => mb_substr(trim($context), 0, 200), 'sources' => []];
        }
        if (!in_array($source, $this->items[$key]['sources'], true)) {
            $this->items[$key]['sources'][] = $source;
        }
    }

    public function scan(DOMXPath $xp, string $source): void
    {
        foreach ($xp->query('//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));
            $ctx = trim(preg_replace('/\s+/u', ' ', $a->textContent));
            $l = strtolower($href);
            if (str_starts_with($l, 'tel:')) {
                $this->add('phone', self::phone(substr($href, 4)), $ctx, $source);
            } elseif (str_starts_with($l, 'mailto:')) {
                $this->add('email', strtolower(explode('?', substr($href, 7))[0]), $ctx, $source);
            } elseif (preg_match('~(line\.me|lin\.ee)/~', $l)) {
                $this->add('line_url', $href, $ctx, $source);
                if (preg_match('#line\.me/ti/p/~?(@?[A-Za-z0-9._-]+)#', $href, $lm)) {
                    $this->add('line_id', $lm[1], 'from ' . $href, $source);
                }
            } elseif (preg_match('~(facebook\.com|fb\.com|m\.me)/~', $l) && !str_contains($l, 'sharer')) {
                $this->add('facebook', $href, $ctx, $source);
            } elseif (preg_match('~(youtube\.com|tiktok\.com|instagram\.com|twitter\.com|x\.com)/~', $l) && !preg_match('~share|intent/~', $l)) {
                $this->add('social', $href, $ctx, $source);
            } elseif (preg_match('~(google\.[a-z.]+/maps|maps\.app\.goo\.gl|goo\.gl/maps)~', $l)) {
                $this->add('map', $href, $ctx, $source);
            }
        }
        foreach ($xp->query('//iframe') as $f) {
            foreach (['src', 'data-src-cmplz', 'data-src'] as $attr) {
                $v = $f->getAttribute($attr);
                if (str_contains($v, 'google.com/maps')) {
                    $this->add('map_embed', html_entity_decode($v), '', $source);
                }
            }
        }

        // Text: scan leaf-ish elements so the context stays short. Article bodies are skipped for
        // address / company / registration — they mention third parties (insurers, MLM brands).
        $inArticle = 'ancestor::*[contains(concat(" ", normalize-space(@class), " "), " seocrawler-single-article-content ")]';
        foreach ($xp->query('//body//*[not(self::script) and not(self::style) and not(self::noscript)][text()[normalize-space()]]') as $el) {
            $article = $xp->query($inArticle, $el)->length > 0;
            $own = '';
            foreach ($el->childNodes as $c) {
                if ($c instanceof DOMText) {
                    $own .= $c->data . ' ';
                }
            }
            $own = trim(preg_replace('/\s+/u', ' ', $own));
            if ($own === '') {
                continue;
            }
            $full = trim(preg_replace('/\s+/u', ' ', $el->textContent));
            $ctx = mb_strlen($full) <= 300 ? $full : $own;

            if (preg_match_all('~(?<![\d])(?:\+66[- ]?|0)[2-9]\d?[- ]?\d{3}[- ]?\d{3,4}(?![\d])~u', $own, $m)) {
                foreach ($m[0] as $p) {
                    $digits = preg_replace('/\D/', '', $p);
                    $mobile = (bool) preg_match('/^0[689]/', $digits);
                    if ($mobile ? strlen($digits) === 10 : (strlen($digits) >= 9 && strlen($digits) <= 11)) {
                        $this->add('phone', self::phone($p), $ctx, $source);
                    }
                }
            }
            if (preg_match_all('~[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}~', $own, $m)) {
                foreach ($m[0] as $e) {
                    if (!preg_match('~\.(png|jpe?g|gif|webp|svg)$~i', $e)) {
                        $this->add('email', strtolower($e), $ctx, $source);
                    }
                }
            }
            if (preg_match_all('~\bline\s*(?:id|oa)\s*[:：]?\s*(@?[A-Za-z0-9._-]{3,})~iu', $own, $m)) {
                foreach ($m[1] as $id) {
                    $this->add('line_id', $id, $ctx, $source);
                }
            }
            if ($article) {
                continue;
            }
            if (preg_match('~(เลขที่|ซอย|ซ\.|ถนน|ถ\.|แขวง|ตำบล|ต\.|อำเภอ|อ\.|เขต|จังหวัด|จ\.)~u', $own) && preg_match('~(?<!\d)\d{5}(?!\d)~', $own)
                && mb_strlen($own) < 400) {
                $this->add('address', $own, $full, $source);
            }
            if (preg_match_all('~(?:เลข(?:ทะเบียน(?:นิติบุคคล|บริษัท)?|ประจำตัวผู้เสียภาษี(?:อากร)?|ผู้เสียภาษี)|Tax\s*ID|Registration\s*No\.?)\s*[:：]?\s*([\d][\d\s-]{8,18}\d)~iu', $own, $m)) {
                foreach ($m[1] as $id) {
                    $this->add('company_registration', preg_replace('/\D/', '', $id), $ctx, $source);
                }
            }
            if (preg_match_all('~บริษัท\s+[\p{Thai}A-Za-z0-9 .&()-]{2,80}?\s+จำกัด(?:\s*\(มหาชน\))?|[A-Z][A-Za-z0-9 .&-]{1,60}\s(?:Co\.,?\s*Ltd\.?|Company Limited)~u', $own, $m)) {
                foreach ($m[0] as $c) {
                    $this->add('company_name', trim($c), $ctx, $source);
                }
            }
        }
    }

    private static function phone(string $p): string
    {
        $d = preg_replace('/\D/', '', $p);
        if (str_starts_with($d, '66')) {
            $d = '0' . substr($d, 2);
        }
        if (strlen($d) === 10) {
            return substr($d, 0, 3) . '-' . substr($d, 3, 3) . '-' . substr($d, 6);
        }
        if (strlen($d) === 9) {
            return substr($d, 0, 2) . '-' . substr($d, 2, 3) . '-' . substr($d, 5);
        }
        return $d;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $items = array_values($this->items);
        usort($items, fn ($a, $b) => [$a['kind'], -count($a['sources'])] <=> [$b['kind'], -count($b['sources'])]);
        foreach ($items as &$i) {
            $i['source_count'] = count($i['sources']);
            if (count($i['sources']) > 5) {
                $i['sources'] = array_merge(array_slice($i['sources'], 0, 5), ['… +' . (count($i['sources']) - 5) . ' more']);
            }
        }
        return $items;
    }
}
