<?php
/**
 * ตัวช่วยกลางของ hook ทุกตัว
 *
 * hook ของ Claude Code รับ JSON ทาง stdin และตอบกลับเป็น JSON ทาง stdout
 * ไฟล์นี้ห่อรายละเอียดพวกนั้นไว้ เพื่อให้ hook แต่ละตัวสั้นและอ่านง่าย
 *
 * ‼️ ใช้ PHP ล้วน ไม่พึ่ง jq — เพราะ jq ไม่ได้ติดมากับ Windows
 *    แต่ PHP มีอยู่แล้วทุกเครื่องในทีมเพราะเป็น stack ของโปรเจค
 */

declare(strict_types=1);

/** อ่าน payload ที่ Claude Code ส่งมาทาง stdin */
function hook_input(): array
{
    $raw = stream_get_contents(STDIN);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

/** อ่านค่าซ้อนชั้นแบบ "tool_input.file_path" */
function hook_get(array $data, string $path, mixed $default = null): mixed
{
    foreach (explode('.', $path) as $key) {
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return $default;
        }
        $data = $data[$key];
    }

    return $data;
}

/** รากของโปรเจค — ไฟล์นี้อยู่ที่ scripts/hooks/ */
function hook_project_root(): string
{
    return dirname(__DIR__, 2);
}

/** จบแบบเงียบ ไม่มีอะไรจะบอก */
function hook_ok(): never
{
    exit(0);
}

/** ส่งข้อความเข้า context ของโมเดล แล้วจบ */
function hook_context(string $text, string $event = 'SessionStart'): never
{
    echo json_encode([
        'suppressOutput'    => true,
        'hookSpecificOutput' => [
            'hookEventName'     => $event,
            'additionalContext' => $text,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit(0);
}

/** ปฏิเสธคำสั่งใน PreToolUse พร้อมเหตุผล */
function hook_deny(string $reason): never
{
    echo json_encode([
        'hookSpecificOutput' => [
            'hookEventName'            => 'PreToolUse',
            'permissionDecision'       => 'deny',
            'permissionDecisionReason' => $reason,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit(0);
}

/** แจ้งเตือนผู้ใช้บนหน้าจอ โดยไม่บล็อก */
function hook_warn(string $message): never
{
    echo json_encode([
        'systemMessage' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit(0);
}

/**
 * รันคำสั่งแล้วคืน [exit code, output]
 * ใช้แทน shell_exec เพื่อให้ได้ exit code จริงและได้ทั้ง stdout/stderr
 *
 * ‼️ ต้องรวม stderr เข้า stdout ด้วย 2>&1 ห้ามเปิดสองท่อแล้วอ่านทีละท่อ
 *    เปิดสองท่อแล้วอ่าน stdout จนจบก่อน จะค้างตายเมื่อคำสั่งเขียน stderr มากพอจนท่อเต็ม
 *    ฝั่งเรารอ stdout ฝั่งมันรอให้ stderr ว่าง ไม่มีใครขยับ
 *    hook ที่ค้างแบบนี้จะทำให้ Claude Code ค้างตามไปด้วยจนกว่าจะหมด timeout
 *    และ stream_select ใช้กับ pipe ของ proc_open บน Windows ไม่ได้ จึงต้องใช้ท่อเดียว
 */
function hook_run(string $command, string $cwd): array
{
    $proc = proc_open($command . ' 2>&1', [1 => ['pipe', 'w']], $pipes, $cwd);

    if (!is_resource($proc)) {
        return [-1, ''];
    }

    $out = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return [proc_close($proc), trim($out)];
}

/** ยังไม่มี scripts/check.php (ตอนโปรเจคเพิ่ง clone) ให้ปล่อยผ่านอย่างเงียบ */
function hook_checker_missing(string $root): bool
{
    return !is_file($root . '/scripts/check.php');
}

/**
 * ผู้ใช้สั่งให้แตะ main ในครั้งนี้โดยเฉพาะหรือไม่
 *
 * กฎเหล็กเขียนไว้ว่า "ห้าม commit ลง main ยกเว้น ... ผู้ใช้สั่งให้ push ขึ้น main เอง"
 * ข้อยกเว้นนี้มีอยู่ในกฎอยู่แล้ว แต่ hook มองไม่เห็นเจตนาของผู้ใช้
 * จึงต้องให้ผู้ใช้เขียนคำอนุญาตลงในคำสั่งตรงๆ
 *
 *     WF_ALLOW_MAIN=1 git push origin main
 *
 * ‼️ เจตนาของการออกแบบแบบนี้
 *    · ต้องพิมพ์เพิ่มเอง ทำโดยบังเอิญไม่ได้
 *    · เห็นอยู่ในคำสั่ง จึงตรวจย้อนหลังได้ว่าใครอนุญาตเมื่อไหร่
 *    · AI ห้ามใส่เองถ้าผู้ใช้ไม่ได้สั่งในรอบนั้น — ใส่เองเมื่อไหร่เท่ากับข้ามกฎ
 */
function hook_user_allowed_main(string $command): bool
{
    return preg_match('/\bWF_ALLOW_MAIN=1\b/', $command) === 1;
}
