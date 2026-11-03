<?php
/**
 * หมวดหมู่บทความ — URL /category/{slug}/ (slug ภาษาไทยเก็บแบบ decode)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Category extends Model
{
    protected string $table = 'categories';

    protected array $fillable = ['slug', 'name', 'description', 'seo_title', 'meta_description', 'robots', 'sort'];

    public const ROBOTS = ['index', 'noindex'];

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        return Database::selectOne(
            'SELECT * FROM categories WHERE slug = :slug AND deleted_at IS NULL LIMIT 1',
            ['slug' => $slug]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function allLive(): array
    {
        return Database::select('SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY sort, id');
    }
}
