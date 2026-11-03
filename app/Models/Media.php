<?php
/**
 * ไฟล์สื่อ — path อ้างจาก public/ (รูปเดิมของ WordPress อยู่ที่ /wp-content/uploads/<path เดิม>)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Media extends Model
{
    protected string $table = 'media';

    protected array $fillable = ['path', 'mime', 'width', 'height', 'alt', 'title', 'sizes', 'bytes'];

    /**
     * @param  array<int, int> $ids
     * @return array<int, array<string, mixed>> id => แถว
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));

        if ($ids === []) {
            return [];
        }

        // placeholder ทีละตัว (:m0, :m1 ...) — ห้ามต่อค่าเข้า SQL
        $marks    = [];
        $bindings = [];

        foreach ($ids as $index => $id) {
            $marks[]               = ':m' . $index;
            $bindings['m' . $index] = $id;
        }

        $rows = Database::select(
            'SELECT * FROM media WHERE deleted_at IS NULL AND id IN (' . implode(', ', $marks) . ')',
            $bindings
        );

        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row['id']] = $row;
        }

        return $out;
    }
}
