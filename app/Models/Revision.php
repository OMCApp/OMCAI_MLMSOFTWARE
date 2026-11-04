<?php
/**
 * ประวัติของหน้า/บทความ — เก็บสำเนาก่อนบันทึกทุกครั้ง ให้ย้อนกลับได้
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Revision extends Model
{
    protected string $table = 'revisions';

    protected array $fillable = ['entity', 'entity_id', 'data', 'user_id'];

    public const ENTITIES = ['page', 'post'];

    /** @return array<int, array<string, mixed>> */
    public function forEntity(string $entity, int $entityId, int $limit = 50): array
    {
        return Database::select(
            'SELECT r.id, r.entity, r.entity_id, r.user_id, r.created_at, u.display_name AS author FROM revisions r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.entity = :entity AND r.entity_id = :entity_id AND r.deleted_at IS NULL
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT ' . max(1, min($limit, 200)),
            ['entity' => $entity, 'entity_id' => $entityId]
        );
    }
}
