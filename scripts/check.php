<?php
/**
 * ชุดตรวจมาตรฐาน — กำแพงที่กฎในโฟลเดอร์ standard/ พึ่งพา
 *
 *   php scripts/check.php                    ตรวจทั้งหมด
 *   php scripts/check.php security i18n      ตรวจเฉพาะบางกลุ่ม
 *   php scripts/check.php --file app/x.php   ตรวจเฉพาะไฟล์เดียว (ใช้โดย hook)
 *   php scripts/check.php --changed          ตรวจไฟล์ที่เปลี่ยนตั้งแต่แตกจาก main + การตรวจทั้งโปรเจค
 *                                            แตะของกลางเมื่อไหร่ยกระดับเป็นตรวจเต็มเอง
 *     --base <branch>                        เทียบกับ branch อื่นที่ไม่ใช่ main
 *   php scripts/check.php --list             ดูรายการตรวจทั้งหมด
 *
 * ตรวจเต็มยังจำเป็นที่ ปิด phase (C4) · ประตู D · ก่อนเปิด PR · CI — ดู standard/testing.md ข้อ 2.1
 *
 * exit 0 = ผ่าน · exit 1 = มีข้อ error
 *
 * ‼️ ห้ามแก้ไฟล์นี้เพื่อให้โค้ดผ่าน — ถ้าเชื่อว่ากฎผิดจริง ให้แจ้งผู้ใช้
 *    แล้วแก้ที่ repo มาตรฐานกลาง ไม่ใช่แก้ในโปรเจค
 */

declare(strict_types=1);

require __DIR__ . '/lib/Ctx.php';
require __DIR__ . '/lib/Report.php';

$root = dirname(__DIR__);
$argv = $_SERVER['argv'] ?? [];
array_shift($argv);

$onlyFile    = null;
$groups      = [];
$listOnly    = false;
$changedMode = false;
$base        = null;
$stamp       = false;   // hook ของ Claude Code ใช้ — ผ่านแล้วประทับตราไว้ให้ pre-commit
$reuseStamp  = false;   // git pre-commit ใช้ — ตราตรงกับงานชุดนี้แล้วไม่ต้องรันซ้ำ

for ($i = 0; $i < count($argv); $i++) {
    $arg = $argv[$i];

    if ($arg === '--file') {
        $onlyFile = $argv[++$i] ?? null;
        continue;
    }
    if (str_starts_with($arg, '--file=')) {
        $onlyFile = substr($arg, 7);
        continue;
    }
    if ($arg === '--list') {
        $listOnly = true;
        continue;
    }
    if ($arg === '--changed') {
        $changedMode = true;
        continue;
    }
    if ($arg === '--base') {
        $base = $argv[++$i] ?? null;
        continue;
    }
    if (str_starts_with($arg, '--base=')) {
        $base = substr($arg, 7);
        continue;
    }
    if ($arg === '--stamp') {
        $stamp = true;
        continue;
    }
    if ($arg === '--reuse-stamp') {
        $reuseStamp = true;
        continue;
    }
    if (str_starts_with($arg, '--')) {
        continue;
    }

    $groups[] = $arg;
}

if ($onlyFile !== null) {
    $onlyFile = str_replace('\\', '/', $onlyFile);
    $onlyFile = ltrim(str_replace(str_replace('\\', '/', $root), '', $onlyFile), '/');
}

// ---------------------------------------------------------------- โหลดการตรวจ

$checks = [];

foreach (glob(__DIR__ . '/checks/*.php') ?: [] as $path) {
    $group = basename($path, '.php');
    $defs  = require $path;

    if (!is_array($defs)) {
        continue;
    }

    foreach ($defs as $id => $def) {
        $def['id']    = $id;
        $def['group'] = $group;
        $checks[]     = $def;
    }
}

usort($checks, fn (array $a, array $b) => [$a['group'], $a['id']] <=> [$b['group'], $b['id']]);

if ($listOnly) {
    $current = '';
    foreach ($checks as $check) {
        if ($check['group'] !== $current) {
            $current = $check['group'];
            echo "\n" . Report::bold($current) . "\n";
        }
        printf("  %-26s %s\n", $check['id'], $check['title']);
    }
    echo "\n";
    exit(0);
}

// ---------------------------------------------------------------- คัดการตรวจ

if ($groups !== []) {
    $checks = array_values(array_filter(
        $checks,
        fn (array $c) => in_array($c['group'], $groups, true) || in_array($c['id'], $groups, true)
    ));

    if ($checks === []) {
        Report::line('ไม่พบกลุ่มการตรวจที่ระบุ — ดูรายการทั้งหมดด้วย --list');
        exit(1);
    }
}

if ($onlyFile !== null) {
    $ext = strtolower((string) pathinfo($onlyFile, PATHINFO_EXTENSION));

    $checks = array_values(array_filter($checks, function (array $c) use ($ext) {
        // การตรวจที่ไม่ผูกกับไฟล์ (เช่น เทียบ hash มาตรฐาน) ข้ามตอนตรวจรายไฟล์
        if (($c['scope'] ?? null) === null) {
            return false;
        }

        return in_array($ext, $c['scope'], true);
    }));
}

// ---------------------------------------------------------------- รัน

$changed = null;
$label   = null;

if ($onlyFile === null && ($changedMode || $stamp || $reuseStamp)) {
    require __DIR__ . '/lib/Changes.php';
}

if ($onlyFile === null && $reuseStamp && Changes::consumeStamp($root)) {
    Report::line(Report::green('✓') . ' ชุดตรวจผ่านแล้วเมื่อครู่กับงานชุดนี้ (hook ของ Claude Code) — ไม่รันซ้ำ');
    exit(0);
}

if ($onlyFile === null && $changedMode) {
    $found = Changes::detect($root, $base);

    if ($found['files'] === null) {
        Report::line('ตรวจทั้งโปรเจค — ' . $found['reason']);
    } else {
        $shared = Changes::shared($found['files']);

        if ($shared !== []) {
            Report::line('ตรวจทั้งโปรเจค — แตะของกลาง ('
                . implode(' · ', array_slice($shared, 0, 3)) . (count($shared) > 3 ? ' …' : '')
                . ') ซึ่งกระทบหน้าที่ไม่ได้แตะด้วย');
        } else {
            $changed = $found['files'];
            $label   = count($changed) . ' ไฟล์ที่เปลี่ยน (' . $found['label'] . ') + การตรวจทั้งโปรเจค';
        }

        $blockers = Changes::notSmall($found['files']);
        Report::line($blockers === []
            ? 'ขนาดงาน: เข้าเกณฑ์งานเล็ก (standard/flow.md ข้อ 13)'
            : 'ขนาดงาน: ไม่ใช่งานเล็ก — ' . implode(' · ', $blockers));
    }
}

$ctx = new Ctx($root, $onlyFile, $changed);

foreach ($checks as $check) {
    $ctx->begin($check['id'], $check['group'], $check['title']);

    // การตรวจรายไฟล์เห็นเฉพาะไฟล์ที่เปลี่ยน · การตรวจที่เทียบข้ามไฟล์ (ไม่มี scope) เห็นทั้งโปรเจคเสมอ
    if ($changed !== null) {
        $ctx->limitTo(($check['scope'] ?? null) === null ? null : $changed);
    }

    try {
        ($check['run'])($ctx);
    } catch (Throwable $e) {
        $ctx->error('scripts/checks/' . $check['group'] . '.php', 0,
            'การตรวจล้มเหลว: ' . $e->getMessage(),
            'แจ้งผู้ใช้ — นี่เป็นบั๊กของตัวตรวจเอง ไม่ใช่ของโค้ดที่ถูกตรวจ');
    }
}

$code = Report::render($ctx, $onlyFile, $label);

if ($code === 0 && $stamp && $onlyFile === null) {
    Changes::writeStamp($root);
}

exit($code);
