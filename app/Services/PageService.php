<?php
/**
 * หน้าของเว็บสาธารณะ — หาหน้า ตรวจสถานะ ถอดบล็อก สร้าง breadcrumb
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Revision;
use Core\Auth;
use Core\Database;

final class PageService
{
    public function __construct(private readonly Page $pages = new Page())
    {
    }

    /**
     * หน้าที่ path นี้ ถ้าคนนี้ดูได้
     *
     * ‼️ draft (หรือตั้งเวลาเผยแพร่ในอนาคต) → null เสมอ ยกเว้น $preview ที่ middleware อนุญาตแล้ว
     *    สาธารณะต้องเห็น 404 ไม่ใช่หน้าว่างหรือ 403 (403 บอกว่ามีหน้านี้อยู่)
     *
     * @return array<string, mixed>|null
     */
    public function findVisible(string $path, bool $preview): ?array
    {
        $page = $this->pages->findByPath($path);

        if ($page === null) {
            return null;
        }

        return ($preview || self::isLive($page)) ? $page : null;
    }

    /** @param array<string, mixed> $row */
    public static function isLive(array $row): bool
    {
        if (($row['status'] ?? '') !== 'published') {
            return false;
        }

        $at = $row['published_at'] ?? null;

        return $at === null || strtotime((string) $at) <= time();
    }

    /** หน้าของ path นี้ใช้เป็นแหล่ง SEO ของหน้าที่ระบบสร้าง (/blog/) — เฉพาะที่เผยแพร่แล้ว */
    public function liveByPath(string $path): ?array
    {
        $page = $this->pages->findByPath($path);

        return ($page !== null && self::isLive($page)) ? $page : null;
    }

    /**
     * บล็อกของหน้า — JSON list ของ {"type": string, "data": object}
     * บล็อก faq ที่อ้างกลุ่ม (data.group) ถูกเติม data.items จากตาราง faqs ที่นี่
     * เพื่อให้ทั้ง partial และ FAQPage schema ใช้ข้อมูลชุดเดียวกัน (schema ต้องตรงกับที่แสดงจริง)
     *
     * @param  array<string, mixed> $page
     * @return array<int, array{type:string, data:array<string,mixed>}>
     */
    public function blocks(array $page): array
    {
        $raw    = json_decode((string) ($page['blocks'] ?? ''), true);
        $blocks = [];

        foreach (is_array($raw) ? $raw : [] as $block) {
            if (!is_array($block) || !is_string($block['type'] ?? null)) {
                continue;
            }

            $blocks[] = [
                'type' => $block['type'],
                'data' => is_array($block['data'] ?? null) ? $block['data'] : [],
            ];
        }

        return $this->fillFaqGroups($blocks);
    }

    /**
     * breadcrumb จาก parent_id ขึ้นไปจนถึงราก — หน้าแรกไม่มี breadcrumb
     *
     * @param  array<string, mixed> $page
     * @return array<int, array{label:string, url:?string}>
     */
    public function breadcrumbs(array $page, string $homeLabel, string $homeUrl = '/'): array
    {
        if (in_array((string) ($page['path'] ?? ''), ['/', $homeUrl], true)) {
            return [];
        }

        $byId = [];

        foreach ($this->pages->outline() as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $trail   = [];
        $guard   = 0;
        $current = $page['parent_id'] !== null ? (int) $page['parent_id'] : null;

        // guard กัน parent วนเป็นวงจากข้อมูลที่แก้ผิด
        while ($current !== null && isset($byId[$current]) && $guard++ < 10) {
            $parent = $byId[$current];

            if ($parent['status'] === 'published' && !in_array($parent['path'], ['/', $homeUrl], true)) {
                array_unshift($trail, ['label' => (string) $parent['title'], 'url' => (string) $parent['path']]);
            }

            $current = $parent['parent_id'] !== null ? (int) $parent['parent_id'] : null;
        }

        array_unshift($trail, ['label' => $homeLabel, 'url' => $homeUrl]);
        $trail[] = ['label' => (string) $page['title'], 'url' => null];

        return $trail;
    }

    /**
     * path ของทุกภาษาของหน้านี้ที่เผยแพร่แล้วและ index ได้ — [locale => path] (รวมตัวเอง)
     * ‼️ hreflang ต้องชี้หน้าที่ index ได้และ canonical เป็นตัวเองเท่านั้น ไม่งั้น Google ทิ้งทั้งชุด
     *
     * @param  array<string, mixed> $page
     * @return array<string, string>
     */
    public function translationPaths(array $page): array
    {
        $group = (string) ($page['translation_group'] ?? '');

        if ($group === '') {
            return [];
        }

        $out = [];

        foreach ($this->pages->translations($group) as $row) {
            if (self::isLive($row) && $row['robots'] === 'index' && (string) ($row['canonical_override'] ?? '') === '') {
                $out[(string) $row['locale']] = (string) $row['path'];
            }
        }

        return $out;
    }

    /**
     * [locale => path] ของภาษาที่มีอยู่ — ปุ่มสลับภาษาใช้ (รวมหน้าที่ยังไม่ index ได้ เช่น /en/blog/)
     *
     * @param  array<string, mixed> $page
     * @return array<string, string>
     */
    public function switcherPaths(array $page): array
    {
        $group = (string) ($page['translation_group'] ?? '');
        $out   = [];

        foreach ($group === '' ? [] : $this->pages->translations($group) as $row) {
            if (self::isLive($row)) {
                $out[(string) $row['locale']] = (string) $row['path'];
            }
        }

        return $out;
    }

    /**
     * ทุกกลุ่มคำแปลที่ index ได้ — sitemap (query เดียวทั้งเว็บ)
     *
     * @return array<string, array<string, string>> group => [locale => path]
     */
    public function translationGroups(): array
    {
        $out = [];

        foreach ($this->pages->translationIndex() as $row) {
            $out[(string) $row['translation_group']][(string) $row['locale']] = (string) $row['path'];
        }

        return $out;
    }

    /**
     * ชุด hreflang จาก [locale => path] — ว่างถ้ามีภาษาเดียว · x-default = ไทย (ภาษาหลักของเว็บ)
     * ชุดเดียวกันทั้งใน <head> และ sitemap จึง reciprocal เสมอ
     *
     * @param  array<string, string> $paths
     * @return list<array{hreflang:string, path:string}>
     */
    public static function hreflangSet(array $paths): array
    {
        if (count($paths) < 2) {
            return [];
        }

        $out = [];

        foreach (LocaleService::codes() as $locale) {
            if (isset($paths[$locale])) {
                $out[] = ['hreflang' => LocaleService::htmlLang($locale), 'path' => $paths[$locale]];
            }
        }

        if (isset($paths[LocaleService::DEFAULT])) {
            $out[] = ['hreflang' => 'x-default', 'path' => $paths[LocaleService::DEFAULT]];
        }

        return $out;
    }

    /**
     * หน้าที่ index ได้สำหรับ sitemap — ตัดหน้าที่ canonical ชี้ไปที่อื่น
     *
     * @return array<int, array<string, mixed>>
     */
    public function sitemapEntries(): array
    {
        return array_values(array_filter(
            $this->pages->indexable(),
            static fn (array $row): bool => (string) ($row['canonical_override'] ?? '') === ''
        ));
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];
        $path   = (string) ($data['path'] ?? '');

        if ($path === '' || !str_starts_with($path, '/') || !str_ends_with($path, '/')) {
            $errors['path'] = 'page.error_path';
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'page.error_title';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Page::STATUSES, true)) {
            $errors['status'] = 'page.error_status';
        }
        if (!in_array((string) ($data['robots'] ?? 'index'), Page::ROBOTS, true)) {
            $errors['robots'] = 'page.error_robots';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->pages->create($data);
    }

    /**
     * ‼️ เก็บสำเนาก่อนแก้ทุกครั้ง (revisions) — ย้อนกลับได้ และเห็นว่าใครแก้อะไร
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        return (bool) Database::transaction(function () use ($id, $data): bool {
            $before = $this->pages->find($id);

            if ($before === null) {
                return false;
            }

            (new Revision())->create([
                'entity'    => 'page',
                'entity_id' => $id,
                'data'      => (string) json_encode($before, JSON_UNESCAPED_UNICODE),
                'user_id'   => Auth::id(),
            ]);

            return $this->pages->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        return $this->pages->delete($id);
    }

    /**
     * @param  array<int, array{type:string, data:array<string,mixed>}> $blocks
     * @return array<int, array{type:string, data:array<string,mixed>}>
     */
    private function fillFaqGroups(array $blocks): array
    {
        $groups = [];

        foreach ($blocks as $block) {
            if ($block['type'] === 'faq' && !isset($block['data']['items']) && is_string($block['data']['group'] ?? null)) {
                $groups[$block['data']['group']] = true;
            }
        }

        if ($groups === []) {
            return $blocks;
        }

        $faqs   = new Faq();
        $loaded = [];

        // จำนวนกลุ่มต่อหน้ามีหนึ่งหรือสองกลุ่ม ไม่ใช่ต่อแถว — ไม่ใช่ N+1
        foreach (array_keys($groups) as $group) {
            $loaded[$group] = $faqs->publishedInGroup($group);
        }

        foreach ($blocks as $index => $block) {
            $group = $block['data']['group'] ?? null;

            if ($block['type'] === 'faq' && is_string($group) && isset($loaded[$group]) && !isset($block['data']['items'])) {
                $blocks[$index]['data']['items'] = array_map(
                    static fn (array $row): array => ['question' => (string) $row['question'], 'answer' => (string) $row['answer']],
                    $loaded[$group]
                );
            }
        }

        return $blocks;
    }
}
