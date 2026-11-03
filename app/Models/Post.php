<?php
/**
 * บทความ — URL คือ /{yyyy}/{mm}/{dd}/{slug}/ จาก published_at (เวลาท้องถิ่น แบบ WordPress)
 *
 * ‼️ "เผยแพร่แล้ว" = status published และ published_at ถึงเวลาแล้ว
 *    บทความที่ตั้งเวลาไว้ล่วงหน้าต้องยังไม่โผล่ทั้งในหน้า sitemap และ feed
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [
        'slug', 'title', 'excerpt', 'content', 'featured_image_id', 'category_id', 'author_name',
        'status', 'published_at', 'seo_title', 'meta_description', 'canonical_override', 'robots',
        'og_image_id', 'legacy_url',
    ];

    public const STATUSES = ['draft', 'published'];
    public const ROBOTS   = ['index', 'noindex'];

    private const PUBLISHED = "status = 'published' AND deleted_at IS NULL
                               AND published_at IS NOT NULL AND published_at <= NOW()";

    /**
     * ทุกแถวที่ยังไม่ถูกลบที่ใช้ slug นี้ — slug ซ้ำได้ถ้าวันที่ต่างกัน
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBySlug(string $slug): array
    {
        return Database::select(
            'SELECT * FROM posts WHERE slug = :slug AND deleted_at IS NULL ORDER BY published_at DESC, id DESC',
            ['slug' => $slug]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function latestPublished(int $limit, int $offset = 0, ?int $categoryId = null): array
    {
        $limit  = max(1, min($limit, 100));
        $offset = max(0, $offset);

        if ($categoryId === null) {
            return Database::select(
                'SELECT * FROM posts WHERE ' . self::PUBLISHED
                . ' ORDER BY published_at DESC, id DESC'
                . ' LIMIT ' . $limit . ' OFFSET ' . $offset
            );
        }

        return Database::select(
            'SELECT * FROM posts WHERE category_id = :category_id AND ' . self::PUBLISHED
            . ' ORDER BY published_at DESC, id DESC'
            . ' LIMIT ' . $limit . ' OFFSET ' . $offset,
            ['category_id' => $categoryId]
        );
    }

    public function countPublished(?int $categoryId = null): int
    {
        $row = $categoryId === null
            ? Database::selectOne('SELECT COUNT(*) AS total FROM posts WHERE ' . self::PUBLISHED)
            : Database::selectOne(
                'SELECT COUNT(*) AS total FROM posts WHERE category_id = :category_id AND ' . self::PUBLISHED,
                ['category_id' => $categoryId]
            );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * บทความที่ index ได้ — sitemap
     *
     * @return array<int, array<string, mixed>>
     */
    public function indexable(): array
    {
        return Database::select(
            "SELECT id, slug, published_at, canonical_override, updated_at FROM posts
             WHERE robots = 'index' AND " . self::PUBLISHED . '
             ORDER BY published_at DESC, id DESC'
        );
    }

    /**
     * จำนวนบทความที่เผยแพร่แล้วและวันแก้ล่าสุดของแต่ละหมวด
     *
     * @return array<int, array{category_id:int, total:int, last_updated:string}>
     */
    public function statsByCategory(): array
    {
        return Database::select(
            'SELECT category_id, COUNT(*) AS total, MAX(updated_at) AS last_updated FROM posts
             WHERE category_id IS NOT NULL AND ' . self::PUBLISHED . '
             GROUP BY category_id'
        );
    }
}
