<?php
/**
 * รายการของหน้าจัดการ (CMS) — ค้นข้อความ + กรองเท่ากับ + เรียง + แบ่งหน้า ที่เดียวทุกตาราง
 *
 * Model ที่ใช้ต้องประกาศ
 *   protected array $searchColumns   คอลัมน์ที่ค้นด้วยข้อความ (LIKE)
 *   protected array $filterColumns   คอลัมน์ที่กรองแบบเท่ากับ
 *   public const SORTABLE            คอลัมน์ที่ยอมให้เรียง
 *
 * ‼️ ชื่อคอลัมน์มาจาก allowlist ของ Model เท่านั้น — ค่าจากผู้ใช้ผูก placeholder ทุกตัว
 * ‼️ คอลัมน์ที่ phase อื่นเพิ่มทีหลัง (locale) ถูกข้ามเงียบถ้ายังไม่มีในตาราง
 */

declare(strict_types=1);

namespace App\Models\Concerns;

use Core\Database;

trait AdminSearch
{
    /**
     * @param  array<string, string> $filters คอลัมน์ => ค่า (ค่าว่าง = ไม่กรอง)
     * @return array{rows:array<int,array<string,mixed>>,total:int}
     */
    public function adminSearch(array $filters, string $term, string $sort, string $direction, int $page, int $perPage): array
    {
        $table    = $this->table();
        $where    = ['deleted_at IS NULL'];
        $bindings = [];

        foreach ($this->filterColumns as $column) {
            $value = (string) ($filters[$column] ?? '');

            if ($value === '' || !Database::hasColumn($table, $column)) {
                continue;
            }

            $where[]               = $column . ' = :f_' . $column;
            $bindings['f_' . $column] = $value;
        }

        $term = trim($term);

        if ($term !== '' && $this->searchColumns !== []) {
            $like = '%' . addcslashes($term, '\\%_') . '%';
            $or   = [];

            foreach ($this->searchColumns as $index => $column) {
                $or[]                   = $column . ' LIKE :q' . $index;
                $bindings['q' . $index] = $like;
            }

            $where[] = '(' . implode(' OR ', $or) . ')';
        }

        $sort      = in_array($sort, static::SORTABLE, true) ? $sort : 'id';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $perPage   = max(1, min($perPage, 200));
        $clause    = ' WHERE ' . implode(' AND ', $where);

        $total = (int) (Database::selectOne('SELECT COUNT(*) AS total FROM ' . $table . $clause, $bindings)['total'] ?? 0);
        $rows  = Database::select(
            'SELECT * FROM ' . $table . $clause . ' ORDER BY ' . $sort . ' ' . $direction . ', id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . max(0, ($page - 1) * $perPage),
            $bindings
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return array<string, int> ค่าของคอลัมน์ => จำนวนแถว (เช่นนับตาม status) */
    public function countBy(string $column): array
    {
        if (!in_array($column, $this->filterColumns, true)) {
            return [];
        }

        $out = [];

        foreach (Database::select(
            'SELECT ' . $column . ' AS v, COUNT(*) AS n FROM ' . $this->table() . ' WHERE deleted_at IS NULL GROUP BY ' . $column
        ) as $row) {
            $out[(string) $row['v']] = (int) $row['n'];
        }

        return $out;
    }
}
