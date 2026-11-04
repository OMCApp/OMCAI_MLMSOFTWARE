<?php
/**
 * หมวดหมู่บทความ — /category/{slug}/
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;

final class CategoryService
{
    public function __construct(private readonly Category $categories = new Category())
    {
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        return $this->categories->findBySlug($slug);
    }

    /** @return array<string, mixed>|null */
    public function find(?int $id): ?array
    {
        return $id === null ? null : $this->categories->find($id);
    }

    /** @return array<int, string> id => ชื่อ — ดึงทีเดียวแทนการหาทีละบทความ */
    public function names(): array
    {
        $out = [];

        foreach ($this->categories->allLive() as $row) {
            $out[(int) $row['id']] = (string) $row['name'];
        }

        return $out;
    }

    public static function path(array $category): string
    {
        return '/category/' . $category['slug'] . '/';
    }

    /**
     * หมวดที่มีบทความเผยแพร่แล้วอย่างน้อยหนึ่งเรื่องและ index ได้ — sitemap
     * lastmod = บทความที่แก้ล่าสุดในหมวด (หน้าหมวดเปลี่ยนเมื่อบทความเปลี่ยน)
     *
     * @param  array<int, array<string, mixed>> $stats category_id => [total, last_updated]
     * @return array<int, array{path:string, lastmod:string}>
     */
    public function sitemapEntries(array $stats): array
    {
        $out = [];

        foreach ($this->categories->allLive() as $category) {
            $id = (int) $category['id'];

            if ($category['robots'] !== 'index' || (int) ($stats[$id]['total'] ?? 0) === 0) {
                continue;
            }

            $out[] = [
                'path'    => self::path($category),
                'lastmod' => max((string) $category['updated_at'], (string) $stats[$id]['last_updated']),
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (preg_match('#^[^/\s?#]+$#u', (string) ($data['slug'] ?? '')) !== 1) {
            $errors['slug'] = 'cms.category.error_slug';
        }
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'cms.category.error_name';
        }
        if (!in_array((string) ($data['robots'] ?? 'index'), Category::ROBOTS, true)) {
            $errors['robots'] = 'cms.category.error_robots';
        }

        return $errors;
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->categories->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateForCms(array $data, ?int $id): array
    {
        $errors = $this->validate($data);

        if (!isset($errors['slug'])) {
            $existing = $this->categories->findBySlug((string) $data['slug']);

            if ($existing !== null && (int) $existing['id'] !== $id
                && (string) ($existing['locale'] ?? TranslationService::default()) === (string) ($data['locale'] ?? ($existing['locale'] ?? TranslationService::default()))) {
                $errors['slug'] = 'cms.category.error_slug_taken';
            }
        }

        return $errors;
    }

    /** ลบได้เฉพาะหมวดที่ไม่มีบทความ — ไม่งั้นบทความหลุดหมวดและ URL หมวดเดิม 404 */
    public function postCount(int $id): int
    {
        return $this->categories->postCount($id);
    }

    /**
     * คำแปลของหมวด — slug เดิม · ภาษาใหม่ · กลุ่มเดียวกัน
     *
     * @return array{id:int, error:?string}
     */
    public function translate(int $id, string $locale): array
    {
        if (!TranslationService::enabled('categories') || !in_array($locale, TranslationService::locales(), true)) {
            return ['id' => 0, 'error' => 'cms.translation.error_unavailable'];
        }

        $source = $this->categories->find($id);

        if ($source === null) {
            return ['id' => 0, 'error' => 'cms.translation.error_unavailable'];
        }

        $group    = TranslationService::ensureGroup('categories', $source);
        $siblings = TranslationService::siblings('categories', $group);

        if (isset($siblings[$locale]) || ($source['locale'] ?? TranslationService::default()) === $locale) {
            return ['id' => (int) ($siblings[$locale]['id'] ?? $id), 'error' => 'cms.translation.error_exists'];
        }

        try {
            return (array) \Core\Database::transaction(function () use ($source, $group, $locale, $id): array {
                if ((string) ($source['translation_group'] ?? '') === '') {
                    $this->categories->update($id, ['translation_group' => $group]);
                }

                $newId = $this->categories->create(array_intersect_key($source, array_flip([
                    'slug', 'name', 'description', 'seo_title', 'meta_description', 'robots', 'sort',
                ])) + ['locale' => $locale, 'translation_group' => $group]);

                return ['id' => $newId, 'error' => null];
            });
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return ['id' => 0, 'error' => 'cms.translation.error_conflict'];
            }

            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->categories->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return $this->categories->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->categories->delete($id);
    }
}
