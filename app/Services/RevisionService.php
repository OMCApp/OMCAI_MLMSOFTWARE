<?php
/**
 * ประวัติของหน้า/บทความ — PageService/PostService เขียนให้อัตโนมัติก่อนแก้ทุกครั้ง
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Revision;

final class RevisionService
{
    public function __construct(private readonly Revision $revisions = new Revision())
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function history(string $entity, int $entityId): array
    {
        if (!in_array($entity, Revision::ENTITIES, true)) {
            return [];
        }

        return $this->revisions->forEntity($entity, $entityId);
    }

    /** @return array<string, mixed>|null สำเนาของข้อมูลในรุ่นนั้น */
    public function snapshot(int $revisionId): ?array
    {
        $row = $this->revisions->find($revisionId);

        if ($row === null) {
            return null;
        }

        $data = json_decode((string) $row['data'], true);

        return is_array($data) ? $data : null;
    }
}
