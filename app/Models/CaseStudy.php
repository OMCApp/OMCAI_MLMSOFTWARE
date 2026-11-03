<?php
/**
 * ผลงานลูกค้า — ‼️ published ได้เฉพาะของจริง หน้า /portfolio/ แสดงสถานะว่างเมื่อยังไม่มี
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class CaseStudy extends Model
{
    protected string $table = 'case_studies';

    protected array $fillable = [
        'slug', 'title', 'client_name', 'industry', 'summary', 'content', 'cover_image_id', 'results',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description',
    ];

    public const STATUSES = ['draft', 'published'];

    /** @return array<int, array<string, mixed>> */
    public function published(): array
    {
        return Database::select(
            "SELECT * FROM case_studies
             WHERE status = 'published' AND deleted_at IS NULL
               AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY sort, id"
        );
    }
}
