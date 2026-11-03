<?php
/**
 * บทความ — permalink แบบ WordPress เดิม /{yyyy}/{mm}/{dd}/{slug}/
 *
 * ‼️ URL บทความเดิม 37 เรื่องต้องตรงทุกตัวอักษร (PLAN.md) — slug ภาษาไทยเก็บแบบ decode
 *    เบราว์เซอร์ส่งมาแบบ percent-encode แต่ Core\Path::requested() decode ให้แล้ว จึงเทียบตรงๆ ได้
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Models\Revision;
use Core\Auth;
use Core\Database;

final class PostService
{
    public function __construct(private readonly Post $posts = new Post())
    {
    }

    /** /2025/05/20/mlm-software-คำตอบการจัดการสต็อก/ (decode แล้ว) */
    public static function permalink(array $post): string
    {
        $at = (string) ($post['published_at'] ?? '');

        if ($at === '') {
            return '/blog/';
        }

        return '/' . date('Y/m/d', (int) strtotime($at)) . '/' . $post['slug'] . '/';
    }

    /** @param array<string, mixed> $row */
    public static function isLive(array $row): bool
    {
        return ($row['status'] ?? '') === 'published'
            && ($row['published_at'] ?? null) !== null
            && strtotime((string) $row['published_at']) <= time();
    }

    /**
     * หาบทความจากส่วนของ URL
     *
     * @return array{post: array<string,mixed>}|array{redirect: string}|null
     *         redirect = slug ถูกแต่วันที่ผิด → 301 ไป URL ที่ถูก (เหมือน WordPress เดิม)
     */
    public function resolve(string $year, string $month, string $day, string $slug, bool $preview): ?array
    {
        $date    = $year . '-' . $month . '-' . $day;
        $visible = array_values(array_filter(
            $this->posts->findBySlug($slug),
            static fn (array $row): bool => $preview || self::isLive($row)
        ));

        if ($visible === []) {
            return null;
        }

        foreach ($visible as $row) {
            $at = (string) ($row['published_at'] ?? '');

            // draft ที่ยังไม่มีวันเผยแพร่ ดูตัวอย่างได้ที่วันที่ใดก็ได้
            if (($at !== '' && date('Y-m-d', (int) strtotime($at)) === $date) || ($at === '' && $preview)) {
                return ['post' => $row];
            }
        }

        // ตัวที่เผยแพร่แล้วมาก่อน — ห้าม redirect ไปหา draft
        usort($visible, static fn (array $a, array $b): int => (int) self::isLive($b) <=> (int) self::isLive($a));
        $target = $visible[0];

        if (($target['published_at'] ?? null) === null) {
            return null;
        }

        return ['redirect' => self::permalink($target)];
    }

    /**
     * @return array{posts: array<int, array<string,mixed>>, total: int, pages: int}
     */
    public function listing(int $page, int $perPage, ?int $categoryId = null): array
    {
        $total = $this->posts->countPublished($categoryId);
        $posts = $this->posts->latestPublished($perPage, ($page - 1) * $perPage, $categoryId);

        return ['posts' => $posts, 'total' => $total, 'pages' => (int) max(1, ceil($total / max(1, $perPage)))];
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit): array
    {
        return $this->posts->latestPublished($limit);
    }

    /** @return array<int, array<string, mixed>> */
    public function sitemapEntries(): array
    {
        return array_values(array_filter(
            $this->posts->indexable(),
            static fn (array $row): bool => (string) ($row['canonical_override'] ?? '') === ''
        ));
    }

    /** @return array<int, array{category_id:int, total:int, last_updated:string}> */
    public function statsByCategory(): array
    {
        $out = [];

        foreach ($this->posts->statsByCategory() as $row) {
            $out[(int) $row['category_id']] = $row;
        }

        return $out;
    }

    // ------------------------------------------------------------- CMS (Phase 3 / 5)

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];
        $slug   = (string) ($data['slug'] ?? '');

        // slug ห้ามมี / หรือช่องว่าง — เป็นส่วนเดียวของ URL
        if ($slug === '' || preg_match('#[/\s?#]#u', $slug) === 1) {
            $errors['slug'] = 'post.error_slug';
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'post.error_title';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Post::STATUSES, true)) {
            $errors['status'] = 'post.error_status';
        }
        if (($data['status'] ?? '') === 'published' && (string) ($data['published_at'] ?? '') === '') {
            $errors['published_at'] = 'post.error_published_at';
        }
        if (!in_array((string) ($data['robots'] ?? 'index'), Post::ROBOTS, true)) {
            $errors['robots'] = 'post.error_robots';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->posts->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return (bool) Database::transaction(function () use ($id, $data): bool {
            $before = $this->posts->find($id);

            if ($before === null) {
                return false;
            }

            (new Revision())->create([
                'entity'    => 'post',
                'entity_id' => $id,
                'data'      => (string) json_encode($before, JSON_UNESCAPED_UNICODE),
                'user_id'   => Auth::id(),
            ]);

            return $this->posts->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        return $this->posts->delete($id);
    }
}
