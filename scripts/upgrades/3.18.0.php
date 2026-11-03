<?php
/**
 * ขั้นอัปเกรดของ 3.18.0 — โหมดเร็วไม่เลื่อนการตรวจดีไซน์อีกต่อไป
 *
 * โปรเจคที่ทำงานด้วยโหมดเร็วก่อนหน้านี้มี phase ที่สร้างเสร็จโดยไม่เคยเทียบหน้ากับแบบเลย
 * ขั้นนี้ไม่แก้ PLAN.md ให้ เพราะการติ๊ก C4ก คือการยืนยันว่า "เทียบแล้ว" — ติ๊กแทนกันไม่ได้
 *
 * รูปแบบของไฟล์อยู่ที่หัวไฟล์ scripts/upgrades/3.9.0.php
 */

declare(strict_types=1);

return [
    'fast-mode-design-debt' => [
        'title' => 'phase ที่เลื่อนการตรวจไว้ ต้องเทียบกับแบบย้อนหลัง',
        'run'   => static function (string $root): array {
            $plan = $root . '/project/PLAN.md';

            if (!is_file($plan)) {
                return ['ok', 'ยังไม่มี PLAN.md'];
            }

            require_once $root . '/scripts/lib/ProjectDocs.php';

            $pending = [];

            foreach (ProjectDocs::phases((string) file_get_contents($plan)) as $phase) {
                $step = static fn (string $mark, bool $design): bool => array_filter($phase['steps'],
                    static fn (array $s): bool => $s['mark'] === $mark && str_contains($s['text'], '(โหมดเร็ว)')
                        && str_starts_with($s['text'], 'C4ก') === $design
                        && ($design || str_starts_with($s['text'], 'C4'))) !== [];

                if ($step('-', false) && !$step('x', true)) {
                    $pending[] = 'Phase ' . $phase['number'] . ($phase['title'] !== '' ? ' — ' . $phase['title'] : '');
                }
            }

            if ($pending === []) {
                return ['ok', 'ไม่มี phase ที่ค้างการตรวจดีไซน์'];
            }

            return ['manual',
                count($pending) . ' phase สร้างเสร็จในโหมดเร็วโดยยังไม่เคยเทียบกับแบบ: ' . implode(' · ', $pending)
                . ' — เปิดแบบที่อนุมัติแล้วคู่กับหน้าจริงแล้วไล่ทีละบล็อกตาม standard/testing.md ข้อ 2.2 '
                . 'แก้ของที่หายหรือไม่ตรง แล้วจึงเปลี่ยนบรรทัด C4 เป็น '
                . '`- [x] C4ก ดีไซน์ — เทียบกับแบบแล้ว (โหมดเร็ว)` + `- [-] C4ข ทดสอบที่เหลือ — เลื่อนไปช่วง D (โหมดเร็ว)` '
                . '· ห้ามติ๊กก่อนเทียบจริง'];
        },
    ],
];
