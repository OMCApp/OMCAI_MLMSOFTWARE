<?php
/**
 * ชุดทดสอบเว็บสาธารณะ + SEO (Phase 2) — รันบนเครื่องตัวเองเท่านั้น
 *
 *   php tests/run.php
 *
 * ทำอะไร
 *   · เปิด PHP built-in server (server.php) ชี้ฐานข้อมูลเดียวกับที่ config/config.php อ่าน (.env หรือ env vars)
 *   · ใส่ข้อมูลทดสอบชั่วคราว (บทความ หน้า ผู้ใช้แอดมิน) ขึ้นต้นด้วย zz-test แล้วลบทิ้งตอนจบเสมอ
 *   · ยิง HTTP จริง: URL map ทุกแถว · redirect map ทุกแถว · trailing slash · บทความ path ไทย · วันที่ผิด
 *     · 404 log · sitemap/robots/feed · canonical · noindex ตาม environment · /admin
 *   · ส่วนที่ต้องเป็น production (robots.txt, ไม่มี noindex) ทดสอบในโปรเซสนี้ด้วย config ที่ตั้ง env=production
 *
 * ‼️ ไม่พิมพ์ค่าใน .env — แสดงแค่ชื่อ environment
 * ‼️ ห้ามรันกับฐานข้อมูลของ production — สคริปต์ปฏิเสธเองถ้า APP_ENV=production
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

require $root . '/core/autoload.php';
require $root . '/core/helpers.php';

/** @var array<string, mixed> $config */
$config = require $root . '/config/config.php';

if ($config['env'] === 'production') {
    fwrite(STDERR, "APP_ENV=production — ห้ามรันชุดทดสอบนี้กับ production\n");
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['name']),
    (string) $config['db']['user'],
    (string) $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
);

echo "environment: {$config['env']}\n";

// ------------------------------------------------------------------ เครื่องมือ

$results = ['pass' => 0, 'fail' => 0, 'failures' => []];

$check = static function (bool $ok, string $label, string $detail = '') use (&$results): void {
    if ($ok) {
        $results['pass']++;
        return;
    }
    $results['fail']++;
    $results['failures'][] = $label . ($detail !== '' ? "\n      " . $detail : '');
};

$section = static function (string $title): void {
    echo "\n== {$title}\n";
};

$enc = static fn (string $path): string => implode('/', array_map('rawurlencode', explode('/', $path)));

// ------------------------------------------------------------------ server

$port   = random_int(20000, 40000);
$base   = "http://127.0.0.1:{$port}";
$server = proc_open(
    [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', "127.0.0.1:{$port}", 'server.php'],
    [0 => ['file', 'php://stdin', 'r'], 1 => ['file', $root . '/storage/logs/test-server.log', 'a'], 2 => ['file', $root . '/storage/logs/test-server.log', 'a']],
    $pipes,
    $root
);

$cookieJars = [];

/**
 * @return array{status:int, headers:array<string,array<int,string>>, body:string}
 */
$http = static function (string $method, string $url, array $opts = []) use ($base, $config, &$cookieJars, $root): array {
    $ch = curl_init($base . $url);
    $headers = [];

    $curlOpts = [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))][] = trim($parts[1]);
            }
            return strlen($line);
        },
        CURLOPT_HTTPHEADER => $opts['headers'] ?? [],
    ];

    if ($method === 'HEAD') {
        $curlOpts[CURLOPT_NOBODY] = true;
    }
    if (isset($opts['post'])) {
        $curlOpts[CURLOPT_POSTFIELDS] = http_build_query($opts['post']);
    }
    // อัปโหลดไฟล์ (multipart) — ค่าเป็น CURLFile ได้ (Phase 3 คลังสื่อ)
    if (isset($opts['multipart'])) {
        $curlOpts[CURLOPT_POSTFIELDS] = $opts['multipart'];
    }
    if (isset($opts['jar'])) {
        $jar = $cookieJars[$opts['jar']] ??= tempnam(sys_get_temp_dir(), 'omcjar');
        $curlOpts[CURLOPT_COOKIEJAR]  = $jar;
        $curlOpts[CURLOPT_COOKIEFILE] = $jar;
    }
    // staging ที่ตั้งรหัสไว้ — ส่ง Basic Auth โดยไม่พิมพ์ค่า
    if ((string) $config['staging']['pass'] !== '') {
        $curlOpts[CURLOPT_USERPWD] = $config['staging']['user'] . ':' . $config['staging']['pass'];
    }

    curl_setopt_array($ch, $curlOpts);
    $body   = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return ['status' => $status, 'headers' => $headers, 'body' => $body];
};

$header = static fn (array $res, string $name): string => (string) ($res['headers'][strtolower($name)][0] ?? '');

// รอจน server พร้อม
for ($i = 0; $i < 50; $i++) {
    usleep(100000);
    if ($http('GET', '/_health')['status'] === 200) {
        break;
    }
}

// ------------------------------------------------------------------ fixtures

$stamp      = date('YmdHis');
$password   = bin2hex(random_bytes(12));
$username   = 'zz-test-' . $stamp;
$fixtureIds = ['posts' => [], 'pages' => [], 'users' => []];
$thaiSlug   = 'zz-test-บทความทดสอบ-' . $stamp;

$cleanup = static function () use ($pdo, &$fixtureIds, $stamp): void {
    foreach (['posts', 'pages', 'users'] as $table) {
        foreach ($fixtureIds[$table] as $id) {
            $pdo->prepare("DELETE FROM {$table} WHERE id = :id")->execute(['id' => $id]);
        }
    }
    $pdo->prepare('DELETE FROM not_found_log WHERE path LIKE :p')->execute(['p' => '/zz-test-%']);
    $pdo->prepare('DELETE FROM login_attempts WHERE username LIKE :u')->execute(['u' => 'zz-test-%']);
};

register_shutdown_function(static function () use ($cleanup, $server, &$cookieJars): void {
    $cleanup();
    foreach ($cookieJars as $jar) {
        @unlink($jar);
    }
    if (is_resource($server)) {
        proc_terminate($server);
    }
});

$category = $pdo->query("SELECT id, slug FROM categories WHERE slug = CONVERT(X'E0B89BE0B8A3E0B8B0E0B881E0B8B1E0B899' USING utf8mb4) COLLATE utf8mb4_bin AND deleted_at IS NULL")->fetch();

$insertPost = $pdo->prepare(
    'INSERT INTO posts (slug, title, excerpt, content, category_id, author_name, status, published_at, robots, created_at, updated_at)
     VALUES (:slug, :title, :excerpt, :content, :category_id, :author, :status, :published_at, :robots, NOW(), NOW())'
);

$addPost = static function (array $row) use ($insertPost, $pdo, &$fixtureIds): int {
    $insertPost->execute($row + ['excerpt' => null, 'content' => null, 'category_id' => null, 'author' => null, 'robots' => 'index']);
    $id = (int) $pdo->lastInsertId();
    $fixtureIds['posts'][] = $id;
    return $id;
};

$addPost([
    'slug' => $thaiSlug, 'title' => 'บทความทดสอบระบบ <script>x</script>', 'excerpt' => 'สรุปบทความทดสอบ',
    'content' => '<h1>หัวข้อในเนื้อหา</h1><p onclick="alert(1)">ย่อหน้า <a href="javascript:alert(1)">ลิงก์</a></p><script>alert(1)</script>',
    'category_id' => $category['id'] ?? null, 'author' => 'ผู้ทดสอบ', 'status' => 'published', 'published_at' => '2025-05-20 10:00:00',
]);
$addPost(['slug' => 'zz-test-draft-' . $stamp, 'title' => 'Draft post', 'status' => 'draft', 'published_at' => '2025-06-01 10:00:00']);
$addPost(['slug' => 'zz-test-future-' . $stamp, 'title' => 'Future post', 'status' => 'published', 'published_at' => date('Y-m-d H:i:s', time() + 86400 * 30)]);
$addPost(['slug' => 'zz-test-noindex-' . $stamp, 'title' => 'Noindex post', 'status' => 'published', 'published_at' => '2025-04-01 10:00:00', 'robots' => 'noindex']);

$insertPage = $pdo->prepare(
    'INSERT INTO pages (path, title, template, blocks, meta_description, status, robots, created_at, updated_at)
     VALUES (:path, :title, :template, :blocks, :description, :status, :robots, NOW(), NOW())'
);
$faqBlocks = json_encode([['type' => 'faq', 'data' => ['items' => [['question' => 'ระบบรองรับแผนอะไรบ้าง?', 'answer' => '<p>คำตอบทดสอบ</p>']]]]], JSON_UNESCAPED_UNICODE);
$insertPage->execute(['path' => '/zz-test-product-' . $stamp . '/', 'title' => 'Test product', 'template' => 'product', 'blocks' => $faqBlocks,
    'description' => 'คำอธิบายหน้าทดสอบ', 'status' => 'published', 'robots' => 'index']);
$fixtureIds['pages'][] = (int) $pdo->lastInsertId();
$productPath = '/zz-test-product-' . $stamp . '/';

$pdo->prepare(
    "INSERT INTO users (username, display_name, password_hash, role, locale, theme, is_active, created_at, updated_at)
     VALUES (:u, 'Test admin', :h, 'admin', 'th', 'light', 1, NOW(), NOW())"
)->execute(['u' => $username, 'h' => password_hash($password, PASSWORD_DEFAULT)]);
$fixtureIds['users'][] = (int) $pdo->lastInsertId();

// ------------------------------------------------------------------ login (สำหรับ preview)

$section('admin moved under /admin');
$form = $http('GET', '/admin/login', ['jar' => 'admin']);
$check($form['status'] === 200, 'GET /admin/login → 200', 'got ' . $form['status']);
preg_match('/name="_token" value="([^"]+)"/', $form['body'], $m);
$login = $http('POST', '/admin/login', ['jar' => 'admin', 'post' => ['_token' => $m[1] ?? '', 'username' => $username, 'password' => $password]]);
$check($login['status'] === 302 && str_ends_with($header($login, 'location'), '/admin'), 'login redirects to /admin', $login['status'] . ' ' . $header($login, 'location'));
$check($http('GET', '/admin', ['jar' => 'admin'])['status'] === 200, 'GET /admin (logged in) → 200');
$anonAdmin = $http('GET', '/admin');
$check($anonAdmin['status'] === 302 && str_ends_with($header($anonAdmin, 'location'), '/admin/login'), 'anonymous /admin → /admin/login');
$check($http('GET', '/admin/manual', ['jar' => 'admin'])['status'] === 200, '/admin/manual works');
$check($http('GET', '/admin/audit-logs', ['jar' => 'admin'])['status'] === 200, '/admin/audit-logs works');
$check($http('GET', '/admin/support-requests', ['jar' => 'admin'])['status'] === 200, '/admin/support-requests works');
$check($http('GET', '/admin/nope')['status'] === 404, '/admin/nope → 404 (no trailing-slash redirect)');
$oldLogin = $http('GET', '/login/');
$check($oldLogin['status'] === 404, 'old /login/ is no longer a route (404)', (string) $oldLogin['status']);

// ------------------------------------------------------------------ URL map

$section('URL map (PLAN.md) — draft shells: 404 public, 200 with ?preview=1 for admins');
$urlMap = [
    '/', '/mlm-software-omc/', '/services/', '/ocpb-mlm/', '/ocpb-mlm2/', '/contact/', '/portfolio/',
    '/นโยบายความเป็นส่วนตัวข/', '/นโยบายการใช้คุกกี้-cookies-policy/', '/features/', '/compensation-plans/',
    '/compensation-plans/binary/', '/compensation-plans/unilevel/', '/compensation-plans/matrix/', '/compensation-plans/board/',
    '/compensation-plans/stair-step/', '/compensation-plans/hybrid/',
    '/solutions/supplements/', '/solutions/cosmetics/', '/solutions/health-wellness/', '/solutions/distributor/',
    '/solutions/franchise/', '/solutions/international/', '/solutions/custom/',
    '/pricing/', '/demo/', '/about/', '/faq/', '/support/',
    '/mlm-program/', '/mlm-system/', '/direct-sales-software/', '/direct-sales-system/', '/mlm-development/',
    '/mlm-programming/', '/mlm-software-thailand/', '/mlm-migration/', '/commission-system/', '/member-system/',
];
$liveStatus = $pdo->prepare('SELECT status FROM pages WHERE path = :p AND deleted_at IS NULL');

foreach ($urlMap as $path) {
    $liveStatus->execute(['p' => $path]);
    $status = $liveStatus->fetchColumn();
    $check($status !== false, "page row exists: {$path}");

    $public  = $http('GET', $enc($path));
    $preview = $http('GET', $enc($path) . '?preview=1', ['jar' => 'admin']);
    $anonPreview = $http('GET', $enc($path) . '?preview=1');

    if ($status === 'draft' && $path === '/') {
        // non-production: draft home falls back to the design-system preview (SiteController) — still noindex
        $check($public['status'] === 200 && str_contains($header($public, 'x-robots-tag'), 'noindex'), 'draft / → preview home (non-production, noindex)', (string) $public['status']);
    } elseif ($status === 'draft') {
        $check($public['status'] === 404, "draft {$path} → 404 for public", (string) $public['status']);
        $check($anonPreview['status'] === 404, "draft {$path}?preview=1 → 404 when not logged in", (string) $anonPreview['status']);
        $check($preview['status'] === 200, "draft {$path}?preview=1 → 200 for admin", (string) $preview['status']);
        $check(str_contains($preview['body'], '<meta name="robots" content="noindex, nofollow">'), "preview {$path} is noindex,nofollow");
        $check(str_contains($header($preview, 'cache-control'), 'no-store'), "preview {$path} is no-store");
    } else {
        $check($public['status'] === 200, "published {$path} → 200", (string) $public['status']);
    }
}

foreach (['/blog/', '/category/ข่าวขายตรง/', '/category/ประกัน/'] as $path) {
    $res = $http('GET', $enc($path));
    $check($res['status'] === 200, "system listing {$path} → 200", (string) $res['status']);
}
$check($http('GET', '/blog/page/1/')['status'] === 301, '/blog/page/1/ → 301 to /blog/');
$check($http('GET', '/blog/page/99/')['status'] === 404, '/blog/page/99/ (past the end) → 404');
$check($http('GET', $enc('/category/ไม่มีหมวดนี้/'))['status'] === 404, 'unknown category → 404');

// ------------------------------------------------------------------ redirect map

$section('redirect map');
$redirectMap = [
    ['/sample-page/', 301, '/'], ['/sample-page-2/', 301, '/'], ['/home-startup/', 301, '/'], ['/about-us-3/', 301, '/about/'],
    ['/newsletter/', 301, '/blog/'], ['/newsletter-2/', 301, '/blog/'],
    ['/portfolio/allen-walker-optimization/', 301, '/portfolio/'], ['/portfolio/dell-audience-monitoring/', 301, '/portfolio/'],
    ['/portfolio/hp-cloud-service-improvement/', 301, '/portfolio/'], ['/portfolio/travel-tour-marketing-campaign/', 301, '/portfolio/'],
    ['/portfolio/real-estate-web-rank-improve/', 301, '/portfolio/'], ['/portfolio/kleanity-social-engagement-2/', 301, '/portfolio/'],
    ['/portfolio/website-seo-optimization-2/', 301, '/portfolio/'], ['/portfolio/conference-marketing-2/', 301, '/portfolio/'],
    ['/portfolio/architek-rank-improving/', 301, '/portfolio/'],
    ['/portfolio_category/marketing/', 301, '/portfolio/'], ['/portfolio_category/rank-improving/', 301, '/portfolio/'],
    ['/portfolio_tag/cloud/', 301, '/portfolio/'], ['/portfolio_tag/social/', 301, '/portfolio/'],
    ['/author/breeze/', 301, '/about/'], ['/author/tee/', 301, '/about/'],
    ['/mlmsoftware/', 301, '/mlm-software-omc/'], ['/contact-us/', 301, '/contact/'], ['/contact-us-2/', 301, '/contact/'],
    ['/homepage-business-solutions/', 301, '/'], ['/pages/', 301, '/'], ['/comments/feed/', 301, '/feed/'],
    ['/index.php/', 301, '/'], ['/solutions/', 301, '/services/'],
    ['/mlm-software/', 301, '/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/'],
    ['/wp-login.php', 410, null], ['/xmlrpc.php', 410, null], ['/wp-admin/', 410, null], ['/wp-admin/edit.php', 410, null],
];

$hits = $pdo->prepare('SELECT hits FROM redirects WHERE from_path = :p AND deleted_at IS NULL');
$hits->execute(['p' => '/mlm-software/']);
$hitsBefore = (int) $hits->fetchColumn();

foreach ($redirectMap as [$from, $code, $to]) {
    $res = $http('GET', $enc($from));
    $check($res['status'] === $code, "{$from} → {$code}", 'got ' . $res['status']);
    if ($to !== null) {
        $check($header($res, 'location') === $enc($to), "{$from} → Location {$to}", 'got ' . $header($res, 'location'));
    } else {
        $check(str_contains($res['body'], 'noindex'), "{$from} 410 page is noindex");
    }
}

$hits->execute(['p' => '/mlm-software/']);
$check((int) $hits->fetchColumn() === $hitsBefore + 1, 'redirect hit counter incremented');

$withQuery = $http('GET', '/contact-us/?utm_source=x');
$check($header($withQuery, 'location') === '/contact/?utm_source=x', 'redirect keeps query string', $header($withQuery, 'location'));

// ------------------------------------------------------------------ trailing slash

$section('trailing slash');
$res = $http('GET', '/contact');
$check($res['status'] === 301 && $header($res, 'location') === '/contact/', '/contact → 301 /contact/', $res['status'] . ' ' . $header($res, 'location'));
$res = $http('GET', '/contact?a=1&b=2');
$check($header($res, 'location') === '/contact/?a=1&b=2', 'query string kept on trailing-slash 301', $header($res, 'location'));
$res = $http('GET', $enc('/category/ประกัน'));
$check($res['status'] === 301 && $header($res, 'location') === $enc('/category/ประกัน/'), 'Thai path without slash → 301 encoded with slash', $header($res, 'location'));
$check($http('GET', '/zz-test-file-' . $stamp . '.jpg')['status'] === 404, 'missing file-like path is not slash-redirected (404)');
$res = $http('GET', '//evil.example');
$check(!str_starts_with($header($res, 'location'), '//'), 'no protocol-relative open redirect from //host', $header($res, 'location'));
$check($http('GET', '/robots.txt')['status'] === 200 && $http('GET', '/sitemap.xml')['status'] === 200, 'system files not slash-redirected');

// ------------------------------------------------------------------ posts

$section('posts');
$postPath = '/2025/05/20/' . $thaiSlug . '/';
$res = $http('GET', $enc($postPath));
$check($res['status'] === 200, 'encoded Thai post path → 200', (string) $res['status']);
$res2 = $http('GET', strtoupper('/2025/05/20/') . strtoupper(implode('/', array_map('rawurlencode', [$thaiSlug]))) . '/');
$check($res2['status'] === 200 || $res2['status'] === 404, 'uppercase-encoded request handled without error');
$lower = $http('GET', '/2025/05/20/' . strtolower(rawurlencode($thaiSlug)) . '/');
$check($lower['status'] === 200, 'lowercase percent-encoding resolves too', (string) $lower['status']);

$wrong = $http('GET', $enc('/2024/01/01/' . $thaiSlug . '/'));
$check($wrong['status'] === 301 && $header($wrong, 'location') === $enc($postPath), 'wrong date → 301 to correct date', $wrong['status'] . ' ' . $header($wrong, 'location'));

$canonical = 'https://mlmsoftware.omc.co.th' . $enc($postPath);
$check(str_contains($res['body'], '<link rel="canonical" href="' . $canonical . '">'), 'post canonical = production host + encoded path with trailing slash');
$check(str_contains($res['body'], '"@type":"BlogPosting"'), 'post has BlogPosting JSON-LD');
$check(str_contains($res['body'], '"@type":"BreadcrumbList"'), 'post has BreadcrumbList JSON-LD');
$check(str_contains($res['body'], '<meta property="og:type" content="article">'), 'post og:type article');
$check(!str_contains($res['body'], '<script>alert') && !str_contains($res['body'], 'onclick') && !str_contains($res['body'], 'javascript:'), 'post HTML sanitized');
$check(substr_count($res['body'], '<h1') === 1, 'post has exactly one <h1>', (string) substr_count($res['body'], '<h1'));
$jsonLt = chr(92) . 'u003Cscript' . chr(92) . 'u003Ex'; // JSON_HEX_TAG form of "<script>x"
$check(!str_contains($res['body'], '<script>x</script>') && str_contains($res['body'], $jsonLt), 'title escaped in HTML and JSON-LD');

$check($http('GET', '/2025/06/01/zz-test-draft-' . $stamp . '/')['status'] === 404, 'draft post → 404 public');
$check($http('GET', '/2025/06/01/zz-test-draft-' . $stamp . '/?preview=1', ['jar' => 'admin'])['status'] === 200, 'draft post previewable by admin');
$future = date('Y/m/d', time() + 86400 * 30);
$check($http('GET', "/{$future}/zz-test-future-{$stamp}/")['status'] === 404, 'scheduled (future) post → 404');
$head = $http('HEAD', $enc($postPath));
$check($head['status'] === 200, 'HEAD request on post → 200', (string) $head['status']);

// ------------------------------------------------------------------ pages / schema

$section('page SEO + schema');
$res = $http('GET', $productPath);
$check($res['status'] === 200, 'published fixture page → 200');
$check(str_contains($res['body'], '<link rel="canonical" href="https://mlmsoftware.omc.co.th' . $productPath . '">'), 'page canonical on production host with trailing slash');
$check(str_contains($res['body'], '<title>Test product | OMC MLM Software</title>'), 'title rule: title + " | OMC MLM Software"');
$check(str_contains($res['body'], '<meta name="description" content="คำอธิบายหน้าทดสอบ">'), 'meta description');
$check(str_contains($res['body'], '<meta name="robots" content="index, follow'), 'indexable page meta robots = index');
foreach (['Organization', 'WebSite', 'FAQPage', 'SoftwareApplication', 'BreadcrumbList'] as $type) {
    $check(str_contains($res['body'], '"@type":"' . $type . '"'), "page JSON-LD has {$type}");
}
$check(!str_contains($res['body'], 'aggregateRating') && !str_contains($res['body'], '"offers"'), 'no rating/offers invented');
preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $res['body'], $ld);
$check(is_array(json_decode($ld[1] ?? '', true)), 'JSON-LD is valid JSON');

// เดินทุกหน้าของ /blog/ — หลังนำเข้าบทความเดิม (Phase 5) บทความทดสอบไม่ได้อยู่หน้าแรกแล้ว
$blogBodies = '';
for ($blogPage = 1; $blogPage <= 20; $blogPage++) {
    $blog = $http('GET', $blogPage === 1 ? '/blog/' : "/blog/page/{$blogPage}/");
    if ($blog['status'] !== 200) {
        break;
    }
    $blogBodies .= $blog['body'];
}
$check(str_contains($blogBodies, $enc($postPath)), '/blog/ (any page) lists the published post');
$check(!str_contains($blogBodies, 'zz-test-draft-'), '/blog/ hides drafts');

// ------------------------------------------------------------------ 404

$section('404');
$missing = '/zz-test-missing-' . $stamp . '/';
$res = $http('GET', $missing, ['headers' => ['Referer: https://example.com/from']]);
$check($res['status'] === 404, 'unknown path → 404');
$check(str_contains($res['body'], '<meta name="robots" content="noindex, follow">'), '404 is noindex');
$check(!str_contains($res['body'], 'rel="canonical"'), '404 has no canonical');
$http('GET', $missing);
$row = $pdo->prepare('SELECT hits, referrer FROM not_found_log WHERE path = :p');
$row->execute(['p' => $missing]);
$log = $row->fetch();
$check($log !== false && (int) $log['hits'] === 2, '404 logged with hit count', json_encode($log));
$check(($log['referrer'] ?? '') === 'https://example.com/from', '404 log keeps referrer');
$thaiMissing = '/zz-test-ไม่มี-' . $stamp . '/';
$http('GET', $enc($thaiMissing));
$row->execute(['p' => $thaiMissing]);
$check($row->fetch() !== false, '404 log stores Thai path decoded');

// ------------------------------------------------------------------ sitemap / robots / feed

$section('sitemap, robots, feed');
$index = $http('GET', '/sitemap.xml');
$xml   = @simplexml_load_string($index['body']);
$check($xml !== false && $xml->getName() === 'sitemapindex', 'sitemap.xml is a sitemapindex');
$check(str_contains($header($index, 'content-type'), 'xml'), 'sitemap content-type xml');
foreach (['pages', 'posts', 'categories'] as $part) {
    $check(str_contains($index['body'], 'https://mlmsoftware.omc.co.th/sitemap-' . $part . '.xml'), "index lists sitemap-{$part}.xml");
    $sub = $http('GET', '/sitemap-' . $part . '.xml');
    $subXml = @simplexml_load_string($sub['body']);
    $check($sub['status'] === 200 && $subXml !== false && $subXml->getName() === 'urlset', "sitemap-{$part}.xml is a urlset");
}
$posts = $http('GET', '/sitemap-posts.xml')['body'];
$check(str_contains($posts, '<loc>' . $canonical . '</loc>'), 'posts sitemap has published post (encoded)');
$check(str_contains($posts, '<lastmod>'), 'posts sitemap has lastmod');
$check(!str_contains($posts, 'zz-test-draft-') && !str_contains($posts, 'zz-test-future-') && !str_contains($posts, 'zz-test-noindex-'), 'posts sitemap excludes draft/future/noindex');
$pages = $http('GET', '/sitemap-pages.xml')['body'];
$check(str_contains($pages, $productPath), 'pages sitemap has published page');
$check(!str_contains($pages, '/sample-page/') && !str_contains($pages, '/thank-you/'), 'pages sitemap excludes noindex and redirected URLs');
$check(str_contains($pages, '<loc>https://mlmsoftware.omc.co.th/blog/</loc>'), 'pages sitemap includes /blog/ when posts exist');
$cats = $http('GET', '/sitemap-categories.xml')['body'];
$check(str_contains($cats, $enc('/category/ประกัน/')), 'categories sitemap has category with posts');
$check($http('GET', '/sitemap-nope.xml')['status'] === 404, 'unknown sub-sitemap → 404');

$robots = $http('GET', '/robots.txt');
$check(trim($robots['body']) === "User-agent: *\nDisallow: /", 'non-production robots.txt disallows all', $robots['body']);

$feed = $http('GET', '/feed/');
$rss  = @simplexml_load_string($feed['body']);
$check($rss !== false && (string) $rss['version'] === '2.0', '/feed/ is RSS 2.0');
$check(str_contains($header($feed, 'content-type'), 'rss+xml'), 'feed content-type');
// feed มีแค่ล่าสุด 20 เรื่อง — เทียบกับบทความที่เผยแพร่ล่าสุดจริงในฐานข้อมูล (อาจเป็นบทความทดสอบหรือบทความที่นำเข้า)
$newest = $pdo->query("SELECT slug, published_at FROM posts WHERE status = 'published' AND published_at <= NOW() AND robots = 'index' AND deleted_at IS NULL ORDER BY published_at DESC, id DESC LIMIT 1")->fetch();
$newestUrl = $newest !== false ? 'https://mlmsoftware.omc.co.th' . $enc(App\Services\PostService::permalink($newest)) : '';
$check($newestUrl !== '' && str_contains($feed['body'], '<link>' . $newestUrl . '</link>'), 'feed has the newest post permalink', $newestUrl);
$check($rss !== false && count($rss->channel->item) <= 20, 'feed has at most 20 items');
$check(!str_contains($feed['body'], 'zz-test-draft-'), 'feed hides drafts');
$feedNoSlash = $http('GET', '/feed');
$check($feedNoSlash['status'] === 301 && $header($feedNoSlash, 'location') === '/feed/', '/feed → 301 /feed/');

// ------------------------------------------------------------------ Phase 5: บทความที่นำเข้าจากเว็บเดิม

$section('Phase 5 — imported old posts (docs/migration/content.json → migration 030)');

$oldPosts = array_values(array_filter(
    (array) json_decode((string) file_get_contents($root . '/docs/migration/content.json'), true),
    static fn (array $it): bool => ($it['type'] ?? '') === 'post'
));
$check(count($oldPosts) === 36, 'content.json has the 36 old posts', (string) count($oldPosts));

$findImported = $pdo->prepare(
    "SELECT * FROM posts WHERE slug = :slug AND published_date = :date AND deleted_at IS NULL"
);
$importedUrls = [];   // category slug => [canonical...]
$allImported  = [];

foreach ($oldPosts as $old) {
    $path = (string) $old['path_decoded'];

    if (preg_match('#^/(\d{4})/(\d{2})/(\d{2})/([^/]+)/$#u', $path, $pm) !== 1) {
        $check(false, "old post path shape {$path}");
        continue;
    }

    $findImported->execute(['slug' => $pm[4], 'date' => "{$pm[1]}-{$pm[2]}-{$pm[3]}"]);
    $row = $findImported->fetch();

    if ($row === false) {
        $check(false, "imported row exists for {$path}", 'run php scripts/migrate.php');
        continue;
    }

    $check($row['status'] === 'published' && $row['robots'] === 'index', "{$path} is published + index");
    $check((string) $row['legacy_url'] === (string) $old['url'], "{$path} legacy_url = old URL");

    $canon = 'https://mlmsoftware.omc.co.th' . $enc($path);
    $allImported[] = $canon;
    $importedUrls[rawurldecode((string) ($old['categories'][0]['slug'] ?? ''))][] = $canon;

    // ขอด้วย URL เดิมตรงตัว (percent-encode ตัวเล็กแบบ WordPress) และแบบที่ระบบ encode เอง
    $legacyPath = (string) parse_url((string) $old['url'], PHP_URL_PATH);
    $res        = $http('GET', $legacyPath);
    $check($res['status'] === 200, "old URL as-is → 200: {$path}", (string) $res['status']);
    $check($http('GET', $enc($path))['status'] === 200, "encoded path → 200: {$path}");

    $body = $res['body'];
    $check(substr_count($body, '<h1') === 1, "exactly one <h1>: {$path}", (string) substr_count($body, '<h1'));
    preg_match('#<h1[^>]*>(.*?)</h1>#s', $body, $h1);
    $h1Text = trim(html_entity_decode(strip_tags($h1[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $check($h1Text === (string) $row['title'], "H1 = post title: {$path}", $h1Text . ' ≠ ' . $row['title']);

    $check(str_contains($body, '<link rel="canonical" href="' . $canon . '">'), "canonical = production host + encoded old path: {$path}");
    $check(str_contains($body, '<title>' . e((string) $row['seo_title']) . '</title>'), "<title> = seo_title: {$path}");
    $check((string) $row['meta_description'] !== ''
        && str_contains($body, '<meta name="description" content="' . e(App\Services\SeoService::description((string) $row['meta_description'])) . '">'),
        "meta description present: {$path}");
    $check(mb_strlen((string) $row['meta_description']) <= 160, "meta description not cut by the 160 limit: {$path}", (string) mb_strlen((string) $row['meta_description']));
    $descLen = mb_strlen((string) $row['meta_description']);
    $check($descLen >= 50 && $descLen <= 170, "meta description 50–170 chars: {$path}", (string) $descLen);

    // BlogPosting + datePublished ตรงวันที่ใน URL (เวลาไทย)
    $blogPosting = null;
    if (preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $body, $ldm) === 1) {
        foreach ((array) (json_decode($ldm[1], true)['@graph'] ?? []) as $node) {
            if (($node['@type'] ?? '') === 'BlogPosting') {
                $blogPosting = $node;
            }
        }
    }
    $expectedDate = date('c', (int) strtotime((string) $row['published_at']));
    $check($blogPosting !== null && ($blogPosting['datePublished'] ?? '') === $expectedDate
        && str_starts_with($expectedDate, "{$pm[1]}-{$pm[2]}-{$pm[3]}T") && str_ends_with($expectedDate, '+07:00'),
        "BlogPosting datePublished = {$expectedDate}: {$path}", json_encode($blogPosting['datePublished'] ?? null));

    // รูปทุกตัวในบทความ: alt ไม่ว่าง · width/height · ไฟล์มีจริงใน public/
    preg_match('#<article\b.*?</article>#s', $body, $article);
    preg_match_all('#<img\b[^>]*>#s', $article[0] ?? '', $imgs);
    $check(($imgs[0] ?? []) !== [], "post renders at least one image (featured): {$path}");
    foreach ($imgs[0] as $tag) {
        preg_match('#\bsrc="([^"]*)"#', $tag, $src);
        preg_match('#\balt="([^"]*)"#', $tag, $alt);
        $srcPath = rawurldecode((string) parse_url(html_entity_decode($src[1] ?? '', ENT_QUOTES), PHP_URL_PATH));
        $check(trim(html_entity_decode($alt[1] ?? '', ENT_QUOTES)) !== '', "img has alt: {$path} {$srcPath}");
        $check(preg_match('#\bwidth="\d+"#', $tag) === 1 && preg_match('#\bheight="\d+"#', $tag) === 1, "img has width/height: {$path} {$srcPath}");
        $check(str_starts_with($srcPath, '/wp-content/uploads/') && is_file($root . '/public' . $srcPath), "img file exists under public/: {$srcPath}");
    }
}

// วันที่ผิด → 301 ไป URL เดิมที่ถูก (เหมือน WordPress)
$sample = $oldPosts[0] ?? null;
if ($sample !== null) {
    $wrongDate = preg_replace('#^/\d{4}/\d{2}/\d{2}/#', '/2001/01/01/', (string) $sample['path_decoded']);
    $res = $http('GET', $enc((string) $wrongDate));
    $check($res['status'] === 301 && $header($res, 'location') === $enc((string) $sample['path_decoded']), 'imported post: wrong date → 301 to the old URL', $res['status'] . ' ' . $header($res, 'location'));
}

// หน้ารวม: เดินทุกหน้าแล้วต้องเจอครบ
$crawlListing = static function (string $base) use ($http, $enc): array {
    $bodies = '';
    $pages  = 0;
    for ($n = 1; $n <= 30; $n++) {
        $res = $http('GET', $enc($n === 1 ? $base : $base . "page/{$n}/"));
        if ($res['status'] !== 200) {
            return ['bodies' => $bodies, 'pages' => $pages, 'end' => $res['status']];
        }
        $bodies .= $res['body'];
        $pages++;
    }
    return ['bodies' => $bodies, 'pages' => $pages, 'end' => 0];
};

$relative = static fn (string $canonicalUrl): string => substr($canonicalUrl, strlen('https://mlmsoftware.omc.co.th'));

foreach ($importedUrls as $catSlug => $urls) {
    $crawl   = $crawlListing("/category/{$catSlug}/");
    $missing = array_filter($urls, static fn (string $u): bool => !str_contains($crawl['bodies'], 'href="' . $relative($u) . '"'));
    $check($missing === [], "/category/{$catSlug}/ lists all " . count($urls) . ' imported posts', implode(' ', array_slice($missing, 0, 3)));
    $check($crawl['end'] === 404, "/category/{$catSlug}/ pagination ends with 404 after page {$crawl['pages']}", (string) $crawl['end']);
}

$blogCrawl = $crawlListing('/blog/');
$missing   = array_filter($allImported, static fn (string $u): bool => !str_contains($blogCrawl['bodies'], 'href="' . $relative($u) . '"'));
$check($blogCrawl['pages'] >= 4, '/blog/ is paginated (36 posts, 10 per page)', (string) $blogCrawl['pages']);
$check($missing === [], '/blog/ pages list all 36 imported posts', implode(' ', array_slice($missing, 0, 3)));
$check($blogCrawl['end'] === 404, '/blog/ pagination ends with 404 after the last page', (string) $blogCrawl['end']);
$check(str_contains($blogCrawl['bodies'], 'href="/blog/page/2/"'), '/blog/ links to page 2');

$postsSitemap = $http('GET', '/sitemap-posts.xml')['body'];
$missing      = array_filter($allImported, static fn (string $u): bool => !str_contains($postsSitemap, '<loc>' . $u . '</loc>'));
$check($missing === [], 'sitemap-posts.xml includes all 36 imported posts', implode(' ', array_slice($missing, 0, 3)));

$feedXml = @simplexml_load_string($http('GET', '/feed/')['body']);
$latest  = $pdo->query("SELECT slug, published_at FROM posts WHERE status = 'published' AND published_at <= NOW() AND deleted_at IS NULL ORDER BY published_at DESC, id DESC LIMIT 1")->fetch();
$check($feedXml !== false && $latest !== false
    && (string) $feedXml->channel->item[0]->link === 'https://mlmsoftware.omc.co.th' . $enc(App\Services\PostService::permalink($latest)),
    '/feed/ first item is the latest post', $feedXml !== false ? (string) $feedXml->channel->item[0]->link : 'no feed');
$check($feedXml !== false && count($feedXml->channel->item) === 20, '/feed/ lists 20 items', $feedXml !== false ? (string) count($feedXml->channel->item) : '');

// รายการ media ของบทความที่นำเข้า — alt บังคับ · ขนาดครบ · ไฟล์มีจริง
$mediaRows = $pdo->query("SELECT path, alt, width, height FROM media WHERE path LIKE '/wp-content/uploads/%' AND deleted_at IS NULL")->fetchAll();
$check(count($mediaRows) >= 36, 'media rows imported for old images', (string) count($mediaRows));
$badMedia = array_filter($mediaRows, static fn (array $m): bool => trim((string) $m['alt']) === '' || $m['width'] === null || $m['height'] === null
    || !is_file($root . '/public' . $m['path']));
$check($badMedia === [], 'every imported media row has alt + size + a real file', implode(' ', array_column(array_slice($badMedia, 0, 3), 'path')));

// หมวดมีคำอธิบาย SEO จริง (เว็บเดิมมีแค่ชื่อหมวด)
foreach (['/category/ข่าวขายตรง/', '/category/ประกัน/'] as $catPath) {
    $res = $http('GET', $enc($catPath));
    preg_match('#<meta name="description" content="([^"]*)"#', $res['body'], $dm);
    $check(mb_strlen(html_entity_decode($dm[1] ?? '', ENT_QUOTES)) >= 50, "{$catPath} has a real meta description", $dm[1] ?? '');
}

// ------------------------------------------------------------------ Phase 4 — หน้า public 3 ภาษา + SEO + lead

$section('Phase 4 — published pages in th/en/zh (lang · H1 · title/description · canonical · hreflang)');

/** @var array<string, array<string, mixed>> $p4Locales */
$p4Locales = require $root . '/config/locales.php';
$p4Limits  = ['th' => [60, 110, 160], 'en' => [60, 110, 160], 'zh' => [32, 50, 100]];
$p4Pages   = $pdo->query("SELECT path, locale, translation_group, robots, canonical_override FROM pages
                          WHERE status = 'published' AND deleted_at IS NULL AND path NOT LIKE '/zz-test-%' ORDER BY locale, path")->fetchAll();
$p4Group   = [];
$p4Titles  = [];
$p4Descs   = [];
$p4Bodies  = [];

foreach ($p4Pages as $row) {
    if ($row['robots'] === 'index' && (string) $row['canonical_override'] === '') {
        $p4Group[(string) $row['translation_group']][(string) $row['locale']] = (string) $row['path'];
    }
}

$check(count(array_filter($p4Pages, static fn (array $r): bool => $r['locale'] === 'en')) >= 39, 'at least 39 English pages published');
$check(count(array_filter($p4Pages, static fn (array $r): bool => $r['locale'] === 'zh')) >= 39, 'at least 39 Chinese pages published');

foreach ($urlMap as $path) {
    $liveStatus->execute(['p' => $path]);
    $check($liveStatus->fetchColumn() === 'published', "PLAN URL published: {$path}");
}

foreach ($p4Pages as $row) {
    $path   = (string) $row['path'];
    $locale = (string) $row['locale'];
    $res    = $http('GET', $enc($path));
    $body   = $res['body'];
    $p4Bodies[$path] = $body;

    $check($res['status'] === 200, "{$path} → 200", (string) $res['status']);
    $check(str_contains($body, '<html lang="' . $p4Locales[$locale]['html_lang'] . '"'), "{$path} <html lang> = {$p4Locales[$locale]['html_lang']}");
    $check(substr_count($body, '<h1') === 1, "{$path} has exactly one <h1>", (string) substr_count($body, '<h1'));

    preg_match('#<title>(.*?)</title>#s', $body, $t);
    preg_match('#<meta name="description" content="([^"]*)">#', $body, $d);
    $title = html_entity_decode($t[1] ?? '', ENT_QUOTES, 'UTF-8');
    $desc  = html_entity_decode($d[1] ?? '', ENT_QUOTES, 'UTF-8');
    [$maxTitle, $minDesc, $maxDesc] = $p4Limits[$locale];

    $check($title !== '' && mb_strlen($title) <= $maxTitle, "{$path} title ≤ {$maxTitle} chars", mb_strlen($title) . ' ' . $title);

    if ($row['robots'] === 'index') {
        $check(mb_strlen($desc) >= $minDesc && mb_strlen($desc) <= $maxDesc, "{$path} description {$minDesc}–{$maxDesc} chars", (string) mb_strlen($desc));
        $p4Titles[$locale][$title][] = $path;
        $p4Descs[$locale][$desc][]   = $path;
    } else {
        $check(str_contains($body, '<meta name="robots" content="noindex'), "{$path} (noindex row) is noindex");
    }

    $selfCanonical = 'https://mlmsoftware.omc.co.th' . $enc($path);
    $expected      = (string) $row['canonical_override'] !== '' ? 'https://mlmsoftware.omc.co.th' . $enc((string) $row['canonical_override']) : $selfCanonical;
    $check(str_contains($body, '<link rel="canonical" href="' . $expected . '">'), "{$path} canonical correct (never cross-language)");

    // hreflang: ชุดเดียวกันทุกหน้าในกลุ่ม (reciprocal) + ตัวเอง + x-default = ไทย
    $set = $row['robots'] === 'index' && (string) $row['canonical_override'] === '' ? ($p4Group[(string) $row['translation_group']] ?? []) : [];

    if (count($set) >= 2) {
        foreach ($set as $altLocale => $altPath) {
            $check(str_contains($body, '<link rel="alternate" hreflang="' . $p4Locales[$altLocale]['html_lang'] . '" href="https://mlmsoftware.omc.co.th' . $enc($altPath) . '">'),
                "{$path} hreflang {$altLocale} → {$altPath}");
        }
        $check(isset($set['th']) && str_contains($body, '<link rel="alternate" hreflang="x-default" href="https://mlmsoftware.omc.co.th' . $enc($set['th']) . '">'), "{$path} hreflang x-default → Thai");
        $check(isset($set[$locale]) && $set[$locale] === $path, "{$path} hreflang set includes itself");
    } else {
        $check(!str_contains($body, 'hreflang="x-default"'), "{$path} has no hreflang (no published translation / not indexable)");
    }
}

foreach (['th', 'en', 'zh'] as $locale) {
    $dupTitles = array_filter($p4Titles[$locale] ?? [], static fn (array $paths): bool => count($paths) > 1);
    $dupDescs  = array_filter($p4Descs[$locale] ?? [], static fn (array $paths): bool => count($paths) > 1);
    $check($dupTitles === [], "no duplicate <title> among indexable {$locale} pages", json_encode($dupTitles, JSON_UNESCAPED_UNICODE));
    $check($dupDescs === [], "no duplicate meta description among indexable {$locale} pages", json_encode($dupDescs, JSON_UNESCAPED_UNICODE));
}

$section('Phase 4 — content rules (no fake claims · no 传销 · landing pages substantial)');
$p4Forbidden = ['100,000+', '99.9%', '200+', '10+ ปี', 'J&amp;C', 'J&C', 'NUTRIPLUS', 'BEAUTÉ', 'VITA MAX', 'BIO WELLNESS', 'HERBAL LIFE', '传销'];
foreach ($p4Bodies as $path => $body) {
    $visible = strip_tags((string) preg_replace('#<(script|style)\b.*?</\1>#s', '', $body));
    foreach ($p4Forbidden as $term) {
        $check(!str_contains($visible, $term) && !str_contains($body, $term), "{$path} has no '{$term}'");
    }
}

$p4Landing = ['/mlm-program/', '/mlm-system/', '/direct-sales-software/', '/direct-sales-system/', '/mlm-development/',
    '/mlm-programming/', '/mlm-software-thailand/', '/mlm-migration/', '/commission-system/', '/member-system/'];
foreach ($p4Landing as $path) {
    foreach (['' => 3500, '/en' => 3000, '/zh' => 1000] as $prefix => $minChars) {
        $body = $p4Bodies[$prefix . $path] ?? '';
        preg_match('#<main\b.*?</main>#s', $body, $main);
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) preg_replace('#<(script|style|nav|form)\b.*?</\1>#s', '', $main[0] ?? ''))));
        $check(mb_strlen($text) >= $minChars, "landing {$prefix}{$path} has substantial copy (≥ {$minChars} chars)", (string) mb_strlen($text));
        $check(str_contains($body, '"@type":"FAQPage"'), "landing {$prefix}{$path} has an FAQ (FAQPage JSON-LD)");
        $check(str_contains($body, 'action="/lead/"') || str_contains($body, '/lead/"'), "landing {$prefix}{$path} has a lead form");
    }
    $check(preg_match('#href="/20\d\d/\d\d/\d\d/#', $p4Bodies[$path] ?? '') === 1, "landing {$path} (th) links to an old blog post");
}

$check(str_contains($p4Bodies['/'] ?? '', 'ก่อตั้งปี 2552') || str_contains($p4Bodies['/about/'] ?? '', '2552'), 'founding year 2552 shown');
$years = (new DateTimeImmutable('2009-04-03'))->diff(new DateTimeImmutable('today'))->y;
$check(str_contains($p4Bodies['/about/'] ?? '', $years . ' ปี'), "years in business computed ({$years})");
$check(!str_contains(implode('', $p4Bodies), '{years}'), 'no unreplaced {years} placeholder');
$check(str_contains($p4Bodies['/ocpb-mlm/'] ?? '', 'ไม่ใช่คำปรึกษากฎหมาย'), '/ocpb-mlm/ has the legal disclaimer');
$check(str_contains($p4Bodies['/contact/'] ?? '', 'tel:+66991095544') && str_contains($p4Bodies['/contact/'] ?? '', 'https://line.me/ti/p/~omc54'), 'contact page has click-to-call and LINE');

$section('Phase 4 — locale from URL only · no empty translated URLs');
foreach (['en', 'zh', 'en-US,en;q=0.9', 'zh-CN,zh;q=0.9'] as $acceptLanguage) {
    $res = $http('GET', '/', ['headers' => ['Accept-Language: ' . $acceptLanguage]]);
    $check($res['status'] === 200 && $header($res, 'location') === '' && str_contains($res['body'], '<html lang="th"'),
        "Accept-Language: {$acceptLanguage} on / → Thai page 200, no redirect");
}
// UI strings (__()) follow the URL even when APP_LOCALES (admin languages) leaves the locale out
foreach (['/zh/' => '人物为示意图片', '/en/' => 'Person photo is illustrative'] as $path => $caption) {
    foreach (['th', 'en-US,en;q=0.9', 'zh-CN'] as $acceptLanguage) {
        $res = $http('GET', $path, ['headers' => ['Accept-Language: ' . $acceptLanguage]]);
        $check(str_contains($res['body'], $caption), "{$path} UI strings in its own language (Accept-Language: {$acceptLanguage})");
    }
}
foreach (['/en/blog/', '/zh/blog/', '/en/feed/', '/zh/feed/', '/en/category/ข่าวขายตรง/', '/en/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/'] as $path) {
    $res = $http('GET', $enc($path));
    $check($res['status'] === 404 && str_contains($res['body'], 'noindex'), "{$path} → 404 noindex (no translated posts yet)", (string) $res['status']);
}
$thaiPost = $http('GET', $enc('/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/'));
$check(str_contains($thaiPost['body'], 'href="/en/" hreflang="en"') || str_contains($thaiPost['body'], "href=\"/en/\"\n"), 'language switcher on a Thai-only post links to /en/ home');
$check(!str_contains($thaiPost['body'], 'hreflang="x-default"'), 'Thai-only post has no hreflang');
$check(str_contains($thaiPost['body'], 'class="post-related"') && str_contains($thaiPost['body'], 'class="cta-band"'), 'post page has related posts and a CTA band');
$check(preg_match('#<img[^>]+fetchpriority="high"#', $thaiPost['body']) === 1, 'post featured image is the LCP image (fetchpriority=high, not lazy)');
// Phase 6 — imported WordPress images served as WebP + srcset (originals stay reachable)
$check(preg_match('#<figure class="post-cover">\s*<img[^>]+src="[^"]+\.webp\?v=\d+"\s+srcset="[^"]+ 480w#', $thaiPost['body']) === 1, 'post cover is WebP with srcset');
$check(preg_match('#class="prose post-body".*?<img[^>]+src="/wp-content/uploads/[^"]+\.webp"[^>]+srcset="[^"]+ 480w[^"]*"[^>]+sizes="#s', $thaiPost['body']) === 1, 'images inside the article are WebP with srcset + sizes');
$check(!preg_match('#<img[^>]+src="[^"]+\.(png|jpe?g)(\?[^"]*)?"#i', $thaiPost['body'] . $http('GET', '/blog/')['body']), 'no PNG/JPEG <img> on a post or /blog/');
$blogImgs = [];
preg_match_all('#<img class="news-item-image"[^>]*>#', $http('GET', '/blog/')['body'], $blogImgs);
$check(count($blogImgs[0]) > 2 && str_contains($blogImgs[0][0], 'fetchpriority="high"') && !str_contains($blogImgs[0][0], 'loading="lazy"')
    && str_contains($blogImgs[0][1], 'loading="lazy"'), '/blog/: first card image eager (LCP), the rest lazy');

// blog redesign 2026-11-07 — mockup "บทความ / ความรู้"
$blog = $http('GET', '/blog/')['body'];
$check(substr_count($blog, '<h1') === 1 && preg_match('#<h1 class="hero-page-title"[^>]*>\s*บทความ / ความรู้\s*</h1>#u', $blog) === 1, '/blog/: one H1 "บทความ / ความรู้" (keeps the old keyword "บทความ")');
$check(str_contains($blog, 'class="hero-page is-dark has-visual"') && str_contains($blog, '<link rel="canonical" href="https://mlmsoftware.omc.co.th/blog/">'), '/blog/: navy hero + canonical unchanged');
$check(str_contains($blog, 'aria-current="page">') && str_contains($blog, 'href="/category/' . rawurlencode('ข่าวขายตรง') . '/"') && str_contains($blog, 'href="/category/' . rawurlencode('ประกัน') . '/"'),
    '/blog/: category filter links to the existing category URLs (no new filter URLs)');
$check(substr_count($blog, 'class="news-item"') === 10 && substr_count($blog, 'class="news-chip is-category"') === 10, '/blog/: 10 cards, each with its real category chip');
$check(str_contains($blog, 'href="/contact/#lead-form"') && str_contains($blog, 'href="/demo/#lead-form"'), '/blog/: CTA buttons go to the real lead forms');
$check(str_contains($blog, 'rel="next" href="/blog/page/2/"') && str_contains($blog, 'href="/blog/page/4/"'), '/blog/: pagination keeps /blog/page/N/ URLs');
$check(!preg_match('#/blog/\?(filter|category|page)=#', $blog), '/blog/: no query-string listing URLs');
$searchRes = $http('GET', '/blog/?q=' . rawurlencode('คอมมิชชัน'));
$check($searchRes['status'] === 200 && str_contains($searchRes['body'], 'content="noindex, follow"') && !str_contains($searchRes['body'], 'rel="canonical"')
    && substr_count($searchRes['body'], 'class="news-item"') > 0 && str_contains($searchRes['body'], 'class="blog-results"'), 'search: results, noindex follow, no canonical');
$check(substr_count($http('GET', '/blog/?q=' . rawurlencode('%_'))['body'], 'class="news-item"') === 0, 'search: % and _ are literal (not wildcards)');
$check(str_contains($http('GET', '/blog/?q=zz-no-such-word')['body'], 'empty-state'), 'search: no result → empty state with a way back');
$catPage = $http('GET', '/category/' . rawurlencode('ประกัน') . '/')['body'];
$check(substr_count($catPage, 'class="news-item"') >= 9 && preg_match('#<h1 class="hero-page-title"[^>]*>\s*ประกัน\s*</h1>#u', $catPage) === 1
    && str_contains($catPage, 'content="index, follow'), 'category page: own H1, its posts (9 real + test fixtures), still indexable',
    substr_count($catPage, 'class="news-item"') . ' cards · ' . (preg_match('#<h1[^>]*>\s*([^<]*)#u', $catPage, $hm) ? trim($hm[1]) : 'no h1'));
preg_match('#class="post-related".*#s', $thaiPost['body'], $related);
$check(!str_contains($related[0] ?? '', 'fetchpriority="high"'), 'related posts below an article stay lazy');
$check(str_contains($thaiPost['body'], '%E0%B8%9B%E0%B8%81') && !str_contains($thaiPost['body'], 'src="/wp-content/uploads/2025/05/ปก'), 'Thai image file names are percent-encoded in src/srcset');
// the test server (php -S from the project root) does not serve static files — check the original is still on disk
$check(is_file($root . '/public/wp-content/uploads/2025/05/ปกบทความ.png') && is_file($root . '/public/wp-content/uploads/2025/05/ปกบทความ.webp'),
    'original PNG kept next to its WebP (old backlinks / Google Images)');
$home = $p4Bodies['/'] ?? '';
$check(substr_count($home, 'class="lang-switch') >= 3, 'header (desktop + drawer) and footer have the language switcher');
$check(str_contains($home, 'aria-label="' . e(__('site.lang_switch')) . '"') || str_contains($home, 'class="lang-switch is-header" aria-label='), 'language switcher has an accessible label');

$section('GEO — llms.txt · entity schema · old WordPress sitemaps · IndexNow');
$llms = $http('GET', '/llms.txt');
$check($llms['status'] === 200 && str_contains($header($llms, 'content-type'), 'text/markdown') && str_starts_with($llms['body'], '# '), '/llms.txt: 200 markdown');
$check(str_contains($llms['body'], 'Registered: 2009-04-03') && str_contains($llms['body'], '(https://mlmsoftware.omc.co.th/mlm-software-omc/)')
    && str_contains($llms['body'], '## English pages') && str_contains($llms['body'], '## Articles (Thai)'), '/llms.txt: verified facts + live pages in 3 languages + articles');
// zz-test-* = หน้าทดสอบที่ชุดนี้เผยแพร่ชั่วคราว (ออกจาก llms.txt เองเมื่อลบ) — ตรวจแค่ไม่มีหลังบ้าน/หน้า noindex
$check(!str_contains($llms['body'], '/admin/') && !str_contains($llms['body'], '/thank-you/'), '/llms.txt: no admin URLs, no noindex pages (/thank-you/)');
$homeLd = [];
preg_match('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $http('GET', '/')['body'], $homeLd);
$org = (array) (json_decode((string) ($homeLd[1] ?? ''), true)['@graph'][0] ?? []);
$check(($org['foundingDate'] ?? '') === '2009-04-03' && ($org['address']['addressCountry'] ?? '') === 'TH' && ($org['contactPoint'][0]['contactType'] ?? '') === 'sales'
    && in_array('OMC', (array) ($org['alternateName'] ?? []), true), 'Organization schema: foundingDate, address, contactPoint, alternateName');
// อีเมล entity = อีเมลบนโดเมนบริษัท (ไม่ใช่กล่องแจ้งเตือน lead หรืออีเมลฟรีที่ขึ้นก่อนในท้ายเว็บ)
$check(str_ends_with((string) ($org['contactPoint'][0]['email'] ?? ''), '@omc.co.th') && preg_match('/- Contact: .*@omc\.co\.th/', $llms['body']) === 1,
    'Organization + llms.txt: contact e-mail is on the company domain');
$check(!isset($org['aggregateRating']) && !isset($org['award']) && !isset($org['review']), 'Organization schema: no ratings/awards/reviews (nothing unverified)');
foreach (['/wp-sitemap.xml' => '/sitemap.xml', '/wp-sitemap-posts-post-1.xml' => '/sitemap-posts.xml', '/wp-sitemap-posts-page-1.xml' => '/sitemap-pages.xml'] as $from => $to) {
    $res = $http('GET', $from);
    $check($res['status'] === 301 && str_ends_with($header($res, 'location'), $to) && $http('GET', $to)['status'] === 200, "old sitemap {$from} → 301 {$to} (one hop)");
}
// ‼️ ห้ามเรียก IndexNowService::enabled() ตรงนี้ — config() จำค่าครั้งแรกไว้ (static) ทำให้ส่วน production ด้านล่างได้ config ผิด
$check($http('GET', '/' . bin2hex(random_bytes(8)) . '.txt')['status'] === 404, 'IndexNow: key file route answers 404 outside production');

$section('Phase 4 — sitemaps per language with hreflang alternates');
$index = $http('GET', '/sitemap.xml')['body'];
foreach (['pages', 'posts', 'categories', 'pages-en', 'pages-zh'] as $part) {
    $check(str_contains($index, 'https://mlmsoftware.omc.co.th/sitemap-' . $part . '.xml'), "index lists sitemap-{$part}.xml");
}
$check(!str_contains($index, 'sitemap-posts-en.xml') && $http('GET', '/sitemap-posts-en.xml')['status'] === 404, 'no empty sitemap-posts-en.xml');
$smEn = $http('GET', '/sitemap-pages-en.xml');
$check($smEn['status'] === 200 && @simplexml_load_string($smEn['body']) !== false, 'sitemap-pages-en.xml is valid XML');
$check(str_contains($smEn['body'], '<loc>https://mlmsoftware.omc.co.th/en/features/</loc>')
    && str_contains($smEn['body'], '<xhtml:link rel="alternate" hreflang="zh-Hans" href="https://mlmsoftware.omc.co.th/zh/features/" />')
    && str_contains($smEn['body'], '<xhtml:link rel="alternate" hreflang="x-default" href="https://mlmsoftware.omc.co.th/features/" />'),
    'sitemap-pages-en.xml has reciprocal xhtml:link alternates + x-default');
$smTh = $http('GET', '/sitemap-pages.xml')['body'];
$check(!str_contains($smTh, '<loc>https://mlmsoftware.omc.co.th/en/') && !str_contains($smTh, '/thank-you/') && !str_contains($smTh, '/ocpb-mlm2/'), 'Thai pages sitemap: no other locale, no noindex thank-you, no canonicalised /ocpb-mlm2/');

$section('Phase 4 — lead form (CSRF · honeypot · validation · rate limit · thank-you)');
$pdo->prepare('DELETE FROM lead_attempts WHERE ip_address = :ip')->execute(['ip' => '127.0.0.1']);
$leadPage = $http('GET', '/en/demo/?utm_source=zz-test&utm_medium=cpc&utm_campaign=p4', ['jar' => 'lead']);
preg_match('/name="_token" value="([^"]+)"/', $leadPage['body'], $m);
$leadToken = $m[1] ?? '';
$check($leadToken !== '' && str_contains($leadPage['body'], 'name="utm_source" value="zz-test"'), 'lead form has CSRF token and UTM hidden fields');
$check(str_contains($leadPage['body'], 'id="lead-form"') && str_contains($leadPage['body'], 'name="website"'), 'lead form anchor + honeypot field');
$leadBase = ['_token' => $leadToken, 'form_key' => 'demo', 'source_path' => '/en/demo/', 'website' => ''];

$res = $http('POST', '/lead/', ['jar' => 'lead', 'post' => $leadBase + ['name' => 'zz-test lead ' . $stamp, 'email' => 'zz-test@example.com',
    'business_type' => 'supplements', 'need' => 'custom', 'member_estimate' => '1k_10k', 'has_system' => 'no', 'message' => 'hello', 'utm_campaign' => 'p4']]);
$check($res['status'] === 303 && $header($res, 'location') === '/en/thank-you/', 'valid lead → 303 /en/thank-you/', $res['status'] . ' ' . $header($res, 'location'));
$lead = $pdo->prepare("SELECT * FROM leads WHERE name = :n");
$lead->execute(['n' => 'zz-test lead ' . $stamp]);
$leadRow = $lead->fetch();
$check($leadRow !== false && $leadRow['locale'] === 'en' && $leadRow['landing_page'] === '/en/demo/' && $leadRow['status'] === 'new', 'lead stored with locale + landing page');
$leadUtm = json_decode((string) ($leadRow['utm'] ?? ''), true);
$check(($leadUtm['first']['source'] ?? '') === 'zz-test' && ($leadUtm['current']['campaign'] ?? '') === 'p4', 'lead has first-touch UTM (cookie) and current UTM', (string) ($leadRow['utm'] ?? ''));
$auditLead = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE table_name = 'leads' AND record_id = :id AND action = 'create'");
$auditLead->execute(['id' => $leadRow['id'] ?? 0]);
$check((int) $auditLead->fetchColumn() === 1, 'lead create is audited');
$thanks = $http('GET', '/en/thank-you/');
$check($thanks['status'] === 200 && str_contains($thanks['body'], '<meta name="robots" content="noindex'), '/en/thank-you/ is noindex');

$res = $http('POST', '/lead/', ['jar' => 'lead', 'post' => $leadBase + ['name' => '', 'email' => 'not-an-email', 'business_type' => 'bogus']]);
$check($res['status'] === 303 && $header($res, 'location') === '/en/demo/#lead-form', 'invalid lead → back to the form', $header($res, 'location'));
$back = $http('GET', '/en/demo/', ['jar' => 'lead']);
$check(str_contains($back['body'], 'id="f-name-error"') && str_contains($back['body'], 'aria-invalid="true"') && str_contains($back['body'], 'value="not-an-email"'),
    'errors shown under fields (aria-describedby) with old input kept');
$check(str_contains($back['body'], e(__('lead.error_option'))) || str_contains($back['body'], 'Please choose one of the options'), 'option outside LEAD_OPTIONS rejected');

$beforeCount = (int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$res = $http('POST', '/lead/', ['jar' => 'lead', 'post' => ['_token' => $leadToken, 'form_key' => 'demo', 'source_path' => '/en/demo/', 'name' => 'zz-test bot', 'email' => 'bot@example.com', 'website' => 'http://spam.example']]);
$check($res['status'] === 303 && $header($res, 'location') === '/en/thank-you/', 'honeypot filled → looks successful');
$check((int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn() === $beforeCount, 'honeypot lead not stored');

$res = $http('POST', '/lead/', ['jar' => 'lead-nocsrf', 'post' => ['_token' => 'forged', 'form_key' => 'demo', 'source_path' => '/en/demo/', 'name' => 'zz-test csrf', 'email' => 'x@example.com'],
    'headers' => ['Referer: ' . $base . '/en/demo/']]);
$check($res['status'] === 303 && $header($res, 'location') === '/en/demo/', 'CSRF failure → back to the page, not stored', $res['status'] . ' ' . $header($res, 'location'));
$check((int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn() === $beforeCount, 'forged-token lead not stored');

$throttled = false;
for ($i = 0; $i < App\Services\LeadService::MAX_ATTEMPTS + 2; $i++) {
    $res = $http('POST', '/lead/', ['jar' => 'lead', 'post' => $leadBase + ['name' => '']]);
    if ($res['status'] === 303) {
        $after = $http('GET', '/en/demo/', ['jar' => 'lead']);
        if (str_contains($after['body'], e(__('lead.throttled'))) || str_contains($after['body'], 'Too many submissions')) {
            $throttled = true;
            break;
        }
    }
}
$check($throttled, 'rate limit per IP kicks in (lead_attempts)');
$pdo->prepare('DELETE FROM lead_attempts WHERE ip_address = :ip')->execute(['ip' => '127.0.0.1']);
$pdo->prepare("DELETE FROM leads WHERE name LIKE 'zz-test%'")->execute();

$section('Phase 4 — Thai URLs unchanged (docs/migration/url-mapping.md baseline)');
preg_match_all('/^\| \d+ \| `(https?:\/\/[^`]+)` \| [^|]+ \| ([^|]+) \| ([^|]*) \|/mu', (string) file_get_contents($root . '/docs/migration/url-mapping.md'), $rows, PREG_SET_ORDER);
$check(count($rows) === 95, 'url-mapping.md has 95 baseline URLs', (string) count($rows));
foreach ($rows as [, $oldUrl, $newStatus, $redirect]) {
    $parts = parse_url($oldUrl);
    if (($parts['host'] ?? '') !== 'mlmsoftware.omc.co.th' || ($parts['scheme'] ?? '') !== 'https') {
        continue; // host/scheme variants — server level
    }
    $path  = (string) ($parts['path'] ?? '/');
    $url   = $enc($path) . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $res   = $http('GET', $url);
    $newStatus = trim($newStatus);
    if (str_starts_with($newStatus, '301')) {
        $target = trim((string) preg_replace('/^301 → /u', '', trim($redirect)));
        $check($res['status'] === 301 && rawurldecode(explode('?', $header($res, 'location'))[0]) === $target, "{$path} still 301 → {$target}", $res['status'] . ' ' . $header($res, 'location'));
        $res  = $http('GET', $enc($target));
        $path = $target;
    }
    if ($newStatus === '410') {
        $check($res['status'] === 410, "{$path} still 410");
        continue;
    }
    $check($res['status'] === 200, "{$path} → 200 (old URL intact)", (string) $res['status']);
    if (str_contains($header($res, 'content-type'), 'text/html') && !isset($parts['query'])) {
        $check(substr_count($res['body'], '<h1') === 1, "{$path} one H1");
        $canon = $path === '/ocpb-mlm2/' ? '/ocpb-mlm/' : $path;
        $check(str_contains($res['body'], '<link rel="canonical" href="https://mlmsoftware.omc.co.th' . $enc($canon) . '">'), "{$path} canonical unchanged");
        $check(str_contains($res['body'], '<html lang="th"'), "{$path} stays Thai");
    }
}

// ------------------------------------------------------------------ /_preflight (production readiness)

$section('Pre-production — /_preflight (token, read-only, no secrets)');
$check($http('GET', '/_preflight')['status'] === 404, '/_preflight without token → 404 (endpoint hidden)');
$check($http('GET', '/_preflight', ['headers' => ['X-Migrate-Token: wrong']])['status'] === 404, '/_preflight with a wrong token → 404');
$check($http('GET', '/_preflight?token=' . rawurlencode((string) $config['migrate_token']))['status'] === 404, '/_preflight ignores ?token= (header only — no token in access logs)');
if ((string) $config['migrate_token'] !== '') {
    $pre = $http('GET', '/_preflight', ['headers' => ['X-Migrate-Token: ' . $config['migrate_token']]]);
    $check(in_array($pre['status'], [200, 500], true) && str_contains($pre['body'], 'verdict:') && str_contains($pre['body'], 'not the WordPress DB'),
        '/_preflight with token → report', (string) $pre['status']);
    $check(str_contains($pre['body'], 'FAIL  env       APP_ENV'), 'non-production install is flagged (APP_ENV)');
    // ‼️ เฉพาะค่าลับจริง ≥ 8 ตัว — ชื่อผู้ใช้ฐานข้อมูลในเครื่อง ("root") ชนกับคำว่า "document root" ในรายงาน
    $secrets = array_filter([(string) $config['migrate_token'], (string) ($config['app_key'] ?? ''), (string) $config['db']['pass'],
        (string) ($config['mail']['pass'] ?? '')], static fn (string $s): bool => strlen($s) >= 8);
    $check(array_filter($secrets, static fn (string $s): bool => str_contains($pre['body'], $s)) === [], '/_preflight never prints a secret (token, key, DB password, SMTP password)');
    $check(str_contains($pre['body'], 'document root = public/'), '/_preflight (web) checks the document root');
}

// ------------------------------------------------------------------ environment / noindex

$section('noindex by environment');
foreach (['/', '/blog/', $enc($postPath), $productPath, '/sitemap.xml', '/robots.txt', '/feed/'] as $path) {
    $res = $http('GET', $path);
    $check(str_contains($header($res, 'x-robots-tag'), 'noindex'), "non-production X-Robots-Tag noindex on {$path}");
}

// production — ในโปรเซสนี้ด้วย config ชุดเดียวกันแต่ env=production
$prodConfig        = $config;
$prodConfig['env'] = 'production';
$prodConfig['url'] = 'https://mlmsoftware.omc.co.th';
$GLOBALS['__config'] = $prodConfig;
Core\Database::configure($prodConfig['db']);
Core\Lang::configure($prodConfig, $prodConfig['paths']['lang']);

$prodRobots = (new App\Services\RobotsService())->content('production', $prodConfig['url']);
$check(str_contains($prodRobots, "Allow: /\n") && !str_contains($prodRobots, "Disallow: /\n"), 'production robots.txt allows crawling', $prodRobots);
$check(str_contains($prodRobots, 'Sitemap: https://mlmsoftware.omc.co.th/sitemap.xml'), 'production robots.txt has Sitemap line from APP_URL');

$meta = (new App\Services\SeoService())->meta(['title' => 'X', 'path' => '/x/', 'robots' => 'index']);
$check(!str_contains($meta['robots'], 'noindex'), 'production page meta robots has no noindex', $meta['robots']);

$resolution = (new App\Services\SiteResolver())->resolve($productPath, '', false);
$screen     = (new App\Services\SiteViewService())->build($resolution);
$check($screen['status'] === 200 && !$screen['private'] && !str_contains($screen['seo']['robots'], 'noindex'), 'production render of a published page has no noindex');
$check($screen['seo']['canonical'] === 'https://mlmsoftware.omc.co.th' . $productPath, 'production canonical');

// แหล่ง noindex ในโค้ดต้องมาจาก Staging (non-production), preview หรือหน้า noindex/404 เท่านั้น
$sources = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app')) as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.php')
        && preg_match('/header\(\s*[\'"]X-Robots-Tag/i', (string) file_get_contents($file->getPathname())) === 1) {
        $sources[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    }
}
// PreviewController = หน้า /_preview/* ของ Phase 1 ซึ่งมี route เฉพาะ environment ที่ไม่ใช่ production
sort($sources);
$check($sources === ['app/Controllers/PreviewController.php', 'app/Controllers/PublicController.php'], 'only preview responses add X-Robots-Tag in app/', implode(', ', $sources));

// ------------------------------------------------------------------ audit log ผ่าน base Model

$section('audit log');
$redirects = new App\Services\RedirectService();
$newId     = $redirects->create(['from_path' => '/zz-test-audit-' . $stamp . '/', 'match_type' => 'exact', 'to_path' => '/', 'code' => 301]);
$redirects->update($newId, ['to_path' => '/contact/']);
$redirects->delete($newId);
$audit = $pdo->prepare("SELECT action FROM audit_logs WHERE table_name = 'redirects' AND record_id = :id ORDER BY id");
$audit->execute(['id' => $newId]);
$check($audit->fetchAll(PDO::FETCH_COLUMN) === ['create', 'update', 'delete'], 'service writes are audited (create/update/delete)');
$pdo->prepare('DELETE FROM redirects WHERE id = :id')->execute(['id' => $newId]);

// ------------------------------------------------------------------ Phase 3 — CMS admin

require __DIR__ . '/cms.php';

// ------------------------------------------------------------------ Phase 6 — อีเมลแจ้งทีมขาย (SMTP sink ในเครื่อง)

require __DIR__ . '/mail.php';

// ------------------------------------------------------------------ สรุป

echo "\n";
foreach ($results['failures'] as $failure) {
    echo "  FAIL  {$failure}\n";
}
printf("\n%d passed · %d failed\n", $results['pass'], $results['fail']);

exit($results['fail'] === 0 ? 0 : 1);
