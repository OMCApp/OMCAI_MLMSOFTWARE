<?php
/**
 * โน้ตภายในของ lead — ‼️ เพิ่มได้อย่างเดียว (append-only) ไม่มีแก้/ลบ
 *    ประวัติการติดต่อลูกค้าต้องย้อนดูได้ครบ ใครเขียนอะไรเมื่อไหร่ · การเพิ่มลง audit log ผ่าน base Model
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class LeadNote extends Model
{
    protected string $table = 'lead_notes';

    protected array $fillable = ['lead_id', 'user_id', 'body'];

    protected bool $softDelete = false;

    /** @return array<int, array<string, mixed>> */
    public function forLead(int $leadId): array
    {
        return Database::select(
            'SELECT n.id, n.body, n.created_at, u.display_name AS author
             FROM lead_notes n LEFT JOIN users u ON u.id = n.user_id
             WHERE n.lead_id = :id ORDER BY n.created_at DESC, n.id DESC',
            ['id' => $leadId]
        );
    }
}
