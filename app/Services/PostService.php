<?php
/**
 * บทความ — permalink แบบ WordPress เดิม /{yyyy}/{mm}/{dd}/{slug}/
 *
 * ‼️ URL บทความเดิม 37 เรื่องต้องตรงทุกตัวอักษร (PLAN.md) — slug ภาษาไทยเก็บแบบ decode
 *    เบราว์เซอร์ส่งมาแบบ percent-encode แต่ Core\Path::requested() decode ให้แล้ว จึงเทียบตรงๆ ได้
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Models\Revision;
use Core\Auth;
use Core\Database;

final class PostService
{
    public function __construct(private readonly Post $posts = new Post())
    {
    }

    /** /2025/05/20/mlm-software-คำตอบการจัดการสต็อก/ (decode แล้ว) */
    public static function permalink(array $post): string
    {
        $at = (string) ($post['published_at'] ?? '');

        if ($at === '') {
            return '/blog/';
        }

        return '/' . date('Y/m/d', (int) strtotime($at)) . '/' . $post['slug'] . '/';
    }

    /** @param array<string, mixed> $row */
    public static function isLive(array $row): bool
    {
        return ($row['status'] ?? '') === 'published'
            && ($row['published_at'] ?? null) !== null
            && strtotime((string) $row['published_at']) <= time();
    }

    /**
     * หาบทความจากส่วนของ URL
     *
     * @return array{post: array<string,mixed>}|array{redirect: string}|null
     *         redirect = slug ถูกแต่วันที่ผิด → 301 ไป URL ที่ถูก (เหมือน WordPress เดิม)
     */
    public function resolve(string $year, string $month, string $day, string $slug, bool $preview): ?array
    {
        $date    = $year . '-' . $month . '-' . $day;
        $visible = array_values(array_filter(
            $this->posts->findBySlug($slug),
            static fn (array $row): bool => $preview || self::isLive($row)
        ));

        if ($visible === []) {
            return null;
        }

        foreach ($visible as $row) {
            $at = (string) ($row['published_at'] ?? '');

            // draft ที่ยังไม่มีวันเผยแพร่ ดูตัวอย่างได้ที่วันที่ใดก็ได้
            if (($at !== '' && date('Y-m-d', (int) strtotime($at)) === $date) || ($at === '' && $preview)) {
                return ['post' => $row];
            }
        }

        // ตัวที่เผยแพร่แล้วมาก่อน — ห้าม redirect ไปหา draft
        usort($visible, static fn (array $a, array $b): int => (int) self::isLive($b) <=> (int) self::isLive($a));
        $target = $visible[0];

        if (($target['published_at'] ?? null) === null) {
            return null;
        }

        return ['redirect' => self::permalink($target)];
    }

    /**
     * @return array{posts: array<int, array<string,mixed>>, total: int, pages: int}
     */
    public function listing(int $page, int $perPage, ?int $categoryId = null): array
    {
        $total = $this->posts->countPublished($categoryId);
        $posts = $this->posts->latestPublished($perPage, ($page - 1) * $perPage, $categoryId);

        return ['posts' => $posts, 'total' => $total, 'pages' => (int) max(1, ceil($total / max(1, $perPage)))];
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit): array
    {
        return $this->posts->latestPublished($limit);
    }

    /** @return array<int, array<string, mixed>> */
    public function sitemapEntries(): array
    {
        return array_values(array_filter(
            $this->posts->indexable(),
            static fn (array $row): bool => (string) ($row['canonical_override'] ?? '') === ''
        ));
    }

    /** @return array<int, array{category_id:int, total:int, last_updated:string}> */
    public function statsByCategory(): array
    {
        $out = [];

        foreach ($this->posts->statsByCategory() as $row) {
            $out[(int) $row['category_id']] = $row;
        }

        return $out;
    }

    // ------------------------------------------------------------- CMS (Phase 3 / 5)

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];
        $slug   = (string) ($data['slug'] ?? '');

        // slug ห้ามมี / หรือช่องว่าง — เป็นส่วนเดียวของ URL
        if ($slug === '' || preg_match('#[/\s?#]#u', $slug) === 1) {
            $errors['slug'] = 'cms.post.error_slug';
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'cms.post.error_title';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Post::STATUSES, true)) {
            $errors['status'] = 'cms.post.error_status';
        }
        if (($data['status'] ?? '') === 'published' && (string) ($data['published_at'] ?? '') === '') {
            $errors['published_at'] = 'cms.post.error_published_at';
        }
        if (!in_array((string) ($data['robots'] ?? 'index'), Post::ROBOTS, true)) {
            $errors['robots'] = 'cms.post.error_robots';
        }

        return $errors;
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->posts->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->posts->find($id);
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        return $this->posts->countBy('status');
    }

    /**
     * ตรวจเต็มของ CMS — กฎพื้นฐาน + slug+วันที่ไม่ชนบทความอื่น (URL ซ้ำ)
     *
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateForCms(array $data, ?int $id): array
    {
        $errors = $this->validate($data);

        if (($data['published_at'] ?? null) !== null && strtotime((string) $data['published_at']) === false) {
            $errors['published_at'] = 'cms.post.error_published_at';
        }

        if (!isset($errors['slug'])) {
            $date = ($data['published_at'] ?? null) !== null ? date('Y-m-d', (int) strtotime((string) $data['published_at'])) : null;

            foreach ($this->posts->findBySlug((string) $data['slug']) as $row) {
                $rowDate = $row['published_at'] !== null ? date('Y-m-d', (int) strtotime((string) $row['published_at'])) : null;

                if ((int) $row['id'] !== $id && $rowDate === $date
                    && (string) ($row['locale'] ?? TranslationService::default()) === (string) ($data['locale'] ?? ($row['locale'] ?? TranslationService::default()))) {
                    $errors['slug'] = 'cms.post.error_slug_taken';
                }
            }
        }

        return $errors;
    }

    /**
     * ‼️ เนื้อหาผ่าน HtmlSanitizer ก่อนบันทึกเสมอ (allowlist · ตัด script/on* · h1 → h2)
     *
     * @param  array<string, mixed> $data
     * @return array{id:int, redirect:?string}
     */
    public function save(?int $id, array $data, bool $redirectOldUrl): array
    {
        $data['content'] = (new HtmlSanitizer())->clean((string) ($data['content'] ?? ''));

        if ($id === null) {
            return ['id' => $this->create($data), 'redirect' => null];
        }

        $before = $this->posts->find($id);
        $this->update($id, $data);

        if ($before === null || !$redirectOldUrl || !self::isLive($before)) {
            return ['id' => $id, 'redirect' => null];
        }

        $after = $this->posts->find($id) ?? $before;
        $old   = self::permalink($before);
        $new   = self::permalink($after);

        if ($old === $new || ($after['published_at'] ?? null) === null) {
            return ['id' => $id, 'redirect' => null];
        }

        (new RedirectService())->createForMove($old, $new);

        return ['id' => $id, 'redirect' => $old];
    }

    /** ย้อนเนื้อหาไป revision — slug / status / published_at ไม่ถูกย้อน (ไม่เปลี่ยน URL) */
    public function restore(int $id, int $revisionId): bool
    {
        $revision = (new Revision())->find($revisionId);

        if ($revision === null || $revision['entity'] !== 'post' || (int) $revision['entity_id'] !== $id) {
            return false;
        }

        $snapshot = json_decode((string) $revision['data'], true);

        if (!is_array($snapshot)) {
            return false;
        }

        return $this->update($id, array_intersect_key($snapshot, array_flip([
            'title', 'excerpt', 'content', 'featured_image_id', 'category_id', 'author_name',
            'seo_title', 'meta_description', 'canonical_override', 'robots', 'og_image_id',
        ])));
    }

    /**
     * คำแปลของบทความ — slug เดิม · ภาษาใหม่ · กลุ่มเดียวกัน · draft
     *
     * @return array{id:int, error:?string}
     */
    public function translate(int $id, string $locale): array
    {
        if (!TranslationService::enabled('posts') || !in_array($locale, TranslationService::locales(), true)) {
            return ['id' => 0, 'error' => 'cms.translation.error_unavailable'];
        }

        $source = $this->posts->find($id);

        if ($source === null) {
            return ['id' => 0, 'error' => 'cms.translation.error_unavailable'];
        }

        $group    = TranslationService::ensureGroup('posts', $source);
        $siblings = TranslationService::siblings('posts', $group);

        if (isset($siblings[$locale]) || ($source['locale'] ?? TranslationService::default()) === $locale) {
            return ['id' => (int) ($siblings[$locale]['id'] ?? $id), 'error' => 'cms.translation.error_exists'];
        }

        try {
            return $this->cloneInto($source, $group, $locale, $id);
        } catch (\PDOException $e) {
            // unique key ของ posts (slug + วันที่) ยังไม่รวม locale ในบางฐานข้อมูล — แจ้งให้เปลี่ยน slug แทนการล้ม
            if ((string) $e->getCode() === '23000') {
                return ['id' => 0, 'error' => 'cms.translation.error_conflict'];
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed> $source
     * @return array{id:int, error:?string}
     */
    private function cloneInto(array $source, string $group, string $locale, int $id): array
    {
        return (array) Database::transaction(function () use ($source, $group, $locale, $id): array {
            if ((string) ($source['translation_group'] ?? '') === '') {
                $this->posts->update($id, ['translation_group' => $group]);
            }

            $copy = array_intersect_key($source, array_flip([
                'slug', 'title', 'excerpt', 'content', 'featured_image_id', 'category_id', 'author_name',
                'seo_title', 'meta_description', 'robots', 'og_image_id',
            ]));

            // published_at คงไว้ให้ URL วันที่ตรงกับต้นฉบับ แต่ draft จนกว่าจะแปลเสร็จ
            $newId = $this->posts->create($copy + [
                'status' => 'draft', 'published_at' => $source['published_at'], 'locale' => $locale, 'translation_group' => $group,
            ]);

            return ['id' => $newId, 'error' => null];
        });
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->posts->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return (bool) Database::transaction(function () use ($id, $data): bool {
            $before = $this->posts->find($id);

            if ($before === null) {
                return false;
            }

            (new Revision())->create([
                'entity'    => 'post',
                'entity_id' => $id,
                'data'      => (string) json_encode($before, JSON_UNESCAPED_UNICODE),
                'user_id'   => Auth::id(),
            ]);

            return $this->posts->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        return $this->posts->delete($id);
    }
}
