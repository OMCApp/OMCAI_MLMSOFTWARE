<?php
/**
 * redirect ที่จัดการใน CMS — 301 / 302 / 410
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Redirect extends Model
{
    protected string $table = 'redirects';

    protected array $fillable = ['from_path', 'match_type', 'to_path', 'code', 'note', 'enabled'];

    public const CODES       = [301, 302, 410];
    public const MATCH_TYPES = ['exact', 'prefix'];

    /** @return array<string, mixed>|null */
    public function findExact(string $path): ?array
    {
        return Database::selectOne(
            "SELECT * FROM redirects
             WHERE from_path = :path AND match_type = 'exact' AND enabled = 1 AND deleted_at IS NULL
             LIMIT 1",
            ['path' => $path]
        );
    }

    /**
     * กฎแบบ prefix มีไม่กี่ตัว (taxonomy เดิมของ WordPress) — ดึงทั้งหมดแล้วเทียบใน PHP
     * ห้ามเทียบด้วย LIKE เพราะ _ และ % ใน path (portfolio_tag) เป็น wildcard ของ LIKE
     *
     * @return array<int, array<string, mixed>> ยาวสุดก่อน เพื่อให้กฎที่เจาะจงกว่าชนะ
     */
    public function prefixRules(): array
    {
        return Database::select(
            "SELECT * FROM redirects
             WHERE match_type = 'prefix' AND enabled = 1 AND deleted_at IS NULL
             ORDER BY CHAR_LENGTH(from_path) DESC, id"
        );
    }

    /** @return array<int, string> path ต้นทางของกฎแบบตรงตัวที่เปิดอยู่ — sitemap ต้องไม่มี URL เหล่านี้ */
    public function exactSources(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['from_path'],
            Database::select(
                "SELECT from_path FROM redirects WHERE match_type = 'exact' AND enabled = 1 AND deleted_at IS NULL"
            )
        );
    }

    /**
     * นับการใช้งาน — ‼️ ตัวนับ traffic ไม่ใช่การแก้ข้อมูลโดยผู้ใช้ จึงไม่ลง audit log
     * (ลงทุก request แล้ว audit_logs จะบวมจนใช้หาเหตุการณ์จริงไม่ได้ — project/NOTES.md)
     */
    public function recordHit(int $id): void
    {
        Database::run(
            'UPDATE redirects SET hits = hits + 1, last_hit_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }
}
