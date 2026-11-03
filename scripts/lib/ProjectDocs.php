<?php
/**
 * ตัวอ่านเอกสารใน project/ — จุดเดียวที่ตัดสินว่ารูปแบบของ PLAN.md · STATE.md · QA-REPORT.md แปลว่าอะไร
 *
 * ‼️ ห้ามเขียนตัวอ่านไฟล์เหล่านี้ที่อื่นอีก
 *    scripts/board.php แสดงผล · scripts/checks/docs.php บังคับกฎ — ถ้าสองที่อ่านคนละแบบ
 *    บอร์ดจะบอกว่าข้อหนึ่งปิดแล้วขณะที่ชุดตรวจบอกว่ายังเปิด (บทเรียนเดียวกับ Manifest::hash · 3.1.0)
 *
 * รูปแบบของไฟล์อยู่ที่ standard/flow.md ข้อ 9 และ standard/testing.md ข้อ 5
 */

declare(strict_types=1);

final class ProjectDocs
{
    public const PHASE_ICONS = ['✅', '🔨', '🔄', '⬜'];

    /** สถานะของข้อผลตรวจ เรียงตามเลนบนบอร์ด */
    public const ISSUE_STATES = [
        'found'  => 'เจอ',
        'fixing' => 'กำลังแก้',
        'verify' => 'แก้แล้ว รอตรวจซ้ำ',
        'closed' => 'ปิด',
    ];

    public const SEVERITIES = [
        'critical' => 'ต้องแก้ก่อนส่ง',
        'should'   => 'ควรแก้',
        'accepted' => 'รับได้',
    ];

    public static function normalize(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    // ------------------------------------------------------------------ STATE.md

    /** @return array<string, string> บรรทัดแบบ `- **ชื่อ:** ค่า` — ตัวแรกที่เจอชนะ */
    public static function stateFields(string $state): array
    {
        $out = [];

        if (preg_match_all('/^\s*-\s*\*\*(.+?):\*\*[ \t]*(.*)$/mu', self::normalize($state), $rows, PREG_SET_ORDER) > 0) {
            foreach ($rows as $row) {
                $out[trim($row[1])] ??= trim($row[2]);
            }
        }

        return $out;
    }

    /** ตัวอักษรช่วง A-E จากบรรทัด `ช่วง:` · ว่าง = หาไม่เจอ */
    public static function stage(string $state): string
    {
        $letter = strtoupper(mb_substr(trim(self::stateFields($state)['ช่วง'] ?? ''), 0, 1));

        return in_array($letter, ['A', 'B', 'C', 'D', 'E'], true) ? $letter : '';
    }

    /** ผ่านประตู D แล้ว หรืออยู่ช่วง E — จังหวะที่งานค้างต้องหมด */
    public static function shipped(string $state): bool
    {
        $gates = self::stateFields($state)['ประตูที่ผ่านแล้ว'] ?? '';

        return self::stage($state) === 'E' || preg_match('/(?:^|[\s·])D\s*✅/u', $gates) === 1;
    }

    // ------------------------------------------------------------------- PLAN.md

    /**
     * @return list<array{number: int, heading: string, title: string, status: string, body: string,
     *                    items: list<array{text: string, mark: string, group: string}>,
     *                    steps: list<array{text: string, mark: string}>, pages: list<string>}>
     *
     * status: todo · doing · check · done — จากไอคอนในหัวข้อ phase
     * mark:   'x' ติ๊กแล้ว · ' ' ยังไม่ติ๊ก · '-' ตัดสินว่าไม่ทำหรือเลื่อน
     * steps คือบรรทัด C1–C5 · items คือ checklist อื่นทั้งหมด (ครบลูปและหัวข้อที่โปรเจคเพิ่มเอง)
     */
    public static function phases(string $plan): array
    {
        $out = [];

        foreach (preg_split('/^## /m', self::normalize($plan)) ?: [] as $part) {
            if (preg_match('/^Phase\s+(\d+)([^\n]*)/iu', $part, $m) !== 1) {
                continue;
            }

            $heading = $m[2];
            $body    = (string) substr($part, strlen($m[0]));

            $title = trim(str_replace(self::PHASE_ICONS, '', $heading));
            $title = trim((string) preg_replace('/^[\s—–:-]+/u', '', $title));

            $items = [];
            $steps = [];
            $pages = [];
            $group = '';

            foreach (explode("\n", $body) as $line) {
                if (preg_match('/^###\s+(.+)$/u', $line, $h) === 1) {
                    $group = trim($h[1]);
                    continue;
                }

                if (preg_match('/^\s*[-*]\s*\[([ xX-])\]\s*(.+)$/u', $line, $c) === 1) {
                    $row = ['text' => trim($c[2]), 'mark' => strtolower($c[1])];

                    /* ‼️ ห้ามใช้ \b ท้าย C[1-5] — PCRE โหมด /u นับตัวอักษรไทยเป็นตัวอักษรคำ
                       ขั้น "C4ก ดีไซน์" จึงไม่เข้าเงื่อนไขและกลายเป็นรายการครบลูปแทนขั้นตอน
                       ซึ่งทำให้ docs/fast-mode-debt มองไม่เห็นว่า phase นั้นเลื่อนการตรวจไว้ (3.18.0) */
                    if (preg_match('/^C[1-5](?![0-9])/u', $row['text']) === 1) {
                        $steps[] = $row;
                    } else {
                        $items[] = [...$row, 'group' => $group];
                    }
                    continue;
                }

                if (str_starts_with($group, 'หน้า') && preg_match('/^\s*[-*]\s+(.+)$/u', $line, $p) === 1) {
                    $pages[] = trim($p[1]);
                }
            }

            $out[] = [
                'number'  => (int) $m[1],
                'heading' => $heading,
                'title'   => $title,
                'status'  => match (true) {
                    str_contains($heading, '✅') => 'done',
                    str_contains($heading, '🔨') => 'check',
                    str_contains($heading, '🔄') => 'doing',
                    default                      => 'todo',
                },
                'body'    => $body,
                'items'   => $items,
                'steps'   => $steps,
                'pages'   => $pages,
            ];
        }

        usort($out, static fn (array $a, array $b): int => $a['number'] <=> $b['number']);

        return $out;
    }

    /**
     * เลนของข้อ checklist บนบอร์ดแท็บ "งาน" — ตามสถานะของ phase และการติ๊กของข้อนั้น
     *
     * ข้อที่ติ๊กแล้วใน phase ที่ยังทำอยู่ไปอยู่ "รอตรวจ" ไม่ใช่ "เสร็จ"
     * เพราะติ๊กแปลว่าสร้างเสร็จ ส่วนการตรวจของ phase ยังไม่เกิด (C4 · หรือช่วง D ของโหมดเร็ว)
     */
    public static function itemLane(string $phaseStatus, string $mark): string
    {
        return match ($phaseStatus) {
            'done'  => 'done',
            'check' => 'check',
            'doing' => $mark === ' ' ? 'doing' : 'check',
            default => 'todo',
        };
    }

    // -------------------------------------------------------------- QA-REPORT.md

    /**
     * @return list<array{number: string, title: string, severity: string, state: string, stateText: string,
     *                    fields: array<string, string>}>
     *
     * ข้อผลตรวจคือหัวข้อ `### ` ที่อยู่ใต้หมวดความรุนแรง `## 🔴` · `## 🟡` · `## 🟢`
     * ช่องของแต่ละข้อคือ `- **ชื่อ:** ค่า` ค่าต่อหลายบรรทัดได้จนถึงช่องหรือหัวข้อถัดไป
     * ไม่มีบรรทัด `สถานะ:` = เจอ · ยกเว้นหมวด 🟢 ซึ่งแปลว่ารับได้ = ปิด
     */
    public static function qaIssues(string $report): array
    {
        $out = [];

        foreach (preg_split('/^## /m', self::normalize($report)) ?: [] as $section) {
            $severity = match (true) {
                str_starts_with($section, '🔴') => 'critical',
                str_starts_with($section, '🟡') => 'should',
                str_starts_with($section, '🟢') => 'accepted',
                default                        => null,
            };

            if ($severity === null) {
                continue;
            }

            foreach (array_slice(preg_split('/^### /m', $section) ?: [], 1) as $block) {
                $lines = explode("\n", $block);
                $head  = trim((string) array_shift($lines));

                $number = '';
                $title  = $head;

                if (preg_match('/^(\d+)[.)]\s*(.+)$/u', $head, $h) === 1) {
                    $number = $h[1];
                    $title  = trim($h[2]);
                }

                // บรรทัดแม่แบบอย่าง `### 1. [หัวข้อ]` ที่ยังไม่ได้เติม — ต้องตัดเลขข้อออกก่อนค่อยดู
                if ($title === '' || str_starts_with($title, '[')) {
                    continue;
                }

                $fields = [];
                $key    = null;

                foreach ($lines as $line) {
                    if (preg_match('/^\s*-\s*\*\*(.+?):\*\*[ \t]*(.*)$/u', $line, $f) === 1) {
                        $key          = trim($f[1]);
                        $fields[$key] = trim($f[2]);
                        continue;
                    }

                    if ($key !== null && trim($line) !== '') {
                        $fields[$key] = trim($fields[$key] . "\n" . trim($line));
                    }
                }

                $stateText = $fields['สถานะ'] ?? '';
                $state     = self::issueState($stateText, $severity);

                $out[] = [
                    'number'    => $number,
                    'title'     => $title,
                    'severity'  => $severity,
                    'state'     => $state,
                    'stateText' => $stateText,
                    'fields'    => $fields,
                ];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ DESIGN.md

    /** โฟลเดอร์ภาพแบบของผู้ใช้ (เส้นทาง ค) และภาพหน้าจริงที่ถ่ายไว้เทียบ */
    public const REFS_DIR  = 'project/design/refs';
    public const BUILT_DIR = 'project/design/built';

    /**
     * ตารางใต้หัวข้อที่ขึ้นต้นด้วยคำนี้ใน DESIGN.md — แถวแรกเป็นหัวตาราง
     *
     * @return list<list<string>> ค่าในแต่ละช่องตัด ` และช่องว่างออกแล้ว
     */
    public static function table(string $markdown, string $heading): array
    {
        $rows = [];

        foreach (preg_split('/^## /m', self::normalize($markdown)) ?: [] as $section) {
            if (!str_starts_with($section, $heading)) {
                continue;
            }

            foreach (explode("\n", $section) as $line) {
                $line = trim($line);

                if (!str_starts_with($line, '|') || preg_match('/^\|[\s:|-]+\|$/', $line) === 1) {
                    continue;
                }

                $rows[] = array_map(
                    static fn (string $cell): string => trim(str_replace('`', '', $cell)),
                    explode('|', trim($line, '|'))
                );
            }
        }

        return array_slice($rows, 1);   // ตัดหัวตาราง
    }

    /**
     * ทะเบียนแบบของแต่ละหน้า — หัวข้อ `## แบบของแต่ละหน้า` ใน DESIGN.md
     *
     * | หน้า | ภาพแบบ | ระดับ | ต่างจากแบบ |
     *
     * ภาพแบบเขียนเทียบกับ project/design/ เช่น refs/login.png
     * ระดับ: "ตรงแบบ" (ต้องเทียบและอธิบายทุกความต่าง) หรือ "แนวทาง"
     *
     * @return list<array{page: string, ref: string, strict: bool, diff: string}>
     */
    public static function designRefs(string $design): array
    {
        $out = [];

        foreach (self::table($design, 'แบบของแต่ละหน้า') as $row) {
            $ref = ltrim($row[1] ?? '', './');

            if ($ref === '' || str_starts_with($ref, '[')) {
                continue;   // แถวแม่แบบ
            }

            $out[] = [
                'page'   => $row[0] ?? '',
                'ref'    => str_starts_with($ref, 'project/') ? $ref : 'project/design/' . $ref,
                'strict' => !str_contains($row[2] ?? '', 'แนวทาง'),
                'diff'   => $row[3] ?? '',
            ];
        }

        return $out;
    }

    /** ภาพหน้าจริงที่ต้องถ่ายไว้คู่กับภาพแบบ — ชื่อเดียวกัน อยู่ใต้ BUILT_DIR */
    public static function builtFor(string $ref): string
    {
        $relative = str_starts_with($ref, self::REFS_DIR . '/') ? substr($ref, strlen(self::REFS_DIR) + 1) : basename($ref);

        return self::BUILT_DIR . '/' . preg_replace('/\.(jpe?g|webp|gif)$/i', '.png', $relative);
    }

    /**
     * ภาพที่ตัดจากภาพแบบมาใช้ในระบบ — หัวข้อ `## ภาพที่ตัดจากแบบ` ใน DESIGN.md
     * fetch-image.php --file เขียนแถวให้เอง
     *
     * @return list<array{file: string, from: string, temporary: bool}>
     */
    public static function refCrops(string $design): array
    {
        $out = [];

        foreach (self::table($design, 'ภาพที่ตัดจากแบบ') as $row) {
            if (($row[0] ?? '') === '' || str_starts_with($row[0], '[')) {
                continue;
            }

            $out[] = [
                'file'      => $row[0],
                'from'      => $row[1] ?? '',
                'temporary' => str_contains($row[2] ?? '', 'ชั่วคราว'),
            ];
        }

        return $out;
    }

    /** ภาพหนึ่งใบเป็นภาพแบบหลัก หรือเป็นภาพเสริมของหน้าเดียวกัน (@375 · -dark · -error ฯลฯ) */
    public static function refBase(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);

        return (string) preg_replace('/(@\d+|-(dark|light|mobile|tablet|empty|error|loading|open|hover))+$/i', '', $name);
    }

    public static function issueState(string $text, string $severity): string
    {
        return match (true) {
            str_contains($text, 'ปิด')        => 'closed',
            str_contains($text, 'รอตรวจซ้ำ')  => 'verify',
            str_contains($text, 'กำลังแก้')   => 'fixing',
            str_contains($text, 'เจอ')        => 'found',
            $text === '' && $severity === 'accepted' => 'closed',
            default                           => 'found',
        };
    }
}
