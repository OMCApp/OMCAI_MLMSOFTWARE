<?php
/**
 * จุดเชื่อมต่อฐานข้อมูลจุดเดียวของทั้งระบบ
 *
 * ‼️ ATTR_EMULATE_PREPARES => false สำคัญมาก
 *    ถ้าเปิด emulate ไว้ PDO จะประกอบ query เป็น string เอง ทำให้การกัน SQL injection อ่อนลง
 */

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    /** @var array<string, mixed> */
    private static array $config = [];

    /** @param array<string, mixed> $config มาจาก config/config.php เท่านั้น */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$pdo    = null;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = self::$config;

        foreach (['host', 'name', 'user'] as $key) {
            if (($config[$key] ?? '') === '') {
                throw new RuntimeException("Database config is incomplete: missing '{$key}'");
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            (int) ($config['port'] ?? 3306),
            $config['name']
        );

        try {
            self::$pdo = new PDO($dsn, (string) $config['user'], (string) ($config['pass'] ?? ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // ข้อความจาก PDO มีชื่อผู้ใช้ฐานข้อมูลอยู่ ห้ามปล่อยขึ้นไปถึงผู้ใช้
            throw new RuntimeException('Database connection failed', 0, $e);
        }

        return self::$pdo;
    }

    /**
     * รัน query แบบผูกค่าเสมอ
     *
     * @param array<string, mixed> $bindings
     */
    public static function run(string $sql, array $bindings = []): \PDOStatement
    {
        $started = microtime(true);

        $statement = self::pdo()->prepare($sql);
        $statement->execute($bindings);

        self::$queryCount++;
        self::$queryMs += (microtime(true) - $started) * 1000;

        return $statement;
    }

    // ------------------------------------------------------------ ประสิทธิภาพ

    /**
     * ‼️ ตัวนับนี้มีไว้ให้ "เห็นตอนเขียน" ไม่ใช่ให้ไปตรวจตอนช่วง D
     *
     *    testing.md สั่งให้นับ query ต่อหน้าและหา N+1 มาตลอด แต่สั่งให้ทำตอนตรวจรวม
     *    ซึ่งเป็นตอนที่เขียนโค้ดเสร็จทั้งระบบแล้ว — เจอ N+1 ตอนนั้นแปลว่ารื้อ ไม่ใช่แก้
     *
     *    footer แสดงค่านี้ในโหมด development ทุกหน้า คนเขียนจึงเห็นตัวเลขโตขึ้นทันที
     *    ที่เผลอ query ในลูป โดยไม่ต้องจำว่าต้องไปตรวจ
     */
    private static int $queryCount = 0;

    private static float $queryMs = 0.0;

    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    public static function queryMs(): float
    {
        return self::$queryMs;
    }

    /**
     * @param  array<string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $bindings = []): array
    {
        return self::run($sql, $bindings)->fetchAll();
    }

    /**
     * @param  array<string, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public static function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = self::run($sql, $bindings)->fetch();

        return $row === false ? null : $row;
    }

    public static function transaction(callable $work): mixed
    {
        $pdo = self::pdo();

        // MySQL ไม่ทำ DDL แบบ transaction จึงครอบได้เฉพาะการเปลี่ยนข้อมูล
        $pdo->beginTransaction();

        try {
            $result = $work($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /** @var array<string, bool> */
    private static array $columns = [];

    /**
     * ตารางนี้มีคอลัมน์นี้ไหม — ใช้กับคอลัมน์ที่ phase อื่นเพิ่มเข้ามาทีหลัง (เช่น locale ของ pages)
     * ‼️ ชื่อตาราง/คอลัมน์ผูกเป็น placeholder ใน information_schema ได้ จึงไม่ต้องต่อ string
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (!array_key_exists($key, self::$columns)) {
            self::$columns[$key] = self::selectOne(
                'SELECT 1 AS present FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1',
                ['t' => $table, 'c' => $column]
            ) !== null;
        }

        return self::$columns[$key];
    }

    /** ล้างผลที่จำไว้ของ hasColumn() — ใช้ในชุดทดสอบหลังเปลี่ยนโครงตาราง */
    public static function forgetColumns(): void
    {
        self::$columns = [];
    }

    /** ใช้ตอนทดสอบเท่านั้น */
    public static function swap(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
