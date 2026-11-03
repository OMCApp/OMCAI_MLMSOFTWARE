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

    if ($status === 'draft') {
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

$blog = $http('GET', '/blog/');
$check(str_contains($blog['body'], $enc($postPath)), '/blog/ lists the published post');
$check(!str_contains($blog['body'], 'zz-test-draft-'), '/blog/ hides drafts');

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
$check(!str_contains($pages, '/features/') && !str_contains($pages, '/sample-page/'), 'pages sitemap excludes drafts and redirected URLs');
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
$check(str_contains($feed['body'], '<link>' . $canonical . '</link>'), 'feed has post permalink');
$check($rss !== false && count($rss->channel->item) <= 20, 'feed has at most 20 items');
$check(!str_contains($feed['body'], 'zz-test-draft-'), 'feed hides drafts');
$feedNoSlash = $http('GET', '/feed');
$check($feedNoSlash['status'] === 301 && $header($feedNoSlash, 'location') === '/feed/', '/feed → 301 /feed/');

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
$check($sources === ['app/Controllers/PublicController.php'], 'only preview responses add X-Robots-Tag in app/', implode(', ', $sources));

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

// ------------------------------------------------------------------ สรุป

echo "\n";
foreach ($results['failures'] as $failure) {
    echo "  FAIL  {$failure}\n";
}
printf("\n%d passed · %d failed\n", $results['pass'], $results['fail']);

exit($results['fail'] === 0 ? 0 : 1);
