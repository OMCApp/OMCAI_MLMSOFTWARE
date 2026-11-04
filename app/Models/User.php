<?php
/**
 * ผู้ใช้ระบบ — CRUD อย่างเดียว ไม่มี business logic ปน
 * audit log มาเองจาก base Model
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class User extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['username', 'display_name', 'email'];
    protected array $filterColumns = ['role', 'is_active'];

    protected string $table = 'users';

    protected array $fillable = [
        'username', 'display_name', 'email', 'password_hash',
        'role', 'locale', 'theme', 'is_active', 'must_change_password', 'device_token_version',
    ];

    /** คอลัมน์ที่ยอมให้เรียงลำดับ — ผูก placeholder ไม่ได้จึงต้องมี allowlist */
    public const SORTABLE = ['id', 'username', 'display_name', 'role', 'created_at'];

    /** บทบาทที่มีในระบบ (key อังกฤษ — กฎเหล็กข้อ 11) */
    public const ROLES = ['admin', 'editor', 'sales'];

    /** @return array<string, mixed>|null */
    public function findByUsername(string $username): ?array
    {
        return Database::selectOne(
            'SELECT * FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1',
            ['username' => $username]
        );
    }

    /**
     * @param  array<int, string> $roles allowlist ของบทบาท (ค่าคงที่ในโค้ด)
     * @return array<int, array<string, mixed>>
     */
    public function activeWithRoles(array $roles): array
    {
        $marks    = [];
        $bindings = [];
        $roles    = array_values(array_filter($roles, static fn ($role): bool => in_array($role, self::ROLES, true)));

        if ($roles === []) {
            return [];
        }

        foreach ($roles as $index => $role) {
            $marks[]              = ':r' . $index;
            $bindings['r' . $index] = $role;
        }

        $sql  = 'SELECT id, display_name, role FROM users WHERE is_active = 1 AND deleted_at IS NULL';
        $sql .= ' AND role IN (' . implode(', ', $marks) . ')';
        $sql .= ' ORDER BY display_name';

        return Database::select($sql, $bindings);
    }

    public function countActiveAdmins(): int
    {
        return (int) (Database::selectOne(
            "SELECT COUNT(*) AS n FROM users WHERE role = 'admin' AND is_active = 1 AND deleted_at IS NULL"
        )['n'] ?? 0);
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
