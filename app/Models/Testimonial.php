<?php
/**
 * คำรับรองจากลูกค้า — ‼️ published ได้เฉพาะของจริงที่ได้รับอนุญาต (QUESTIONS.md)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Testimonial extends Model
{
    protected string $table = 'testimonials';

    protected array $fillable = [
        'person_name', 'person_title', 'company', 'quote', 'photo_id', 'consent_note', 'status', 'sort',
    ];

    public const STATUSES = ['draft', 'published'];

    /** @return array<int, array<string, mixed>> */
    public function published(): array
    {
        return Database::select(
            "SELECT * FROM testimonials WHERE status = 'published' AND deleted_at IS NULL ORDER BY sort, id"
        );
    }
}
