<?php
/**
 * หน้าของเว็บสาธารณะ — URL มาจาก pages.path ตรงตัว (decode แล้ว ลงท้าย /)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class Page extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['title', 'path', 'seo_title'];
    protected array $filterColumns = ['status', 'locale', 'template'];

    public const SORTABLE = ['id', 'title', 'path', 'status', 'updated_at', 'published_at'];

    protected string $table = 'pages';

    protected array $fillable = [
        'parent_id', 'path', 'title', 'template', 'blocks', 'seo_title', 'meta_description',
        'canonical_override', 'robots', 'og_image_id', 'status', 'published_at', 'sort',
    ];

    /**
     * locale / translation_group มาจาก migration ของ Phase 4 — เขียนได้เฉพาะเมื่อมีคอลัมน์แล้ว
     * (allowlist ของ base Model ใส่คอลัมน์ที่ยังไม่มีไม่ได้ ไม่งั้น INSERT พัง)
     */
    public function __construct()
    {
        foreach (['locale', 'translation_group'] as $column) {
            if (\Core\Database::hasColumn($this->table, $column)) {
                $this->fillable[] = $column;
            }
        }
    }
    public const STATUSES = ['draft', 'published'];
    public const ROBOTS   = ['index', 'noindex'];

    /** @return array<string, mixed>|null */
    public function findByPath(string $path): ?array
    {
        return Database::selectOne(
            'SELECT * FROM pages WHERE path = :path AND deleted_at IS NULL LIMIT 1',
            ['path' => $path]
        );
    }

    /**
     * ทุกหน้าแบบย่อ — ใช้หาเส้นทาง breadcrumb จาก parent_id โดยไม่ query ทีละชั้น
     *
     * @return array<int, array<string, mixed>>
     */
    public function outline(): array
    {
        return Database::select(
            'SELECT id, parent_id, path, title, status FROM pages WHERE deleted_at IS NULL'
        );
    }

    /**
     * หน้าที่เผยแพร่แล้วและให้ index ได้ — sitemap
     *
     * @return array<int, array<string, mixed>>
     */
    public function indexable(): array
    {
        return Database::select(
            "SELECT id, path, template, canonical_override, updated_at FROM pages
             WHERE status = 'published' AND robots = 'index' AND deleted_at IS NULL
               AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY sort, id"
        );
    }
}
