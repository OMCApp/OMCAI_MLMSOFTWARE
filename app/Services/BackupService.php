<?php
/**
 * สำรองฐานข้อมูลจากหน้า CMS (admin) — ผลเหมือน `php scripts/backup.php --db-only`
 *
 * ‼️ ไม่มีปุ่มกู้คืนบนเว็บโดยตั้งใจ — การกู้คืนทับทุกตารางรวมผู้ใช้และ audit log
 *    ทำผ่าน CLI ตามขั้นตอนใน project/DEPLOY.md เท่านั้น
 * ‼️ ไฟล์อยู่ใน storage/backups/ (นอก public/) · ดาวน์โหลดผ่าน Controller ที่ตรวจสิทธิ์ admin
 *    ชื่อไฟล์จาก URL ต้องตรงรูปแบบ db-YYYYMMDD-HHMMSS.sql(.gz) และมีอยู่ในรายการจริง — กัน path traversal
 * ‼️ การสร้าง/ลบไฟล์สำรองลง audit log ด้วย Model::audit() (ไม่ใช่แถวในตาราง จึงไม่ผ่าน base Model เอง)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\DatabaseDump;
use Core\Model;

final class BackupService
{
    private const PATTERN = '/^db-\d{8}-\d{6}\.sql(\.gz)?$/';

    private function dir(): string
    {
        return (string) config('paths.backups');
    }

    /** @return array<int, array{name:string, bytes:int, created_at:string}> ใหม่สุดก่อน */
    public function list(): array
    {
        $out = [];

        foreach (glob($this->dir() . '/db-*.sql*') ?: [] as $file) {
            $name = basename($file);

            if (preg_match(self::PATTERN, $name) === 1) {
                $out[] = ['name' => $name, 'bytes' => (int) filesize($file), 'created_at' => date('Y-m-d H:i:s', (int) filemtime($file))];
            }
        }

        usort($out, static fn (array $a, array $b): int => strcmp($b['name'], $a['name']));

        return $out;
    }

    /** path เต็มของไฟล์ — null ถ้าชื่อผิดรูปแบบหรือไม่มีอยู่ */
    public function path(string $name): ?string
    {
        if (preg_match(self::PATTERN, $name) !== 1) {
            return null;
        }

        $file = $this->dir() . '/' . $name;

        return is_file($file) ? $file : null;
    }

    /** path สำหรับดาวน์โหลด + บันทึกว่าใครดาวน์โหลด (ไฟล์มีข้อมูลทั้งระบบ) */
    public function forDownload(string $name): ?string
    {
        $file = $this->path($name);

        if ($file !== null) {
            Model::audit('backup_download', 'backups', null, null, ['file' => $name]);
        }

        return $file;
    }

    /** สร้างไฟล์สำรองใหม่ — คืนชื่อไฟล์ */
    public function create(): string
    {
        $dir = $this->dir();

        if (!is_dir($dir)) {
            mkdir($dir, 0o750, true);
        }

        $name   = 'db-' . date('Ymd-His') . '.sql';
        $target = $dir . '/' . $name;
        $handle = fopen($target, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('cannot write backup file');
        }

        $dump = new DatabaseDump();

        fwrite($handle, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        fwrite($handle, '-- backup ' . date('Y-m-d H:i:s') . " (CMS)\n\n");

        foreach ($dump->tables() as $table) {
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n" . $dump->createStatement($table) . ";\n\n");

            // คอลัมน์ generated (alive, published_date) ห้ามใส่ใน INSERT
            $columns = $dump->columns($table);
            $list    = '`' . implode('`, `', $columns) . '`';

            foreach ($dump->rows($table, $columns) as $row) {
                $values = array_map(static fn ($v): string => $dump->quote($v), $row);
                fwrite($handle, 'INSERT INTO `' . $table . '` (' . $list . ') VALUES (' . implode(', ', $values) . ");\n");
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        if (function_exists('gzencode')) {
            file_put_contents($target . '.gz', (string) gzencode((string) file_get_contents($target), 9));
            unlink($target);
            $name .= '.gz';
        }

        @chmod($dir . '/' . $name, 0o640);

        Model::audit('backup_create', 'backups', null, null, ['file' => $name]);

        return $name;
    }

    public function delete(string $name): bool
    {
        $file = $this->path($name);

        if ($file === null || !@unlink($file)) {
            return false;
        }

        Model::audit('backup_delete', 'backups', null, ['file' => $name], null);

        return true;
    }
}
