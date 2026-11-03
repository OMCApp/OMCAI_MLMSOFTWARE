<?php
/**
 * ลำดับการจับ URL ของเว็บสาธารณะ — PLAN.md "Routing สาธารณะ" (ห้ามเปลี่ยนลำดับ)
 *
 *   1. ไฟล์จริงใน public/            ← Apache / server.php เสิร์ฟเองก่อนถึง PHP
 *   2. route ระบบ                     ← config/routes.php (Router จับก่อนถึง fallback)
 *   3. trailing slash 301             ← ที่นี่
 *   4. redirects table (301/302/410)  ← ที่นี่
 *   5. บทความ /{yyyy}/{mm}/{dd}/{slug}/
 *   6. หมวด /category/{slug}/ (+ /page/{n}/)
 *   7. บล็อก /blog/ (+ /page/{n}/)
 *   8. หน้า (pages.path ตรงตัว)
 *   9. 404 + บันทึก not_found_log
 *
 * คืนผลเป็น "ข้อมูล" ให้ SiteController ตอบ — ไม่ส่ง header เอง จึงทดสอบได้โดยไม่ต้องยิง HTTP
 *
 * ‼️ path ที่รับเข้ามาต้อง decode แล้ว (Core\Path::requested()) — ฐานข้อมูลเก็บแบบ decode
 */

declare(strict_types=1);

namespace App\Services;

final class SiteResolver
{
    public function __construct(
        private readonly RedirectService $redirects = new RedirectService(),
        private readonly PostService $posts = new PostService(),
        private readonly CategoryService $categories = new CategoryService(),
        private readonly PageService $pages = new PageService(),
    ) {
    }

    /**
     * @return array<string, mixed> ['kind' => redirect|gone|post|category|blog|page|not_found, ...]
     *         redirect: location (path ภายในแบบ decode หรือ URL เต็ม), code
     *         not_found: log (bool) — false สำหรับ path ที่ไม่ใช่ของเว็บสาธารณะ
     */
    public function resolve(string $path, string $query, bool $preview): array
    {
        // byte ที่ไม่ใช่ UTF-8 (เช่น %FF) ไม่มีทางเป็น URL ของเรา และห้ามส่งเข้า query
        if (!mb_check_encoding($path, 'UTF-8') || str_contains($path, "\0")) {
            return ['kind' => 'not_found', 'log' => false];
        }

        // หลังบ้านและ endpoint ภายในที่ไม่มี route — 404 ของระบบ ไม่ใช่ของเว็บ และไม่นับเป็น SEO 404
        if (self::isSystemPath($path)) {
            return ['kind' => 'not_found', 'log' => false, 'system' => true];
        }

        // 3. trailing slash
        if (!str_ends_with($path, '/') && !self::looksLikeFile($path)) {
            return ['kind' => 'redirect', 'code' => 301, 'location' => $path . '/' . ($query !== '' ? '?' . $query : '')];
        }

        // 4. redirects
        $rule = $this->redirects->match($path);

        if ($rule !== null) {
            if ((int) $rule['code'] === 410) {
                return ['kind' => 'gone'];
            }

            return ['kind' => 'redirect', 'code' => (int) $rule['code'], 'location' => $this->redirects->target($rule, $query)];
        }

        // 5. บทความ
        if (preg_match('#^/(\d{4})/(\d{2})/(\d{2})/([^/]+)/$#u', $path, $m) === 1) {
            $found = $this->posts->resolve($m[1], $m[2], $m[3], $m[4], $preview);

            if (isset($found['redirect'])) {
                return ['kind' => 'redirect', 'code' => 301, 'location' => $found['redirect'] . ($query !== '' ? '?' . $query : '')];
            }
            if (isset($found['post'])) {
                $post = $found['post'];

                return [
                    'kind'     => 'post',
                    'post'     => $post,
                    'category' => $this->categories->find($post['category_id'] !== null ? (int) $post['category_id'] : null),
                    'preview'  => !PostService::isLive($post),
                ];
            }
        }

        // 6. หมวด
        if (preg_match('#^/category/([^/]+)/(?:page/(\d+)/)?$#u', $path, $m) === 1) {
            $category = $this->categories->findBySlug($m[1]);

            if ($category !== null) {
                return $this->listing('category', CategoryService::path($category), $m[2] ?? '', $query, $category);
            }
        }

        // 7. บล็อก
        if (preg_match('#^/blog/(?:page/(\d+)/)?$#', $path, $m) === 1) {
            return $this->listing('blog', '/blog/', $m[1] ?? '', $query, null);
        }

        // 8. หน้า
        $page = $this->pages->findVisible($path, $preview);

        if ($page !== null) {
            return ['kind' => 'page', 'page' => $page, 'preview' => !PageService::isLive($page)];
        }

        // 9.
        return ['kind' => 'not_found', 'log' => true];
    }

    /** /admin/... /_... ไม่ใช่ URL สาธารณะ — ห้ามโดน trailing slash, redirect หรือ not_found_log */
    public static function isSystemPath(string $path): bool
    {
        return $path === '/admin' || str_starts_with($path, '/admin/') || str_starts_with($path, '/_');
    }

    /** ช่วงสุดท้ายมีจุด = ไฟล์ (/wp-login.php, /sitemap.xml, /x.jpg) — ไม่เติม / ท้าย */
    public static function looksLikeFile(string $path): bool
    {
        $last = substr((string) strrchr($path, '/'), 1);

        return str_contains($last, '.');
    }

    /**
     * หน้ารวมบทความ — /page/1/ ซ้ำกับหน้าแรกของรายการ จึง 301 กลับไปที่ base (แบบ WordPress)
     * หน้าเกินจำนวนจริง → 404 (ไม่ใช่หน้าว่างที่ Google มองเป็น soft 404)
     *
     * @param  array<string, mixed>|null $category
     * @return array<string, mixed>
     */
    private function listing(string $kind, string $base, string $pageParam, string $query, ?array $category): array
    {
        if ($pageParam === '1') {
            return ['kind' => 'redirect', 'code' => 301, 'location' => $base . ($query !== '' ? '?' . $query : '')];
        }

        $page    = $pageParam === '' ? 1 : (int) $pageParam;
        $perPage = max(1, min((int) SettingService::get('blog.per_page', 10), 50));
        $result  = $this->posts->listing(max(1, $page), $perPage, $category !== null ? (int) $category['id'] : null);

        if ($page < 1 || ($page > 1 && $page > $result['pages'])) {
            return ['kind' => 'not_found', 'log' => true];
        }

        return [
            'kind'     => $kind,
            'base'     => $base,
            'page'     => $page,
            'perPage'  => $perPage,
            'category' => $category,
            'posts'    => $result['posts'],
            'total'    => $result['total'],
            'pages'    => $result['pages'],
        ];
    }
}
