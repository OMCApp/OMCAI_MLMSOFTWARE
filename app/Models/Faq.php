<?php
/**
 * คำถามที่พบบ่อย — แบ่งกลุ่มด้วย group_key ให้บล็อก FAQ แต่ละหน้าเลือกกลุ่มได้
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Faq extends Model
{
    protected string $table = 'faqs';

    protected array $fillable = ['question', 'answer', 'group_key', 'sort', 'status'];

    public const STATUSES = ['draft', 'published'];

    /** @return array<int, array<string, mixed>> */
    public function publishedInGroup(string $group): array
    {
        return Database::select(
            "SELECT id, question, answer FROM faqs
             WHERE group_key = :group_key AND status = 'published' AND deleted_at IS NULL
             ORDER BY sort, id",
            ['group_key' => $group]
        );
    }
}
