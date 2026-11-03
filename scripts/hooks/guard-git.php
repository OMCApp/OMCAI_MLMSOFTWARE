<?php
/**
 * PreToolUse hook (git push) — กันการ push ที่ย้อนกลับยาก
 *
 * ปัญหาที่กัน: push ทับงานคนอื่นโดยไม่รู้ตัว และ push ตรงขึ้น main
 */

declare(strict_types=1);

require __DIR__ . '/hook.php';

$root    = hook_project_root();
$data    = hook_input();
$command = (string) (hook_get($data, 'tool_input.command') ?? '');

/*
 * ‼️ กรองเองในสคริปต์ ห้ามพึ่งเงื่อนไข if ใน settings.json อย่างเดียว
 *
 * ‼️ และต้องตรวจเฉพาะอาร์กิวเมนต์ของ git push เท่านั้น ห้ามตรวจทั้งบรรทัด
 *    ไม่งั้นคำสั่งอย่าง `git rev-parse origin/main && git push origin old`
 *    จะถูกจับว่ากำลัง push ขึ้น main ทั้งที่ไม่ใช่
 */
/*
 * ‼️ (?:\w+=\S*\s+)* จำเป็น เพราะคำสั่งอาจมีตัวแปรสภาพแวดล้อมนำหน้า
 *    ถ้าไม่รองรับ คำสั่งอย่าง FOO=1 git push --force origin main
 *    จะหา git push ไม่เจอเลย แล้วผ่าน guard ไปทั้งหมด
 */
if (preg_match('/(?:^|[;&|]\s*)(?:\w+=\S*\s+)*git\s+(?:-\S+\s+)*push\b([^;&|]*)/', $command, $matches) !== 1) {
    hook_ok();
}

$args = $matches[1];

// force push ต้องให้ผู้ใช้เป็นคนตัดสินใจเสมอ
if (preg_match('/(--force\b|--force-with-lease\b|\s-f\b)/', $args) === 1) {
    hook_deny(
        "กำลังจะ force push\n\n"
        . "กฎเหล็ก: `force push` ต้องขออนุญาตผู้ใช้ก่อนทุกครั้ง "
        . "เพราะเขียนทับประวัติของคนอื่นได้และกู้คืนยาก\n\n"
        . "อธิบายให้ผู้ใช้ทราบว่าทำไมถึงจำเป็น แล้วให้ผู้ใช้เป็นคนสั่งเอง"
    );
}

// push ตรงขึ้น main
if (preg_match('/\b(?:main|master)\b/', $args) === 1 && !hook_user_allowed_main($command)) {
    hook_deny(
        "กำลังจะ push ตรงขึ้น main\n\n"
        . "กฎเหล็ก: การ merge เข้า main เป็นหน้าที่ของผู้ใช้ "
        . "AI มีหน้าที่ push branch แล้วเปิด PR เท่านั้น\n\n"
        . "ให้ push branch ปัจจุบันแล้วเปิด PR แทน\n\n"
        . "ถ้าผู้ใช้สั่งให้ push ขึ้น main เองในรอบนี้ ให้ผู้ใช้เป็นคนอนุญาตในคำสั่ง:\n"
        . "    WF_ALLOW_MAIN=1 git push origin main\n"
        . "‼️ AI ห้ามเติมคำอนุญาตนี้เองถ้าผู้ใช้ไม่ได้สั่งในรอบนั้น"
    );
}

// เตือนถ้ายังไม่ได้ fetch — เสี่ยง conflict ที่มองไม่เห็น
[$code, $out] = hook_run('git rev-list --count HEAD..@{upstream}', $root);

if ($code === 0 && (int) trim($out) > 0) {
    hook_deny(
        'remote มี commit ใหม่ ' . trim($out) . " รายการที่เครื่องนี้ยังไม่มี\n\n"
        . "ต้อง `git fetch` / `git pull` แล้วตรวจ conflict ก่อน push ทุกครั้ง\n"
        . "ถ้าพบความเสี่ยง ต้องแจ้งรายละเอียดให้ผู้ใช้ทราบก่อน ห้าม merge หรือ push ทับเอง"
    );
}

hook_ok();
