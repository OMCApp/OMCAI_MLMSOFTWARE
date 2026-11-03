<?php
/**
 * ประวัติการใช้งาน — อ่านอย่างเดียว
 *
 * ‼️ ไม่มีเมธอด create/update/delete โดยตั้งใจ
 *    audit log ต้องแก้และลบไม่ได้ แม้แต่แอดมิน
 *    ถ้าแก้ได้ก็ไม่มีความหมาย เพราะคนที่ทำผิดลบร่องรอยตัวเองได้
 *    การเขียนเกิดที่ core/Model.php จุดเดียวเท่านั้น
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;

final class AuditLog
{
    public const SORTABLE = ['id', 'created_at', 'action', 'table_name'];

    /**
     * @param  array<string, mixed> $filters
     * @return array{rows:array<int,array<string,mixed>>,total:int}
     */
    public function search(array $filters, string $sort, string $direction, int $page, int $perPage): array
    {
        $sort      = in_array($sort, self::SORTABLE, true) ? $sort : 'created_at';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $where    = ['1 = 1'];
        $bindings = [];

        foreach (['action' => 'a.action', 'table_name' => 'a.table_name'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $where[]        = $column . ' = :' . $key;
                $bindings[$key] = $filters[$key];
            }
        }

        if (($filters['user_id'] ?? '') !== '') {
            $where[]             = 'a.user_id = :user_id';
            $bindings['user_id'] = (int) $filters['user_id'];
        }

        // ‼️ ตัวกรองช่วงวันรับค่าเป็นคริสต์ศักราชเสมอ แม้จะแสดงเป็น พ.ศ. ให้ผู้ใช้เห็น
        if (($filters['from'] ?? '') !== '') {
            $where[]          = 'a.created_at >= :from';
            $bindings['from'] = $filters['from'] . ' 00:00:00';
        }
        if (($filters['to'] ?? '') !== '') {
            $where[]        = 'a.created_at <= :to';
            $bindings['to'] = $filters['to'] . ' 23:59:59';
        }

        $clause = ' WHERE ' . implode(' AND ', $where);

        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS total FROM audit_logs a' . $clause,
            $bindings
        )['total'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $rows = Database::select(
            'SELECT a.*, u.display_name AS actor
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id'
            . $clause
            . ' ORDER BY a.' . $sort . ' ' . $direction
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $bindings
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return array<int, string> */
    public function actions(): array
    {
        $rows = Database::select('SELECT DISTINCT action FROM audit_logs ORDER BY action');

        return array_column($rows, 'action');
    }
}
