<?php
/**
 * บอร์ดความคืบหน้า — สร้างหน้า HTML จาก project/ แล้วให้ Claude เผยแพร่เป็น Artifact
 *
 *   php scripts/board.php               สร้างไฟล์ แล้วบอกว่าต้องเผยแพร่ยังไง
 *   php scripts/board.php --out <path>  เขียนไปที่อื่น
 *
 * สองแท็บ
 *   งาน     การ์ด = ข้อใน checklist ของ PLAN.md · เลน ยังไม่เริ่ม → กำลังทำ → สร้างเสร็จ รอตรวจ → เสร็จ
 *   ผลตรวจ  การ์ด = ข้อใน QA-REPORT.md · เลน เจอ → กำลังแก้ → แก้แล้ว รอตรวจซ้ำ → ปิด
 *   กดการ์ดแล้วเปิดรายละเอียด · ไม่มี JavaScript ก็ยังกางดูได้ในที่ (details/summary)
 *
 * ‼️ บอร์ดเป็นหน้าจอ ไม่ใช่แหล่งข้อมูล — อ่าน project/ กับ git log ทางเดียว
 *    ไม่มีไฟล์บอร์ดใน repo และไม่มีอะไรให้แก้บนบอร์ด
 *    ข้อมูลเดียวกันที่อยู่สองที่จะเพี้ยนจากกันแบบเงียบๆ — มาตรฐานนี้เจอบั๊กชนิดนี้มาหลายรอบแล้ว
 *    ตัวอ่านรูปแบบของเอกสารอยู่ที่ scripts/lib/ProjectDocs.php ที่เดียวกับที่ชุดตรวจใช้
 *
 * ‼️ ไฟล์ที่ได้โหลดฟอนต์จาก Google Fonts — มันคือ Artifact สำหรับทีม ไม่ใช่หน้าของระบบที่ส่งมอบ
 *    กฎห้าม CDN ใช้กับ app/ และ public/ · ถ้าเปิดไฟล์นี้แบบออฟไลน์ ฟอนต์จะ fallback เป็นของเครื่อง
 *
 * ขั้นตอนเผยแพร่และรักษาลิงก์เดิม — standard/flow.md ข้อ 16
 */

declare(strict_types=1);

require __DIR__ . '/lib/ProjectDocs.php';

// สคริปต์นี้ไม่โหลด config/config.php (ไม่ต้องมี .env ก็สร้างบอร์ดได้) จึงต้องตั้งเวลาไทยเอง
// เครื่องที่ php.ini ตั้งเขตเวลาอื่นไว้ เวลา "สร้างบอร์ด" จะเพี้ยน แล้วคนอ่านเข้าใจว่าบอร์ดเก่า
date_default_timezone_set('Asia/Bangkok');

/** ภาพหลักฐานฝังลงหน้าได้ไม่เกินเท่านี้ — Artifact โหลดไฟล์จาก repo ตรงๆ ไม่ได้ และหน้าทั้งหน้าจำกัด 16 MB */
const EVIDENCE_MAX_FILE  = 400 * 1024;
const EVIDENCE_MAX_TOTAL = 4 * 1024 * 1024;

$root = dirname(__DIR__);
$args = array_slice($_SERVER['argv'] ?? [], 1);

$out = null;
foreach ($args as $i => $arg) {
    if ($arg === '--out') {
        $out = $args[$i + 1] ?? null;
    } elseif (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

$plan      = read_file($root . '/project/PLAN.md');
$state     = read_file($root . '/project/STATE.md');
$questions = read_file($root . '/project/QUESTIONS.md');
$feedback  = read_file($root . '/project/FEEDBACK.md');
$qa        = read_file($root . '/project/QA-REPORT.md');

if ($plan === '' && $state === '') {
    fwrite(STDERR, "ยังไม่มี project/PLAN.md หรือ project/STATE.md — ยังไม่มีความคืบหน้าให้แสดง\n"
        . "บอร์ดเริ่มมีประโยชน์ตั้งแต่จบช่วง B (standard/flow.md ข้อ 16)\n");
    exit(1);
}

// ---------------------------------------------------------------- อ่านข้อมูล

$fields  = ProjectDocs::stateFields($state);
$stage   = ProjectDocs::stage($state);
$phases  = ProjectDocs::phases($plan);
$issues  = ProjectDocs::qaIssues($qa);
$budget  = EVIDENCE_MAX_TOTAL;

$severityOrder = array_flip(array_keys(ProjectDocs::SEVERITIES));
usort($issues, static fn (array $a, array $b): int =>
    [$severityOrder[$a['severity']], (int) $a['number']] <=> [$severityOrder[$b['severity']], (int) $b['number']]);

$data = [
    'project'  => project_name($root, $plan),
    'stage'    => $stage,
    'fast'     => str_contains($fields['โหมด'] ?? '', 'เร็ว'),
    'current'  => preg_match('/^\s*(\d+)/', $fields['Phase'] ?? '', $m) === 1 ? (int) $m[1] : null,
    'step'     => $fields['ขั้นใน phase'] ?? '',
    'gates'    => passed_gates($fields['ประตูที่ผ่านแล้ว'] ?? ''),
    'pending'  => section($state, 'ค้างอยู่ที่'),
    'choices'  => array_slice(array_reverse(list_items(section($state, 'ตัดสินใจ'))), 0, 6),
    'updated'  => preg_match('/อัปเดตล่าสุด:\s*([^\n|]+)/u', $state, $m) === 1 ? trim($m[1]) : '',
    'phases'   => $phases,
    'issues'   => $issues,
    'hasQa'    => $qa !== '',
    'open'     => open_questions($questions),
    'feedback' => preg_match_all('/^##\s*\[ \]/m', $feedback),
    'commits'  => git_log($root),
    'evidence' => static function (string $text) use ($root, &$budget): array {
        return evidence($root, $text, $budget);
    },
];

$html = render_page($data);

$out ??= sys_get_temp_dir() . '/wf-board-' . substr(sha1((string) realpath($root)), 0, 8) . '.html';

if (file_put_contents($out, $html) === false) {
    fwrite(STDERR, "เขียนไฟล์ไม่ได้: {$out}\n");
    exit(1);
}

$cards = array_sum(array_map(static fn (array $p): int => count($p['items']), $phases));
$openCritical = count(array_filter($issues, static fn (array $i): bool =>
    $i['severity'] === 'critical' && $i['state'] !== 'closed'));
$link = preg_match('#https?://\S+#', $fields['บอร์ด'] ?? '', $m) === 1 ? $m[0] : null;

echo '✓ สร้างบอร์ดแล้ว — งาน ' . $cards . ' การ์ด · ผลตรวจ ' . count($issues) . ' ข้อ'
    . ($issues !== [] ? " (ต้องแก้ก่อนส่งค้าง {$openCritical})" : '') . "\n";
echo '  ไฟล์: ' . str_replace('\\', '/', $out) . "\n\n";
echo $link === null
    ? "→ เผยแพร่ด้วย Artifact (favicon 📋) แล้วเขียนลิงก์ลง project/STATE.md บรรทัด `- **บอร์ด:** <ลิงก์>`\n"
    : "→ อัปเดต Artifact เดิม — ส่ง url: {$link}\n  ห้ามสร้างใหม่ ลิงก์นี้อาจถูกส่งต่อให้ทีมไปแล้ว\n";
exit(0);

// ============================================================ อ่าน project/

function read_file(string $path): string
{
    return is_file($path) ? ProjectDocs::normalize((string) file_get_contents($path)) : '';
}

/** @return array<string, string> ตัวอักษรช่วง => วันที่ (อาจว่าง) */
function passed_gates(string $line): array
{
    $out = [];

    if (preg_match_all('/(?:^|[\s·])([A-E])(?:-\d)?\s*✅\s*(?:\(([^)]*)\))?/u', $line, $rows, PREG_SET_ORDER) > 0) {
        foreach ($rows as $row) {
            $out[$row[1]] = trim($row[2] ?? '');
        }
    }

    return $out;
}

/** เนื้อหาใต้หัวข้อ `## ...` ที่ขึ้นต้นด้วยคำนี้ จนถึงหัวข้อถัดไป */
function section(string $text, string $startsWith): string
{
    foreach (preg_split('/^## /m', $text) ?: [] as $part) {
        if (str_starts_with($part, $startsWith)) {
            return trim((string) substr($part, (int) strpos($part . "\n", "\n")));
        }
    }

    return '';
}

/** @return list<string> */
function list_items(string $text): array
{
    preg_match_all('/^\s*[-*]\s+(.+)$/mu', $text, $m);

    return array_map('trim', $m[1]);
}

function project_name(string $root, string $plan): string
{
    foreach ([$plan, read_file($root . '/project/SCOPE.md')] as $doc) {
        if (preg_match('/^#\s+[^\n—]*—\s*(.+)$/mu', $doc, $m) === 1 && !str_contains($m[1], '[')) {
            return trim($m[1]);
        }
    }

    return basename((string) realpath($root));
}

/** @return list<array{title: string, where: string, chose: string}> */
function open_questions(string $text): array
{
    $out = [];

    foreach (preg_split('/^## /m', $text) ?: [] as $part) {
        if (preg_match('/^\[ \]\s*([^\n]*)/u', $part, $m) !== 1) {
            continue;
        }

        $head  = trim($m[1]);
        $where = '';
        $title = $head;

        // รูปแบบ: 2026-08-20 · ช่วง C phase 2 — คิวที่ถูกข้ามควรกลับมาต่อท้ายไหม
        if (preg_match('/^(.*?)\s+—\s+(.+)$/u', $head, $h) === 1) {
            $where = trim($h[1]);
            $title = trim($h[2]);
        }

        $out[] = [
            'title' => $title,
            'where' => $where,
            'chose' => preg_match('/\*\*เลือกไป:\*\*\s*([^\n]+)/u', $part, $c) === 1 ? trim($c[1]) : '',
        ];
    }

    return $out;
}

/** @return list<array{hash: string, date: string, subject: string}> */
function git_log(string $root): array
{
    $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    $out  = [];

    exec('git -c core.quotepath=off -C ' . escapeshellarg($root)
        . ' log -n 10 --no-merges --date=format:"%Y-%m-%d %H:%M" --format="%h%x1f%ad%x1f%s" 2>' . $null, $lines, $code);

    if ($code !== 0) {
        return [];
    }

    foreach ($lines as $line) {
        $parts = explode("\x1f", $line);

        if (count($parts) === 3) {
            $out[] = ['hash' => $parts[0], 'date' => $parts[1], 'subject' => $parts[2]];
        }
    }

    return $out;
}

/**
 * ภาพที่อ้างถึงในช่องหลักฐาน — ฝังเป็น data URI ถ้าเล็กพอ ไม่งั้นบอกแค่ path
 *
 * ‼️ อ่านได้เฉพาะไฟล์ที่อยู่ในโปรเจค — path มาจากข้อความในเอกสาร จึงต้องกันไม่ให้ชี้ออกนอก root
 *
 * @return list<array{path: string, src: ?string, note: string}>
 */
function evidence(string $root, string $text, int &$budget): array
{
    $types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $base  = str_replace('\\', '/', (string) realpath($root));
    $out   = [];

    preg_match_all('#(?<![\w/.])([\w\-./]+\.(?:png|jpe?g|webp|gif))\b#iu', $text, $m);

    foreach (array_unique($m[1]) as $path) {
        $real = realpath($root . '/' . ltrim($path, '/'));
        $real = $real === false ? '' : str_replace('\\', '/', $real);

        if ($real === '' || !str_starts_with($real, $base . '/') || !is_file($real)) {
            $out[] = ['path' => $path, 'src' => null, 'note' => 'ไม่พบไฟล์ในโปรเจค'];
            continue;
        }

        $size = (int) filesize($real);

        if ($size > EVIDENCE_MAX_FILE || $size > $budget) {
            $out[] = ['path' => $path, 'src' => null, 'note' => 'ใหญ่เกินจะฝังในบอร์ด — เปิดใน repo'];
            continue;
        }

        $budget -= $size;
        $type    = $types[strtolower(pathinfo($real, PATHINFO_EXTENSION))];
        $out[]   = ['path' => $path, 'src' => 'data:' . $type . ';base64,' . base64_encode((string) file_get_contents($real)), 'note' => ''];
    }

    return $out;
}

// ============================================================ HTML

function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** escape แล้วแปลง `code` · **ตัวหนา** · ขึ้นบรรทัดใหม่ ที่ใช้ในเอกสารของ project/ */
function md(string $text): string
{
    $html = e($text);
    $html = (string) preg_replace('/`([^`]+)`/u', '<code>$1</code>', $html);
    $html = (string) preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $html);

    return nl2br($html, false);
}

function plain(string $text): string
{
    return trim(str_replace(['**', '`'], '', $text));
}

/** @param array<string, mixed> $d */
function render_page(array $d): string
{
    $stages = ['A' => 'เก็บข้อมูล', 'B' => 'วางแผน · ออกแบบ', 'C' => 'ลงมือทีละ phase', 'D' => 'ตรวจรวม', 'E' => 'ส่งขึ้น server'];
    $order  = array_keys($stages);
    $at     = $d['stage'] === '' ? -1 : (int) array_search($d['stage'], $order, true);

    $phaseStates = ['todo' => 'ยังไม่เริ่ม', 'doing' => 'กำลังทำ', 'check' => 'สร้างเสร็จ รอตรวจ', 'done' => 'เสร็จ'];
    $marks       = ['x' => 'ติ๊กแล้ว', ' ' => 'ยังไม่ติ๊ก', '-' => 'ตัดสินว่าไม่ทำ หรือเลื่อน'];

    // แท็บงาน — การ์ดคือข้อใน checklist
    $workLanes = array_fill_keys(array_keys($phaseStates), []);
    foreach ($d['phases'] as $phase) {
        foreach ($phase['items'] as $item) {
            $workLanes[ProjectDocs::itemLane($phase['status'], $item['mark'])][] = [$phase, $item];
        }
    }

    // แท็บผลตรวจ
    $qaLanes = array_fill_keys(array_keys(ProjectDocs::ISSUE_STATES), []);
    foreach ($d['issues'] as $issue) {
        $qaLanes[$issue['state']][] = $issue;
    }

    $workCount    = array_sum(array_map('count', $workLanes));
    $critical     = array_values(array_filter($d['issues'], static fn (array $i): bool => $i['severity'] === 'critical'));
    $criticalOpen = count(array_filter($critical, static fn (array $i): bool => $i['state'] !== 'closed'));
    $defaultTab   = in_array($d['stage'], ['D', 'E'], true) && $d['issues'] !== [] ? 'panel-qa' : 'panel-work';

    $laneTone  = ['todo' => 'todo', 'doing' => 'doing', 'check' => 'check', 'done' => 'done',
                  'found' => 'todo', 'fixing' => 'doing', 'verify' => 'check', 'closed' => 'done'];
    $issueFieldOrder = ['เจอที่', 'ทำยังไงถึงเจอ', 'ผลที่ได้ vs ผลที่ควรเป็น', 'หลักฐาน', 'มาจาก', 'สถานะ', 'แก้ใน'];

    ob_start();
    ?>
<meta charset="utf-8">
<title>บอร์ดงาน <?= e($d['project']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap">
<style>
:root {
  --ground: #F4F6F9;
  --lane: #E7EBF0;
  --surface: #FFFFFF;
  --ink: #18202C;
  --ink-2: #454F5E;
  --ink-3: #5F6978;
  --rule: #D8DDE5;
  --rule-strong: #B3BCC8;
  --todo: #6F7888;
  --todo-bg: #ECEFF3;
  --doing: #1F63B5;
  --doing-bg: #E4EDF9;
  --check: #B4530F;
  --check-bg: #FAECE1;
  --done: #2B7A4B;
  --done-bg: #E3F2E8;
  --critical: #B42318;
  --should: #8A6400;
  --backdrop: rgb(24 32 44 / 0.45);
  --display: "Chakra Petch", "IBM Plex Sans Thai", Tahoma, sans-serif;
  --body: "IBM Plex Sans Thai", "Leelawadee UI", Tahoma, sans-serif;
  --mono: "IBM Plex Mono", ui-monospace, Consolas, monospace;
  color-scheme: light;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --ground: #0F1319; --lane: #151A21; --surface: #1D232C; --ink: #E6EAF0; --ink-2: #B3BBC7; --ink-3: #8F99A8;
    --rule: #2B323D; --rule-strong: #404957;
    --todo: #8F98A8; --todo-bg: #232A33; --doing: #62A4EA; --doing-bg: #172A40;
    --check: #EA8C4E; --check-bg: #33231A; --done: #5CBB83; --done-bg: #173020;
    --critical: #F2776C; --should: #D8A63C; --backdrop: rgb(0 0 0 / 0.6);
    color-scheme: dark;
  }
}
:root[data-theme="dark"] {
  --ground: #0F1319; --lane: #151A21; --surface: #1D232C; --ink: #E6EAF0; --ink-2: #B3BBC7; --ink-3: #8F99A8;
  --rule: #2B323D; --rule-strong: #404957;
  --todo: #8F98A8; --todo-bg: #232A33; --doing: #62A4EA; --doing-bg: #172A40;
  --check: #EA8C4E; --check-bg: #33231A; --done: #5CBB83; --done-bg: #173020;
  --critical: #F2776C; --should: #D8A63C; --backdrop: rgb(0 0 0 / 0.6);
  color-scheme: dark;
}

* { box-sizing: border-box; }
/* แผงแท็บตั้ง display: grid ซึ่งชนะ hidden ของเบราว์เซอร์ — ไม่มีบรรทัดนี้ ทุกแท็บโผล่พร้อมกันเมื่อเปิดไฟล์ตรง */
[hidden] { display: none !important; }
body { margin: 0; background: var(--ground); color: var(--ink); font: 15px/1.6 var(--body); }
code { font: 0.88em var(--mono); background: var(--todo-bg); padding: 0.05em 0.35em; border-radius: 3px; overflow-wrap: anywhere; }
button { font: inherit; color: inherit; }
:focus-visible { outline: 2px solid var(--doing); outline-offset: 2px; }
.sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
.wrap { max-width: 1280px; margin: 0 auto; padding-inline: clamp(16px, 3vw, 32px); padding-block: 22px 48px; display: grid; gap: 20px; }

.bar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 20px; }
.bar h1 { margin: 0; font: 600 clamp(20px, 2.4vw, 26px)/1.2 var(--display); text-wrap: balance; }
.mode { padding: 1px 10px; border: 1px solid var(--rule-strong); border-radius: 999px; font-size: 13px; font-weight: 500; color: var(--ink-2); white-space: nowrap; }
.mode.is-fast { border-color: var(--check); color: var(--ink); background: var(--check-bg); }
.rail { list-style: none; margin: 0; padding: 0; display: flex; align-items: center; gap: 4px; }
.rail li { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 6px; font: 600 14px/1 var(--display); color: var(--ink-3); border: 1px solid var(--rule); }
.rail li.is-past { color: var(--ink-2); background: var(--lane); border-color: var(--lane); }
.rail li.is-now { color: #FFFFFF; background: var(--doing); border-color: var(--doing); }
:root[data-theme="dark"] .rail li.is-now { color: #0F1319; }
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .rail li.is-now { color: #0F1319; } }
.rail-name { font-size: 14px; color: var(--ink-2); }
.stamp { margin-left: auto; font: 12px/1.5 var(--mono); color: var(--ink-3); text-align: right; font-variant-numeric: tabular-nums; }

.now { margin: 0; display: flex; flex-wrap: wrap; gap: 4px 14px; align-items: baseline; font-size: 15px; }
.now b { font-weight: 600; color: var(--ink-3); font-size: 13px; }
.now .say { color: var(--ink); max-width: 90ch; }
.now .meta { color: var(--ink-3); font-size: 13.5px; }

.phases { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 6px; }
.phases li { display: flex; align-items: center; gap: 7px; padding: 3px 10px 3px 8px; border: 1px solid var(--rule); border-radius: 999px; background: var(--surface); font-size: 13px; }
.phases .n { font: 500 12px var(--mono); color: var(--ink-3); }
.phases .c { font: 12px var(--mono); color: var(--ink-3); font-variant-numeric: tabular-nums; }

.shape { width: 9px; height: 9px; flex: none; background: var(--tone); border-radius: 50%; }
.shape.s-todo { background: transparent; box-shadow: inset 0 0 0 2px var(--tone); }
.shape.s-check { border-radius: 1px; transform: rotate(45deg); width: 8px; height: 8px; }
.shape.s-done { border-radius: 2px; }
.tone-todo { --tone: var(--todo); --tone-bg: var(--todo-bg); }
.tone-doing { --tone: var(--doing); --tone-bg: var(--doing-bg); }
.tone-check { --tone: var(--check); --tone-bg: var(--check-bg); }
.tone-done { --tone: var(--done); --tone-bg: var(--done-bg); }

.tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--rule-strong); }
.tabs button { background: none; border: 0; border-bottom: 3px solid transparent; margin-bottom: -1px; padding: 8px 14px 7px; font: 600 15px var(--display); color: var(--ink-3); cursor: pointer; display: flex; gap: 8px; align-items: baseline; }
.tabs button[aria-selected="true"] { color: var(--ink); border-bottom-color: var(--ink); }
.tabs .n { font: 500 12.5px var(--mono); color: var(--ink-3); }
.panel { display: grid; gap: 14px; }
.panel > h2 { margin: 0; font: 600 17px var(--display); }
.tabs:not([hidden]) ~ .panel > h2 { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }

.gate { margin: 0; display: flex; flex-wrap: wrap; gap: 4px 18px; align-items: center; font-size: 14px; }
.gate .verdict { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; }
.gate .verdict.is-blocked { color: var(--critical); }
.gate .verdict.is-clear { color: var(--done); }
.gate .tally { color: var(--ink-3); font-size: 13.5px; }

.lanes { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(250px, 1fr); gap: 12px; overflow-x: auto; padding-bottom: 6px; scroll-snap-type: x proximity; align-items: start; }
.lane { background: var(--lane); border-radius: 10px; padding: 10px; display: grid; gap: 8px; scroll-snap-align: start; min-width: 0; }
.lane-head { display: flex; align-items: center; gap: 8px; padding: 2px 4px 4px; }
.lane-head h3 { margin: 0; font: 600 14.5px/1.3 var(--display); }
.lane-head .count { margin-left: auto; font: 500 12.5px var(--mono); color: var(--ink-3); font-variant-numeric: tabular-nums; }
.lane-empty { margin: 0; padding: 14px 10px; border: 1px dashed var(--rule-strong); border-radius: 8px; font-size: 13px; color: var(--ink-3); text-align: center; }

.card { background: var(--surface); border: 1px solid var(--rule); border-radius: 8px; }
.card[open] { border-color: var(--rule-strong); }
.card-sum { list-style: none; cursor: pointer; padding: 10px 12px 11px; display: grid; gap: 6px; border-radius: 8px; }
.card-sum::-webkit-details-marker { display: none; }
.card-sum:hover { background: color-mix(in srgb, var(--surface) 88%, var(--ink) 12%); }
.card.is-current { box-shadow: inset 3px 0 0 var(--doing); }
.card-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; font-size: 12px; color: var(--ink-3); }
.tag { font: 500 11.5px/1 var(--mono); padding: 3px 6px; border-radius: 4px; background: var(--lane); color: var(--ink-2); }
.card-title { font-size: 14.5px; line-height: 1.45; font-weight: 500; overflow-wrap: anywhere; }
.card-foot { display: flex; flex-wrap: wrap; gap: 4px 10px; align-items: center; font-size: 12.5px; color: var(--ink-3); }
.card-foot .where { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
.sev { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; }
.sev::before { content: ""; width: 9px; height: 9px; flex: none; }
.sev-critical { color: var(--critical); }
.sev-critical::before { background: var(--critical); border-radius: 1px; }
.sev-should { color: var(--should); }
.sev-should::before { background: var(--should); clip-path: polygon(50% 0, 100% 100%, 0 100%); }
.sev-accepted { color: var(--ink-3); }
.sev-accepted::before { border-radius: 50%; box-shadow: inset 0 0 0 2px var(--todo); }
.chip { font-size: 12px; padding: 1px 7px; border-radius: 4px; background: var(--tone-bg); color: var(--ink); }
.card-detail { display: grid; gap: 12px; padding: 0 12px 12px; font-size: 14px; }

.detail-list { margin: 0; display: grid; gap: 12px; }
.detail-list dt { font-size: 12.5px; font-weight: 600; color: var(--ink-3); }
.detail-list dd { margin: 2px 0 0; overflow-wrap: anywhere; }
.steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 3px; }
.steps li { display: flex; gap: 8px; }
.steps .m { font: 12.5px var(--mono); color: var(--ink-3); width: 3ch; flex: none; }
.shots { display: grid; gap: 8px; margin-top: 6px; }
.shots img { max-width: 100%; height: auto; border: 1px solid var(--rule); border-radius: 6px; display: block; }
.shots .p { font: 12px var(--mono); color: var(--ink-3); overflow-wrap: anywhere; }

dialog { width: min(640px, calc(100vw - 32px)); max-height: min(84vh, 760px); padding: 0; border: 1px solid var(--rule-strong); border-radius: 12px; background: var(--surface); color: var(--ink); }
dialog::backdrop { background: var(--backdrop); }
.detail-inner { display: grid; gap: 14px; padding: 18px 20px 22px; }
.detail-head { display: flex; gap: 12px; align-items: start; }
.detail-head h2 { margin: 0; font: 600 18px/1.35 var(--display); text-wrap: balance; flex: 1; }
.close { background: var(--lane); border: 1px solid var(--rule); border-radius: 6px; padding: 3px 12px; cursor: pointer; font-size: 14px; }

.split { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 28px; margin-top: 10px; }
.side h2 { margin: 0 0 10px; font: 600 16px/1.3 var(--display); display: flex; gap: 10px; align-items: baseline; }
.side h2 small { font: 500 12.5px var(--mono); color: var(--ink-3); }
.qs { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
.qs li { display: grid; gap: 1px; padding-left: 12px; border-left: 2px solid var(--rule-strong); }
.qs .where { font: 12px var(--mono); color: var(--ink-3); }
.qs .t { font-weight: 500; }
.qs .c { font-size: 14px; color: var(--ink-2); }
.commits { list-style: none; margin: 0; padding: 0; }
.commits li { display: grid; grid-template-columns: 8ch minmax(0, 1fr) auto; gap: 12px; padding: 6px 0; border-bottom: 1px solid var(--rule); font-size: 14px; align-items: baseline; }
.commits .h, .commits .d { font: 12px var(--mono); color: var(--ink-3); font-variant-numeric: tabular-nums; }
.commits .s { overflow-wrap: anywhere; }
.choices { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; font-size: 14px; color: var(--ink-2); max-width: 90ch; }
.muted { margin: 0; font-size: 13.5px; color: var(--ink-3); }
.note { margin: 0; font-size: 12.5px; color: var(--ink-3); border-top: 1px solid var(--rule); padding-top: 12px; }

@media (max-width: 760px) {
  .split { grid-template-columns: minmax(0, 1fr); }
  .stamp { margin-left: 0; text-align: left; flex-basis: 100%; }
  .lanes { grid-auto-columns: minmax(78%, 1fr); }
  .commits li { grid-template-columns: 8ch minmax(0, 1fr); }
  .commits .d { grid-column: 2; }
}
</style>

<div class="wrap">

<header class="bar">
  <h1><?= e($d['project']) ?></h1>
  <span class="mode<?= $d['fast'] ? ' is-fast' : '' ?>"><?= $d['fast'] ? 'โหมดเร็ว' : 'โหมดปกติ' ?></span>
  <ol class="rail" aria-label="ช่วงของงาน">
    <?php foreach ($stages as $letter => $name):
        $i     = (int) array_search($letter, $order, true);
        $class = $at === -1 ? '' : ($i < $at ? 'is-past' : ($i === $at ? 'is-now' : ''));
        $gate  = $d['gates'][$letter] ?? null;
        $tip   = $name . ($gate !== null ? ' — ผ่านประตู' . ($gate !== '' ? ' ' . $gate : '') : '');
    ?>
    <li class="<?= $class ?>" title="<?= e($tip) ?>"<?= $class === 'is-now' ? ' aria-current="step"' : '' ?>><?= $letter ?><span class="sr"> <?= e($tip) ?></span></li>
    <?php endforeach; ?>
  </ol>
  <?php if ($at >= 0): ?><span class="rail-name"><?= e($stages[$d['stage']]) ?></span><?php endif; ?>
  <span class="stamp">สร้างบอร์ด <?= e(date('Y-m-d H:i')) ?><?= $d['updated'] !== '' ? '<br>STATE.md ' . e($d['updated']) : '' ?></span>
</header>

<p class="now">
  <b>ตอนนี้</b>
  <span class="say"><?= $d['pending'] !== '' ? md((string) preg_replace('/\s+/u', ' ', $d['pending'])) : 'STATE.md ยังไม่ได้เขียนว่าค้างอยู่ที่ไหน' ?></span>
  <?php if ($d['current'] !== null): ?><span class="meta">Phase <?= $d['current'] ?><?= $d['step'] !== '' ? ' · ' . md($d['step']) : '' ?></span><?php endif; ?>
</p>

<?php if ($d['phases'] !== []): ?>
<ul class="phases" aria-label="สถานะของแต่ละ phase">
  <?php foreach ($d['phases'] as $p):
      $done  = count(array_filter($p['items'], static fn (array $i): bool => $i['mark'] !== ' '));
  ?>
  <li class="tone-<?= $p['status'] ?>"><span class="shape s-<?= $p['status'] ?>" aria-hidden="true"></span><span class="n">P<?= $p['number'] ?></span><?= md($p['title']) ?><span class="sr"> — <?= e($phaseStates[$p['status']]) ?></span><span class="c"><?= $done ?>/<?= count($p['items']) ?></span></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="tabs" role="tablist" aria-label="มุมมองของบอร์ด" data-default="<?= $defaultTab ?>" hidden>
  <button type="button" role="tab" id="tab-work" aria-controls="panel-work" data-panel="panel-work">งาน <span class="n"><?= $workCount ?></span></button>
  <button type="button" role="tab" id="tab-qa" aria-controls="panel-qa" data-panel="panel-qa">ผลตรวจ <span class="n"><?= count($d['issues']) ?></span></button>
</div>

<section class="panel" id="panel-work" role="tabpanel" aria-labelledby="tab-work">
  <h2>งาน</h2>
  <?php if ($workCount === 0): ?>
  <p class="muted">PLAN.md ยังไม่มี checklist ของ phase ไหน</p>
  <?php else: ?>
  <div class="lanes">
    <?php foreach ($workLanes as $lane => $cards): ?>
    <section class="lane tone-<?= $laneTone[$lane] ?>" aria-labelledby="lane-<?= $lane ?>">
      <div class="lane-head"><span class="shape s-<?= $lane ?>" aria-hidden="true"></span><h3 id="lane-<?= $lane ?>"><?= e($phaseStates[$lane]) ?></h3><span class="count"><?= count($cards) ?></span></div>
      <?php if ($cards === []): ?>
      <p class="lane-empty"><?= $lane === 'check' && !$d['fast'] ? 'งานที่สร้างแล้ว รอตรวจตอนจบ phase' : 'ไม่มี' ?></p>
      <?php endif; ?>
      <?php foreach ($cards as [$phase, $item]):
          $isCurrent = $lane === 'doing' && $d['current'] === $phase['number'];
      ?>
      <details class="card<?= $isCurrent ? ' is-current' : '' ?>" data-title="<?= e(plain($item['text'])) ?>">
        <summary class="card-sum">
          <span class="card-meta"><span class="tag">P<?= $phase['number'] ?></span><?= md($phase['title']) ?></span>
          <span class="card-title"><?= md($item['text']) ?></span>
          <?php if ($item['mark'] === '-' || $isCurrent): ?>
          <span class="card-foot"><?= $item['mark'] === '-' ? '<span class="chip">ไม่ทำ / เลื่อน</span>' : '' ?><?= $isCurrent && $d['step'] !== '' ? '<span class="chip">phase นี้อยู่ที่ ' . md($d['step']) . '</span>' : '' ?></span>
          <?php endif; ?>
        </summary>
        <div class="card-detail">
          <dl class="detail-list">
            <div><dt>Phase</dt><dd>Phase <?= $phase['number'] ?> — <?= md($phase['title']) ?> · <?= e($phaseStates[$phase['status']]) ?></dd></div>
            <?php if ($item['group'] !== ''): ?><div><dt>หมวด</dt><dd><?= md($item['group']) ?></dd></div><?php endif; ?>
            <div><dt>ข้อนี้</dt><dd><?= e($marks[$item['mark']]) ?></dd></div>
            <?php if ($phase['steps'] !== []): ?>
            <div><dt>ขั้นของ phase</dt><dd><ul class="steps"><?php foreach ($phase['steps'] as $s): ?><li><span class="m">[<?= $s['mark'] === ' ' ? '&nbsp;' : e($s['mark']) ?>]</span><span><?= md($s['text']) ?></span></li><?php endforeach; ?></ul></dd></div>
            <?php endif; ?>
            <?php if ($phase['pages'] !== []): ?>
            <div><dt>หน้าใน phase นี้</dt><dd><ul class="steps"><?php foreach ($phase['pages'] as $page): ?><li><?= md($page) ?></li><?php endforeach; ?></ul></dd></div>
            <?php endif; ?>
          </dl>
        </div>
      </details>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="panel" id="panel-qa" role="tabpanel" aria-labelledby="tab-qa">
  <h2>ผลตรวจ</h2>
  <?php if (!$d['hasQa']): ?>
  <p class="muted">ยังไม่มี QA-REPORT.md — ผลตรวจรวมเริ่มมีตอนช่วง D</p>
  <?php elseif ($d['issues'] === []): ?>
  <p class="muted">QA-REPORT.md ยังไม่มีข้อที่ตรวจเจอ</p>
  <?php else: ?>
  <p class="gate">
    <?php if ($criticalOpen > 0): ?>
    <span class="verdict is-blocked"><span class="sev sev-critical" aria-hidden="true"></span>ยังผ่านประตู D ไม่ได้ — ต้องแก้ก่อนส่งค้าง <?= $criticalOpen ?> ข้อ</span>
    <?php else: ?>
    <span class="verdict is-clear">ข้อต้องแก้ก่อนส่งปิดครบแล้ว</span>
    <?php endif; ?>
    <span class="tally">
      <?php foreach (ProjectDocs::SEVERITIES as $sev => $label):
          $all  = array_filter($d['issues'], static fn (array $i): bool => $i['severity'] === $sev);
          $open = array_filter($all, static fn (array $i): bool => $i['state'] !== 'closed');
          if ($all === []) { continue; }
      ?>
      <?= e($label) ?> ปิด <?= count($all) - count($open) ?>/<?= count($all) ?> &nbsp;
      <?php endforeach; ?>
    </span>
  </p>
  <div class="lanes">
    <?php foreach ($qaLanes as $lane => $cards): ?>
    <section class="lane tone-<?= $laneTone[$lane] ?>" aria-labelledby="lane-<?= $lane ?>">
      <div class="lane-head"><span class="shape s-<?= $laneTone[$lane] ?>" aria-hidden="true"></span><h3 id="lane-<?= $lane ?>"><?= e(ProjectDocs::ISSUE_STATES[$lane]) ?></h3><span class="count"><?= count($cards) ?></span></div>
      <?php if ($cards === []): ?><p class="lane-empty">ไม่มี</p><?php endif; ?>
      <?php foreach ($cards as $issue):
          $f   = $issue['fields'];
          $fix = trim($f['แก้ใน'] ?? '');
      ?>
      <details class="card" data-title="<?= e(($issue['number'] !== '' ? $issue['number'] . '. ' : '') . plain($issue['title'])) ?>">
        <summary class="card-sum">
          <span class="card-meta"><span class="sev sev-<?= $issue['severity'] ?>"><?= e(ProjectDocs::SEVERITIES[$issue['severity']]) ?></span><?= $issue['number'] !== '' ? '<span class="tag">#' . e($issue['number']) . '</span>' : '' ?></span>
          <span class="card-title"><?= md($issue['title']) ?></span>
          <?php if (($f['เจอที่'] ?? '') !== '' || $fix !== '' || ($f['มาจาก'] ?? '') !== ''): ?>
          <span class="card-foot">
            <?php if (($f['เจอที่'] ?? '') !== ''): ?><span class="where"><?= e(plain(strtok($f['เจอที่'], "\n") ?: '')) ?></span><?php endif; ?>
            <?php if (($f['มาจาก'] ?? '') !== ''): ?><span class="chip tone-check"><?= e(plain($f['มาจาก'])) ?></span><?php endif; ?>
            <?php if ($fix !== ''): ?><span class="tag">แก้ใน <?= e(plain(strtok($fix, "\n") ?: '')) ?></span><?php endif; ?>
          </span>
          <?php endif; ?>
        </summary>
        <div class="card-detail">
          <dl class="detail-list">
            <div><dt>ความรุนแรง</dt><dd><span class="sev sev-<?= $issue['severity'] ?>"><?= e(ProjectDocs::SEVERITIES[$issue['severity']]) ?></span></dd></div>
            <?php
            $keys = [...array_values(array_intersect($issueFieldOrder, array_keys($f))), ...array_values(array_diff(array_keys($f), $issueFieldOrder))];
            foreach ($keys as $key):
                if (trim($f[$key]) === '') { continue; }
            ?>
            <div><dt><?= e($key) ?></dt><dd><?= md($f[$key]) ?>
              <?php if ($key === 'หลักฐาน'): $shots = ($d['evidence'])($f[$key]); if ($shots !== []): ?>
              <div class="shots"><?php foreach ($shots as $shot): ?><?php if ($shot['src'] !== null): ?><img src="<?= $shot['src'] ?>" alt="หลักฐาน <?= e($shot['path']) ?>" loading="lazy"><?php else: ?><span class="p"><?= e($shot['path']) ?> — <?= e($shot['note']) ?></span><?php endif; ?><?php endforeach; ?></div>
              <?php endif; endif; ?>
            </dd></div>
            <?php endforeach; ?>
            <?php if (!isset($f['สถานะ'])): ?><div><dt>สถานะ</dt><dd><?= e(ProjectDocs::ISSUE_STATES[$issue['state']]) ?> (ไม่ได้เขียนไว้ใน QA-REPORT.md)</dd></div><?php endif; ?>
          </dl>
        </div>
      </details>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<div class="split">
  <section class="side" aria-labelledby="q-head">
    <h2 id="q-head">รอคำตอบจากคุณ <small><?= count($d['open']) ?></small></h2>
    <?php if ($d['open'] === []): ?>
    <p class="muted">ไม่มีเรื่องที่รอคำตอบ</p>
    <?php else: ?>
    <ul class="qs">
      <?php foreach ($d['open'] as $q): ?>
      <li>
        <?php if ($q['where'] !== ''): ?><span class="where"><?= e($q['where']) ?></span><?php endif; ?>
        <span class="t"><?= md($q['title']) ?></span>
        <?php if ($q['chose'] !== ''): ?><span class="c">ระหว่างรอ เลือกไปก่อนว่า <?= md($q['chose']) ?></span><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if ($d['choices'] !== []): ?>
    <h2 style="margin-top: 22px">ตัดสินใจไปแล้วล่าสุด</h2>
    <ul class="choices"><?php foreach ($d['choices'] as $choice): ?><li><?= md($choice) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if ($d['feedback'] > 0): ?><p class="muted" style="margin-top: 14px">มาตรฐานไม่ครอบคลุม <?= $d['feedback'] ?> เรื่อง (FEEDBACK.md)</p><?php endif; ?>
  </section>

  <section class="side" aria-labelledby="c-head">
    <h2 id="c-head">งานล่าสุด <small>git</small></h2>
    <?php if ($d['commits'] === []): ?>
    <p class="muted">ยังไม่มี commit</p>
    <?php else: ?>
    <ul class="commits">
      <?php foreach ($d['commits'] as $c): ?>
      <li><span class="h"><?= e($c['hash']) ?></span><span class="s"><?= md($c['subject']) ?></span><span class="d"><?= e($c['date']) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>

<p class="note">บอร์ดนี้สร้างจาก PLAN.md · STATE.md · QUESTIONS.md · QA-REPORT.md และ git ของโปรเจค — แก้ที่ไฟล์เหล่านั้น ไม่ใช่ที่หน้านี้ · อัปเดตทุกครั้งที่จบ phase ทุกประตู และระหว่างแก้ผลตรวจในช่วง D</p>

</div>

<dialog id="detail" aria-labelledby="detail-title">
  <div class="detail-inner">
    <div class="detail-head"><h2 id="detail-title"></h2><button type="button" class="close" data-close>ปิด</button></div>
    <div class="detail-body"></div>
  </div>
</dialog>

<script>
(() => {
  const tabs = document.querySelector('.tabs');
  if (tabs) {
    const buttons = [...tabs.querySelectorAll('[role="tab"]')];
    const show = (id, focus) => {
      buttons.forEach((button) => {
        const on = button.dataset.panel === id;
        button.setAttribute('aria-selected', on ? 'true' : 'false');
        button.tabIndex = on ? 0 : -1;
        document.getElementById(button.dataset.panel).hidden = !on;
        if (on && focus) button.focus();
      });
    };
    buttons.forEach((button, index) => {
      button.addEventListener('click', () => show(button.dataset.panel));
      button.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
        event.preventDefault();
        const step = event.key === 'ArrowRight' ? 1 : buttons.length - 1;
        show(buttons[(index + step) % buttons.length].dataset.panel, true);
      });
    });
    tabs.hidden = false;
    show(tabs.dataset.default);
  }

  // การ์ดเป็น details/summary — ไม่มี JS ก็กางดูในที่ได้ · มี JS เปิดเป็นกล่องรายละเอียดแทน
  const dialog = document.getElementById('detail');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const title = document.getElementById('detail-title');
  const body = dialog.querySelector('.detail-body');
  let opener = null;

  document.addEventListener('click', (event) => {
    const summary = event.target.closest('summary.card-sum');
    if (summary) {
      event.preventDefault();
      const card = summary.parentElement;
      opener = summary;
      title.textContent = card.dataset.title;
      // โคลนโหนดที่ PHP escape ไว้แล้ว — ไม่แปลงเป็นข้อความ HTML แล้ว parse ซ้ำ
      body.replaceChildren(...card.querySelector('.card-detail').cloneNode(true).childNodes);
      dialog.showModal();
      return;
    }
    if (event.target.closest('[data-close]') || event.target === dialog) dialog.close();
  });

  dialog.addEventListener('close', () => { if (opener) opener.focus(); });
})();
</script>
<?php

    return (string) ob_get_clean();
}
