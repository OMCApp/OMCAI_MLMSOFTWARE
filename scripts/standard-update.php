<?php
/**
 * อัปเดตมาตรฐานในโปรเจค — ดูก่อนเสมอ แล้วค่อยลงมือ
 *
 *   php scripts/standard-update.php              ดูว่าจะเปลี่ยนอะไร ไม่เขียนอะไรเลย
 *   php scripts/standard-update.php --yes        ลงมือ
 *     --ref <tag หรือ branch>                    เลือกเวอร์ชัน · ค่าเริ่มต้นคือล่าสุดของ repo กลาง
 *     --from <url หรือ path>                     แหล่งอื่นที่ไม่ใช่ .standard-source
 *
 *   php scripts/standard-update.php --seal       บันทึกลายเซ็นของส่วนที่เป็นของมาตรฐาน (repo กลางใช้ตอนปล่อยเวอร์ชัน)
 *   php scripts/standard-update.php --verify     เทียบว่าไฟล์ของมาตรฐานถูกแก้ในโปรเจคไหม
 *
 * ‼️ ตัวที่ลงมือจริงคือ updater ของเวอร์ชันที่ดึงมา ไม่ใช่ตัวที่อยู่ในโปรเจค
 *    ตัวในโปรเจคแค่ดึง repo กลางมาไว้ชั่วคราว แล้วส่งต่อด้วย --apply --target
 *    โปรเจคที่ updater เก่าจึงได้วิธีอัปเดตของเวอร์ชันใหม่เสมอ
 *
 *    เหตุผล: updater ถึง 3.14.0 ทับแค่ standard/ กับ CLAUDE.md
 *    สคริปต์ตรวจรุ่นใหม่ไม่เคยไปถึงโปรเจคที่อัปเดต กฎใหม่จึงอ้างถึงการตรวจที่ไม่มีอยู่
 *    และ ⚠️ ใน CHANGELOG ทั้ง 23 จุดไม่มีจุดไหนถูกแก้ในโปรเจคจริง
 *
 * ทับเฉพาะส่วนที่เป็นของมาตรฐาน (Manifest::MANAGED) · รวมค่า .claude/settings.json
 * ไม่แตะ project/ และโค้ดของโปรเจค ยกเว้นที่ขั้นอัปเกรดใน scripts/upgrades/ บอกไว้
 */

declare(strict_types=1);

require __DIR__ . '/lib/Manifest.php';

/** แหล่งเริ่มต้นเมื่อโปรเจคไม่มี .standard-source — โปรเจคที่เริ่มก่อน 3.14.0 ไม่เคยมีไฟล์นี้ */
const DEFAULT_SOURCE = 'https://github.com/OMC-MLM/OMC_MD.git';

/** เวอร์ชันแรกที่ updater รับช่วงแบบ --apply --target ได้ */
const FIRST_HANDOFF = '3.15.0';

$self = dirname(__DIR__);
$args = array_slice($_SERVER['argv'] ?? [], 1);

$has = static fn (string $flag): bool => in_array($flag, $args, true);
$opt = static function (string $name) use ($args): ?string {
    foreach ($args as $i => $arg) {
        if ($arg === $name) {
            return $args[$i + 1] ?? null;
        }
        if (str_starts_with($arg, $name . '=')) {
            return substr($arg, strlen($name) + 1);
        }
    }

    return null;
};

// ---------------------------------------------------------------------- seal

if ($has('--seal')) {
    $count = Manifest::write($self);

    out('✓ บันทึกลายเซ็นของมาตรฐานเวอร์ชัน ' . read_version($self) . " แล้ว ({$count} ไฟล์)");
    out('  ครอบคลุม ' . implode(' · ', Manifest::MANAGED) . ' — แก้ในโปรเจคเมื่อไหร่ check.php จับได้');
    exit(0);
}

// -------------------------------------------------------------------- verify

if ($has('--verify')) {
    $expected = Manifest::read($self . '/' . Manifest::FILE);

    if ($expected === []) {
        fail('ยังไม่มีลายเซ็น — รัน `php scripts/standard-update.php --seal` ก่อน');
    }

    $changed = [];

    foreach (Manifest::build($self) as $path => $hash) {
        if (!isset($expected[$path])) {
            $changed[] = "เพิ่มเข้ามา: {$path}";
        } elseif ($expected[$path] !== $hash) {
            $changed[] = "ถูกแก้:     {$path}";
        }
        unset($expected[$path]);
    }

    foreach (array_keys($expected) as $path) {
        $changed[] = "หายไป:     {$path}";
    }

    if ($changed === []) {
        out('✓ ของมาตรฐานตรงกับลายเซ็น ไม่มีใครแก้ในโปรเจค');
        exit(0);
    }

    fwrite(STDERR, '✗ ของมาตรฐานถูกแก้ในโปรเจค ' . count($changed) . " จุด\n\n  " . implode("\n  ", $changed) . "\n\n");
    fwrite(STDERR, "คืนค่าเดิมด้วย `php scripts/standard-update.php` (ดูก่อน แล้วสั่ง --yes)\n");
    fwrite(STDERR, "อยากแก้กฎหรือสคริปต์ต้องแก้ที่ repo มาตรฐานกลางแล้วปล่อยเวอร์ชันใหม่ · ต้องเบี่ยงเบนให้บันทึกที่ project/NOTES.md\n");
    exit(1);
}

// --------------------------------------------------------- รับช่วงจากโปรเจค

if ($has('--apply')) {
    $target = $opt('--target');

    if ($target === null || !is_dir($target)) {
        fail('ต้องระบุ --target <โฟลเดอร์โปรเจค>');
    }

    exit(apply_update($self, (string) realpath($target), $has('--yes'), $opt('--origin')));
}

// ------------------------------------------------ ดึง repo กลางแล้วส่งต่อ

$source = $opt('--from');

if ($source === null && is_file($self . '/.standard-source')) {
    $source = trim((string) file_get_contents($self . '/.standard-source'));
}

if ($source === null || $source === '') {
    $source = DEFAULT_SOURCE;
}

$ref  = $opt('--ref');
$temp = null;

if (is_remote($source)) {
    $temp = sys_get_temp_dir() . '/standard-' . bin2hex(random_bytes(4));

    out("ดึงมาตรฐานจาก {$source}" . ($ref !== null ? " ({$ref})" : '') . ' ...');

    exec(
        'git clone --quiet --depth 1 '
        . ($ref !== null ? '--branch ' . escapeshellarg($ref) . ' ' : '')
        . escapeshellarg($source) . ' ' . escapeshellarg($temp) . ' 2>&1',
        $lines,
        $code
    );

    if ($code !== 0) {
        remove_tree($temp);
        fail("ดึงจาก git ไม่สำเร็จ:\n  " . implode("\n  ", $lines));
    }

    $dir = $temp;
} else {
    if ($ref !== null) {
        fail('--ref ใช้ได้เฉพาะแหล่งที่เป็น git URL — แหล่งที่เป็นโฟลเดอร์ ให้ checkout เวอร์ชันที่ต้องการในโฟลเดอร์นั้นเอง');
    }

    $dir = (string) realpath($source);

    if ($dir === '' || !is_dir($dir . '/standard')) {
        fail("ไม่พบโฟลเดอร์ standard/ ที่ {$source}");
    }
    if ($dir === realpath($self)) {
        fail('แหล่งกับโปรเจคเป็นโฟลเดอร์เดียวกัน');
    }
}

$version = read_version($dir);

if (!is_file($dir . '/scripts/standard-update.php') || version_compare($version, FIRST_HANDOFF, '<')) {
    if ($temp !== null) {
        remove_tree($temp);
    }
    fail("มาตรฐานที่ดึงมาเป็นเวอร์ชัน {$version} — อัปเดตด้วยวิธีนี้ได้ตั้งแต่ " . FIRST_HANDOFF . ' ขึ้นไป');
}

// ‼️ ใช้ `php` จาก PATH แบบเดียวกับ hook ทุกตัว — ไม่ใช้ PHP_BINARY
//    path ของ PHP_BINARY มักมีช่องว่าง และ cmd.exe ตัดเครื่องหมายคำพูดตัวแรกทิ้งเมื่อคำสั่งขึ้นต้นด้วยมัน
passthru(
    'php ' . escapeshellarg($dir . '/scripts/standard-update.php')
    . ' --apply --target ' . escapeshellarg($self)
    . ' --origin ' . escapeshellarg($source)
    . ($has('--yes') ? ' --yes' : ''),
    $code
);

if ($temp !== null) {
    remove_tree($temp);
}

exit($code);

// ============================================================ ตัวลงมือจริง

/**
 * รันจาก repo มาตรฐานเวอร์ชันใหม่ ($source) เข้าไปที่โปรเจค ($target)
 */
function apply_update(string $source, string $target, bool $yes, ?string $origin): int
{
    $before = read_version($target);
    $after  = read_version($source);

    if ($before === '') {
        fail("{$target} ไม่ใช่โปรเจคที่ใช้มาตรฐานนี้ — ไม่มี standard/VERSION");
    }
    if (version_compare($after, $before, '<')) {
        fail("มาตรฐานที่ดึงมา ({$after}) เก่ากว่าที่โปรเจคใช้อยู่ ({$before}) — ย้อนเวอร์ชันไม่รองรับ ให้ย้อนด้วย git แทน");
    }

    // ------------------------------------------------------------------ แผน

    $src = Manifest::build($source);
    $dst = Manifest::build($target);
    $old = Manifest::read($target . '/' . Manifest::FILE);   // ลายเซ็นตอนที่โปรเจครับเวอร์ชันเดิมมา

    $add = $change = $remove = $keep = $edited = $unknown = [];

    foreach ($src as $path => $hash) {
        if (!isset($dst[$path])) {
            $add[] = $path;
            continue;
        }
        if ($dst[$path] === $hash) {
            continue;
        }

        $change[] = $path;

        if (!isset($old[$path])) {
            $unknown[] = $path;
        } elseif ($old[$path] !== $dst[$path]) {
            $edited[] = $path;
        }
    }

    foreach ($dst as $path => $hash) {
        if (isset($src[$path])) {
            continue;
        }

        // ‼️ ลบเฉพาะไฟล์ที่มีในลายเซ็นเดิม — คือของที่มาตรฐานเคยให้มาแล้วเลิกใช้
        //    ไฟล์ที่ไม่มีในลายเซ็นแยกไม่ออกว่าเป็นของโปรเจคหรือของรุ่นเก่า จึงไม่ลบ แต่บอก
        if (isset($old[$path])) {
            $remove[] = $path;
            if ($old[$path] !== $hash) {
                $edited[] = $path;
            }
        } else {
            $keep[] = $path;
        }
    }

    $settings = merge_settings($source . '/.claude/settings.json', $target . '/.claude/settings.json');
    $upgrades = pending_upgrades($source, $before, $after);
    $notes    = changelog_between($source, $before, $after);

    [$blockers, $warnings] = preflight($target);

    if ($settings['error'] !== null) {
        $blockers[] = $settings['error'];
    }
    if ($edited !== []) {
        $warnings[] = 'ไฟล์ของมาตรฐานที่โปรเจคแก้เอง ' . count($edited) . ' ไฟล์ จะถูกทับ — '
            . 'ถ้าต้องเบี่ยงเบนจริง บันทึกเหตุผลที่ project/NOTES.md แล้วเสนอแก้ที่ repo กลาง: '
            . implode(' · ', $edited);
    }
    if ($keep !== []) {
        $warnings[] = 'ไฟล์ที่ไม่มีในมาตรฐานเวอร์ชันใหม่ ' . count($keep) . ' ไฟล์ — ไม่ลบให้ เพราะแยกไม่ออกว่าเป็นของโปรเจค'
            . 'หรือของมาตรฐานรุ่นเก่า · check.php จะทักจนกว่าจะย้ายออกหรือลบ: ' . implode(' · ', $keep);
    }

    $nothing = $add === [] && $change === [] && $remove === [] && !$settings['changed'] && $upgrades === [];

    // ------------------------------------------------------------ แสดงแผน

    out();
    out("มาตรฐาน {$before} → {$after}" . ($before === $after ? ' (เวอร์ชันเดียวกัน — คืนไฟล์ของมาตรฐานให้ตรงต้นฉบับ)' : ''));
    if ($origin !== null) {
        out("แหล่ง: {$origin}");
    }

    if ($notes !== []) {
        out();
        out('เวอร์ชันระหว่างทาง: ' . implode(' · ', array_keys($notes)));

        foreach ($notes as $version => $marks) {
            foreach ($marks as $mark) {
                out("  ⚠️ {$version} — {$mark}");
            }
        }
    }

    out();

    if ($nothing) {
        out('✓ โปรเจคตรงกับมาตรฐานเวอร์ชันนี้แล้ว ไม่มีอะไรต้องทำ');
        return 0;
    }

    out('ไฟล์ของมาตรฐาน — เพิ่ม ' . count($add) . ' · แก้ ' . count($change) . ' · ลบ ' . count($remove));

    $rows = [
        ...array_map(static fn (string $p): string => "  + {$p}", $add),
        ...array_map(static fn (string $p): string => "  ~ {$p}", $change),
        ...array_map(static fn (string $p): string => "  - {$p}", $remove),
    ];

    foreach (array_slice($rows, 0, 40) as $row) {
        out($row);
    }
    if (count($rows) > 40) {
        out('  … อีก ' . (count($rows) - 40) . ' ไฟล์');
    }

    if ($settings['changed']) {
        out('  ~ .claude/settings.json (รวมค่า: hooks ของมาตรฐาน + permission ทั้งสองฝั่ง'
            . ($settings['extras'] !== [] ? ' · เก็บของโปรเจคไว้ ' . count($settings['extras']) . ' รายการ' : '') . ')');
    }

    if ($unknown !== []) {
        out('  · ' . count($unknown) . ' ไฟล์ในนี้ไม่มีลายเซ็นเดิมให้เทียบ (ส่วนที่มาตรฐานก่อน 3.15.0 ไม่ได้เฝ้า) '
            . '— แยกไม่ออกว่าโปรเจคแก้เองหรือแค่เป็นรุ่นเก่า ดูใน git diff หลังอัปเดต');
    }

    if ($upgrades !== []) {
        out();
        out('ขั้นอัปเกรดที่จะรัน');

        foreach ($upgrades as $version => $file) {
            foreach (load_steps($file) as $step) {
                out("  {$version} — {$step['title']}");
            }
        }
    }

    out();
    out('ไม่แตะ: project/ · app/ · core/ · lang/ · database/ · public/ · config/ · .github/ — '
        . 'โค้ดของโปรเจคเปลี่ยนเฉพาะที่ขั้นอัปเกรดบอกว่าแก้ให้');

    foreach ($warnings as $warning) {
        out();
        out("! {$warning}");
    }

    if ($blockers !== []) {
        out();
        foreach ($blockers as $blocker) {
            out("✗ ต้องแก้ก่อน: {$blocker}");
        }
    }

    if (!$yes) {
        out();
        out($blockers === []
            ? '→ ยังไม่ได้เขียนอะไร · สั่งซ้ำพร้อม --yes เพื่อลงมือ'
            : '→ ยังไม่ได้เขียนอะไร · แก้ข้อที่ต้องแก้ก่อน แล้วสั่งซ้ำพร้อม --yes');

        return 0;
    }

    if ($blockers !== []) {
        return 1;
    }

    // --------------------------------------------------------------- ลงมือ

    foreach ([...$add, ...$change] as $path) {
        $to = $target . '/' . $path;

        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0o777, true);
        }

        copy($source . '/' . $path, $to);
    }

    foreach ($remove as $path) {
        @unlink($target . '/' . $path);
    }

    if ($settings['changed'] && $settings['content'] !== null) {
        if (!is_dir($target . '/.claude')) {
            mkdir($target . '/.claude', 0o777, true);
        }
        file_put_contents($target . '/.claude/settings.json', $settings['content']);
    }

    // ‼️ ใช้ลายเซ็นของต้นฉบับ ห้ามคำนวณจากไฟล์ในโปรเจค
    //    คำนวณจากโปรเจคแปลว่าไฟล์ที่ไม่ได้ลบ ($keep) จะถูกประทับว่าเป็นของมาตรฐานไปด้วย
    //    แล้ว check.php จะไม่มีวันทักมันอีก
    if (is_file($source . '/' . Manifest::FILE)) {
        copy($source . '/' . Manifest::FILE, $target . '/' . Manifest::FILE);
    } else {
        Manifest::write($target);
    }

    // AGENTS.md คือ CLAUDE.md ที่เปลี่ยนชื่อไฟล์ตัวเอง สำหรับ AI ตัวอื่นที่อ่านชื่อนี้ — ต้อง generate ทุกครั้ง
    if (is_file($target . '/CLAUDE.md')) {
        file_put_contents(
            $target . '/AGENTS.md',
            str_replace('CLAUDE.md', 'AGENTS.md', (string) file_get_contents($target . '/CLAUDE.md'))
        );
    }

    // copy() ไม่พาสิทธิ์รันมาด้วย — pre-commit ที่รันไม่ได้คือกำแพงที่หายไปแบบเงียบบน macOS/Linux
    if (PHP_OS_FAMILY !== 'Windows') {
        foreach (glob($target . '/scripts/hooks/*') ?: [] as $hook) {
            if (pathinfo($hook, PATHINFO_EXTENSION) === '') {
                @chmod($hook, 0o755);
            }
        }
    }

    if ($origin !== null && is_remote($origin) && !is_file($target . '/.standard-source')) {
        file_put_contents($target . '/.standard-source', $origin . "\n");
    }

    out();
    out('✓ ทับไฟล์ของมาตรฐานแล้ว');

    $manual = [];

    if ($upgrades !== []) {
        out();
        out('ขั้นอัปเกรด');

        foreach ($upgrades as $version => $file) {
            foreach (load_steps($file) as $id => $step) {
                try {
                    [$status, $message] = ($step['run'])($target);
                } catch (Throwable $e) {
                    [$status, $message] = ['manual', 'ขั้นนี้ล้มเหลว: ' . $e->getMessage()];
                }

                $mark = match ($status) {
                    'done'  => '✓ แก้ให้แล้ว',
                    'ok'    => '✓ ไม่ต้องทำอะไร',
                    default => '! ต้องแก้เอง',
                };

                out("  {$mark} — {$version} {$step['title']}");
                out("    {$message}");

                if (!in_array($status, ['done', 'ok'], true)) {
                    $manual[] = "{$version}/{$id}";
                }
            }
        }
    }

    out();
    out('รันชุดตรวจทั้งโปรเจค ...');

    [$checkCode, $checkOut] = run_in('php scripts/check.php', $target);

    if ($checkCode === 0) {
        out('✓ ชุดตรวจผ่านทั้งหมด');
    } else {
        $lines = preg_split('/\R/', trim($checkOut)) ?: [];
        out(implode("\n", array_slice($lines, -25)));
    }

    out();
    out("✓ อัปเดตมาตรฐาน {$before} → {$after} แล้ว");
    out();
    out('ขั้นต่อไป');
    out("  1. ดู git diff แล้ว commit เป็นก้อนเดียว — \"chore: อัปเดตมาตรฐาน {$before} → {$after}\"");
    out('  2. บันทึกลง project/STATE.md หัวข้อ "ตัดสินใจอะไรไปแล้ว" ว่าอัปเดตเมื่อไหร่ จากเวอร์ชันไหน');

    $n = 3;
    if ($manual !== []) {
        out("  {$n}. ขั้นอัปเกรดที่ต้องแก้เอง " . count($manual) . ' ข้อข้างบน');
        $n++;
    }
    if ($checkCode !== 0) {
        out("  {$n}. ชุดตรวจไม่ผ่าน — กฎใหม่จับของที่มีอยู่เดิม แยกเป็นงานต่อจากนี้ ไม่ต้องรวมใน commit ของการอัปเดต");
    }

    return 0;
}

// ============================================================ ตัวช่วย

/**
 * รวม .claude/settings.json — hooks และค่าอื่นของมาตรฐานชนะ · permission ของทั้งสองฝั่งรวมกัน
 *
 * ‼️ ห้ามทับทั้งไฟล์ — ไฟล์นี้เป็นทั้งที่ผูก hook ของมาตรฐาน และที่ที่ทีมเพิ่ม permission ของโปรเจคเอง
 *    ทับทั้งไฟล์คือลบ permission ของทีมทิ้งทุกครั้งที่อัปเดต · ไม่แตะเลยคือ hook ใหม่ไม่เคยถูกผูก
 *
 * @return array{content: ?string, changed: bool, error: ?string, extras: list<string>}
 */
function merge_settings(string $from, string $to): array
{
    $none = ['content' => null, 'changed' => false, 'error' => null, 'extras' => []];

    if (!is_file($from)) {
        return $none;
    }

    $standard = json_decode((string) file_get_contents($from), true);

    if (!is_array($standard)) {
        return [...$none, 'error' => '.claude/settings.json ของมาตรฐานอ่านไม่ได้'];
    }

    $project = [];

    if (is_file($to)) {
        $project = json_decode((string) file_get_contents($to), true);

        if (!is_array($project)) {
            return [...$none, 'error' => '.claude/settings.json ของโปรเจคไม่ใช่ JSON ที่ถูกต้อง — แก้ให้อ่านได้ก่อน'];
        }
    }

    $merged = $project;

    foreach ($standard as $key => $value) {
        if ($key !== 'permissions') {
            $merged[$key] = $value;
        }
    }

    $extras = [];
    $perms  = is_array($project['permissions'] ?? null) ? $project['permissions'] : [];

    foreach (is_array($standard['permissions'] ?? null) ? $standard['permissions'] : [] as $kind => $list) {
        $mine = is_array($perms[$kind] ?? null) ? $perms[$kind] : [];

        foreach (array_diff($mine, $list) as $extra) {
            $extras[] = "{$kind}: {$extra}";
        }

        $perms[$kind] = array_values(array_unique([...$list, ...$mine]));
    }

    if ($perms !== []) {
        $merged['permissions'] = $perms;
    }

    // เรียงคีย์ตามของมาตรฐาน — diff จะได้แสดงเฉพาะที่เปลี่ยนจริง ไม่ขยับทั้งไฟล์
    $ordered = [];
    foreach (array_keys($standard) as $key) {
        if (array_key_exists($key, $merged)) {
            $ordered[$key] = $merged[$key];
        }
    }
    foreach ($merged as $key => $value) {
        $ordered[$key] ??= $value;
    }

    $json = (string) json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $json = (string) preg_replace_callback('/^( +)/m', static fn (array $m): string =>
        str_repeat(' ', intdiv(strlen($m[1]), 2)), $json) . "\n";

    $current = is_file($to) ? str_replace("\r\n", "\n", (string) file_get_contents($to)) : '';

    return ['content' => $json, 'changed' => $current !== $json, 'error' => null, 'extras' => $extras];
}

/** @return array<string, string> เวอร์ชัน => ไฟล์ · เฉพาะที่อยู่ระหว่าง (เก่า, ใหม่] */
function pending_upgrades(string $source, string $before, string $after): array
{
    $out = [];

    foreach (glob($source . '/scripts/upgrades/*.php') ?: [] as $file) {
        $version = basename($file, '.php');

        if (preg_match('/^\d+\.\d+\.\d+$/', $version) === 1
            && version_compare($version, $before, '>')
            && version_compare($version, $after, '<=')) {
            $out[$version] = $file;
        }
    }

    uksort($out, 'version_compare');

    return $out;
}

/** @return array<string, array{title: string, run: callable}> */
function load_steps(string $file): array
{
    $steps = (static fn (string $path): mixed => require $path)($file);

    return is_array($steps) ? $steps : [];
}

/** @return array<string, list<string>> เวอร์ชัน => หัวข้อที่มี ⚠️ */
function changelog_between(string $source, string $before, string $after): array
{
    // นิยามว่าอะไรนับเป็น ⚠️ อยู่ที่ Manifest::breakingNotes() ที่เดียวกับที่ check.php ใช้บังคับ
    $all = Manifest::breakingNotes((string) @file_get_contents($source . '/standard/CHANGELOG.md'));
    $out = [];

    foreach ($all as $version => $marks) {
        $version = (string) $version;

        if (!version_compare($version, $before, '>') || !version_compare($version, $after, '<=')) {
            continue;
        }

        $out[$version] = array_map(static fn (string $mark): string =>
            mb_strlen($mark) > 90 ? mb_substr($mark, 0, 87) . '…' : $mark, $marks);
    }

    uksort($out, 'version_compare');

    return $out;
}

/** @return array{0: list<string>, 1: list<string>} [ต้องแก้ก่อน, ควรรู้] */
function preflight(string $target): array
{
    $blockers = [];
    $warnings = [];

    [$code, $branch] = run_in('git rev-parse --abbrev-ref HEAD', $target);

    if ($code !== 0) {
        $blockers[] = 'ไม่ใช่ git repo — ต้องมี git เพื่อดูสิ่งที่เปลี่ยนและย้อนกลับได้';
    } else {
        $branch = trim($branch);

        if (in_array($branch, ['main', 'master'], true)) {
            $blockers[] = "อยู่บน {$branch} — สร้าง branch ก่อน (กฎเหล็ก: ห้าม commit ลง main)";
        }

        [, $status] = run_in('git status --porcelain', $target);

        if (trim($status) !== '') {
            $blockers[] = 'มีงานที่ยังไม่ commit — commit ให้หมดก่อน การอัปเดตจะได้เป็น commit ของมันเองและย้อนกลับได้ในคำสั่งเดียว';
        }
    }

    $plan = $target . '/project/PLAN.md';

    if (is_file($plan)) {
        foreach (preg_split('/\R/', (string) file_get_contents($plan)) ?: [] as $line) {
            if (str_starts_with(ltrim($line), '#') && str_contains($line, '🔄')) {
                $warnings[] = 'มี phase ที่กำลังทำอยู่ (' . trim(str_replace('🔄', '', ltrim($line, "# \t"))) . ') '
                    . '— แนะนำให้อัปเดตหลังผ่านประตูของ phase นี้ เพราะกฎใหม่อาจเปลี่ยนเกณฑ์ของงานที่ทำไปครึ่งทาง';
                break;
            }
        }
    }

    return [$blockers, $warnings];
}

/**
 * @return array{0: int, 1: string}
 *
 * ‼️ รวม stderr เข้า stdout ท่อเดียว ด้วยเหตุผลเดียวกับ hook_run() ใน scripts/hooks/hook.php
 *    เปิดสองท่อแล้วอ่านทีละท่อค้างตายได้ และ stream_select ใช้กับ pipe บน Windows ไม่ได้
 */
function run_in(string $command, string $cwd): array
{
    $proc = proc_open($command . ' 2>&1', [1 => ['pipe', 'w']], $pipes, $cwd);

    if (!is_resource($proc)) {
        return [-1, ''];
    }

    $out = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return [proc_close($proc), $out];
}

function read_version(string $root): string
{
    return trim((string) @file_get_contents($root . '/standard/VERSION'));
}

function is_remote(string $source): bool
{
    return preg_match('#^(https?://|ssh://|git@)#', $source) === 1;
}

function out(string $text = ''): void
{
    echo $text . "\n";
}

function fail(string $text): never
{
    fwrite(STDERR, $text . "\n");
    exit(1);
}

function remove_tree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        // ‼️ git ตั้งไฟล์ใน .git/objects เป็นอ่านอย่างเดียว — บน Windows unlink ไม่ได้ถ้าไม่ปลดก่อน
        @chmod($item->getPathname(), 0o777);
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }

    @rmdir($path);
}
