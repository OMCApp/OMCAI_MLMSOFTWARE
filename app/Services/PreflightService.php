<?php
/**
 * ตรวจความพร้อมของเซิร์ฟเวอร์ก่อนเปิดใช้งาน — PHP · extension · .env · ฐานข้อมูล · migration · สิทธิ์ไฟล์ · SMTP · document root
 *
 * ใช้จาก /_preflight (token เดียวกับ /_migrate — ไม่มี SSH ก็ตรวจได้) และ tools/launch/preflight.php (CLI)
 *
 * ‼️ อ่านอย่างเดียว — ไม่เขียนฐานข้อมูล ไม่ส่งอีเมล ไม่ล็อกอิน SMTP (แค่ทักทาย + STARTTLS)
 * ‼️ ห้ามคืนค่าลับ — รายงานแค่ "มี/ไม่มี · ความยาว · ตรง/ไม่ตรงเกณฑ์" ไม่มีรหัสผ่าน token หรือชื่อผู้ใช้ฐานข้อมูล
 * ‼️ กันพลาดร้ายแรงที่สุด: ฐานข้อมูลที่ชี้อยู่มีตาราง WordPress = ชี้ผิดฐาน → fail (ห้าม migrate ลงฐานของเว็บเดิม)
 */

declare(strict_types=1);

namespace App\Services;

use Core\Database;

final class PreflightService
{
    public const PRODUCTION_URL = 'https://mlmsoftware.omc.co.th';

    /** @var list<array{area:string,check:string,status:string,detail:string}> */
    private array $rows = [];

    /**
     * @param  array<string, mixed>      $config  config/config.php
     * @param  array<string, mixed>|null $server  $_SERVER ของ request เว็บ (null = CLI — ข้ามข้อ document root)
     * @return list<array{area:string,check:string,status:string,detail:string}>
     */
    public function run(array $config, string $root, ?array $server = null): array
    {
        $this->rows = [];
        $this->php();
        $this->env($config);
        $this->database($root);
        $this->files($config, $root);
        $this->mail($config);
        if ($server !== null) {
            $this->docroot($server, $root);
        }

        return $this->rows;
    }

    /** ok | warn | fail ที่แย่ที่สุด */
    public static function verdict(array $rows): string
    {
        $statuses = array_column($rows, 'status');

        return in_array('fail', $statuses, true) ? 'fail' : (in_array('warn', $statuses, true) ? 'warn' : 'ok');
    }

    public static function text(array $rows): string
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = sprintf('%-4s  %-9s %-28s %s', strtoupper($r['status']), $r['area'], $r['check'], $r['detail']);
        }

        return implode("\n", $out) . "\n\nverdict: " . strtoupper(self::verdict($rows)) . "\n";
    }

    private function add(string $area, string $check, string $status, string $detail = ''): void
    {
        $this->rows[] = ['area' => $area, 'check' => $check, 'status' => $status, 'detail' => $detail];
    }

    private function php(): void
    {
        // readonly properties · never · new in initializers → 8.1 ขึ้นไป · ทดสอบจริงบน 8.2
        $v = PHP_VERSION;
        $this->add('php', 'version', version_compare($v, '8.1.0', '<') ? 'fail' : (version_compare($v, '8.2.0', '<') ? 'warn' : 'ok'),
            $v . ' (needs ≥ 8.1, tested on 8.2)');

        foreach (['pdo_mysql' => 'fail', 'mbstring' => 'fail', 'json' => 'fail', 'openssl' => 'fail', 'fileinfo' => 'fail',
                  'gd' => 'warn', 'zlib' => 'warn', 'curl' => 'warn'] as $ext => $missing) {
            $this->add('php', 'ext ' . $ext, extension_loaded($ext) ? 'ok' : $missing,
                extension_loaded($ext) ? '' : ['gd' => 'media WebP/thumbnails off', 'zlib' => 'backups not gzipped', 'curl' => 'tools only'][$ext] ?? 'required');
        }
        $this->add('php', 'GD WebP', function_exists('imagewebp') ? 'ok' : 'warn', function_exists('imagewebp') ? '' : 'uploads stay original format');
        $this->add('php', 'display_errors', in_array(strtolower((string) ini_get('display_errors')), ['', '0', 'off', 'stderr'], true) ? 'ok' : 'warn',
            'app hides errors itself; server default should be off');
        $this->add('php', 'upload_max_filesize', 'ok', (string) ini_get('upload_max_filesize') . ' · post_max_size ' . (string) ini_get('post_max_size'));
    }

    /** @param array<string, mixed> $config */
    private function env(array $config): void
    {
        $prod = ($config['env'] ?? '') === 'production';
        $this->add('env', 'APP_ENV', $prod ? 'ok' : 'fail', $prod ? 'production' : 'is "' . (string) $config['env'] . '" — must be production');
        $url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->add('env', 'APP_URL', $url === self::PRODUCTION_URL ? 'ok' : 'fail', $url === self::PRODUCTION_URL ? $url : 'must be ' . self::PRODUCTION_URL);
        $canon = (string) ($config['seo']['canonical_url'] ?? '');
        $this->add('env', 'CANONICAL_URL', $canon === self::PRODUCTION_URL ? 'ok' : 'fail', $canon === self::PRODUCTION_URL ? 'ok' : 'must be ' . self::PRODUCTION_URL);
        $this->add('env', 'APP_HTTPS', !empty($config['https']) ? 'ok' : 'fail', !empty($config['https']) ? 'secure cookies + HSTS' : 'must be true on production');
        $this->add('env', 'debug', empty($config['debug']) ? 'ok' : 'fail', empty($config['debug']) ? 'off' : 'on — must be off');

        $key = (string) ($config['app_key'] ?? '');
        $this->add('env', 'APP_KEY', strlen($key) >= 32 ? 'ok' : 'fail', strlen($key) >= 32 ? strlen($key) . ' chars' : 'missing or shorter than 32 chars');
        $token = (string) ($config['migrate_token'] ?? '');
        $this->add('env', 'MIGRATE_TOKEN', strlen($token) >= 32 ? 'ok' : 'fail', strlen($token) . ' chars (needs ≥ 32)');
        $this->add('env', 'STAGING_PASS', (string) ($config['staging']['pass'] ?? '') === '' ? 'ok' : 'warn', 'must be empty on production (otherwise Basic Auth)');
        $locales = (array) ($config['locales'] ?? []);
        $this->add('env', 'APP_LOCALES', count(array_intersect(['th', 'en', 'zh'], $locales)) === 3 ? 'ok' : 'warn', implode(',', $locales) . ' (expected th,en,zh)');
    }

    private function database(string $root): void
    {
        try {
            $pdo = Database::pdo();
        } catch (\Throwable $e) {
            $this->add('db', 'connect', 'fail', 'cannot connect (check DB_* in .env)');

            return;
        }

        $this->add('db', 'connect', 'ok');
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $this->add('db', 'server', 'ok', $version);

        try {
            $json = $pdo->query("SELECT JSON_UNQUOTE(JSON_EXTRACT('{\"a\":\"ok\"}', '\$.a'))")->fetchColumn();
            $this->add('db', 'JSON functions', $json === 'ok' ? 'ok' : 'fail', 'needs MySQL ≥ 5.7.8 / MariaDB ≥ 10.2.7');
        } catch (\Throwable) {
            $this->add('db', 'JSON functions', 'fail', 'not supported — needs MySQL ≥ 5.7.8 / MariaDB ≥ 10.2.7');
        }

        $charset = (string) $pdo->query('SELECT @@character_set_database')->fetchColumn();
        $this->add('db', 'charset', $charset === 'utf8mb4' ? 'ok' : 'warn', $charset . ' (tables are created utf8mb4 either way)');

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $wp     = array_values(array_filter($tables, static fn (string $t): bool => (bool) preg_match('/_(options|posts|postmeta|usermeta)$/', $t)
            && !in_array($t, ['posts'], true)));
        $this->add('db', 'not the WordPress DB', $wp === [] ? 'ok' : 'fail',
            $wp === [] ? count($tables) . ' tables' : 'WordPress tables found (' . implode(', ', array_slice($wp, 0, 3)) . ') — use a NEW empty database');

        $files   = array_map('basename', glob($root . '/database/migrations/*.sql') ?: []);
        $applied = in_array('migrations', $tables, true) ? $pdo->query('SELECT filename FROM migrations')->fetchAll(\PDO::FETCH_COLUMN) : [];
        $pending = array_values(array_diff($files, $applied));
        $this->add('db', 'migrations', $pending === [] ? 'ok' : 'warn',
            count($applied) . ' applied · ' . count($pending) . ' pending' . ($pending === [] ? '' : ' (run /_migrate) — next: ' . $pending[0]));

        if (in_array('users', $tables, true)) {
            $users = $pdo->query('SELECT COUNT(*) AS n, SUM(must_change_password) AS m FROM users WHERE deleted_at IS NULL')->fetch(\PDO::FETCH_ASSOC) ?: [];
            $seedOnly = (int) ($users['n'] ?? 0) === 1 && (int) ($users['m'] ?? 0) === 1;
            $this->add('db', 'admin accounts', $seedOnly ? 'warn' : 'ok', $seedOnly
                ? 'only the initial admin (seed password) — log in and change it right after go-live'
                : (int) ($users['n'] ?? 0) . ' account(s)');
        }
    }

    /** @param array<string, mixed> $config */
    private function files(array $config, string $root): void
    {
        foreach (['storage' => $root . '/storage', 'storage/logs' => $root . '/storage/logs', 'storage/backups' => $root . '/storage/backups',
                  'public/uploads' => $root . '/public/uploads'] as $label => $dir) {
            $ok = is_dir($dir) && is_writable($dir);
            $this->add('files', $label . ' writable', $ok ? 'ok' : 'fail', $ok ? '' : (is_dir($dir) ? 'not writable by PHP' : 'missing'));
        }

        $legacy = $root . '/public/wp-content/uploads';
        $count  = is_dir($legacy) ? iterator_count(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($legacy, \FilesystemIterator::SKIP_DOTS))) : 0;
        $this->add('files', 'old images uploaded', $count >= 500 ? 'ok' : 'fail', $count . ' files in public/wp-content/uploads (originals + WebP ≈ 600)');

        try {
            $missing = 0;
            $media   = Database::select("SELECT path, sizes FROM media WHERE deleted_at IS NULL AND path LIKE '/wp-content/uploads/%'");
            foreach ($media as $row) {
                $paths = [(string) $row['path']];
                foreach ((array) json_decode((string) ($row['sizes'] ?? ''), true) as $variant) {
                    $paths[] = (string) $variant;
                }
                foreach ($paths as $p) {
                    $missing += is_file($root . '/public' . $p) ? 0 : 1;
                }
            }
            $this->add('files', 'media files on disk', $missing === 0 ? 'ok' : 'fail', $missing === 0 ? 'every media row has its file' : $missing . ' file(s) missing');
        } catch (\Throwable) {
            $this->add('files', 'media files on disk', 'warn', 'media table not ready (run /_migrate first)');
        }

        $this->add('files', '.env not in public/', is_file($root . '/public/.env') ? 'fail' : 'ok', is_file($root . '/public/.env') ? 'remove public/.env' : '');
        $this->add('files', 'maintenance flag', is_file((string) $config['paths']['maintenance']) ? 'warn' : 'ok',
            is_file((string) $config['paths']['maintenance']) ? 'present — site answers 503 until /_migrate succeeds' : 'off');
    }

    /** @param array<string, mixed> $config */
    private function mail(array $config): void
    {
        $mail = (array) ($config['mail'] ?? []);

        if (!MailService::configured()) {
            $this->add('mail', 'configured', 'warn', 'MAIL_* not set — leads are saved but sales gets no e-mail');

            return;
        }

        $this->add('mail', 'configured', 'ok', (string) $mail['driver'] . ' · from ' . (string) $mail['from'] . ' · to ' . LeadNotifier::recipient());

        if ($mail['driver'] !== 'smtp') {
            return;
        }

        // ทักทาย + STARTTLS เท่านั้น — ไม่ AUTH ไม่ส่ง (ทดสอบส่งจริงที่ CMS → ตั้งค่า → ส่งอีเมลทดสอบ)
        $host   = (string) $mail['host'];
        $port   = (int) $mail['port'];
        $scheme = $mail['encryption'] === 'ssl' ? 'ssl' : 'tcp';
        $s      = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 8);

        if ($s === false) {
            $this->add('mail', 'SMTP reachable', 'fail', "{$host}:{$port} — {$errstr} (outbound port blocked by the host?)");

            return;
        }

        stream_set_timeout($s, 8);
        $read = static function () use ($s): string {
            $out = '';
            while (($line = fgets($s, 512)) !== false) {
                $out .= $line;
                if (strlen($line) < 4 || $line[3] !== '-') {
                    break;
                }
            }

            return $out;
        };
        $greeting = $read();
        fwrite($s, "EHLO preflight.local\r\n");
        $ehlo = $read();
        $tls  = 'n/a';
        if ($mail['encryption'] === 'tls') {
            $tls = 'not offered';
            if (str_contains($ehlo, 'STARTTLS')) {
                fwrite($s, "STARTTLS\r\n");
                $read();
                $tls = @stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) ? 'ok (certificate verified)' : 'handshake failed';
            }
        }
        @fwrite($s, "QUIT\r\n");
        fclose($s);
        $this->add('mail', 'SMTP reachable', str_starts_with($greeting, '220') ? 'ok' : 'fail', "{$host}:{$port} · " . trim(substr($greeting, 0, 40)));
        if ($mail['encryption'] === 'tls') {
            $this->add('mail', 'STARTTLS', str_starts_with($tls, 'ok') ? 'ok' : 'fail', $tls);
        }
    }

    /** @param array<string, mixed> $server */
    private function docroot(array $server, string $root): void
    {
        $docroot = rtrim(str_replace('\\', '/', (string) ($server['DOCUMENT_ROOT'] ?? '')), '/');
        $public  = rtrim(str_replace('\\', '/', (string) realpath($root . '/public')), '/');
        $real    = rtrim(str_replace('\\', '/', (string) realpath($docroot)), '/');
        $this->add('server', 'document root = public/', $real === $public ? 'ok' : 'warn', $real === $public
            ? 'ok'
            : 'document root is not public/ — the root .htaccess fallback is in use (see LAUNCH.md section 4)');
        $https = (($server['HTTPS'] ?? '') !== '' && strtolower((string) $server['HTTPS']) !== 'off') || ($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $this->add('server', 'request over HTTPS', $https ? 'ok' : 'warn', $https ? (string) ($server['SERVER_PROTOCOL'] ?? '') : 'this check was called over plain HTTP');
    }
}
