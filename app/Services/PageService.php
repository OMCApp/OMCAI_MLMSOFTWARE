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
    public function breadcrumbs(array $page, string $homeLabel): array
    {
        if (($page['path'] ?? '') === '/') {
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

            if ($parent['status'] === 'published' && $parent['path'] !== '/') {
                array_unshift($trail, ['label' => (string) $parent['title'], 'url' => (string) $parent['path']]);
            }

            $current = $parent['parent_id'] !== null ? (int) $parent['parent_id'] : null;
        }

        array_unshift($trail, ['label' => $homeLabel, 'url' => '/']);
        $trail[] = ['label' => (string) $page['title'], 'url' => null];

        return $trail;
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
            $errors['path'] = 'cms.page.error_path';
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'cms.page.error_title';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Page::STATUSES, true)) {
            $errors['status'] = 'cms.page.error_status';
        }
        if (!in_array((string) ($data['robots'] ?? 'index'), Page::ROBOTS, true)) {
            $errors['robots'] = 'cms.page.error_robots';
        }

        return $errors;
    }

    /** เทมเพลตของหน้า — ค่าในฐานข้อมูลเป็น key อังกฤษ */
    public const TEMPLATES = ['default', 'home', 'product', 'landing', 'legal', 'contact'];

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->pages->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->pages->find($id);
    }

    /** @return array<int, string> id => "title (path)" สำหรับเลือกหน้าแม่/ลิงก์เมนู */
    public function options(?int $except = null, ?string $locale = null): array
    {
        $out = [];

        foreach ($this->pages->outline() as $row) {
            if ($except !== null && (int) $row['id'] === $except) {
                continue;
            }

            if ($locale !== null && TranslationService::enabled('pages')
                && !TranslationService::pathMatchesLocale((string) $row['path'], $locale)) {
                continue;
            }

            $out[(int) $row['id']] = $row['title'] . ' (' . $row['path'] . ')';
        }

        asort($out);

        return $out;
    }

    /**
     * ตรวจเต็มของ CMS: กฎพื้นฐาน + path ไม่ซ้ำ + path ตรงกับภาษา + หน้าแม่ไม่วน
     *
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateForCms(array $data, ?int $id): array
    {
        $errors = $this->validate($data);
        $path   = (string) ($data['path'] ?? '');

        if (!isset($errors['path']) && (preg_match('#//|\s|\?|\#|\.\.#u', $path) === 1)) {
            $errors['path'] = 'cms.page.error_path';
        }

        if (!isset($errors['path'])) {
            $existing = $this->pages->findByPath($path);

            if ($existing !== null && (int) $existing['id'] !== $id) {
                $errors['path'] = 'cms.page.error_path_taken';
            }
        }

        if (!isset($errors['path']) && TranslationService::enabled('pages')
            && !TranslationService::pathMatchesLocale($path, TranslationService::normalize((string) ($data['locale'] ?? TranslationService::default())))) {
            $errors['path'] = 'cms.translation.error_path_locale';
        }

        if (!in_array((string) ($data['template'] ?? 'default'), self::TEMPLATES, true)) {
            $errors['template'] = 'cms.page.error_template';
        }

        $parent = $data['parent_id'] ?? null;

        if ($parent !== null && $id !== null) {
            // หน้าแม่ห้ามเป็นตัวเองหรือลูกหลานของตัวเอง — breadcrumb จะวน
            $byId  = array_column($this->pages->outline(), null, 'id');
            $guard = 0;

            for ($cursor = (int) $parent; $cursor > 0 && $guard++ < 20; $cursor = (int) ($byId[$cursor]['parent_id'] ?? 0)) {
                if ($cursor === $id) {
                    $errors['parent_id'] = 'cms.page.error_parent';
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * บันทึกจาก CMS — สร้างใหม่หรือแก้ไข (แก้ไขเก็บ revision ให้อัตโนมัติ)
     *
     * ‼️ เปลี่ยน path ของหน้าที่เผยแพร่แล้ว + ขอ redirect → สร้าง 301 จาก path เดิมให้ (URL เดิมมี backlink/ranking)
     *
     * @param  array<string, mixed> $data
     * @return array{id:int, redirect:?string}
     */
    public function save(?int $id, array $data, bool $redirectOldPath): array
    {
        if ($id === null) {
            return ['id' => $this->create($data), 'redirect' => null];
        }

        $before = $this->pages->find($id);
        $this->update($id, $data);

        $oldPath = (string) ($before['path'] ?? '');
        $newPath = (string) ($data['path'] ?? $oldPath);

        if ($redirectOldPath && $before !== null && $oldPath !== $newPath && self::isLive($before)) {
            (new RedirectService())->createForMove($oldPath, $newPath);

            return ['id' => $id, 'redirect' => $oldPath];
        }

        return ['id' => $id, 'redirect' => null];
    }

    /**
     * ย้อนกลับไปเนื้อหาของ revision — เก็บสถานะปัจจุบันเป็น revision ใหม่ก่อน (ย้อนกลับซ้ำได้)
     * ‼️ path / status / published_at ไม่ถูกย้อน — การย้อนเนื้อหาต้องไม่เปลี่ยน URL หรือเผยแพร่โดยไม่ตั้งใจ
     */
    public function restore(int $id, int $revisionId): bool
    {
        $revision = (new \App\Models\Revision())->find($revisionId);

        if ($revision === null || $revision['entity'] !== 'page' || (int) $revision['entity_id'] !== $id) {
            return false;
        }

        $snapshot = json_decode((string) $revision['data'], true);

        if (!is_array($snapshot)) {
            return false;
        }

        $data = array_intersect_key($snapshot, array_flip([
            'title', 'template', 'blocks', 'seo_title', 'meta_description', 'canonical_override', 'robots', 'og_image_id', 'parent_id',
        ]));

        return $this->update($id, $data);
    }

    /**
     * สร้างคำแปลของหน้า: คัดลอกแถวไปภาษาใหม่ในกลุ่มเดียวกัน · path = prefix ภาษา + slug เดิม · สถานะ draft
     *
     * @return array{id:int, error:?string}
     */
    public function translate(int $id, string $locale): array
    {
        if (!TranslationService::enabled('pages') || !in_array($locale, TranslationService::locales(), true)) {
            return ['id' => 0, 'error' => 'cms.translation.error_unavailable'];
        }

        $source = $this->pages->find($id);

        if ($source === null) {
            return ['id' => 0, 'error' => 'cms.translation.error_unavailable'];
        }

        $group    = TranslationService::ensureGroup('pages', $source);
        $siblings = TranslationService::siblings('pages', $group);

        if (isset($siblings[$locale]) || ($source['locale'] ?? TranslationService::default()) === $locale) {
            return ['id' => (int) ($siblings[$locale]['id'] ?? $id), 'error' => 'cms.translation.error_exists'];
        }

        $path = TranslationService::translatePath((string) $source['path'], (string) ($source['locale'] ?? TranslationService::default()), $locale);

        if ($this->pages->findByPath($path) !== null) {
            return ['id' => 0, 'error' => 'cms.page.error_path_taken'];
        }

        return (array) Database::transaction(function () use ($source, $group, $locale, $path, $id): array {
            if ((string) ($source['translation_group'] ?? '') === '') {
                $this->pages->update($id, ['translation_group' => $group]);
            }

            $newId = $this->pages->create([
                'parent_id'          => null,
                'path'               => $path,
                'title'              => $source['title'],
                'template'           => $source['template'],
                'blocks'             => $source['blocks'],
                'seo_title'          => $source['seo_title'],
                'meta_description'   => $source['meta_description'],
                'canonical_override' => null,
                'robots'             => $source['robots'],
                'og_image_id'        => $source['og_image_id'],
                'status'             => 'draft',
                'published_at'       => null,
                'sort'               => $source['sort'],
                'locale'             => $locale,
                'translation_group'  => $group,
            ]);

            return ['id' => $newId, 'error' => null];
        });
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        return $this->pages->countBy('status');
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
                    // q/a = คีย์ของ partial blocks/faq.php · question/answer = คีย์เดิมที่ SchemaService อ่าน
                    static fn (array $row): array => [
                        'q' => (string) $row['question'], 'a' => (string) $row['answer'],
                        'question' => (string) $row['question'], 'answer' => (string) $row['answer'],
                    ],
                    $loaded[$group]
                );
            }
        }

        return $blocks;
    }
}
