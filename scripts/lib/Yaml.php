<?php
/**
 * ตัวอ่าน YAML แบบย่อ — รองรับเฉพาะที่ project/DESIGN.md ใช้จริง
 *
 * ทำไมไม่ใช้ library: กฎเหล็กข้อ 2 ห้ามเพิ่ม dependency โดยไม่ถามผู้ใช้
 * และ ext/yaml ไม่ได้ติดมากับ PHP ทุกเครื่อง — ถ้าพึ่งมันจะพังบนเครื่องเพื่อนร่วมทีม
 *
 * รองรับ: แผนที่ซ้อนชั้นด้วยการย่อหน้า · แผนที่แบบบรรทัดเดียว { a: 1, b: "x" } · คอมเมนต์ #
 * ไม่รองรับ: อาเรย์ (-), anchor, multi-line string — DESIGN.md ไม่ได้ใช้
 */

declare(strict_types=1);

final class Yaml
{
    /** @return array<string, mixed> */
    public static function parse(string $text): array
    {
        $rows = [];

        foreach (preg_split('/\R/', $text) ?: [] as $number => $raw) {
            $line = self::stripComment($raw);

            if (trim($line) === '') {
                continue;
            }

            $rows[] = [
                'indent' => strlen($line) - strlen(ltrim($line, ' ')),
                'text'   => trim($line),
                'line'   => $number + 1,
            ];
        }

        $index = 0;

        return self::block($rows, $index, 0);
    }

    /**
     * @param  array<int, array{indent:int,text:string,line:int}> $rows
     * @return array<string, mixed>
     */
    private static function block(array $rows, int &$index, int $indent): array
    {
        $out = [];

        while ($index < count($rows)) {
            $row = $rows[$index];

            if ($row['indent'] < $indent) {
                break;
            }

            if ($row['indent'] > $indent) {
                // ย่อหน้าลึกกว่าที่ควร — ข้ามไปเพื่อไม่ให้ทั้งไฟล์พัง
                $index++;
                continue;
            }

            if (!str_contains($row['text'], ':')) {
                $index++;
                continue;
            }

            [$key, $rest] = explode(':', $row['text'], 2);
            $key  = trim($key, " \t\"'");
            $rest = trim($rest);
            $index++;

            if ($rest === '') {
                $next = $rows[$index] ?? null;

                $out[$key] = ($next !== null && $next['indent'] > $indent)
                    ? self::block($rows, $index, $next['indent'])
                    : null;

                continue;
            }

            $out[$key] = self::value($rest);
        }

        return $out;
    }

    private static function value(string $raw): mixed
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{')) {
            return self::inlineMap($raw);
        }

        return self::scalar($raw);
    }

    /** @return array<string, mixed> */
    private static function inlineMap(string $raw): array
    {
        $body = trim($raw, "{} \t");
        $out  = [];

        foreach (self::splitTop($body) as $part) {
            if (!str_contains($part, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $part, 2);
            $out[trim($key, " \t\"'")] = self::scalar(trim($value));
        }

        return $out;
    }

    /**
     * แยกด้วยจุลภาคโดยไม่ตัดข้างในวงเล็บหรือเครื่องหมายคำพูด
     * จำเป็นเพราะค่าอย่าง cubic-bezier(.2,0,0,1) มีจุลภาคอยู่ข้างใน
     *
     * @return array<int, string>
     */
    private static function splitTop(string $text): array
    {
        $parts = [];
        $depth = 0;
        $quote = '';
        $buf   = '';

        foreach (str_split($text) as $char) {
            if ($quote !== '') {
                $buf .= $char;
                if ($char === $quote) {
                    $quote = '';
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $buf  .= $char;
                continue;
            }

            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
            }
            if ($char === ')' || $char === ']' || $char === '}') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = trim($buf);
                $buf     = '';
                continue;
            }

            $buf .= $char;
        }

        if (trim($buf) !== '') {
            $parts[] = trim($buf);
        }

        return $parts;
    }

    private static function scalar(string $raw): mixed
    {
        $raw = trim($raw);

        if ((str_starts_with($raw, '"') && str_ends_with($raw, '"'))
            || (str_starts_with($raw, "'") && str_ends_with($raw, "'"))) {
            return substr($raw, 1, -1);
        }

        return match (strtolower($raw)) {
            'true'           => true,
            'false'          => false,
            'null', '~', ''  => null,
            default          => is_numeric($raw) ? $raw + 0 : $raw,
        };
    }

    private static function stripComment(string $line): string
    {
        $quote = '';

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if ($quote !== '') {
                if ($char === $quote) {
                    $quote = '';
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            // # ที่ขึ้นต้นบรรทัดหรือมีช่องว่างนำหน้า ถือเป็นคอมเมนต์
            // (#1F4FD8 จึงไม่ถูกตัด เพราะไม่มีช่องว่างนำหน้า)
            if ($char === '#' && ($i === 0 || $line[$i - 1] === ' ')) {
                return substr($line, 0, $i);
            }
        }

        return $line;
    }

    /** ดึง front matter ระหว่าง --- ออกมา */
    public static function frontMatter(string $document): ?string
    {
        if (preg_match('/\A\s*---\R(.*?)\R---\s*\R/s', $document, $m) !== 1) {
            return null;
        }

        return $m[1];
    }
}
