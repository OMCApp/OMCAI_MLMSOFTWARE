<?php
/**
 * อ่านโครงสร้างและข้อมูลทุกตาราง สำหรับไฟล์สำรอง (BackupService)
 *
 * ‼️ ชื่อตารางผูก placeholder ไม่ได้ — ใช้ได้เฉพาะชื่อที่มาจาก SHOW FULL TABLES ของฐานข้อมูลนี้เอง
 *    และต้องผ่านรูปแบบ [A-Za-z0-9_] (allowlist) ก่อนประกอบเป็น SQL ทุกครั้ง · ไม่มีค่าจากผู้ใช้เข้ามาที่นี่
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use RuntimeException;

final class DatabaseDump
{
    /** @return array<int, string> ตารางจริง (ไม่รวม view) */
    public function tables(): array
    {
        $out = [];

        foreach (Database::select('SHOW FULL TABLES') as $row) {
            $values = array_values($row);

            if (($values[1] ?? '') === 'BASE TABLE') {
                $out[] = self::identifier((string) $values[0]);
            }
        }

        return $out;
    }

    public function createStatement(string $table): string
    {
        $row = Database::selectOne('SHOW CREATE TABLE `' . self::identifier($table) . '`');

        return (string) ($row['Create Table'] ?? '');
    }

    /** @return array<int, string> คอลัมน์ที่เขียนได้ (ไม่รวม generated column) */
    public function columns(string $table): array
    {
        $out = [];

        foreach (Database::select('SHOW COLUMNS FROM `' . self::identifier($table) . '`') as $column) {
            if (!str_contains(strtoupper((string) $column['Extra']), 'GENERATED')) {
                $out[] = self::identifier((string) $column['Field']);
            }
        }

        return $out;
    }

    /**
     * อ่านทีละแถว (ไม่โหลดทั้งตารางเข้าหน่วยความจำ)
     *
     * @param  array<int, string> $columns
     * @return \Generator<int, array<int, mixed>>
     */
    public function rows(string $table, array $columns): \Generator
    {
        $list      = implode(', ', array_map(static fn (string $c): string => '`' . self::identifier($c) . '`', $columns));
        $statement = Database::run('SELECT ' . $list . ' FROM `' . self::identifier($table) . '`');

        while (($row = $statement->fetch(\PDO::FETCH_NUM)) !== false) {
            yield $row;
        }
    }

    public function quote(mixed $value): string
    {
        return $value === null ? 'NULL' : (string) Database::pdo()->quote((string) $value);
    }

    private static function identifier(string $name): string
    {
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) !== 1) {
            throw new RuntimeException('unexpected identifier in backup');
        }

        return $name;
    }
}
