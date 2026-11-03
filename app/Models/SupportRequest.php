<?php
/**
 * เรื่องที่ผู้ใช้แจ้งเข้ามา
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class SupportRequest extends Model
{
    protected string $table = 'support_requests';

    protected array $fillable = [
        'user_id', 'type', 'page_url', 'error_reference', 'manual_slug',
        'message', 'user_agent', 'locale', 'theme', 'ip_address',
        'attachment_path', 'attachment_name', 'status', 'admin_note', 'resolved_at',
    ];

    public const TYPES    = ['bug', 'feature_request', 'how_to'];
    public const STATUSES = ['new', 'acknowledged', 'in_progress', 'done', 'rejected'];
    public const SORTABLE = ['id', 'created_at', 'status', 'type'];

    /** จำนวนเรื่องที่ส่งจาก IP หรือผู้ใช้นี้ในชั่วโมงที่ผ่านมา — ใช้ทำ rate limit */
    public function recentCount(?int $userId, string $ip): int
    {
        /* ‼️ ห้ามใช้ placeholder ชื่อเดียวกันซ้ำใน query เดียว
           ATTR_EMULATE_PREPARES = false ทำให้ MySQL นับ marker ทีละตัว → SQLSTATE[HY093]
           แปลว่า rate limit ของระบบแจ้งปัญหาจะพังทุกครั้งที่มีคนส่งเรื่องเข้ามา
           ต่อเงื่อนไขเฉพาะตอนที่ต้องใช้จริงแทน อ่านง่ายกว่าและไม่มีทางชนกัน */
        $sql    = 'SELECT COUNT(*) AS total FROM support_requests
                    WHERE created_at > (NOW() - INTERVAL 1 HOUR)';
        $params = ['ip' => $ip];

        if ($userId !== null) {
            $sql .= ' AND (ip_address = :ip OR user_id = :user_id)';
            $params['user_id'] = $userId;
        } else {
            $sql .= ' AND ip_address = :ip';
        }

        $row = Database::selectOne($sql, $params);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * บทความคู่มือที่ถูกถามต่อบ่อยที่สุด
     * = รายการบทความที่ต้องแก้ก่อน เรียงตามความจริงไม่ใช่ตามความรู้สึก
     *
     * @return array<int, array<string, mixed>>
     */
    public function mostAskedManuals(int $limit = 10): array
    {
        return Database::select(
            'SELECT manual_slug, COUNT(*) AS total
             FROM support_requests
             WHERE type = :type AND manual_slug IS NOT NULL AND deleted_at IS NULL
             GROUP BY manual_slug
             ORDER BY total DESC
             LIMIT ' . max(1, min($limit, 50)),
            ['type' => 'how_to']
        );
    }
}
