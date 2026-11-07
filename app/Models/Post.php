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
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class Post extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['title', 'slug', 'excerpt'];
    protected array $filterColumns = ['status', 'locale', 'category_id'];

    public const SORTABLE = ['id', 'title', 'status', 'published_at', 'updated_at'];

    protected string $table = 'posts';

    protected array $fillable = [
        'slug', 'title', 'excerpt', 'content', 'featured_image_id', 'category_id', 'author_name',
        'status', 'published_at', 'seo_title', 'meta_description', 'canonical_override', 'robots',
        'og_image_id', 'legacy_url', 'locale', 'translation_group', 'tags',
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
    public function latestPublished(int $limit, int $offset = 0, ?int $categoryId = null, string $locale = 'th'): array
    {
        $limit  = max(1, min($limit, 100));
        $offset = max(0, $offset);

        [$where, $params] = self::scope($categoryId, $locale);

        return Database::select(
            'SELECT * FROM posts WHERE ' . $where . ' AND ' . self::PUBLISHED
            . ' ORDER BY published_at DESC, id DESC'
            . ' LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );
    }

    /**
     * ค้นบทความที่เผยแพร่แล้ว — ‼️ prepared · ตัว % _ \ ในคำค้นถูก escape (ไม่ใช่ wildcard)
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchPublished(string $term, string $locale, int $limit): array
    {
        $like  = '%' . addcslashes($term, '%_\\') . '%';
        $limit = max(1, min($limit, 100));

        return Database::select(
            'SELECT * FROM posts WHERE locale = :locale AND ' . self::PUBLISHED . '
               AND (title LIKE :q1 OR excerpt LIKE :q2 OR content LIKE :q3 OR tags LIKE :q4)
             ORDER BY (title LIKE :q5) DESC, published_at DESC, id DESC'
            . ' LIMIT ' . $limit,
            ['locale' => $locale, 'q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like]
        );
    }

    public function countPublished(?int $categoryId = null, string $locale = 'th'): int
    {
        [$where, $params] = self::scope($categoryId, $locale);

        $row = Database::selectOne('SELECT COUNT(*) AS total FROM posts WHERE ' . $where . ' AND ' . self::PUBLISHED, $params);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * บทความเผยแพร่แล้วในหมวดเดียวกัน (ไม่รวมตัวเอง) — "บทความที่เกี่ยวข้อง" ท้ายบทความ
     *
     * @return array<int, array<string, mixed>>
     */
    public function relatedPublished(int $postId, int $categoryId, int $limit): array
    {
        $limit = max(1, min($limit, 12));

        return Database::select(
            'SELECT * FROM posts WHERE category_id = :category_id AND id <> :id AND ' . self::PUBLISHED
            . ' ORDER BY published_at DESC, id DESC'
            . ' LIMIT ' . $limit,
            ['category_id' => $categoryId, 'id' => $postId]
        );
    }

    /** @return array{0:string, 1:array<string, mixed>} เงื่อนไขภาษา + หมวด (prepared) */
    private static function scope(?int $categoryId, string $locale): array
    {
        $where  = 'locale = :locale';
        $params = ['locale' => $locale];

        if ($categoryId !== null) {
            $where .= ' AND category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        return [$where, $params];
    }

    /**
     * บทความที่ index ได้ — sitemap
     *
     * @return array<int, array<string, mixed>>
     */
    public function indexable(): array
    {
        return Database::select(
            "SELECT id, slug, locale, translation_group, published_at, canonical_override, updated_at FROM posts
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
