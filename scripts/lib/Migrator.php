<?php
/**
 * เครื่องยนต์ของ migration — ใช้ร่วมกันระหว่าง CLI และ route ลับที่ CI เรียก
 *
 * หลักที่ยึด
 *   · จำว่ารันไฟล์ไหนไปแล้วในตาราง migrations → รันซ้ำได้ไม่พัง
 *   · ไฟล์ที่ apply แล้วห้ามแก้ → เก็บ hash ไว้เทียบ ถ้าเปลี่ยนถือว่าผิดกฎ
 *   · เรียงตามเลขนำหน้าเสมอ
 */

declare(strict_types=1);

final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsDir,
    ) {
    }

    public function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                filename   VARCHAR(255) NOT NULL,
                checksum   CHAR(64)     NOT NULL,
                applied_at DATETIME     NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_filename (filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return array<int, string> ชื่อไฟล์ทั้งหมดเรียงตามเลขนำหน้า */
    public function all(): array
    {
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        $names = array_map('basename', $files);

        usort($names, static function (string $a, string $b): int {
            preg_match('/^(\d+)/', $a, $ma);
            preg_match('/^(\d+)/', $b, $mb);

            return [(int) ($ma[1] ?? 0), $a] <=> [(int) ($mb[1] ?? 0), $b];
        });

        return $names;
    }

    /** @return array<string, string> filename => checksum */
    public function applied(): array
    {
        $rows = $this->pdo->query('SELECT filename, checksum FROM migrations')->fetchAll();
        $out  = [];

        foreach ($rows as $row) {
            $out[$row['filename']] = $row['checksum'];
        }

        return $out;
    }

    /** @return array<int, string> */
    public function pending(): array
    {
        $applied = $this->applied();

        return array_values(array_filter($this->all(), fn (string $f) => !isset($applied[$f])));
    }

    /**
     * ไฟล์ที่ apply ไปแล้วแต่เนื้อหาเปลี่ยน — schema ระหว่างเครื่องจะเพี้ยนไม่ตรงกัน
     *
     * @return array<int, string>
     */
    public function changed(): array
    {
        $out = [];

        foreach ($this->applied() as $name => $checksum) {
            $path = $this->migrationsDir . '/' . $name;

            if (!is_file($path)) {
                $out[] = $name . ' (ไฟล์หายไป)';
                continue;
            }

            if (hash('sha256', (string) file_get_contents($path)) !== $checksum) {
                $out[] = $name . ' (เนื้อหาเปลี่ยน)';
            }
        }

        return $out;
    }

    /**
     * รัน migration ที่ยังไม่เคยรัน
     *
     * @return array<int, string> รายชื่อไฟล์ที่รันไป
     * @throws RuntimeException เมื่อรันไม่สำเร็จ
     */
    public function run(): array
    {
        $this->ensureTable();

        $changed = $this->changed();

        if ($changed !== []) {
            throw new RuntimeException(
                "ไฟล์ migration ที่ apply ไปแล้วถูกแก้:\n  · " . implode("\n  · ", $changed)
                . "\n\nห้ามแก้ไฟล์ที่ apply แล้ว ต้องสร้างไฟล์ใหม่เสมอ"
                . " ไม่งั้น schema ระหว่างเครื่องในทีมจะเพี้ยนไม่ตรงกัน"
            );
        }

        $done = [];

        foreach ($this->pending() as $name) {
            $path = $this->migrationsDir . '/' . $name;
            $sql  = (string) file_get_contents($path);

            /* ‼️ ต้องตัดคอมเมนต์ออกก่อนแล้วค่อยหา ห้ามดูแค่ N ตัวอักษรแรก
               คอมเมนต์ภาษาไทย 3 บรรทัดกินที่ ~187 ตัวอักษร ทำให้บรรทัด SET NAMES
               ถูกตัดกลางพอดี แล้ว Migrator ปฏิเสธ migration ของโครงเอง ตั้งแต่ clone มาใหม่ */
            $head = preg_replace('#^(?:\s*(?:--[^\n]*|/\*.*?\*/|\#[^\n]*)\s*)+#s', '', ltrim($sql)) ?? $sql;

            if (preg_match('/SET\s+NAMES\s+utf8mb4/i', mb_substr($head, 0, 200)) !== 1) {
                throw new RuntimeException(
                    "{$name} ไม่มี `SET NAMES utf8mb4` ที่ต้นไฟล์\n"
                    . 'ถ้าไม่มี ภาษาไทยอาจถูกเขียนลงฐานข้อมูลแบบเพี้ยนโดยไม่มี error แจ้งเลย'
                );
            }

            foreach ($this->statements($sql) as $statement) {
                try {
                    $this->pdo->exec($statement);
                } catch (PDOException $e) {
                    throw new RuntimeException(
                        "{$name} รันไม่สำเร็จ: " . $e->getMessage()
                        . "\n\nคำสั่งที่พัง:\n" . mb_substr(trim($statement), 0, 300)
                    );
                }
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO migrations (filename, checksum, applied_at) VALUES (:f, :c, NOW())'
            );
            $stmt->execute([
                'f' => $name,
                'c' => hash('sha256', $sql),
            ]);

            $done[] = $name;
        }

        return $done;
    }

    /** รวม migration ที่ยังไม่รันเป็นไฟล์เดียว ไว้ import ผ่าน phpMyAdmin เป็นทางสำรอง */
    public function export(): string
    {
        $out = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";
        $out .= "-- รวม migration ที่ยังไม่ได้รัน · สร้างเมื่อ " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- ใช้เป็นทางสำรองเมื่อ route migrate มีปัญหา\n\n";

        foreach ($this->pending() as $name) {
            $sql = (string) file_get_contents($this->migrationsDir . '/' . $name);
            $sql = (string) preg_replace('/^\s*SET\s+NAMES[^;]*;\s*/i', '', $sql);

            $out .= "-- ─── {$name} ───\n" . trim($sql) . "\n\n";
        }

        return $out;
    }

    /**
     * แยกคำสั่งด้วย ; โดยไม่ตัดข้างในเครื่องหมายคำพูดหรือคอมเมนต์
     *
     * @return array<int, string>
     */
    private function statements(string $sql): array
    {
        $out   = [];
        $buf   = '';
        $quote = '';
        $len   = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($quote === '') {
                // คอมเมนต์บรรทัดเดียว
                if (($char === '-' && $next === '-') || $char === '#') {
                    $end  = strpos($sql, "\n", $i);
                    $i    = $end === false ? $len : $end;
                    $buf .= "\n";
                    continue;
                }

                // คอมเมนต์หลายบรรทัด
                if ($char === '/' && $next === '*') {
                    $end = strpos($sql, '*/', $i);
                    $i   = $end === false ? $len : $end + 1;
                    continue;
                }

                if ($char === '"' || $char === "'" || $char === '`') {
                    $quote = $char;
                }

                if ($char === ';') {
                    if (trim($buf) !== '') {
                        $out[] = trim($buf);
                    }
                    $buf = '';
                    continue;
                }
            } elseif ($char === $quote && ($sql[$i - 1] ?? '') !== '\\') {
                $quote = '';
            }

            $buf .= $char;
        }

        if (trim($buf) !== '') {
            $out[] = trim($buf);
        }

        return $out;
    }
}
