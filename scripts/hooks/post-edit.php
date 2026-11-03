<?php
/**
 * PostToolUse hook (Write|Edit) — ตรวจไฟล์ที่เพิ่งแก้ทันที
 *
 * ตรวจตอนนี้ดีกว่ารอไปเจอตอน commit เพราะ AI ยังจำบริบทของสิ่งที่เพิ่งเขียนอยู่
 * และแก้ทันทีถูกกว่าไล่แก้ทีหลังหลายไฟล์พร้อมกัน
 */

declare(strict_types=1);

require __DIR__ . '/hook.php';

$root = hook_project_root();
$data = hook_input();

$file = (string) (hook_get($data, 'tool_input.file_path')
    ?? hook_get($data, 'tool_response.filePath')
    ?? '');

if ($file === '' || hook_checker_missing($root)) {
    hook_ok();
}

// ตรวจเฉพาะไฟล์ที่กฎมาตรฐานครอบคลุม
$ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, ['php', 'css', 'js', 'sql'], true)) {
    hook_ok();
}

// ไฟล์นอกโฟลเดอร์ของแอปไม่ต้องตรวจ (เช่น vendor, project, docs)
$relative = str_replace('\\', '/', $file);
$relative = str_replace(str_replace('\\', '/', $root) . '/', '', $relative);

$watched = ['app/', 'core/', 'config/', 'public/', 'database/', 'lang/'];
$inScope = false;
foreach ($watched as $prefix) {
    if (str_starts_with($relative, $prefix)) {
        $inScope = true;
        break;
    }
}

if (!$inScope) {
    hook_ok();
}

[$code, $output] = hook_run(
    'php scripts/check.php --file ' . escapeshellarg($relative),
    $root
);

if ($code === 0 || $output === '') {
    hook_ok();
}

hook_context(
    "ชุดตรวจมาตรฐานพบปัญหาในไฟล์ที่เพิ่งแก้ (`{$relative}`)\n\n"
    . $output
    . "\n\n**แก้ที่โค้ดทันที ห้ามแก้ที่ scripts/check.php ให้ผ่าน**"
    . " ถ้าเชื่อว่ากฎในสคริปต์ผิดจริง ให้แจ้งผู้ใช้ แล้วแก้ที่ repo มาตรฐาน ไม่ใช่แก้ในโปรเจค",
    'PostToolUse'
);
