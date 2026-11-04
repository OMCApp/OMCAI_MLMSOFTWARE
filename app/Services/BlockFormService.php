<?php
/**
 * ฟอร์มของบล็อกใน CMS (block builder) — นิยามช่องของแต่ละชนิด + แปลงค่าจากฟอร์มเป็น data ของบล็อก
 *
 * ‼️ คีย์ของแต่ละชนิดตรงกับที่ partial ใน app/Views/partials/blocks/<ชนิด>.php ประกาศไว้ 1:1
 *    แก้ partial เพิ่มคีย์เมื่อไหร่ ต้องเพิ่มช่องที่นี่ในคอมมิตเดียวกัน
 * ‼️ ยกเว้นที่จงใจ (project/NOTES.md Phase 3):
 *    · stats.items  — มาจาก Settings → ตัวเลขที่ "ยืนยันแล้ว" เท่านั้น ห้ามพิมพ์ตัวเลขในบล็อก
 *    · trust_bar.logos — มาจาก Settings → โลโก้ลูกค้าที่ "ยืนยันแล้ว" เท่านั้น
 *    · testimonials.items — มาจากตาราง testimonials ที่ published (ยืนยันการอนุญาตแล้ว)
 *    · lead_form.options / privacy_url — BlockService::prepare() ใส่ให้ตอนแสดง
 *
 * ชนิดช่อง: text · textarea · number · checkbox · select · lines (list<string> บรรทัดละรายการ)
 *          paragraphs (ย่อหน้าคั่นด้วยบรรทัดว่าง) · html (ผ่าน HtmlSanitizer) · image (เลือกจากคลังสื่อ)
 *          link (label + url [+ style]) · list (แถวซ้ำได้ — fields ข้างใน) · cells (ค่าคั่นด้วย | : + = มี · - = ไม่มี)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;

final class BlockFormService
{
    private const HERO_STYLES = ['primary', 'secondary', 'outline'];
    private const CTA_STYLES  = ['light', 'ghost-light'];
    private const PLAN_KEYS   = ['binary', 'unilevel', 'matrix', 'board', 'stair_step', 'hybrid'];
    private const FORM_KEYS   = ['demo', 'quote', 'consult', 'contact', 'migration'];

    /** @return array<string, array<int, array<string, mixed>>> ชนิด => ช่อง */
    public static function schema(): array
    {
        $head = [
            ['key' => 'eyebrow', 'type' => 'text'],
            ['key' => 'title', 'type' => 'text'],
            ['key' => 'lead', 'type' => 'textarea'],
        ];
        $action = ['key' => 'action', 'type' => 'link'];

        return [
            'hero' => [
                ['key' => 'eyebrow', 'type' => 'text'],
                ['key' => 'title', 'type' => 'textarea', 'required' => true],
                ['key' => 'lead', 'type' => 'textarea'],
                ['key' => 'bullets', 'type' => 'lines'],
                ['key' => 'actions', 'type' => 'list', 'fields' => [
                    ['key' => 'label', 'type' => 'text'], ['key' => 'url', 'type' => 'text'],
                    ['key' => 'style', 'type' => 'select', 'options' => self::HERO_STYLES],
                ]],
                ['key' => 'image', 'type' => 'image'],
            ],
            'trust_bar' => [
                ['key' => 'title', 'type' => 'text'],
                ['key' => 'logos', 'type' => 'from_settings'],
            ],
            'stats' => [
                ['key' => 'items', 'type' => 'from_settings'],
            ],
            'feature_grid' => array_merge($head, [
                ['key' => 'columns', 'type' => 'select', 'options' => ['4', '3', '8']],
                ['key' => 'items', 'type' => 'list', 'fields' => [
                    ['key' => 'icon', 'type' => 'text'], ['key' => 'title', 'type' => 'text'],
                    ['key' => 'text', 'type' => 'textarea'], ['key' => 'url', 'type' => 'text'],
                ]],
            ]),
            'split' => array_merge([
                ['key' => 'layout', 'type' => 'select', 'options' => ['plain', 'band']],
                ['key' => 'visual', 'type' => 'select', 'options' => ['image', 'bars']],
                ['key' => 'image', 'type' => 'image'],
                ['key' => 'reverse', 'type' => 'checkbox'],
            ], $head, [
                ['key' => 'bullets', 'type' => 'lines'],
                ['key' => 'action', 'type' => 'link', 'styles' => self::HERO_STYLES],
            ]),
            'plan_cards' => array_merge($head, [
                ['key' => 'plans', 'type' => 'list', 'fields' => [
                    ['key' => 'key', 'type' => 'select', 'options' => self::PLAN_KEYS],
                    ['key' => 'name', 'type' => 'text'], ['key' => 'text', 'type' => 'textarea'],
                    ['key' => 'url', 'type' => 'text'],
                ]],
                $action,
            ]),
            'industry_grid' => array_merge($head, [
                ['key' => 'items', 'type' => 'list', 'fields' => [
                    ['key' => 'title', 'type' => 'text'], ['key' => 'subtitle', 'type' => 'text'],
                    ['key' => 'url', 'type' => 'text'], ['key' => 'icon', 'type' => 'text'],
                    ['key' => 'image', 'type' => 'image'],
                ]],
            ]),
            'dashboard_showcase' => array_merge($head, [
                ['key' => 'bullets', 'type' => 'lines'],
                $action,
            ]),
            'about_teaser' => [
                ['key' => 'eyebrow', 'type' => 'text'],
                ['key' => 'title', 'type' => 'text'],
                ['key' => 'text', 'type' => 'textarea'],
                ['key' => 'points', 'type' => 'lines'],
                $action,
                ['key' => 'image', 'type' => 'image'],
            ],
            'testimonials' => [
                ['key' => 'eyebrow', 'type' => 'text'],
                ['key' => 'title', 'type' => 'text'],
                ['key' => 'items', 'type' => 'from_settings'],
            ],
            'faq' => array_merge($head, [
                ['key' => 'group', 'type' => 'text'],
                ['key' => 'items', 'type' => 'list', 'fields' => [
                    ['key' => 'q', 'type' => 'text'], ['key' => 'a', 'type' => 'textarea'],
                ]],
            ]),
            'cta_band' => [
                ['key' => 'title', 'type' => 'text', 'required' => true],
                ['key' => 'text', 'type' => 'textarea'],
                ['key' => 'actions', 'type' => 'list', 'fields' => [
                    ['key' => 'label', 'type' => 'text'], ['key' => 'url', 'type' => 'text'],
                    ['key' => 'style', 'type' => 'select', 'options' => self::CTA_STYLES],
                    ['key' => 'icon', 'type' => 'text'],
                ]],
            ],
            'rich_text' => [
                ['key' => 'title', 'type' => 'text'],
                ['key' => 'paragraphs', 'type' => 'paragraphs'],
                ['key' => 'list', 'type' => 'lines'],
                ['key' => 'html', 'type' => 'html'],
            ],
            'steps' => array_merge($head, [
                ['key' => 'items', 'type' => 'list', 'fields' => [
                    ['key' => 'title', 'type' => 'text'], ['key' => 'text', 'type' => 'textarea'],
                ]],
            ]),
            'comparison_table' => array_merge($head, [
                ['key' => 'columns', 'type' => 'lines'],
                ['key' => 'rows', 'type' => 'list', 'fields' => [
                    ['key' => 'label', 'type' => 'text'], ['key' => 'values', 'type' => 'cells'],
                ]],
                ['key' => 'highlight', 'type' => 'number'],
                ['key' => 'caption', 'type' => 'text'],
            ]),
            'lead_form' => array_merge([
                ['key' => 'form_key', 'type' => 'select', 'options' => self::FORM_KEYS],
            ], $head, [
                ['key' => 'submit', 'type' => 'text'],
            ]),
        ];
    }

    /**
     * ค่าจากฟอร์ม (blocks[n][type], blocks[n][sort], blocks[n][remove], blocks[n][data][...]) → รายการบล็อกของ pages.blocks
     *
     * @param  array<mixed> $input
     * @return array<int, array{type:string, data:array<string,mixed>}>
     */
    public function fromInput(array $input): array
    {
        $schema = self::schema();
        $rows   = [];
        $order  = 0;

        foreach ($input as $raw) {
            if (!is_array($raw) || !isset($schema[$raw['type'] ?? '']) || !empty($raw['remove'])) {
                continue;
            }

            $rows[] = [
                'sort'  => is_numeric($raw['sort'] ?? null) ? (int) $raw['sort'] : $order,
                'order' => $order++,
                'type'  => (string) $raw['type'],
                'data'  => $this->normalize($schema[$raw['type']], is_array($raw['data'] ?? null) ? $raw['data'] : []),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => [$a['sort'], $a['order']] <=> [$b['sort'], $b['order']]);

        return array_map(static fn (array $row): array => ['type' => $row['type'], 'data' => $row['data']], $rows);
    }

    /**
     * @param  array<int, array<string, mixed>> $fields
     * @param  array<mixed>                     $values
     * @return array<string, mixed>
     */
    private function normalize(array $fields, array $values): array
    {
        $out = [];

        foreach ($fields as $field) {
            $key   = $field['key'];
            $value = $values[$key] ?? null;
            $clean = $this->value($field, $value);

            if ($clean !== null && $clean !== '' && $clean !== []) {
                $out[$key] = $clean;
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $field */
    private function value(array $field, mixed $value): mixed
    {
        $text = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';

        switch ($field['type']) {
            case 'text':
            case 'textarea':
                return $text;

            case 'number':
                return is_numeric($text) ? (int) $text : null;

            case 'checkbox':
                return !empty($value) ? true : null;

            case 'select':
                $options = array_map('strval', (array) ($field['options'] ?? []));

                if (!in_array($text, $options, true)) {
                    return null;
                }

                return $field['key'] === 'columns' ? (int) $text : $text;

            case 'lines':
                return array_values(array_filter(array_map('trim', explode("\n", $text)), 'strlen'));

            case 'paragraphs':
                return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $text) ?: []), 'strlen'));

            case 'html':
                return $text === '' ? '' : (new HtmlSanitizer())->clean($text);

            case 'cells':
                return array_map(static function (string $cell): bool|string {
                    $cell = trim($cell);

                    return match ($cell) {
                        '+'     => true,
                        '-'     => false,
                        default => $cell,
                    };
                }, $text === '' ? [] : explode('|', $text));

            case 'image':
                return $this->image(is_array($value) ? (int) ($value['media_id'] ?? 0) : 0);

            case 'link':
                $link  = is_array($value) ? $value : [];
                $label = trim((string) ($link['label'] ?? ''));
                $url   = trim((string) ($link['url'] ?? ''));

                if ($label === '' || $url === '') {
                    return null;
                }

                $out   = ['label' => $label, 'url' => $url];
                $style = (string) ($link['style'] ?? '');

                if (in_array($style, (array) ($field['styles'] ?? []), true)) {
                    $out['style'] = $style;
                }

                return $out;

            case 'list':
                $items   = [];
                $choices = array_column(array_filter($field['fields'], static fn (array $f): bool => $f['type'] === 'select'), 'key');

                foreach (is_array($value) ? $value : [] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $item = $this->normalize($field['fields'], $row);

                    // แถวว่าง (ช่องว่างที่เผื่อไว้ให้เพิ่ม) ถูกตัดทิ้ง — ตัวเลือก (select) มีค่าเสมอจึงไม่นับ
                    if (array_diff(array_keys($item), $choices) !== []) {
                        $items[] = $item;
                    }
                }

                return $items;

            default:
                return null; // from_settings — ไม่เก็บในบล็อก
        }
    }

    /** @return array{src:string,alt:string,width:?int,height:?int}|null */
    private function image(int $mediaId): ?array
    {
        if ($mediaId <= 0) {
            return null;
        }

        $row = (new Media())->find($mediaId);

        if ($row === null) {
            return null;
        }

        return MediaService::imageData($row);
    }

    /**
     * id ของ media ที่ src นี้ชี้ (ใช้เลือกค่าเดิมในฟอร์ม)
     *
     * @param array<string, mixed>|null $image
     */
    public static function mediaIdOf(?array $image): int
    {
        $src = (string) ($image['src'] ?? '');

        return $src === '' ? 0 : (new MediaService())->idByUrl($src);
    }

    /**
     * บล็อกว่างของชนิดนี้ (ปุ่ม "เพิ่มบล็อก")
     *
     * @return array{type:string, data:array<string,mixed>}
     */
    public static function blank(string $type): array
    {
        return ['type' => $type, 'data' => []];
    }
}
