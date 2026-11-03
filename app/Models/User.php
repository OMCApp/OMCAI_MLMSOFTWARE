<?php
/**
 * ผู้ใช้ระบบ — CRUD อย่างเดียว ไม่มี business logic ปน
 * audit log มาเองจาก base Model
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'username', 'display_name', 'email', 'password_hash',
        'role', 'locale', 'theme', 'is_active',
    ];

    /** คอลัมน์ที่ยอมให้เรียงลำดับ — ผูก placeholder ไม่ได้จึงต้องมี allowlist */
    public const SORTABLE = ['id', 'username', 'display_name', 'role', 'created_at'];

    /** @return array<string, mixed>|null */
    public function findByUsername(string $username): ?array
    {
        return Database::selectOne(
            'SELECT * FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1',
            ['username' => $username]
        );
    }

    public function usernameTaken(string $username, ?int $exceptId = null): bool
    {
        /* ‼️ ห้ามใช้ placeholder ชื่อเดียวกันซ้ำใน query เดียว
           เดิมเขียนว่า `(:except IS NULL OR id <> :except)` ซึ่งพังทุกครั้งที่เพิ่ม/แก้ผู้ใช้
           เพราะ ATTR_EMULATE_PREPARES = false ทำให้ MySQL นับ marker ทีละตัว
           แล้วต้องการค่าผูกเท่าจำนวนที่โผล่ → SQLSTATE[HY093]
           ต่อเงื่อนไขเฉพาะตอนที่ต้องใช้จริง อ่านง่ายกว่าและไม่มีทางชนกัน */
        $sql    = 'SELECT id FROM users WHERE username = :username AND deleted_at IS NULL';
        $params = ['username' => $username];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except';
            $params['except'] = $exceptId;
        }

        return Database::selectOne($sql . ' LIMIT 1', $params) !== null;
    }
}
