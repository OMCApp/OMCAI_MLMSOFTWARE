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
            $errors['slug'] = 'category.error_slug';
        }
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'category.error_name';
        }
        if (!in_array((string) ($data['robots'] ?? 'index'), Category::ROBOTS, true)) {
            $errors['robots'] = 'category.error_robots';
        }

        return $errors;
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
