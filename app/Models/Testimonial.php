<?php
/**
 * คำรับรองจากลูกค้า — ‼️ published ได้เฉพาะของจริงที่ได้รับอนุญาต (QUESTIONS.md)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class Testimonial extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['person_name', 'company', 'quote'];
    protected array $filterColumns = ['status'];

    public const SORTABLE = ['id', 'sort', 'person_name', 'status', 'updated_at'];

    protected string $table = 'testimonials';

    protected array $fillable = [
        'person_name', 'person_title', 'company', 'quote', 'photo_id', 'consent_note', 'consent_confirmed_at', 'consent_confirmed_by', 'status', 'sort',
    ];

    public const STATUSES = ['draft', 'published'];

    /** @return array<int, array<string, mixed>> */
    public function published(): array
    {
        return Database::select(
            "SELECT * FROM testimonials WHERE status = 'published' AND consent_confirmed_at IS NOT NULL AND deleted_at IS NULL ORDER BY sort, id"
        );
    }
}
