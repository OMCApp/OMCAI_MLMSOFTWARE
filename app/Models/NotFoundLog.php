<?php
/**
 * บันทึก URL ที่ไม่เจอ — แถวเดียวต่อ path นับจำนวนครั้งต่อไปเรื่อยๆ
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class NotFoundLog extends Model
{
    protected string $table = 'not_found_log';

    protected array $fillable = ['path', 'referrer', 'user_agent', 'hits', 'first_seen_at', 'last_seen_at'];

    /**
     * ‼️ upsert ทีเดียว ไม่ SELECT ก่อน — กัน request พร้อมกันสร้างแถวซ้ำ
     *    ตัวนับ traffic ไม่ลง audit log (เหตุผลเดียวกับ Redirect::recordHit)
     *    แถวที่แอดมินซ่อนไปแล้ว (deleted_at) กลับมาเมื่อมีคนเข้าอีก
     */
    public function record(string $path, ?string $referrer, ?string $userAgent): void
    {
        Database::run(
            'INSERT INTO not_found_log
                (path, referrer, user_agent, hits, first_seen_at, last_seen_at, created_at, updated_at)
             VALUES (:path, :referrer, :user_agent, 1, NOW(), NOW(), NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                hits = hits + 1,
                last_seen_at = NOW(),
                updated_at = NOW(),
                deleted_at = NULL,
                referrer = COALESCE(VALUES(referrer), referrer),
                user_agent = VALUES(user_agent)',
            ['path' => $path, 'referrer' => $referrer, 'user_agent' => $userAgent]
        );
    }

    /** @return array<string, mixed>|null */
    public function findByPath(string $path): ?array
    {
        return Database::selectOne(
            'SELECT * FROM not_found_log WHERE path = :path LIMIT 1',
            ['path' => $path]
        );
    }
}
