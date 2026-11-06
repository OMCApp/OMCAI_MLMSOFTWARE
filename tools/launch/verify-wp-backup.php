<?php
/**
 * พิสูจน์ว่า backup ของ WordPress เดิม "กู้คืนได้จริง" — รันบนเครื่องทดสอบ (มี MySQL/MariaDB) ไม่ใช่บนเซิร์ฟเวอร์ production
 *
 *   php tools/launch/verify-wp-backup.php --sql=<wordpress.sql | .sql.gz> --files=<โฟลเดอร์ที่แตกไฟล์เว็บเดิมแล้ว>
 *
 * ทำอะไร
 *   1. ไฟล์เว็บ: มี wp-config.php · wp-includes/version.php · wp-content/uploads (นับไฟล์/ขนาด) · plugins · themes
 *      รูปทุกรูปที่บทความเดิมใช้ (docs/migration/images.csv) ต้องอยู่ใน backup
 *   2. ฐานข้อมูล: กู้ลงฐานชั่วคราว wpverify_<เวลา> (MySQL ในเครื่อง · user จาก config/config.php) แล้วตรวจ
 *      ตาราง WordPress ครบ · siteurl/home · บทความ/หน้าที่เผยแพร่ · ทุกบทความและหน้าเดิม (docs/migration/content.json: 36 + 10) มี slug ใน backup
 *   3. ลบฐานชั่วคราวทิ้งเสมอ (แม้ล้มเหลวกลางทาง)
 *
 * ‼️ ไม่แตะเซิร์ฟเวอร์ production · ไม่พิมพ์ค่าใน wp-config.php (แค่บอกว่ามีค่า DB_NAME/DB_USER/AUTH_KEY ครบ)
 * ‼️ ไฟล์ backup มีข้อมูลส่วนตัว/รหัสผ่าน — เก็บนอกโฟลเดอร์โปรเจค ห้าม commit (.gitignore ไม่ครอบคลุมทุกที่ที่คุณอาจวาง)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$opts  = getopt('', ['sql:', 'files:']);
$sql   = (string) ($opts['sql'] ?? '');
$files = rtrim(str_replace('\\', '/', (string) ($opts['files'] ?? '')), '/');
if ($sql === '' || $files === '' || !is_file($sql) || !is_dir($files)) {
    fwrite(STDERR, "usage: php tools/launch/verify-wp-backup.php --sql=<dump.sql|.sql.gz> --files=<extracted site folder>\n");
    exit(2);
}

$root   = dirname(__DIR__, 2);
$config = require $root . '/config/config.php';
if ($config['env'] === 'production') {
    fwrite(STDERR, "run this on a test machine, not on production\n");
    exit(2);
}
if (str_starts_with(realpath($sql) ?: '', realpath($root) ?: '-') || str_starts_with(realpath($files) ?: '', realpath($root) ?: '-')) {
    fwrite(STDERR, "keep the backup OUTSIDE the project folder (it contains passwords and personal data)\n");
    exit(2);
}

$ok = 0;
$bad = [];
$check = static function (bool $pass, string $label, string $detail = '') use (&$ok, &$bad): void {
    $pass ? $ok++ : $bad[] = $label;
    echo ($pass ? '  ok    ' : '  FAIL  ') . $label . ($detail !== '' ? " — {$detail}" : '') . "\n";
};

// ------------------------------------------------------------------ 1. files
echo "files: {$files}\n";
$wpConfig = $files . '/wp-config.php';
$check(is_file($wpConfig), 'wp-config.php present');
if (is_file($wpConfig)) {
    $cfg = (string) file_get_contents($wpConfig);
    $has = array_filter(['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'AUTH_KEY', 'SECURE_AUTH_KEY'], static fn (string $k): bool => preg_match("/define\(\s*['\"]{$k}['\"]/", $cfg) === 1);
    $check(count($has) === 6, 'wp-config.php has DB settings + auth keys', count($has) . '/6 (values not shown)');
    $prefix = preg_match('/\$table_prefix\s*=\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $cfg, $m) === 1 ? $m[1] : 'wp_';
} else {
    $prefix = 'wp_';
}
$version = is_file($files . '/wp-includes/version.php') && preg_match("/\\\$wp_version\s*=\s*'([^']+)'/", (string) file_get_contents($files . '/wp-includes/version.php'), $vm) === 1 ? $vm[1] : '';
$check($version !== '', 'WordPress core files present', $version !== '' ? 'version ' . $version : 'wp-includes/version.php missing');
$uploads = $files . '/wp-content/uploads';
[$count, $bytes] = [0, 0];
if (is_dir($uploads)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads, FilesystemIterator::SKIP_DOTS)) as $f) {
        $count++;
        $bytes += $f->getSize();
    }
}
$check($count > 0, 'wp-content/uploads present', $count . ' files · ' . round($bytes / 1048576, 1) . ' MB');
$check(is_dir($files . '/wp-content/plugins') && is_dir($files . '/wp-content/themes'), 'plugins + themes present',
    count(glob($files . '/wp-content/plugins/*', GLOB_ONLYDIR) ?: []) . ' plugins · ' . count(glob($files . '/wp-content/themes/*', GLOB_ONLYDIR) ?: []) . ' themes');
$check(is_file($files . '/.htaccess'), '.htaccess (WordPress permalinks) present');

// every image the old posts used (captured from the live site) must be in the backup
$missing = [];
$fh = fopen($root . '/docs/seo/baseline-2026-11-03/images.csv', 'rb');
fgetcsv($fh, null, ',', '"', '');
while (($row = fgetcsv($fh, null, ',', '"', '')) !== false) {
    $path = rawurldecode((string) parse_url((string) ($row[1] ?? ''), PHP_URL_PATH));
    if (str_starts_with($path, '/wp-content/uploads/') && !is_file($files . $path)) {
        $missing[$path] = true;
    }
}
fclose($fh);
$check($missing === [], 'every image used on the old site is in the backup', $missing === [] ? '' : count($missing) . ' missing, e.g. ' . array_key_first($missing));

// ------------------------------------------------------------------ 2. database restore into a scratch DB
$d       = $config['db'];
$scratch = 'wpverify_' . date('Ymd_His');
$pdo     = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $d['host'], $d['port']), (string) $d['user'], (string) $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "\ndatabase: restoring {$sql} into scratch DB {$scratch}\n";

try {
    $pdo->exec("CREATE DATABASE `{$scratch}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysql = is_file('C:/xampp/mysql/bin/mysql.exe') ? 'C:/xampp/mysql/bin/mysql.exe' : 'mysql';
    $cmd   = [$mysql, '--default-character-set=utf8mb4', '-h', (string) $d['host'], '-P', (string) $d['port'], '-u', (string) $d['user'], $scratch];
    $env   = getenv() + ['MYSQL_PWD' => (string) $d['pass']];   // password via env, not argv
    $proc  = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
    $in    = str_ends_with($sql, '.gz') ? gzopen($sql, 'rb') : fopen($sql, 'rb');
    while (!(str_ends_with($sql, '.gz') ? gzeof($in) : feof($in))) {
        fwrite($pipes[0], (string) (str_ends_with($sql, '.gz') ? gzread($in, 1 << 20) : fread($in, 1 << 20)));
    }
    str_ends_with($sql, '.gz') ? gzclose($in) : fclose($in);
    fclose($pipes[0]);
    $err  = trim((string) stream_get_contents($pipes[2]));
    $code = proc_close($proc);
    $check($code === 0, 'SQL dump imports without errors', $code === 0 ? '' : mb_substr($err, 0, 200));

    $db     = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $d['host'], $d['port'], $scratch), (string) $d['user'], (string) $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $core   = array_map(static fn (string $t): string => $prefix . $t, ['options', 'posts', 'postmeta', 'users', 'usermeta', 'terms', 'term_taxonomy', 'term_relationships', 'comments']);
    $check(array_diff($core, $tables) === [], 'WordPress core tables restored', count($tables) . ' tables · prefix ' . $prefix);

    if (in_array($prefix . 'options', $tables, true)) {
        $opt = $db->query("SELECT option_name, option_value FROM `{$prefix}options` WHERE option_name IN ('siteurl','home')")->fetchAll(PDO::FETCH_KEY_PAIR);
        $check(str_contains((string) ($opt['home'] ?? ''), 'mlmsoftware.omc.co.th'), 'home/siteurl = mlmsoftware.omc.co.th', ($opt['home'] ?? '?') . ' · ' . ($opt['siteurl'] ?? '?'));
    }
    if (in_array($prefix . 'posts', $tables, true)) {
        $counts = $db->query("SELECT post_type, COUNT(*) FROM `{$prefix}posts` WHERE post_status = 'publish' GROUP BY post_type")->fetchAll(PDO::FETCH_KEY_PAIR);
        $check((int) ($counts['post'] ?? 0) >= 36, 'published posts restored', (int) ($counts['post'] ?? 0) . ' posts · ' . (int) ($counts['page'] ?? 0) . ' pages (live site had 36 posts)');

        $slugs   = array_flip(array_map('rawurldecode', $db->query("SELECT post_name FROM `{$prefix}posts` WHERE post_status = 'publish'")->fetchAll(PDO::FETCH_COLUMN)));
        $content = json_decode((string) file_get_contents($root . '/docs/migration/content.json'), true);
        $absent  = [];
        foreach ((array) $content as $item) {
            if (!in_array($item['type'] ?? '', ['post', 'page'], true)) {
                continue;
            }
            $segments = array_values(array_filter(explode('/', (string) $item['path_decoded'])));
            $slug     = (string) end($segments);
            if ($slug !== '' && !isset($slugs[$slug])) {
                $absent[] = (string) $item['path_decoded'];
            }
        }
        $check($absent === [], 'every old post/page URL (content.json) exists in the backup', $absent === [] ? '46 of 46' : count($absent) . ' missing, e.g. ' . $absent[0]);
    }
} catch (Throwable $e) {
    $check(false, 'database restore', $e->getMessage());
} finally {
    $pdo->exec("DROP DATABASE IF EXISTS `{$scratch}`");
    echo "  ..    scratch DB {$scratch} dropped\n";
}

printf("\n%d ok · %d failed → %s\n", $ok, count($bad), $bad === [] ? 'BACKUP IS RESTORABLE' : 'NOT VERIFIED');
exit($bad === [] ? 0 : 1);
