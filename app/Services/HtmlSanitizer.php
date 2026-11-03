<?php
/**
 * ทำความสะอาด HTML ของเนื้อหาบทความ/บล็อก rich_text ก่อนแสดง
 *
 * ‼️ เนื้อหาบทความเป็น HTML (มาจาก CMS และจากการย้ายเว็บเดิม) จึงผ่าน e() ไม่ได้
 *    ทางเดียวที่ปลอดภัยคือ allowlist: เก็บเฉพาะแท็กและ attribute ที่รู้จัก ที่เหลือทิ้งทั้งหมด
 *    on*= · style · <script> · <iframe> · javascript: ถูกตัดทิ้งเสมอ
 *    blacklist (ลบเฉพาะสิ่งที่รู้ว่าอันตราย) ห้ามใช้ — มีวิธีเลี่ยงใหม่ทุกปี
 *
 * ‼️ <h1> ถูกลดเป็น <h2> — H1 ของหน้ามาจาก template ตัวเดียว (PLAN.md "H1 หนึ่งตัวต่อหน้า")
 */

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlSanitizer
{
    /** แท็ก => attribute ที่ยอมให้มี */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'mark' => [], 'small' => [],
        'sub' => [], 'sup' => [], 'code' => [], 'pre' => [], 'blockquote' => ['cite'], 'q' => [],
        'ul' => [], 'ol' => ['start'], 'li' => [], 'dl' => [], 'dt' => [], 'dd' => [],
        'a' => ['href', 'title', 'rel', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'srcset', 'sizes'],
        'figure' => [], 'figcaption' => [], 'span' => [], 'div' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'th' => ['colspan', 'rowspan', 'scope'],
        'td' => ['colspan', 'rowspan'], 'caption' => [],
    ];

    /** แท็กที่ทิ้งพร้อมเนื้อหาข้างใน (ไม่ใช่แค่ถอดเปลือก) */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select',
        'textarea', 'noscript', 'template', 'svg', 'math', 'link', 'meta', 'base', 'head', 'title'];

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');

        // meta charset บังคับให้ libxml อ่านเป็น UTF-8 (ไม่งั้นภาษาไทยเพี้ยนเป็น ISO-8859-1)
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body><div id="__root">' . $html . '</div></body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');

        if ($root === null) {
            return '';
        }

        $this->walk($root);

        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private function walk(DOMNode $node): void
    {
        // คัดลอกรายการลูกก่อน เพราะจะแก้โครงสร้างระหว่างวน
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }

            if ($tag === 'h1') {
                $child = $this->rename($child, 'h2');
                $tag   = 'h2';
            }

            $this->walk($child);

            if (!array_key_exists($tag, self::ALLOWED)) {
                // ถอดเปลือก เก็บเนื้อหาข้างใน
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $this->filterAttributes($child, self::ALLOWED[$tag]);
        }
    }

    /** @param array<int, string> $allowed */
    private function filterAttributes(DOMElement $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (!in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }

            if (in_array($name, ['href', 'src', 'cite'], true) && !self::safeUrl($attribute->nodeValue ?? '')) {
                $element->removeAttribute($attribute->nodeName);
            }

            if ($name === 'srcset' && preg_match('/(?:javascript|data|vbscript):/i', (string) $attribute->nodeValue) === 1) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        // ลิงก์เปิดแท็บใหม่ต้องตัดสิทธิ์หน้าปลายทางเข้าถึง window.opener
        if ($element->tagName === 'a' && $element->getAttribute('target') !== '') {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function rename(DOMElement $element, string $tag): DOMElement
    {
        $replacement = $element->ownerDocument->createElement($tag);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $replacement->setAttribute($attribute->nodeName, (string) $attribute->nodeValue);
        }
        while ($element->firstChild !== null) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode?->replaceChild($replacement, $element);

        return $replacement;
    }

    /** ยอมเฉพาะ http(s), mailto, tel, path ภายใน, #anchor */
    private static function safeUrl(string $url): bool
    {
        $url = trim(preg_replace('/[\x00-\x20]+/', '', $url) ?? '');

        if ($url === '' || str_starts_with($url, '#') || (str_starts_with($url, '/') && !str_starts_with($url, '//'))) {
            return true;
        }

        return preg_match('#^(?:https?://|mailto:|tel:)#i', $url) === 1;
    }
}
