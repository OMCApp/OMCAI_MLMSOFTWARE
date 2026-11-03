<?php
/**
 * PreToolUse hook (git commit) — กันไม่ให้ commit ทั้งที่ชุดตรวจยังไม่ผ่าน
 *
 * ซ้อนกับ git pre-commit hook โดยตั้งใจ:
 *   - git hook  กันคนในทีมที่ commit เอง (ต้องสั่ง core.hooksPath ก่อน)
 *   - hook ตัวนี้ กันตอน AI สั่ง commit (ทำงานทันทีโดยไม่ต้องตั้งค่าอะไร)
 * ต้องมีทั้งคู่ถึงจะกันได้ครบทุกทาง
 */

declare(strict_types=1);

require __DIR__ . '/hook.php';

$root    = hook_project_root();
$data    = hook_input();
$command = (string) (hook_get($data, 'tool_input.command') ?? '');

// ‼️ ต้องกรองเองในสคริปต์ ห้ามพึ่งเงื่อนไข if ใน settings.json อย่างเดียว
//    ถ้าไม่กรอง hook จะทำงานกับทุกคำสั่ง Bash แล้วบล็อกทั้งทีมเมื่อชุดตรวจไม่ผ่าน
// (?:\w+=\S*\s+)* รองรับตัวแปรสภาพแวดล้อมนำหน้า ไม่งั้นใส่ FOO=1 นำหน้าแล้วข้าม guard ได้
if (preg_match('/(?:^|[;&|]\s*)(?:\w+=\S*\s+)*git\s+(?:-\S+\s+)*commit\b/', $command) !== 1) {
    hook_ok();
}

if (hook_checker_missing($root)) {
    hook_ok();
}

// ห้าม commit ลง main ยกเว้น commit แรกตอนตั้งโปรเจค
[, $branch] = hook_run('git rev-parse --abbrev-ref HEAD', $root);
$branch = trim($branch);

if (in_array($branch, ['main', 'master'], true) && !hook_user_allowed_main($command)) {
    [$hasCommits] = hook_run('git rev-parse --verify HEAD', $root);

    if ($hasCommits === 0) {
        hook_deny(
            "กำลังจะ commit ลง `{$branch}` โดยตรง\n\n"
            . "กฎเหล็ก: ทำงานใน branch เท่านั้น ห้าม commit ลง main "
            . "ยกเว้น commit แรกตอนตั้งโปรเจค หรือผู้ใช้สั่งเอง\n\n"
            . "ให้ถามชื่อ branch จากผู้ใช้ก่อน แล้วสร้าง branch ใหม่\n\n"
            . "ถ้าผู้ใช้สั่งให้ทำจริงในรอบนี้ ให้ผู้ใช้เป็นคนอนุญาตในคำสั่ง:\n"
            . "    WF_ALLOW_MAIN=1 git commit ...\n"
            . "‼️ AI ห้ามเติมคำอนุญาตนี้เองถ้าผู้ใช้ไม่ได้สั่งในรอบนั้น"
        );
    }
}

// --changed: ตรวจไฟล์ที่เปลี่ยน + การตรวจทั้งโปรเจค (แตะของกลางแล้วยกระดับเป็นเต็มเอง)
// --stamp:   ผ่านแล้วประทับตราไว้ ให้ git pre-commit ที่ถูกเรียกต่อจากนี้ไม่ต้องรันซ้ำ
[$code, $output] = hook_run('php scripts/check.php --changed --stamp', $root);

if ($code === 0) {
    hook_ok();
}

hook_deny(
    "ชุดตรวจมาตรฐานไม่ผ่าน — ยังไม่ commit\n\n"
    . $output
    . "\n\n**แก้ที่โค้ดก่อน ห้ามแก้ที่ scripts/check.php ให้ผ่าน**"
);
