<?php
/**
 * base Model — ‼️ จุดที่เขียน audit log อัตโนมัติ
 *
 * เหตุผลที่ audit log ต้องอยู่ที่นี่ ไม่ใช่ไล่เขียนทีละ Controller
 *   · ไล่เขียนเองจะลืมบางจุดแน่นอน ทำให้ log ไม่ครบและเชื่อถือไม่ได้ทั้งระบบ
 *   · ประวัติที่ผ่านมากู้คืนไม่ได้ — log ที่เพิ่มวันนี้ตอบคำถามเรื่องเมื่อวานไม่ได้
 *
 * ‼️ ถ้าเขียนข้อมูลด้วยวิธีที่ไม่ผ่านคลาสนี้ (query ตรง หรือ bulk insert)
 *    ต้องเรียก Model::audit() เองที่จุดนั้น ห้ามปล่อยผ่าน
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

abstract class Model
{
    /** ชื่อตารางในฐานข้อมูล */
    protected string $table = '';

    /** คอลัมน์ที่เขียนได้ — ทำหน้าที่เป็น allowlist กันคอลัมน์แปลกปลอมด้วย */
    protected array $fillable = [];

    /** คอลัมน์ที่ห้ามให้ค่าจริงโผล่ใน audit log */
    protected array $hidden = ['password_hash', 'token', 'remember_token'];

    /** ตารางนี้ใช้ soft delete ไหม */
    protected bool $softDelete = true;

    public function table(): string
    {
        if ($this->table === '') {
            throw new RuntimeException(static::class . ' must define $table');
        }

        return $this->table;
    }

    // ------------------------------------------------------------------ อ่าน

    /** @return array<string, mixed>|null */
    public function find(int $id, bool $withTrashed = false): ?array
    {
        $sql = 'SELECT * FROM ' . $this->table() . ' WHERE id = :id'
            . $this->trashedClause($withTrashed) . ' LIMIT 1';

        return Database::selectOne($sql, ['id' => $id]);
    }

    /**
     * ดึงรายการพร้อมกรอง เรียง และแบ่งหน้า
     *
     * @param  array<string, mixed> $where     คอลัมน์ => ค่า (คอลัมน์ต้องอยู่ใน allowlist)
     * @param  array<int, string>   $sortable  คอลัมน์ที่ยอมให้เรียง
     * @return array{rows:array<int,array<string,mixed>>,total:int}
     */
    public function paginate(
        array $where = [],
        string $sort = 'id',
        string $direction = 'desc',
        int $page = 1,
        int $perPage = 25,
        array $sortable = ['id'],
    ): array {
        // ‼️ ชื่อคอลัมน์และทิศทางผูก placeholder ไม่ได้ จึงต้องผ่าน allowlist ก่อนเสมอ
        //    จุดนี้คือช่องโหว่ที่หลุดบ่อยที่สุดในหน้าที่มีการเรียงลำดับ
        $sort      = in_array($sort, $sortable, true) ? $sort : 'id';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        [$clause, $bindings] = $this->buildWhere($where);

        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS total FROM ' . $this->table() . $clause,
            $bindings
        )['total'] ?? 0);

        $perPage = max(1, min($perPage, 200));
        $offset  = ($page - 1) * $perPage;

        $rows = Database::select(
            'SELECT * FROM ' . $this->table() . $clause
            . ' ORDER BY ' . $sort . ' ' . $direction
            . ' LIMIT ' . $perPage . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param  array<string, mixed> $where
     * @return array{0:string,1:array<string,mixed>}
     */
    protected function buildWhere(array $where): array
    {
        $parts    = [];
        $bindings = [];

        foreach ($where as $column => $value) {
            if (!in_array($column, $this->fillable, true) && $column !== 'id') {
                continue; // คอลัมน์นอก allowlist ถูกทิ้งเงียบ
            }

            $parts[]           = $column . ' = :w_' . $column;
            $bindings['w_' . $column] = $value;
        }

        if ($this->softDelete) {
            $parts[] = 'deleted_at IS NULL';
        }

        return [$parts === [] ? '' : ' WHERE ' . implode(' AND ', $parts), $bindings];
    }

    protected function trashedClause(bool $withTrashed): string
    {
        return ($this->softDelete && !$withTrashed) ? ' AND deleted_at IS NULL' : '';
    }

    // ------------------------------------------------------------------ เขียน

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $data = $this->onlyFillable($data);

        if ($data === []) {
            throw new RuntimeException('Nothing to insert for ' . $this->table());
        }

        $columns = array_keys($data);
        $sql     = 'INSERT INTO ' . $this->table()
            . ' (' . implode(', ', $columns) . ', created_at, updated_at)'
            . ' VALUES (:' . implode(', :', $columns) . ', NOW(), NOW())';

        Database::run($sql, $data);

        $id = (int) Database::pdo()->lastInsertId();

        self::audit('create', $this->table(), $id, null, $this->mask($data));

        return $id;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        $data = $this->onlyFillable($data);

        if ($data === []) {
            return false;
        }

        $before = $this->find($id);

        if ($before === null) {
            return false;
        }

        // ‼️ เก็บเฉพาะฟิลด์ที่ค่าต่างกันจริง ไม่ใช่ทั้งแถว
        //    เก็บทั้งแถวแล้วจะดูไม่ออกว่าอะไรเปลี่ยน
        $old = [];
        $new = [];

        foreach ($data as $column => $value) {
            if ((string) ($before[$column] ?? '') === (string) $value) {
                continue;
            }

            $old[$column] = $before[$column] ?? null;
            $new[$column] = $value;
        }

        if ($new === []) {
            return true; // ไม่มีอะไรเปลี่ยน ไม่ต้องบันทึก log ให้ตารางบวม
        }

        $sets = [];
        foreach (array_keys($new) as $column) {
            $sets[] = $column . ' = :' . $column;
        }

        Database::run(
            'UPDATE ' . $this->table() . ' SET ' . implode(', ', $sets)
            . ', updated_at = NOW() WHERE id = :id',
            $new + ['id' => $id]
        );

        self::audit('update', $this->table(), $id, $this->mask($old), $this->mask($new));

        return true;
    }

    /** soft delete เป็นค่าเริ่มต้น — ลบจริงแล้ว audit log จะชี้ไปหาของที่ไม่มีอยู่ */
    public function delete(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }

        if ($this->softDelete) {
            Database::run(
                'UPDATE ' . $this->table() . ' SET deleted_at = NOW() WHERE id = :id',
                ['id' => $id]
            );
        } else {
            Database::run('DELETE FROM ' . $this->table() . ' WHERE id = :id', ['id' => $id]);
        }

        self::audit('delete', $this->table(), $id, null, null);

        return true;
    }

    public function restore(int $id): bool
    {
        if (!$this->softDelete) {
            return false;
        }

        Database::run(
            'UPDATE ' . $this->table() . ' SET deleted_at = NULL WHERE id = :id',
            ['id' => $id]
        );

        self::audit('restore', $this->table(), $id, null, null);

        return true;
    }

    // ------------------------------------------------------------- audit log

    /**
     * ‼️ user_id ต้องมาจาก Auth::id() เท่านั้น
     *    ห้ามรับเป็นพารามิเตอร์จาก Controller เพราะจะเผลอส่งผิดคนได้
     *
     * @param array<string, mixed>|null $old
     * @param array<string, mixed>|null $new
     */
    public static function audit(
        string $action,
        string $table,
        ?int $recordId,
        ?array $old = null,
        ?array $new = null,
    ): void {
        try {
            Database::run(
                'INSERT INTO audit_logs
                    (user_id, action, table_name, record_id, old_values, new_values, ip_address, created_at)
                 VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip, NOW())',
                [
                    'user_id'    => Auth::id(),
                    'action'     => $action,
                    'table_name' => $table,
                    'record_id'  => $recordId,
                    'old_values' => $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
                    'new_values' => $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
                    'ip'         => Auth::ip(),
                ]
            );
        } catch (\Throwable $e) {
            // audit log ล้มเหลวห้ามทำให้งานหลักพัง แต่ต้องรู้ว่ามันล้มเหลว
            Logger::error('audit_log_failed', [
                'table'  => $table,
                'action' => $action,
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * ‼️ ห้ามบันทึกรหัสผ่านหรือ token ลง audit log
     *    ถ้าฟิลด์ที่เปลี่ยนคือรหัสผ่าน ให้บันทึกแค่ว่ามีการเปลี่ยน ไม่เก็บค่า
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mask(array $data): array
    {
        foreach (array_keys($data) as $column) {
            if (in_array($column, $this->hidden, true)) {
                $data[$column] = '[changed]';
            }
        }

        return $data;
    }
}
