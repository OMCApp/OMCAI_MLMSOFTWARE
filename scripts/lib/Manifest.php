<?php
/**
 * ลายเซ็นของชุดมาตรฐาน — จุดเดียวที่ตัดสินว่า "ไฟล์ใน standard/ ถูกแก้ในโปรเจคหรือยัง"
 *
 * ‼️ ห้ามคำนวณ hash ที่อื่นอีก
 *    เดิมโค้ดนี้มีสองชุด (scripts/standard-update.php กับ scripts/checks/docs.php)
 *    พอแก้วิธี hash ที่ชุดเดียว อีกชุดคิดคนละแบบ แล้วรายงานว่ามาตรฐานถูกแก้ทั้งที่ไม่มีใครแตะ
 *    นี่คือกฎ "ทุกอย่างเป็นของกลาง แก้ที่เดียวเปลี่ยนทั้งระบบ" ที่มาตรฐานบังคับไว้เอง
 */

declare(strict_types=1);

final class Manifest
{
    /**
     * โฟลเดอร์และไฟล์ที่เป็นของมาตรฐานกลาง — standard-update.php ทับให้ และห้ามแก้ในโปรเจค
     *
     * ‼️ ก่อน 3.15.0 มีแค่ standard/ กับ CLAUDE.md
     *    สคริปต์ตรวจรุ่นใหม่จึงไม่เคยไปถึงโปรเจคที่อัปเดต — กฎใหม่บอกว่า "check.php จับข้อนี้ให้"
     *    ทั้งที่ตัวตรวจในโปรเจคยังเป็นรุ่นเก่า และสั่งให้รันสคริปต์ที่ไม่มีอยู่จริง
     *
     * .claude/settings.json ไม่อยู่ในนี้โดยตั้งใจ — โปรเจคเพิ่ม permission ของตัวเองได้
     * updater จึงรวมค่า (hooks ของมาตรฐาน + permission ทั้งสองฝั่ง) แทนการทับทั้งไฟล์
     */
    public const MANAGED = ['standard', 'CLAUDE.md', 'scripts', '.claude/commands', '.codex/hooks.json'];

    public const FILE = 'standard/.manifest';

    /**
     * hash เนื้อไฟล์โดยไม่สนใจว่าขึ้นบรรทัดใหม่ด้วย LF หรือ CRLF
     *
     * ‼️ ต้อง normalize เสมอ
     *    git บน Windows แปลง LF เป็น CRLF ตอน checkout ได้ ทำให้ลายเซ็นไม่ตรงทั้งชุด
     *    ทั้งที่ไม่มีใครแก้มาตรฐานเลย — clone สดๆ จะ fail ทันที 19 ข้อ แล้ว commit ไม่ได้เลย
     *    `.gitattributes` กันไว้ที่ต้นทางแล้ว แต่ config ของเครื่องแต่ละคน override ได้
     */
    public static function hash(string $text): string
    {
        return hash('sha256', str_replace(["\r\n", "\r"], "\n", $text));
    }

    public static function hashFile(string $path): string
    {
        return self::hash((string) file_get_contents($path));
    }

    /**
     * หัวข้อ ⚠️ ใน CHANGELOG แยกตามเวอร์ชัน — จุดเดียวที่นิยามว่าอะไรนับเป็น ⚠️
     *
     * นับเฉพาะหัวข้อ `###` ที่มี ⚠️ และบรรทัดที่ขึ้นต้นด้วย ⚠️ — ไม่นับการเอ่ยถึงกลางประโยค
     *
     * ‼️ updater ใช้แสดงว่าต้องระวังอะไร · check.php (docs/upgrade-coverage) ใช้บังคับว่าต้องมีขั้นอัปเกรด
     *    สองที่ต้องนับแบบเดียวกัน ไม่งั้นเวอร์ชันเดียวกัน "มี ⚠️" ในที่หนึ่ง แต่ "ไม่มี" ในอีกที่
     *
     * @return array<string, list<string>> เวอร์ชัน => หัวข้อ (ตัดเครื่องหมาย markdown แล้ว)
     */
    public static function breakingNotes(string $changelog): array
    {
        $out = [];

        foreach (preg_split('/^## /m', $changelog) ?: [] as $section) {
            if (preg_match('/^(\d+\.\d+\.\d+)/', $section, $m) !== 1) {
                continue;
            }

            $marks = [];

            foreach (preg_split('/\R/', $section) ?: [] as $line) {
                $line = trim($line);

                if (!str_contains($line, '⚠️') || !(str_starts_with($line, '###') || str_starts_with($line, '⚠️'))) {
                    continue;
                }

                $marks[] = trim(str_replace(['###', '⚠️', '**', '`'], '', $line));
            }

            $out[$m[1]] = $marks;
        }

        return $out;
    }

    /** @return array<string, string> path => sha256 เรียงตาม path */
    public static function build(string $root): array
    {
        $out = [];

        foreach (self::MANAGED as $entry) {
            $path = $root . '/' . $entry;

            if (is_file($path)) {
                $out[$entry] = self::hashFile($path);
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }

            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($items as $item) {
                if (!$item->isFile() || $item->getBasename() === '.manifest') {
                    continue;
                }

                $relative = str_replace('\\', '/', $item->getPathname());
                $relative = ltrim(substr($relative, strlen(str_replace('\\', '/', $root))), '/');

                $out[$relative] = self::hashFile($item->getPathname());
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * อ่านไฟล์ลายเซ็นเป็น map
     *
     * @return array<string, string>
     */
    public static function parse(string $text): array
    {
        $out = [];

        foreach (preg_split('/\R/', $text) ?: [] as $row) {
            $row = trim($row);

            if ($row === '' || str_starts_with($row, '#') || !str_contains($row, '  ')) {
                continue;
            }

            [$hash, $path] = explode('  ', $row, 2);
            $out[$path]    = $hash;
        }

        return $out;
    }

    /** @return array<string, string> */
    public static function read(string $file): array
    {
        return is_file($file) ? self::parse((string) file_get_contents($file)) : [];
    }

    /** @return int จำนวนไฟล์ที่บันทึกลายเซ็น */
    public static function write(string $root): int
    {
        $manifest = self::build($root);
        $lines    = [];

        foreach ($manifest as $path => $hash) {
            $lines[] = $hash . '  ' . $path;
        }

        file_put_contents(
            $root . '/' . self::FILE,
            "# ลายเซ็นของชุดมาตรฐาน — สร้างโดย scripts/standard-update.php\n"
            . "# ห้ามแก้ด้วยมือ · check.php ใช้ไฟล์นี้จับว่ามีคนแก้ standard/ ในโปรเจคหรือไม่\n"
            . implode("\n", $lines) . "\n"
        );

        return count($manifest);
    }
}
