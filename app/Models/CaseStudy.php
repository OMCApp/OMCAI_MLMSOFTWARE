<?php
/**
 * ผลงานลูกค้า — ‼️ published ได้เฉพาะของจริง หน้า /portfolio/ แสดงสถานะว่างเมื่อยังไม่มี
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class CaseStudy extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['title', 'client_name', 'slug'];
    protected array $filterColumns = ['status'];

    public const SORTABLE = ['id', 'sort', 'title', 'status', 'updated_at'];

    protected string $table = 'case_studies';

    protected array $fillable = [
        'slug', 'title', 'client_name', 'industry', 'summary', 'content', 'cover_image_id', 'results', 'consent_confirmed_at', 'consent_confirmed_by',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description',
    ];

    public const STATUSES = ['draft', 'published'];

    public function slugTaken(string $slug, ?int $exceptId): bool
    {
        $sql    = 'SELECT id FROM case_studies WHERE slug = :slug AND deleted_at IS NULL';
        $params = ['slug' => $slug];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except';
            $params['except'] = $exceptId;
        }

        return Database::selectOne($sql . ' LIMIT 1', $params) !== null;
    }

    /** @return array<int, array<string, mixed>> */
    public function published(): array
    {
        return Database::select(
            "SELECT * FROM case_studies
             WHERE status = 'published' AND consent_confirmed_at IS NOT NULL AND deleted_at IS NULL
               AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY sort, id"
        );
    }
}
