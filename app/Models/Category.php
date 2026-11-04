<?php
/**
 * หมวดหมู่บทความ — URL /category/{slug}/ (slug ภาษาไทยเก็บแบบ decode)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class Category extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['name', 'slug'];
    protected array $filterColumns = ['locale', 'robots'];

    public const SORTABLE = ['id', 'name', 'slug', 'sort', 'updated_at'];

    protected string $table = 'categories';

    protected array $fillable = [
        'slug', 'name', 'description', 'seo_title', 'meta_description', 'robots', 'sort', 'locale', 'translation_group',
    ];

    public const ROBOTS = ['index', 'noindex'];

    /** @return array<string, mixed>|null หมวดของภาษานี้ (URL /category/x/ = th · /en/category/x/ = en) */
    public function findBySlug(string $slug, string $locale = 'th'): ?array
    {
        return Database::selectOne(
            'SELECT * FROM categories WHERE slug = :slug AND locale = :locale AND deleted_at IS NULL LIMIT 1',
            ['slug' => $slug, 'locale' => $locale]
        );
    }

    public function postCount(int $id): int
    {
        return (int) (Database::selectOne(
            'SELECT COUNT(*) AS n FROM posts WHERE category_id = :id AND deleted_at IS NULL',
            ['id' => $id]
        )['n'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function allLive(): array
    {
        return Database::select('SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY sort, id');
    }
}
