<?php
/**
 * ทุกครั้งที่มีการ POST ฟอร์ม lead (สำเร็จ · กรอกผิด · สแปม) — ใช้จำกัดจำนวนครั้งต่อ IP
 *
 * ‼️ ไม่ extends Core\Model โดยตั้งใจ — เป็นตัวนับที่เขียนทุก request เหมือน login_attempts
 *    ลง audit log ทุกแถวคือ log ท่วมโดยไม่มีประโยชน์ (NOTES.md)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;

final class LeadAttempt
{
    public const OUTCOMES = ['sent', 'invalid', 'spam', 'throttled'];

    public function record(string $ipKey, string $outcome): void
    {
        Database::run(
            'INSERT INTO lead_attempts (ip_address, outcome, attempted_at) VALUES (:ip, :outcome, NOW())',
            ['ip' => mb_substr($ipKey, 0, 45), 'outcome' => in_array($outcome, self::OUTCOMES, true) ? $outcome : 'invalid']
        );
    }

    public function countSince(string $ipKey, int $minutes): int
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total FROM lead_attempts WHERE ip_address = :ip AND attempted_at > (NOW() - INTERVAL ' . max(1, $minutes) . ' MINUTE)',
            ['ip' => mb_substr($ipKey, 0, 45)]
        );

        return (int) ($row['total'] ?? 0);
    }

    /** ล้างแถวเก่า — เรียกเป็นครั้งคราวตอนบันทึก (ไม่ต้องมี cron) */
    public function prune(int $days = 7): void
    {
        Database::run('DELETE FROM lead_attempts WHERE attempted_at < (NOW() - INTERVAL ' . max(1, $days) . ' DAY)');
    }
}
