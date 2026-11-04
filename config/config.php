<?php
/**
 * ‼️ ไฟล์เดียวในระบบที่อ่าน .env
 *
 * ที่อื่นอ่านค่าผ่าน config('key.path') เท่านั้น
 * เหตุผล: วันที่เปลี่ยนวิธีเก็บค่าตั้ง (เช่นย้ายไป environment variable ของ host)
 *          จะได้แก้ที่เดียว ไม่ต้องไล่ทั้งโปรเจค
 */

declare(strict_types=1);

/**
 * อ่าน .env แบบง่าย — ไม่พึ่ง library เพราะกฎเหล็กห้ามเพิ่ม dependency โดยไม่ถาม
 *
 * @return array<string, string>
 */
$readEnv = static function (string $path): array {
    if (!is_file($path)) {
        return [];
    }

    $out = [];

    foreach (preg_split('/\R/', (string) file_get_contents($path)) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // ตัดเครื่องหมายคำพูดรอบค่าออก แต่เก็บสิ่งที่อยู่ข้างในไว้ทั้งหมด
        if (strlen($value) > 1
            && (($value[0] === '"' && str_ends_with($value, '"'))
                || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        $out[$key] = $value;
    }

    return $out;
};

$root = dirname(__DIR__);
$env  = $readEnv($root . '/.env') + $_ENV + $_SERVER;

$get = static function (string $key, string $default = '') use ($env): string {
    $value = $env[$key] ?? $default;

    return is_string($value) ? $value : $default;
};

// ‼️ default ต้องเป็น production เสมอ
//    ถ้า default เป็น development แล้วลืมตั้งบน server จริง จะกลายเป็นเปิดแสดง error ให้คนนอกเห็น
$appEnv = $get('APP_ENV', 'production');
$debug  = $appEnv === 'development';

date_default_timezone_set($get('APP_TIMEZONE', 'Asia/Bangkok'));

/* ‼️ generate จาก project/DESIGN.md ด้วย scripts/design-sync.php ห้ามแก้ด้วยมือ
   โปรเจคที่ยังไม่ได้รัน design-sync จะยังไม่มีไฟล์นี้ จึงต้องเผื่อค่าไว้ให้รันขึ้นได้ */
$designPath = $root . '/config/design.php';
$design     = is_file($designPath) ? (array) require $designPath : [];

return [
    'name'  => $get('APP_NAME', 'System'),
    'env'   => $appEnv,
    'debug' => $debug,
    'url'   => rtrim($get('APP_URL'), '/'),
    'https' => $get('APP_HTTPS', $appEnv === 'production' ? 'true' : 'false') === 'true',

    /* ‼️ canonical / og:url / sitemap <loc> ชี้ host ของ production เสมอ แม้รันบน staging
       (PLAN.md "SEO ที่ทุกหน้า public ต้องมี") — staging ถูกกันด้วย noindex + Basic Auth อยู่แล้ว
       และหน้าที่เห็นบน staging ต้องเป็นหน้าเดียวกับที่จะขึ้นจริงทุกตัวอักษร
       ไม่ใช้ APP_URL เพราะบน staging APP_URL คือ http://IP/~omcai/... ซึ่งห้ามหลุดไปเป็น canonical */
    'seo' => [
        'canonical_url' => rtrim($get('CANONICAL_URL', 'https://mlmsoftware.omc.co.th'), '/'),
    ],

    // อีเมลแจ้งเตือน lead ใหม่ (App\Services\MailService ใช้ mail() ของ PHP) — ปิด/ไม่มีผู้ส่ง = ไม่ส่ง แค่บันทึก log
    'mail' => [
        'enabled' => $get('MAIL_ENABLED', 'false') === 'true',
        'from'    => $get('MAIL_FROM', ''),
    ],

    'db' => [
        'host' => $get('DB_HOST', '127.0.0.1'),
        'port' => (int) $get('DB_PORT', '3306'),
        'name' => $get('DB_NAME'),
        'user' => $get('DB_USER'),
        'pass' => $get('DB_PASS'),
    ],

    // ‼️ th + en เป็นค่าบังคับทุกโปรเจค แม้ระบบจะใช้ภาษาไทยอย่างเดียว
    //    ทำวันแรกแทบไม่มีต้นทุน ส่วนที่แพงคือย้อนกลับมาทำทีหลัง
    'locales'        => array_values(array_filter(array_map(
        'trim',
        explode(',', $get('APP_LOCALES', implode(',', array_keys((array) require __DIR__ . '/locales.php'))))
    ))),
    'default_locale' => $get('APP_DEFAULT_LOCALE', 'th'),

    /* ‼️ ค่าหน้าตาไม่ได้อยู่ใน .env — มันเป็นการตัดสินใจของ DESIGN.md ไม่ใช่ของ environment
       config/design.php generate จาก DESIGN.md ด้วย design-sync.php
       เคยคัดลอกไว้ใน .env ด้วยมือ แล้วสองที่เถียงกันได้เงียบๆ โดยไม่มีอะไรรายงาน */
    'ui' => $design + [
        'nav'           => 'sidebar',
        'default_theme' => 'light',
    ],

    // ‼️ ใช้เฉพาะ environment ที่ไม่ใช่ production — ตั้ง STAGING_PASS แล้วทุกหน้าต้องใส่รหัสก่อนเข้า
    'staging' => [
        'user' => $get('STAGING_USER', 'omc'),
        'pass' => $get('STAGING_PASS'),
    ],

    'session' => [
        'secure'       => $get('APP_HTTPS', 'false') === 'true',
        'idle_timeout' => (int) $get('SESSION_IDLE_TIMEOUT', '0'),
    ],

    'paths' => [
        'log'      => $root . '/storage/logs/app.log',
        'uploads'  => $root . '/storage/uploads',
        'backups'  => $root . '/storage/backups',
        'lang'     => $root . '/lang',
        'maintenance' => $root . '/storage/maintenance.flag',
    ],

    'upload' => [
        'max_bytes'  => (int) $get('UPLOAD_MAX_BYTES', '5242880'),
        'extensions' => array_values(array_filter(array_map(
            'trim',
            explode(',', $get('UPLOAD_EXTENSIONS', 'jpg,jpeg,png,webp,pdf'))
        ))),
    ],

    'backup' => [
        'keep_days'         => (int) $get('BACKUP_KEEP_DAYS', '14'),
        'log_max_bytes'     => (int) $get('LOG_MAX_BYTES', '10485760'),
        'audit_keep_months' => (int) $get('AUDIT_KEEP_MONTHS', '12'),
    ],

    // ‼️ ปลายทางที่แก้โครงสร้างฐานข้อมูลได้ เปิดอยู่บนอินเทอร์เน็ต
    //    สุ่มใหม่ทุกโปรเจค อย่าใช้ซ้ำ และเปลี่ยนทันทีถ้าเคยหลุด
    'migrate_token' => $get('MIGRATE_TOKEN'),

    'features' => [
        'auth'    => $get('FEATURE_AUTH', 'true') === 'true',
        'manual'  => $get('FEATURE_MANUAL', 'true') === 'true',
        'support' => $get('FEATURE_SUPPORT', 'true') === 'true',
    ],
];
