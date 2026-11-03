<?php
/**
 * ตัดโปรเจคใหม่ออกจาก repo มาตรฐาน — ทำครั้งเดียวหลัง clone
 *
 *   php scripts/init-project.php           ดูว่าจะทำอะไรบ้าง ยังไม่ลงมือ
 *   php scripts/init-project.php --yes     ลงมือจริง
 *   php scripts/init-project.php --remote <url>   ตั้ง origin ใหม่ให้ด้วย
 *
 * ‼️ ทำไมต้องมีสคริปต์นี้
 *    `git clone` พา `.git` ทั้งก้อนมาด้วย — origin ยังชี้ไป repo มาตรฐาน
 *    และประวัติของมาตรฐานหลายสิบ commit ปนอยู่ในโปรเจคของลูกค้า
 *
 *    เดิม README บอกให้ผู้ใช้พิมพ์เอง ซึ่งขัดกับกฎของมาตรฐานเองที่ว่า
 *    "ห้ามให้ผู้ใช้พิมพ์คำสั่งตั้งค่าเอง ถ้ามันทำแทนได้" และ "สิ่งที่ต้องจำคือสิ่งที่จะถูกลืม"
 *    ลืมแล้วจะรู้ตัวตอนกด push ครั้งแรก ซึ่งเป็นตอนที่แก้ยากขึ้นแล้ว
 */

declare(strict_types=1);

require __DIR__ . '/lib/Report.php';

$root = dirname(__DIR__);
$argv = $_SERVER['argv'] ?? [];

$apply  = in_array('--yes', $argv, true);
$remote = null;

foreach ($argv as $i => $arg) {
    if ($arg === '--remote') {
        $remote = $argv[$i + 1] ?? null;
    }
    if (str_starts_with($arg, '--remote=')) {
        $remote = substr($arg, 9);
    }
}

/** @return array{0:int,1:string} */
function git(string $command, string $root): array
{
    $out  = [];
    $code = 0;
    exec('git -C ' . escapeshellarg($root) . ' ' . $command . ' 2>&1', $out, $code);

    return [$code, trim(implode("\n", $out))];
}

// ------------------------------------------------------------- ตรวจเงื่อนไข

/* ‼️ 4 เงื่อนไขนี้คือสิ่งที่ทำให้การลบ .git ปลอดภัยพอจะทำอัตโนมัติได้
   ครบทั้งสี่ = .git ก้อนนี้ไม่มีอะไรที่ไม่ได้อยู่บน repo มาตรฐานอยู่แล้ว
   ลบแล้วกู้คืนได้ด้วยการ clone ใหม่ · ขาดข้อใดข้อหนึ่งแปลว่าอาจมีงานของใครอยู่ ต้องหยุด */
$blockers = [];

[$code] = git('rev-parse --git-dir', $root);

if ($code !== 0) {
    Report::line();
    Report::line(Report::green('✓ โปรเจคนี้ไม่ได้เป็น git repo อยู่แล้ว — ไม่มีอะไรต้องตัด'));
    Report::line();
    exit(0);
}

/* ‼️ ต้องดู exit code ไม่ใช่ดูว่าผลลัพธ์ว่างหรือไม่
   git() รวม stderr เข้ามาด้วย — repo ที่ไม่มี origin จะได้ข้อความ error กลับมา
   ซึ่ง "ไม่ว่าง" แล้วตรรกะที่เช็คแค่ค่าว่างจะตีความว่ามี origin
   เจอตอนทดสอบรันซ้ำหลังตัดขาดแล้ว มันตอบว่า "ยังตัดให้ไม่ได้" แทนที่จะบอกว่าไม่ต้องทำแล้ว */
[$originCode, $origin] = git('remote get-url origin', $root);

if ($originCode !== 0 || $origin === '') {
    Report::line();
    Report::line(Report::green('✓ ไม่มี origin อยู่แล้ว — โปรเจคนี้ตัดขาดจาก repo มาตรฐานเรียบร้อย'));
    Report::line();
    exit(0);
}

[, $dirty] = git('status --porcelain', $root);

if ($dirty !== '') {
    $blockers[] = 'มีไฟล์ที่แก้ไว้แต่ยังไม่ commit — ตัดตอนนี้จะทำให้ของที่ทำค้างไว้ปนเข้า commit แรก';
}

// ไม่มี upstream = เทียบไม่ได้ ไม่ใช่ = มี commit ค้าง จึงต้องแยกด้วย exit code เช่นกัน
[$aheadCode, $ahead] = git('log --oneline @{u}..HEAD', $root);

if ($aheadCode === 0 && $ahead !== '') {
    $n = substr_count($ahead, "\n") + 1;
    $blockers[] = "มี {$n} commit ที่ยังไม่ได้ push — อาจเป็นงานของโปรเจคนี้ ไม่ใช่ของมาตรฐาน";
}

if (is_file($root . '/project/STATE.md')) {
    $blockers[] = 'มี project/STATE.md แล้ว — โปรเจคนี้เริ่มงานไปแล้ว ไม่ใช่ clone สดๆ';
}

// ---------------------------------------------------------------- รายงาน

Report::line();
Report::line(Report::bold('ตัดโปรเจคใหม่ออกจาก repo มาตรฐาน'));
Report::line(Report::dim($root));
Report::line();

[, $count] = git('rev-list --all --count', $root);

Report::line('  origin ตอนนี้    ' . Report::yellow($origin));
Report::line('  ประวัติที่ติดมา  ' . Report::yellow($count . ' commit') . Report::dim(' (ของ repo มาตรฐาน)'));
Report::line();

if ($blockers !== []) {
    Report::line(Report::red('✗ ยังตัดให้ไม่ได้'));
    Report::line();

    foreach ($blockers as $reason) {
        Report::line('  · ' . $reason);
    }

    Report::line();
    Report::line(Report::dim('  จัดการเรื่องข้างบนก่อน แล้วรันใหม่ — หรือทำเองด้วย rm -rf .git && git init'));
    Report::line();
    exit(1);
}

Report::line('  จะทำ 4 อย่าง');
Report::line('    1. บันทึก origin เดิมลง .standard-source ไว้ให้ standard-update.php ใช้');
Report::line('    2. ลบ .git แล้ว git init ใหม่');
Report::line('    3. commit แรกของโปรเจค');
Report::line('    4. ตั้ง core.hooksPath ให้ pre-commit ทำงาน');

if ($remote !== null) {
    Report::line('    5. ตั้ง origin ใหม่เป็น ' . $remote);
}

Report::line();

if (!$apply) {
    Report::line(Report::dim('  ยังไม่ได้ลงมือ — สั่ง `php scripts/init-project.php --yes` เพื่อทำจริง'));
    Report::line();
    Report::line(Report::dim('  ‼️ ปลอดภัยเพราะ .git ก้อนนี้ไม่มีอะไรที่ไม่ได้อยู่บน repo มาตรฐานแล้ว'));
    Report::line(Report::dim('     ตรวจแล้วว่า working tree สะอาด ไม่มี commit ของตัวเอง และยังไม่ได้เริ่มงาน'));
    Report::line();
    exit(0);
}

// ---------------------------------------------------------------- ลงมือ

/* ‼️ เขียน .standard-source ก่อนลบ .git — ไม่งั้น url ของ repo มาตรฐานหายไปพร้อมกัน
   แล้ว standard-update.php จะอัปเดตมาตรฐานไม่ได้อีกเลยโดยไม่มีใครรู้ว่าต้องหาจากไหน */
if (!is_file($root . '/.standard-source')) {
    file_put_contents($root . '/.standard-source', $origin . "\n");
}

$version = trim((string) @file_get_contents($root . '/standard/VERSION'));

if (!delete_tree($root . '/.git')) {
    fwrite(STDERR, "ลบ .git ไม่สำเร็จ — อาจมีโปรแกรมอื่นเปิดไฟล์ในนั้นค้างอยู่\n");
    exit(1);
}

foreach ([
    'init -q',
    'add -A',
    'commit -q -m ' . escapeshellarg("chore: เริ่มโปรเจคจากมาตรฐาน {$version}"),
    'config core.hooksPath scripts/hooks',
] as $step) {
    [$code, $out] = git($step, $root);

    if ($code !== 0) {
        fwrite(STDERR, "git {$step} ล้มเหลว:\n{$out}\n");
        exit(1);
    }
}

if ($remote !== null) {
    git('remote add origin ' . escapeshellarg($remote), $root);
}

Report::line(Report::green('✓ ตัดขาดเรียบร้อย'));
Report::line();
Report::line('  ประวัติ         ' . Report::green('1 commit') . Report::dim(' — commit แรกของโปรเจคนี้'));
Report::line('  .standard-source ' . Report::green($origin));

if ($remote === null) {
    Report::line();
    Report::line(Report::yellow('  ยังไม่มี origin') . ' — ตั้งเมื่อสร้าง repo ของโปรเจคแล้ว');
    Report::line(Report::dim('    git remote add origin <url ของ repo โปรเจค>'));
}

Report::line();
exit(0);

// ------------------------------------------------------------------ ฟังก์ชัน

/** ‼️ .git บน Windows มีไฟล์ read-only — rmdir ธรรมดาจะล้มเหลวเงียบๆ */
function delete_tree(string $path): bool
{
    if (!is_dir($path)) {
        return true;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        @chmod($item->getPathname(), 0777);

        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }

    return @rmdir($path);
}
