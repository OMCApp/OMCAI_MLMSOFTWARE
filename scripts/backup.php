<?php
/**
 * งานเบื้องหลังที่ตั้ง cron ไว้บนเครื่องจริง
 *
 *   php scripts/backup.php              สำรองฐานข้อมูล + ตัด log + เก็บ audit เก่าเข้าคลัง
 *   php scripts/backup.php --db-only    สำรองฐานข้อมูลอย่างเดียว
 *
 * ตั้ง cron วันละครั้ง เช่น  0 3 * * *  php /path/to/scripts/backup.php
 *
 * ‼️ ใช้ PDO ล้วน ไม่พึ่ง mysqldump — shared host ส่วนใหญ่ไม่ให้เรียกคำสั่งภายนอก
 *    ระบบธุรกิจที่ไม่มี backup คือระบบที่รอเวลาพัง
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$args = $_SERVER['argv'] ?? [];

$configPath = $root . '/config/config.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "ไม่พบ config/config.php\n");
    exit(1);
}

/** @var array<string, mixed> $config */
$config = require $configPath;
$db     = $config['db'] ?? [];

$keepDays    = (int) ($config['backup']['keep_days'] ?? 14);
$logMaxBytes = (int) ($config['backup']['log_max_bytes'] ?? 10 * 1024 * 1024);
$auditMonths = (int) ($config['backup']['audit_keep_months'] ?? 12);

$backupDir = $root . '/storage/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0o777, true);
}

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
        (string) $db['user'],
        (string) ($db['pass'] ?? ''),
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "ต่อฐานข้อมูลไม่ได้: {$e->getMessage()}\n");
    exit(1);
}

// ------------------------------------------------------------------ สำรอง DB

$target = $backupDir . '/db-' . date('Ymd-His') . '.sql';
$handle = fopen($target, 'wb');

if ($handle === false) {
    fwrite(STDERR, "เขียนไฟล์สำรองไม่ได้: {$target}\n");
    exit(1);
}

fwrite($handle, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
fwrite($handle, '-- สำรองเมื่อ ' . date('Y-m-d H:i:s') . ' · ฐานข้อมูล ' . $db['name'] . "\n\n");

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$rows   = 0;

foreach ($tables as $table) {
    $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '', (string) $table) . '`')->fetch();
    $ddl    = $create['Create Table'] ?? null;

    if ($ddl === null) {
        continue;
    }

    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n{$ddl};\n\n");

    $statement = $pdo->query('SELECT * FROM `' . str_replace('`', '', (string) $table) . '`');

    while (($row = $statement->fetch()) !== false) {
        $values = array_map(
            static fn ($value) => $value === null ? 'NULL' : (string) $pdo->quote((string) $value),
            array_values($row)
        );

        $columns = implode('`, `', array_keys($row));

        fwrite($handle, "INSERT INTO `{$table}` (`{$columns}`) VALUES (" . implode(', ', $values) . ");\n");
        $rows++;
    }

    fwrite($handle, "\n");
}

fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($handle);

// บีบอัดถ้าทำได้ — ไฟล์สำรองโตเร็วกว่าที่คิดเสมอ
if (function_exists('gzencode')) {
    $raw = (string) file_get_contents($target);
    file_put_contents($target . '.gz', (string) gzencode($raw, 9));
    unlink($target);
    $target .= '.gz';
}

echo '✓ สำรองฐานข้อมูลแล้ว ' . count($tables) . " ตาราง {$rows} แถว\n  " . basename($target) . "\n";

// ------------------------------------------------------------ หมุนไฟล์เก่าทิ้ง

$cutoff  = time() - ($keepDays * 86400);
$removed = 0;

foreach (glob($backupDir . '/db-*.sql*') ?: [] as $file) {
    if (filemtime($file) < $cutoff) {
        unlink($file);
        $removed++;
    }
}

if ($removed > 0) {
    echo "✓ ลบไฟล์สำรองที่เก่ากว่า {$keepDays} วันแล้ว {$removed} ไฟล์\n";
}

if (in_array('--db-only', $args, true)) {
    exit(0);
}

// ---------------------------------------------------------------------- log

$logPath = $root . '/storage/logs/app.log';

if (is_file($logPath) && filesize($logPath) > $logMaxBytes) {
    $rotated = $root . '/storage/logs/app-' . date('Ymd-His') . '.log';
    rename($logPath, $rotated);
    touch($logPath);

    echo '✓ ตัด log ที่โตเกิน ' . round($logMaxBytes / 1048576, 1) . " MB แล้ว\n";
}

foreach (glob($root . '/storage/logs/app-*.log') ?: [] as $file) {
    if (filemtime($file) < $cutoff) {
        unlink($file);
    }
}

// -------------------------------------------------------- audit log เข้าคลัง

// ตารางนี้โตเร็วที่สุดในระบบ — ย้ายของเก่าออกไปเก็บแยก ไม่ใช่ลบทิ้ง
try {
    $exists = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetch();

    if ($exists !== false) {
        $before = (new DateTimeImmutable("-{$auditMonths} months"))->format('Y-m-d H:i:s');

        $old = $pdo->prepare('SELECT * FROM audit_logs WHERE created_at < :before ORDER BY id');
        $old->execute(['before' => $before]);
        $records = $old->fetchAll();

        if ($records !== []) {
            $archive = $backupDir . '/audit-archive-' . date('Ymd-His') . '.sql';
            $lines   = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";

            foreach ($records as $record) {
                $values  = array_map(
                    static fn ($value) => $value === null ? 'NULL' : (string) $pdo->quote((string) $value),
                    array_values($record)
                );
                $columns = implode('`, `', array_keys($record));
                $lines  .= "INSERT INTO `audit_logs` (`{$columns}`) VALUES (" . implode(', ', $values) . ");\n";
            }

            file_put_contents($archive, $lines);

            $delete = $pdo->prepare('DELETE FROM audit_logs WHERE created_at < :before');
            $delete->execute(['before' => $before]);

            echo '✓ ย้าย audit log ที่เก่ากว่า ' . $auditMonths . ' เดือนเข้าคลังแล้ว '
                . count($records) . " แถว\n  " . basename($archive) . "\n";
        }
    }
} catch (PDOException $e) {
    fwrite(STDERR, "จัดการ audit log ไม่สำเร็จ: {$e->getMessage()}\n");
}

echo "\nเสร็จเรียบร้อย\n";
exit(0);
