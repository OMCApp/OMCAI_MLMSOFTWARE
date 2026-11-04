<?php
/**
 * ไฟล์สื่อ — path อ้างจาก public/ (รูปเดิมของ WordPress อยู่ที่ /wp-content/uploads/<path เดิม>)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class Media extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['path', 'alt', 'title'];
    protected array $filterColumns = ['mime'];

    public const SORTABLE = ['id', 'created_at', 'bytes', 'path'];

    protected string $table = 'media';

    protected array $fillable = ['path', 'mime', 'width', 'height', 'alt', 'title', 'sizes', 'bytes'];

    /** รูปที่ URL นี้ชี้ — ต้นฉบับ (path) หรือ WebP ขนาดเต็ม (sizes.webp) */
    public function findByUrl(string $url): ?array
    {
        return Database::selectOne(
            "SELECT * FROM media WHERE deleted_at IS NULL
               AND (path = :p OR JSON_UNQUOTE(JSON_EXTRACT(sizes, '$.webp')) = :w)
             ORDER BY id LIMIT 1",
            ['p' => $url, 'w' => $url]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function allForPicker(): array
    {
        return Database::select('SELECT id, path, title, alt FROM media WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 2000');
    }

    /** @return array<int, array<string, mixed>> รูปที่มี alt แล้ว (แทรกในบทความได้) */
    public function allForEditor(): array
    {
        return Database::select(
            "SELECT id, path, title, alt, width, height, sizes FROM media WHERE deleted_at IS NULL AND alt <> '' ORDER BY id DESC LIMIT 500"
        );
    }

    /** @return array<int, string> */
    public function allPaths(): array
    {
        return array_map(static fn (array $r): string => (string) $r['path'], Database::select('SELECT path FROM media WHERE deleted_at IS NULL'));
    }

    public function missingAltCount(): int
    {
        return (int) (Database::selectOne("SELECT COUNT(*) AS n FROM media WHERE deleted_at IS NULL AND alt = ''")['n'] ?? 0);
    }

    /**
     * ที่ที่รูปนี้ถูกอ้างถึง — id ในคอลัมน์รูป + URL ในบล็อกของหน้า/เนื้อหาบทความ/ค่าตั้ง
     *
     * ‼️ LOCATE ไม่ใช่ LIKE — _ และ % ในชื่อไฟล์เป็น wildcard ของ LIKE
     *
     * @param  array<int, string> $urls
     * @return array<int, array{kind:string, id:int, title:string}>
     */
    public function usage(int $id, array $urls): array
    {
        $out = [];

        foreach (Database::select(
            'SELECT id, title FROM pages WHERE deleted_at IS NULL AND og_image_id = :id',
            ['id' => $id]
        ) as $row) {
            $out[] = ['kind' => 'page', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
        }

        foreach (Database::select(
            'SELECT id, title FROM posts WHERE deleted_at IS NULL AND (featured_image_id = :a OR og_image_id = :b)',
            ['a' => $id, 'b' => $id]
        ) as $row) {
            $out[] = ['kind' => 'post', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
        }

        foreach (Database::select('SELECT id, person_name FROM testimonials WHERE deleted_at IS NULL AND photo_id = :id', ['id' => $id]) as $row) {
            $out[] = ['kind' => 'testimonial', 'id' => (int) $row['id'], 'title' => (string) $row['person_name']];
        }

        foreach (Database::select('SELECT id, title FROM case_studies WHERE deleted_at IS NULL AND cover_image_id = :id', ['id' => $id]) as $row) {
            $out[] = ['kind' => 'case_study', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
        }

        foreach ($urls as $url) {
            // JSON เก็บ / แบบไม่ escape (JSON_UNESCAPED_SLASHES) — เทียบทั้งสองรูปเผื่อแถวเก่า
            $escaped = str_replace('/', '\\/', $url);

            foreach (Database::select(
                'SELECT id, title FROM pages WHERE deleted_at IS NULL
                   AND (LOCATE(:a, CAST(blocks AS CHAR)) > 0 OR LOCATE(:b, CAST(blocks AS CHAR)) > 0)',
                ['a' => $url, 'b' => $escaped]
            ) as $row) {
                $out[] = ['kind' => 'page', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
            }

            foreach (Database::select(
                'SELECT id, title FROM posts WHERE deleted_at IS NULL AND LOCATE(:a, content) > 0',
                ['a' => $url]
            ) as $row) {
                $out[] = ['kind' => 'post', 'id' => (int) $row['id'], 'title' => (string) $row['title']];
            }

            foreach (Database::select(
                'SELECT setting_key FROM settings WHERE deleted_at IS NULL
                   AND (LOCATE(:a, CAST(value AS CHAR)) > 0 OR LOCATE(:b, CAST(value AS CHAR)) > 0)',
                ['a' => $url, 'b' => $escaped]
            ) as $row) {
                $out[] = ['kind' => 'setting', 'id' => 0, 'title' => (string) $row['setting_key']];
            }
        }

        $unique = [];

        foreach ($out as $item) {
            $unique[$item['kind'] . ':' . $item['id'] . ':' . $item['title']] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param  array<int, int> $ids
     * @return array<int, array<string, mixed>> id => แถว
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));

        if ($ids === []) {
            return [];
        }

        // placeholder ทีละตัว (:m0, :m1 ...) — ห้ามต่อค่าเข้า SQL
        $marks    = [];
        $bindings = [];

        foreach ($ids as $index => $id) {
            $marks[]               = ':m' . $index;
            $bindings['m' . $index] = $id;
        }

        $rows = Database::select(
            'SELECT * FROM media WHERE deleted_at IS NULL AND id IN (' . implode(', ', $marks) . ')',
            $bindings
        );

        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row['id']] = $row;
        }

        return $out;
    }

    /**
     * รูปหลายรูปตาม path ต้นฉบับ (รูปในเนื้อหาบทความ) — query เดียวทั้งชุด
     *
     * @param  array<int, string> $paths
     * @return array<string, array<string, mixed>> path → แถว
     */
    public function findManyByPath(array $paths): array
    {
        $paths = array_values(array_unique(array_filter($paths, static fn (string $p) => $p !== '')));

        if ($paths === []) {
            return [];
        }

        $marks    = [];
        $bindings = [];

        foreach ($paths as $index => $path) {
            $marks[]                = ':p' . $index;
            $bindings['p' . $index] = $path;
        }

        $rows = Database::select('SELECT * FROM media WHERE deleted_at IS NULL AND path IN (' . implode(', ', $marks) . ')', $bindings);
        $out  = [];

        foreach ($rows as $row) {
            $out[(string) $row['path']] = $row;
        }

        return $out;
    }
}
