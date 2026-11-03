<?php
/**
 * ตั้งค่าเครื่องนี้ให้พร้อมทำงาน — ทำแทนทุกขั้นที่เคยให้คนพิมพ์เอง
 *
 *   php scripts/setup.php              ตั้งค่าทั้งหมดที่ทำอัตโนมัติได้
 *   php scripts/setup.php --check      ตรวจอย่างเดียว ไม่แก้อะไร (hook ใช้ตัวนี้)
 *   php scripts/setup.php --production ไม่ต้องตั้ง .env เป็นโหมด development
 *
 * exit 0 = พร้อมทำงาน · exit 1 = ยังมีของที่คนต้องทำเอง
 *
 * ‼️ ทุกอย่างในไฟล์นี้ต้องรันซ้ำได้โดยไม่พัง — ผู้ใช้รันซ้ำเมื่อไหร่ก็ได้
 * ‼️ ห้ามทับค่าที่ผู้ใช้กรอกไว้แล้ว เติมเฉพาะช่องที่ยังว่าง
 */

declare(strict_types=1);

require __DIR__ . '/lib/Report.php';

$root       = dirname(__DIR__);
$args       = $_SERVER['argv'] ?? [];
$checkOnly  = in_array('--check', $args, true);
$production = in_array('--production', $args, true);

/** ปัญหาที่คนต้องลงมือเอง — สคริปต์ทำแทนไม่ได้ */
$manual = [];
/** สิ่งที่ทำให้แล้ว */
$done = [];

// ================================================================ ตัวช่วย

/**
 * รันคำสั่งแล้วคืน [exit code, output]
 *
 * ‼️ ต้องรวม stderr เข้า stdout ด้วย 2>&1 ห้ามเปิดสองท่อแล้วอ่านทีละท่อ
 *    เปิดสองท่อแล้วอ่าน stdout จนจบก่อน จะค้างตายเมื่อคำสั่งเขียน stderr มากพอจนท่อเต็ม
 *    ฝั่งเรารอ stdout ฝั่งมันรอให้ stderr ว่าง ไม่มีใครขยับ (composer เขียน stderr ทั้งหมด)
 *    และ stream_select ใช้กับ pipe ของ proc_open บน Windows ไม่ได้ จึงต้องใช้ท่อเดียว
 *
 * @return array{0:int,1:string}
 */
function sh(string $command, string $cwd): array
{
    $proc = proc_open($command . ' 2>&1', [1 => ['pipe', 'w']], $pipes, $cwd);

    if (!is_resource($proc)) {
        return [-1, ''];
    }

    $out = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return [proc_close($proc), trim($out)];
}

function has_command(string $name): bool
{
    $probe = PHP_OS_FAMILY === 'Windows' ? "where {$name}" : "command -v {$name}";
    [$code] = sh($probe, sys_get_temp_dir());

    return $code === 0;
}

/**
 * อ่าน .env เป็น map — ใช้ตัดสินว่าช่องไหนยังว่าง
 *
 * @return array<string, string>
 */
function env_read(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $out = [];

    foreach (preg_split('/\R/', (string) file_get_contents($path)) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);

        if (strlen($v) > 1 && ($v[0] === '"' || $v[0] === "'") && str_ends_with($v, $v[0])) {
            $v = substr($v, 1, -1);
        }

        $out[trim($k)] = $v;
    }

    return $out;
}

/**
 * เขียนค่าลง .env โดยรักษาคอมเมนต์และลำดับเดิมไว้ทั้งหมด
 *
 * ‼️ ห้าม generate .env ใหม่ทั้งไฟล์ — คอมเมนต์เตือนที่มากับ .env.example คือส่วนหนึ่งของมาตรฐาน
 *
 * @param array<string, string> $values
 */
function env_write(string $path, array $values): void
{
    $lines = preg_split('/\R/', (string) file_get_contents($path)) ?: [];

    foreach ($lines as $i => $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($line, '=')) {
            continue;
        }

        $key = trim(explode('=', $line, 2)[0]);

        if (array_key_exists($key, $values)) {
            $v = $values[$key];
            // ใส่เครื่องหมายคำพูดเมื่อค่ามีช่องว่าง เพื่อให้ตัวอ่าน .env ตีความถูก
            $lines[$i] = $key . '=' . (str_contains($v, ' ') ? '"' . $v . '"' : $v);
            unset($values[$key]);
        }
    }

    // คีย์ที่ไม่มีใน .env.example (เช่นเพิ่มมาทีหลัง) ต่อท้ายไว้
    foreach ($values as $k => $v) {
        $lines[] = $k . '=' . (str_contains($v, ' ') ? '"' . $v . '"' : $v);
    }

    file_put_contents($path, implode("\n", $lines));
}

/** ชื่อโฟลเดอร์ → ชื่อฐานข้อมูลที่ MySQL รับได้ */
function db_name_from(string $folder): string
{
    $name = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $folder));
    $name = trim($name, '_');

    if ($name === '' || ctype_digit($name[0])) {
        $name = 'app_' . $name;
    }

    return substr($name, 0, 60);
}

// ================================================================ --check

/**
 * ตรวจแบบเร็ว ไม่แตะฐานข้อมูล — ถูกเรียกทุกครั้งที่เปิด session
 * จึงห้ามมีอะไรที่ช้าหรือต้องต่อเน็ต
 */
if ($checkOnly) {
    $missing = [];

    if (!is_file($root . '/core/autoload.php')) {
        $missing[] = 'ไม่มี core/autoload.php';
    }

    if (!is_file($root . '/.env')) {
        $missing[] = 'ยังไม่มี .env';
    } elseif ((env_read($root . '/.env')['MIGRATE_TOKEN'] ?? '') === '') {
        $missing[] = 'MIGRATE_TOKEN ยังว่าง';
    }

    if (is_dir($root . '/.git')) {
        [, $hooksPath] = sh('git config core.hooksPath', $root);

        if (trim($hooksPath) !== 'scripts/hooks') {
            $missing[] = 'ยังไม่ได้เปิด pre-commit hook';
        }
    }

    if ($missing === []) {
        exit(0);
    }

    echo implode("\n", $missing) . "\n";
    exit(1);
}

// ================================================================ เริ่มจริง

Report::line();
Report::line(Report::bold('ตั้งค่าเครื่องนี้ให้พร้อมทำงาน'));
Report::line(Report::dim($root));
Report::line();

// ---------------------------------------------------------------- 1. PHP

$need = ['pdo_mysql', 'mbstring', 'json', 'openssl', 'fileinfo'];
$lack = array_values(array_filter($need, static fn ($e) => !extension_loaded($e)));

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    $manual[] = 'PHP ' . PHP_VERSION . ' เก่าเกินไป — ต้อง 8.1 ขึ้นไป';
} elseif ($lack !== []) {
    $manual[] = 'เปิดส่วนขยาย PHP ที่ขาด: ' . implode(', ', $lack) . ' (แก้ที่ php.ini)';
} else {
    $done[] = 'PHP ' . PHP_VERSION . ' · ส่วนขยายครบ';
}

/* ---------------------------------------------------------------- 2. autoloader
 *
 * ‼️ ระบบไม่ต้องใช้ composer แล้ว — โปรเจคไม่มี library สักตัว
 *    composer เคยถูกติดตั้งเพื่อทำงานเดียวคือ map namespace → โฟลเดอร์
 *    ซึ่งตอนนี้ core/autoload.php ทำเองแล้ว
 *
 *    ผลคือ clone มาแล้วเปิดหน้าเว็บได้ทันที ไม่ต้องมี composer บนเครื่อง
 *    ซึ่งเป็นเรื่องที่เคยบล็อกคนที่ยังไม่ได้ติดตั้งมันไว้
 */
if (!is_file($root . '/core/autoload.php')) {
    $manual[] = 'ไม่พบ core/autoload.php — ไฟล์นี้คือตัวโหลดคลาสของระบบ ขาดไม่ได้';
} else {
    $done[] = 'autoloader ของระบบพร้อมใช้ (ไม่ต้องมี composer)';
}

// ---------------------------------------------------------------- 3. .env

$envPath = $root . '/.env';
$created = false;

if (!is_file($envPath)) {
    if (!is_file($root . '/.env.example')) {
        $manual[] = 'ไม่มีทั้ง .env และ .env.example';
    } else {
        copy($root . '/.env.example', $envPath);
        $created = true;
    }
}

if (is_file($envPath)) {
    $env      = env_read($envPath);
    $patch    = [];
    $envNotes = [];

    // ‼️ MIGRATE_TOKEN คือกุญแจของปลายทางที่แก้โครงสร้างฐานข้อมูลได้ ซึ่งเปิดอยู่บนอินเทอร์เน็ต
    //    ต้องไม่ซ้ำข้ามโปรเจค — โปรเจคหนึ่งหลุดแล้วซ้ำ เท่ากับหลุดทุกโปรเจค
    if (($env['MIGRATE_TOKEN'] ?? '') === '') {
        $patch['MIGRATE_TOKEN'] = bin2hex(random_bytes(32));
        $envNotes[] = 'MIGRATE_TOKEN สุ่มใหม่ 64 ตัวอักษร';
    }

    if (($env['APP_NAME'] ?? '') === '' || ($env['APP_NAME'] ?? '') === 'ชื่อระบบ') {
        $patch['APP_NAME'] = basename($root);
        $envNotes[] = 'APP_NAME = ' . basename($root) . ' (เปลี่ยนได้ตอนสัมภาษณ์)';
    }

    // เครื่องตัวเองต้องเห็น error จริง ไม่งั้นดีบักไม่ได้เลย
    // ‼️ ค่านี้อยู่ใน .env ซึ่งไม่ขึ้น git จึงไม่มีทางติดไปถึง server
    if (!$production && ($env['APP_ENV'] ?? 'production') !== 'development') {
        $patch['APP_ENV']   = 'development';
        $patch['APP_URL']   = 'http://localhost:8000';
        $patch['APP_HTTPS'] = 'false';
        $envNotes[] = 'APP_ENV = development (บน server จริงต้องเป็น production)';
    }

    if (($env['DB_NAME'] ?? '') === '') {
        $patch['DB_NAME'] = db_name_from(basename($root));
        $envNotes[] = 'DB_NAME = ' . $patch['DB_NAME'];
    }

    if ($patch !== []) {
        env_write($envPath, $patch);
    }

    $done[] = ($created ? '.env สร้างใหม่จาก .env.example' : '.env มีอยู่แล้ว — เติมเฉพาะช่องที่ยังว่าง')
        . ($envNotes === [] ? '' : "\n" . implode("\n", array_map(static fn ($n) => '· ' . $n, $envNotes)));
}

/* ---------------------------------------------------- 3ก. ยังผูกกับ repo มาตรฐานอยู่ไหม
 *
 * ‼️ `git clone` พา `.git` ทั้งก้อนมาด้วย — origin ยังชี้ไป repo มาตรฐาน
 *    และประวัติของมาตรฐานหลายสิบ commit ปนอยู่ในโปรเจคของลูกค้า
 *
 *    ปล่อยไว้แล้วจะรู้ตัวตอนกด push ครั้งแรก ซึ่งเป็นตอนที่แก้ยากขึ้นแล้ว
 *    ตรวจตรงนี้เพราะ A0 คือจุดที่ทุกโปรเจคผ่านแน่นอนก่อนเริ่มงาน
 *
 * ‼️ ไม่ตัดให้เองเงียบๆ เพราะ repo มาตรฐานกลางกับ clone สดๆ **หน้าตาเหมือนกันทุกอย่าง**
 *    ต่างกันแค่เจตนา ซึ่งเครื่องดูไม่ออก — ถ้าเดาผิดคือทำลายประวัติของ repo กลางเอง
 */
if (is_dir($root . '/.git') && !is_file($root . '/project/STATE.md')) {
    /* ‼️ ดู exit code ทุกคำสั่ง เพราะ sh() รวม stderr เข้ามาด้วย
       repo ที่ไม่มี origin หรือไม่มี upstream จะได้ข้อความ error ที่ "ไม่ว่าง" กลับมา
       เช็คแค่ค่าว่างจะตีความผิดทั้งสองทาง */
    [$originCode, $origin] = sh('git remote get-url origin', $root);
    [, $dirty]             = sh('git status --porcelain', $root);
    [$aheadCode, $ahead]   = sh('git log --oneline @{u}..HEAD', $root);

    $noOwnCommit = $aheadCode !== 0 || trim($ahead) === '';

    if ($originCode === 0 && trim($origin) !== '' && trim($dirty) === '' && $noOwnCommit) {
        $manual[] = "โปรเจคนี้ยังผูกกับ repo มาตรฐานอยู่ — origin ชี้ไป " . trim($origin) . "\n"
            . "ถ้านี่คือโปรเจคใหม่ ให้ตัดขาดก่อนเริ่มงาน (ทำครั้งเดียว ใช้เวลาไม่กี่วินาที)\n"
            . "php scripts/init-project.php --yes";
    }
}

// ---------------------------------------------------------------- 4. git hook

if (!is_dir($root . '/.git')) {
    $done[] = 'ยังไม่ใช่ git repo — ข้ามการเปิด hook';
} else {
    sh('git config core.hooksPath scripts/hooks', $root);

    [, $now] = sh('git config core.hooksPath', $root);

    if (trim($now) !== 'scripts/hooks') {
        $manual[] = 'ตั้ง core.hooksPath ไม่สำเร็จ — สั่งเอง: git config core.hooksPath scripts/hooks';
    } else {
        // ‼️ "สั่งคำสั่งแล้ว" ไม่เท่ากับ "มันทำงาน"
        //    path ผิด · ไม่มี php ใน PATH · สิทธิ์ไฟล์ไม่พอ — พังได้หลายทางและพังเงียบทุกทาง
        //    วิธีเดียวที่รู้แน่คือสร้างไฟล์ที่ผิดกฎจริง แล้วดูว่าถูกบล็อกไหม
        $rel  = 'app/Views/pages/_setup-hook-selftest.php';
        $bait = $root . '/' . $rel;
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';

        @mkdir(dirname($bait), 0o777, true);
        file_put_contents(
            $bait,
            "<?php /* ไฟล์ทดสอบ hook ของ setup.php — ถูกลบทิ้งอัตโนมัติ */ ?>\n"
            . "<div><?= \$unescaped ?></div>\n"
        );

        sh('git add -f -- ' . $rel, $root);

        [$hookCode, $hookOut] = sh('git hook run pre-commit', $root);

        // git เก่ากว่า 2.36 ไม่มีคำสั่ง git hook run — เรียกสคริปต์ตรงๆ แทน
        if ($hookCode === 129 || str_contains($hookOut, 'usage: git hook')) {
            [$hookCode, $hookOut] = sh('sh scripts/hooks/pre-commit', $root);
        }

        // ‼️ ทดสอบซ้ำแบบต่อท่อ — เคยเป็นช่องที่ทำให้ commit ที่ fail หลุดเข้าไปได้จริง
        //    `git commit | head` ทำให้ hook ตายด้วย SIGPIPE ก่อนถึงบรรทัด exit
        //    แล้ว git ไม่ได้รับสัญญาณให้หยุด · `| head` คือสิ่งที่ AI พิมพ์เป็นนิสัย
        //    ถ้ากรณีนี้ไม่ถูกบล็อก แปลว่ากฎทุกข้อในมาตรฐานคือกฎที่ข้ามได้
        //
        // ‼️ exit code ของ pipeline คือของคำสั่งตัวท้าย (head) ไม่ใช่ของ hook
        //    จึงต้องให้ hook เขียนค่าของตัวเองลงไฟล์ ไม่งั้นวัดผิดว่าผ่านตลอด
        $codeFile = $root . '/.setup-hookcode';
        @unlink($codeFile);

        sh(
            // ‼️ single quote ของ PHP ไม่ตีความ backslash — ห้ามเขียน \$? เพราะจะได้ตัวอักษร \$? จริงๆ
            'sh -c "{ sh scripts/hooks/pre-commit; echo $? > .setup-hookcode; } '
            . '2>&1 | head -3 > /dev/null"',
            $root
        );

        $pipeCode = is_file($codeFile) ? (int) trim((string) file_get_contents($codeFile)) : -1;
        @unlink($codeFile);

        sh('git rm -q --cached -f -- ' . $rel, $root);
        @unlink($bait);

        if ($hookCode === 0) {
            $manual[] = "pre-commit hook ตั้งแล้วแต่ไม่บล็อกไฟล์ที่ผิดกฎ — ตรวจว่ามี php ใน PATH ไหม\n"
                . $hookOut;
        } elseif ($pipeCode === 0 || $pipeCode > 128) {
            // 0 = ปล่อยผ่าน · >128 = ตายด้วยสัญญาณ · -1 = ไม่มี sh ให้ทดสอบ (ข้ามไป ไม่ถือว่า fail)
            $manual[] = "‼️ pre-commit hook บล็อกได้ตอนเรียกปกติ แต่ **ข้ามได้เมื่อต่อท่อ** "
                . "(`git commit | head`)\n"
                . "hook คืนค่า {$pipeCode} ซึ่งแปลว่าตายด้วยสัญญาณก่อนถึงบรรทัด exit\n"
                . 'อย่าเพิ่งใช้งาน — กฎทุกข้อในมาตรฐานจะข้ามได้หมด';
        } elseif (str_contains($hookOut, '_setup-hook-selftest')) {
            $done[] = 'pre-commit hook เปิดแล้ว — ทดสอบด้วยไฟล์ผิดกฎจริง บล็อกได้ทั้งแบบปกติและแบบต่อท่อ';
        } else {
            $done[] = 'pre-commit hook เปิดแล้ว — บล็อกได้จริง (ติดกฎข้ออื่นก่อนถึงไฟล์ทดสอบ)';
        }
    }
}

// ---------------------------------------------------------------- 5. ฐานข้อมูล

$env  = env_read($envPath);
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$name = $env['DB_NAME'] ?? '';
$user = $env['DB_USER'] ?? '';
$pass = $env['DB_PASS'] ?? '';

$connect = static function (
    string $host,
    string $port,
    string $user,
    string $pass,
    ?string $db = null
): ?PDO {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4"
        . ($db !== null ? ";dbname={$db}" : '');

    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT          => 3,
        ]);
    } catch (PDOException) {
        return null;
    }
};

$pdo = null;

if ($user !== '') {
    $pdo = $connect($host, $port, $user, $pass);

    if ($pdo === null) {
        $manual[] = "ต่อฐานข้อมูลด้วยค่าใน .env ไม่ได้ (DB_USER={$user})\n"
            . 'ตรวจว่า MySQL เปิดอยู่ และรหัสผ่านถูกต้อง';
    }
} elseif (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    // ‼️ ลองค่าตั้งต้นได้เฉพาะกับ MySQL บนเครื่องตัวเอง และเฉพาะตอนที่ช่องยังว่าง
    //    ช่องว่าง = ผู้ใช้ยังไม่ได้ตั้งใจกรอกอะไร · host อื่นห้ามลองเด็ดขาด
    foreach ([['root', ''], ['root', 'root']] as [$u, $p]) {
        $try = $connect($host, $port, $u, $p);

        if ($try !== null) {
            $pdo  = $try;
            $user = $u;
            $pass = $p;
            env_write($envPath, ['DB_USER' => $u, 'DB_PASS' => $p]);
            $done[] = "ต่อ MySQL ที่ {$host} ด้วยผู้ใช้ {$u} ได้ — บันทึกลง .env แล้ว";
            break;
        }
    }

    if ($pdo === null) {
        $manual[] = "ต่อ MySQL ที่ {$host}:{$port} ไม่ได้\n"
            . 'ถ้ายังไม่ได้เปิด MySQL ให้เปิดก่อน · ถ้ารหัสไม่ใช่ค่าตั้งต้น '
            . 'ให้บอก user/pass มา แล้วรันสคริปต์นี้ใหม่';
    }
} else {
    $manual[] = "DB_HOST เป็น {$host} ซึ่งไม่ใช่เครื่องตัวเอง — กรอก DB_USER / DB_PASS ใน .env เอง";
}

if ($pdo !== null && $name !== '') {
    $stmt = $pdo->prepare(
        'SELECT DEFAULT_CHARACTER_SET_NAME cs, DEFAULT_COLLATION_NAME co
           FROM information_schema.SCHEMATA
          WHERE SCHEMA_NAME = ?'
    );
    $stmt->execute([$name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing === false) {
        // ‼️ ต้อง utf8mb4 เท่านั้น — utf8 ของ MySQL เก็บได้ 3 ไบต์ emoji หายและไทยบางตัวเพี้ยน
        $pdo->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $done[] = "สร้างฐานข้อมูล {$name} ด้วย utf8mb4_unicode_ci";
    } elseif ($existing['cs'] !== 'utf8mb4') {
        // ‼️ ปล่อยไว้แล้วภาษาไทยจะเพี้ยนตอนบันทึกโดยไม่มี error แจ้ง และกู้คืนยาก
        $manual[] = "ฐานข้อมูล {$name} มีอยู่แล้ว แต่ charset เป็น {$existing['cs']} ไม่ใช่ utf8mb4\n"
            . "ภาษาไทยจะเพี้ยนแบบไม่มี error แจ้ง ต้องแก้ก่อนใช้งาน:\n"
            . "ALTER DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    } else {
        $done[] = "ฐานข้อมูล {$name} พร้อมแล้ว ({$existing['co']})";
    }

    if ($existing === false || $existing['cs'] === 'utf8mb4') {
        Report::line(Report::dim('→ กำลังรัน migration...'));

        [$code, $out] = sh('php scripts/migrate.php', $root);

        if ($code === 0) {
            $first  = trim((string) strtok($out, "\n"));
            $done[] = 'migration — ' . ($first === '' ? 'ไม่มีที่ค้าง' : $first);
        } else {
            $manual[] = "รัน migration ไม่ผ่าน:\n" . $out;
        }
    }
}

// ---------------------------------------------------------------- 6. plugin

/**
 * ‼️ ข้อเดียวที่สคริปต์ทำแทนไม่ได้จริงๆ
 *    /plugin install เปิดหน้าต่างเลือกแบบโต้ตอบ และหลังติดตั้งต้องเปิด session ใหม่
 *    ทำได้แค่บอกให้ชัดว่าขาดตัวไหน และต้องพิมพ์อะไร
 */
$home     = getenv('HOME') ?: getenv('USERPROFILE') ?: '';
$settings = $home !== '' ? $home . '/.claude/settings.json' : '';
$enabled  = [];

if ($settings !== '' && is_file($settings)) {
    $json    = json_decode((string) file_get_contents($settings), true);
    $enabled = array_keys(array_filter((array) (($json['enabledPlugins'] ?? []))));
}

/* ‼️ รายการนี้ต้องตรงกับตารางกลุ่มบังคับใน standard/skills.md เสมอ
   `docs/mandatory-plugins-wired` เทียบให้ — ประกาศว่าบังคับในเอกสารแต่ที่นี่ไม่รู้
   แปลว่า A0 จะบอกว่าครบแล้วเดินต่อ ทั้งที่ช่วง B จะไปตันเพราะไม่มี plugin ตัวนั้น */
$required = [
    'superpowers'       => 'superpowers@claude-plugins-official',
    'security-guidance' => 'security-guidance@claude-plugins-official',
    'frontend-design'   => 'frontend-design@claude-plugins-official',
    'impeccable'        => 'impeccable@impeccable',
];

// marketplace ที่ไม่ใช่ของทางการ ต้อง add ก่อนถึงจะ install ได้
$marketplace = ['impeccable' => 'pbakaus/impeccable'];

$lackPlugins = [];

foreach ($required as $short => $full) {
    $found = false;

    foreach ($enabled as $key) {
        if (str_starts_with((string) $key, $short . '@')) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        if (isset($marketplace[$short])) {
            $lackPlugins[] = 'marketplace add ' . $marketplace[$short];
        }

        $lackPlugins[] = 'install ' . $full;
    }
}

if ($settings === '' || !is_file($settings)) {
    $manual[] = 'อ่าน ~/.claude/settings.json ไม่ได้ — ตรวจ plugin เองตาม standard/skills.md';
} elseif ($lackPlugins !== []) {
    $missing = count(array_filter($lackPlugins, static fn ($p) => str_starts_with($p, 'install ')));

    $manual[] = 'ติดตั้ง plugin ที่บังคับอีก ' . $missing . ' ตัว '
        . "(ต้องเปิด session ใหม่หลังติดตั้ง)\n"
        . implode("\n", array_map(static fn ($p) => '/plugin ' . $p, $lackPlugins));
} else {
    $done[] = 'plugin กลุ่มบังคับครบทั้ง ' . count($required) . ' ตัว';
}

// ---------------------------------------------------------------- สรุป

Report::line();

foreach ($done as $item) {
    $lines = explode("\n", $item);
    Report::line(Report::green('✓') . ' ' . array_shift($lines));

    foreach ($lines as $line) {
        Report::line('    ' . Report::dim($line));
    }
}

if ($manual === []) {
    Report::line();
    Report::line(Report::bold('พร้อมทำงานแล้ว') . ' — พิมพ์ ' . Report::bold('/wf') . ' ได้เลย');
    Report::line();

    exit(0);
}

Report::line();
Report::line(Report::bold(Report::yellow('! ต้องทำเอง ' . count($manual) . ' อย่าง')));

foreach ($manual as $item) {
    $lines = explode("\n", $item);
    Report::line();
    Report::line('  · ' . array_shift($lines));

    foreach ($lines as $line) {
        Report::line('    ' . Report::dim(trim($line)));
    }
}

Report::line();
Report::line(Report::dim('แก้แล้วรัน php scripts/setup.php ซ้ำได้ — สคริปต์นี้รันซ้ำได้ไม่พัง'));
Report::line();

exit(1);
