<?php
/**
 * ผู้สนใจที่ส่งฟอร์มจากเว็บ (Phase 4 ทำฟอร์ม · Phase 3 ทำหน้าจัดการ)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Lead extends Model
{
    protected string $table = 'leads';

    protected array $fillable = [
        'form_key', 'name', 'company', 'business_type', 'phone', 'email', 'line_id', 'has_system',
        'need', 'member_estimate', 'message', 'source_url', 'utm', 'ip', 'status', 'assigned_to', 'notes',
    ];

    public const STATUSES  = ['new', 'contacted', 'qualified', 'won', 'lost'];
    public const FORM_KEYS = ['demo', 'quote', 'consult', 'contact', 'migration'];
    public const SORTABLE  = ['id', 'created_at', 'status', 'form_key'];

    /** จำนวนฟอร์มจาก IP นี้ในชั่วโมงที่ผ่านมา — ใช้ทำ rate limit ของฟอร์มสาธารณะ */
    public function recentCountFromIp(string $ip): int
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total FROM leads WHERE ip = :ip AND created_at > (NOW() - INTERVAL 1 HOUR)',
            ['ip' => $ip]
        );

        return (int) ($row['total'] ?? 0);
    }
}
