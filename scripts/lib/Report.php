<?php
/**
 * พิมพ์ผลตรวจให้อ่านแล้วรู้ทันทีว่าต้องแก้อะไรตรงไหน
 */

declare(strict_types=1);

final class Report
{
    private static ?bool $color = null;

    private static function useColor(): bool
    {
        if (self::$color === null) {
            // ใส่สีเฉพาะตอนพิมพ์ลงเทอร์มินัลจริง
            // ถ้าถูก pipe (hook, CI, ไฟล์) รหัสสีจะกลายเป็นขยะที่อ่านยาก
            self::$color = PHP_SAPI === 'cli'
                && getenv('NO_COLOR') === false
                && getenv('CI') === false
                && function_exists('stream_isatty')
                && @stream_isatty(STDOUT);
        }

        return self::$color;
    }

    private static function paint(string $text, string $code): string
    {
        return self::useColor() ? "\033[{$code}m{$text}\033[0m" : $text;
    }

    public static function bold(string $t): string
    {
        return self::paint($t, '1');
    }

    public static function red(string $t): string
    {
        return self::paint($t, '31');
    }

    public static function yellow(string $t): string
    {
        return self::paint($t, '33');
    }

    public static function green(string $t): string
    {
        return self::paint($t, '32');
    }

    public static function dim(string $t): string
    {
        return self::paint($t, '2');
    }

    public static function line(string $text = ''): void
    {
        echo $text . "\n";
    }

    /** @return int exit code */
    public static function render(Ctx $ctx, ?string $onlyFile, ?string $label = null): int
    {
        $errors = array_values(array_filter($ctx->issues, fn ($i) => $i['level'] === 'error'));
        $warns  = array_values(array_filter($ctx->issues, fn ($i) => $i['level'] === 'warn'));

        if ($ctx->issues === []) {
            $what = $label ?? ($onlyFile !== null ? $onlyFile : 'ทั้งโปรเจค');
            self::line(self::green('✓') . " ชุดตรวจมาตรฐานผ่านทั้งหมด — {$what}");

            return 0;
        }

        foreach (['error' => $errors, 'warn' => $warns] as $level => $issues) {
            if ($issues === []) {
                continue;
            }

            $mark  = $level === 'error' ? self::red('✗') : self::yellow('!');
            $label = $level === 'error' ? 'ต้องแก้' : 'ควรดู';

            self::line();
            self::line(self::bold("{$mark} {$label} (" . count($issues) . ')'));

            $lastCheck = '';
            foreach ($issues as $issue) {
                $key = $issue['group'] . '/' . $issue['check'];

                if ($key !== $lastCheck) {
                    $lastCheck = $key;
                    self::line();
                    self::line('  ' . self::bold($key) . ' — ' . $issue['title']);
                }

                $where = $issue['file'] . ($issue['line'] > 0 ? ':' . $issue['line'] : '');
                self::line('    ' . $where);
                self::line('      ' . $issue['message']);

                if ($issue['snippet'] !== '') {
                    self::line('      ' . self::dim($issue['snippet']));
                }
                if ($issue['fix'] !== '') {
                    self::line('      ' . self::green('→ ') . $issue['fix']);
                }
            }
        }

        self::line();
        self::line(str_repeat('─', 60));
        self::line(
            'สรุป: ' . self::red((string) count($errors) . ' ต้องแก้')
            . ' · ' . self::yellow((string) count($warns) . ' ควรดู')
        );

        if ($errors !== []) {
            self::line();
            self::line(self::bold('แก้ที่โค้ด ห้ามแก้ที่ scripts/check.php ให้ผ่าน'));
            self::line(self::dim('ถ้าเชื่อว่ากฎในสคริปต์ผิดจริง ให้แจ้งผู้ใช้ แล้วแก้ที่ repo มาตรฐานกลาง'));
        }

        self::line();

        return $errors === [] ? 0 : 1;
    }
}
