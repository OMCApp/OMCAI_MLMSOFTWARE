<?php
/**
 * ไฟล์ที่เปลี่ยนในงานรอบนี้ — ใช้โดย check.php --changed
 *
 * ‼️ ทำไมหน่วยคือ "ไฟล์ที่เปลี่ยน" ไม่ใช่ "phase"
 *    phase ไม่มีตัวตนในไฟล์ · ไฟล์หนึ่งถูกหลาย phase แตะได้ และสถาปัตยกรรมนี้ตั้งใจให้ทุกอย่างเป็นของกลาง
 *    phase 2 แก้ partials/field.php ทีเดียวพังหน้าของ phase 1 ได้ (บั๊ก $rows ที่ทำตารางหายทั้งระบบ · 3.2.0)
 *    จึงวัดจาก git ว่าแตะอะไรจริง แล้วยกระดับเป็นตรวจเต็มทันทีที่แตะของกลาง
 */

declare(strict_types=1);

final class Changes
{
    /** แตะที่เหล่านี้แล้วผลกระทบลามไปหน้าที่ไม่ได้แตะ — ต้องตรวจทั้งโปรเจค */
    public const SHARED = [
        'core/',
        'config/',
        'app/Views/partials/',
        'app/Views/layouts/',
        'public/assets/css/',
        'public/assets/js/',
        'project/DESIGN.md',
        'scripts/',
        'standard/',
        'CLAUDE.md',
    ];

    /** แตะที่เหล่านี้แล้วไม่ใช่งานเล็ก — เกณฑ์เดียวกับ standard/flow.md ข้อ 13 */
    public const NOT_SMALL = [
        'config/routes.php'   => 'เพิ่มหรือแก้ route',
        'database/'           => 'แตะโครงสร้างฐานข้อมูล',
        'app/Controllers/'    => 'แตะ Controller',
        'app/Services/'       => 'แตะ business logic',
        'app/Models/'         => 'แตะการเข้าถึงข้อมูล',
        'app/Middlewares/'    => 'แตะการตรวจสิทธิ์',
        'core/'               => 'แตะแกนของระบบ',
        'app/Views/partials/' => 'แตะ component กลาง',
        'app/Views/layouts/'  => 'แตะ layout กลาง',
        'public/assets/js/'   => 'แตะ JavaScript กลาง',
        'project/DESIGN.md'   => 'เปลี่ยนดีไซน์ทั้งระบบ',
    ];

    /** งานเล็กแตะได้ไม่เกินเท่านี้ ไม่นับไฟล์ภาษา บทความคู่มือ และเอกสารใน project/ */
    public const SMALL_MAX_FILES = 5;

    /**
     * @return array{files: list<string>|null, label: string, reason: string}
     *         files = null แปลว่าหาไม่ได้ ให้ผู้เรียกตรวจทั้งโปรเจคแทน
     */
    public static function detect(string $root, ?string $base): array
    {
        if (self::git($root, 'rev-parse --verify HEAD') === null) {
            return ['files' => null, 'label' => '', 'reason' => 'ยังไม่มี commit แรก หรือไม่ใช่ git repo'];
        }

        $baseRef = $base;

        if ($baseRef === null) {
            foreach (['main', 'master'] as $candidate) {
                if (self::git($root, 'rev-parse --verify --quiet refs/heads/' . $candidate) !== null) {
                    $baseRef = $candidate;
                    break;
                }
            }
        }

        // ไม่มี base ให้เทียบ = ดูแค่งานที่ยังไม่ commit ซึ่งพอสำหรับการกัน commit
        // เพราะของที่ commit ไปแล้วถูกตรวจตอน commit ของมันเอง
        $from  = 'HEAD';
        $label = 'งานที่ยังไม่ commit';

        if ($baseRef !== null) {
            $mergeBase = self::git($root, 'merge-base HEAD ' . escapeshellarg($baseRef));

            if ($mergeBase === null || trim($mergeBase[0] ?? '') === '') {
                if ($base !== null) {
                    return ['files' => null, 'label' => '', 'reason' => "หา {$base} ไม่เจอ"];
                }
            } else {
                $from  = trim($mergeBase[0]);
                $label = "ตั้งแต่แตกจาก {$baseRef}";
            }
        }

        $lists = [
            self::git($root, 'diff --name-only --diff-filter=ACMR HEAD'),
            self::git($root, 'ls-files --others --exclude-standard'),
        ];

        if ($from !== 'HEAD') {
            $lists[] = self::git($root, 'diff --name-only --diff-filter=ACMR ' . escapeshellarg($from) . ' HEAD');
        }

        $files = [];

        foreach ($lists as $list) {
            if ($list === null) {
                return ['files' => null, 'label' => '', 'reason' => 'อ่านรายการไฟล์จาก git ไม่ได้'];
            }

            foreach ($list as $path) {
                $path = str_replace('\\', '/', trim($path));

                if ($path !== '' && is_file($root . '/' . $path)) {
                    $files[$path] = true;
                }
            }
        }

        $files = array_keys($files);
        sort($files);

        return ['files' => $files, 'label' => $label, 'reason' => ''];
    }

    /**
     * @param  list<string> $files
     * @return list<string> ไฟล์ที่เป็นของกลาง
     */
    public static function shared(array $files): array
    {
        return array_values(array_filter($files, static function (string $file): bool {
            foreach (self::SHARED as $prefix) {
                if ($file === $prefix || str_starts_with($file, $prefix)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param  list<string> $files
     * @return list<string> เหตุผลที่ไม่เข้าเกณฑ์งานเล็ก · ว่าง = เข้าเกณฑ์
     */
    public static function notSmall(array $files): array
    {
        $reasons = [];

        foreach ($files as $file) {
            foreach (self::NOT_SMALL as $prefix => $why) {
                if ($file === $prefix || str_starts_with($file, $prefix)) {
                    $reasons[$why] = true;
                }
            }
        }

        $counted = array_filter($files, static fn (string $f): bool =>
            !str_starts_with($f, 'lang/')
            && !str_starts_with($f, 'app/Views/manuals/')
            && !(str_starts_with($f, 'project/') && $f !== 'project/DESIGN.md'));

        if (count($counted) > self::SMALL_MAX_FILES) {
            $reasons['แตะ ' . count($counted) . ' ไฟล์ (เกณฑ์ไม่เกิน ' . self::SMALL_MAX_FILES . ')'] = true;
        }

        return array_keys($reasons);
    }

    /*
     * ‼️ ตราประทับ "ตรวจแล้ว" — กันไม่ให้ commit หนึ่งครั้งรันชุดตรวจสองรอบ
     *
     * AI commit ผ่าน hook ของ Claude Code (guard-commit.php) แล้ว git ยังเรียก pre-commit ซ้ำอีกรอบ
     * ของเดิมรันชุดตรวจเต็มทั้งสองรอบกับงานชุดเดียวกัน
     *
     * กุญแจคือสภาพของไฟล์ที่ต่างจาก HEAD ทั้งหมด (รวมไฟล์ใหม่) ไม่ใช่ index
     * เพราะคำสั่งอย่าง `git add x && git commit` ทำให้ hook ตรวจก่อน add แต่ git ถาม pre-commit หลัง add
     * ถ้าใช้ index เป็นกุญแจ สองจังหวะนั้นจะไม่ตรงกันเสมอ ส่วนสภาพของไฟล์ไม่เปลี่ยนเพราะการ add
     *
     * ผิดพลาดได้ทางเดียวคือตรวจซ้ำ ไม่มีทางข้าม — ไฟล์ไหนเปลี่ยนแม้ไบต์เดียว กุญแจไม่ตรงทันที
     */
    public static function writeStamp(string $root): void
    {
        $path = self::stampPath($root);
        $key  = self::fingerprint($root);

        if ($path !== null && $key !== null) {
            @file_put_contents($path, $key . "\n");
        }
    }

    /** ใช้ตราได้ครั้งเดียว ไม่ว่าจะตรงหรือไม่ */
    public static function consumeStamp(string $root): bool
    {
        $path = self::stampPath($root);

        if ($path === null || !is_file($path)) {
            return false;
        }

        $saved = trim((string) file_get_contents($path));
        @unlink($path);

        $now = self::fingerprint($root);

        return $now !== null && $saved !== '' && hash_equals($saved, $now);
    }

    private static function fingerprint(string $root): ?string
    {
        $head      = self::git($root, 'rev-parse HEAD');
        $tracked   = self::git($root, 'diff --name-only HEAD');
        $untracked = self::git($root, 'ls-files --others --exclude-standard');

        if ($head === null || $tracked === null || $untracked === null) {
            return null;
        }

        $paths = array_unique(array_filter(array_map('trim', [...$tracked, ...$untracked])));
        sort($paths);

        $parts = [trim($head[0] ?? '')];

        foreach ($paths as $path) {
            $file    = $root . '/' . $path;
            $parts[] = $path . ':' . (is_file($file) ? (string) sha1_file($file) : 'deleted');
        }

        return sha1(implode("\n", $parts));
    }

    private static function stampPath(string $root): ?string
    {
        $out  = self::git($root, 'rev-parse --git-path wf-checked');
        $path = trim($out[0] ?? '');

        if ($path === '') {
            return null;
        }

        return preg_match('#^([A-Za-z]:[\\\\/]|/)#', $path) === 1 ? $path : $root . '/' . $path;
    }

    /**
     * @return list<string>|null บรรทัดของ output · null = คำสั่งล้มเหลว
     *
     * ‼️ core.quotepath=off จำเป็น — ไม่งั้น git ส่งชื่อไฟล์ภาษาไทยมาเป็นรหัสฐานแปดในเครื่องหมายคำพูด
     *    แล้ว is_file() หาไม่เจอ ไฟล์นั้นจะหลุดจากการตรวจแบบเงียบๆ
     */
    private static function git(string $root, string $args): ?array
    {
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $out  = [];
        $code = 0;

        exec('git -c core.quotepath=off -C ' . escapeshellarg($root) . ' ' . $args . ' 2>' . $null, $out, $code);

        return $code === 0 ? $out : null;
    }
}
