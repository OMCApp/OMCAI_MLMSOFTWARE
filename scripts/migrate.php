<?php
/**
 * รัน migration บนเครื่องตัวเอง
 *
 *   php scripts/migrate.php             รัน migration ที่ยังไม่เคยรัน
 *   php scripts/migrate.php --status    ดูว่ารันอะไรไปแล้ว เหลืออะไร
 *   php scripts/migrate.php --export    รวมที่ยังไม่รันเป็นไฟล์เดียวไว้ import ผ่าน phpMyAdmin
 *
 * บนเครื่องจริง CI จะยิงเรียก route ลับให้อัตโนมัติหลังอัปไฟล์ — ไม่ต้องรันเอง
 * ‼️ ห้ามแก้ไฟล์ migration ที่ apply ไปแล้ว ต้องสร้างไฟล์ใหม่เสมอ
 */

declare(strict_types=1);

require __DIR__ . '/lib/Migrator.php';

$root = dirname(__DIR__);
$args = $_SERVER['argv'] ?? [];

$configPath = $root . '/config/config.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "ไม่พบ config/config.php\n"
        . "โครงโปรเจคยังไม่พร้อม — ดู standard/stages/1-setup.md\n");
    exit(1);
}

/** @var array<string, mixed> $config */
$config = require $configPath;
$db     = $config['db'] ?? [];

foreach (['host', 'name', 'user'] as $key) {
    if (($db[$key] ?? '') === '') {
        fwrite(STDERR, "ตั้งค่า DB_{$key} ใน .env ยังไม่ครบ\n");
        exit(1);
    }
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

$migrator = new Migrator($pdo, $root . '/database/migrations');

// ------------------------------------------------------------------ --export

if (in_array('--export', $args, true)) {
    $target = $root . '/storage/backups/pending-migrations-' . date('Ymd-His') . '.sql';

    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0o777, true);
    }

    file_put_contents($target, $migrator->export());

    $count = count($migrator->pending());
    echo "✓ เขียนไฟล์รวม {$count} migration ที่ยังไม่รันแล้ว\n  " . $target . "\n";
    exit(0);
}

// ------------------------------------------------------------------ --status

if (in_array('--status', $args, true)) {
    $migrator->ensureTable();

    $applied = $migrator->applied();
    $pending = $migrator->pending();
    $changed = $migrator->changed();

    echo 'รันไปแล้ว ' . count($applied) . " รายการ\n";
    foreach (array_keys($applied) as $name) {
        echo "  ✓ {$name}\n";
    }

    echo "\nยังไม่ได้รัน " . count($pending) . " รายการ\n";
    foreach ($pending as $name) {
        echo "  · {$name}\n";
    }

    if ($changed !== []) {
        echo "\n‼️ ไฟล์ที่ apply แล้วแต่ถูกแก้\n";
        foreach ($changed as $name) {
            echo "  ✗ {$name}\n";
        }
        echo "\nต้องสร้างไฟล์ใหม่แทนการแก้ของเดิม ไม่งั้น schema ระหว่างเครื่องจะเพี้ยนไม่ตรงกัน\n";
        exit(1);
    }

    echo "\n";
    exit(0);
}

// ------------------------------------------------------------------ รันจริง

try {
    $done = $migrator->run();
} catch (RuntimeException $e) {
    fwrite(STDERR, "✗ " . $e->getMessage() . "\n");
    exit(1);
}

if ($done === []) {
    echo "✓ ไม่มี migration ใหม่ ฐานข้อมูลเป็นปัจจุบันแล้ว\n";
    exit(0);
}

echo '✓ รัน migration แล้ว ' . count($done) . " รายการ\n";
foreach ($done as $name) {
    echo "  · {$name}\n";
}

echo "\nอย่าลืมตรวจว่าภาษาไทยไม่เพี้ยน — อ่านข้อมูลกลับผ่าน PDO ไม่ใช่ดูจาก console\n";
echo "(console บน Windows แสดงภาษาไทยไม่ได้อยู่แล้ว แยกไม่ออกว่าข้อมูลเสียจริงหรือแค่แสดงผลเพี้ยน)\n";
exit(0);
