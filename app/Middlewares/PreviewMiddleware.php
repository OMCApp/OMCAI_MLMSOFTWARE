<?php
/**
 * สิทธิ์ดูหน้า/บทความที่ยังไม่เผยแพร่ด้วย ?preview=1
 *
 * ‼️ ตัดสินที่นี่ที่เดียว (กฎเหล็กข้อ 4) — SiteResolver แค่อ่านผลผ่าน PreviewService::granted()
 * ‼️ ไม่มีสิทธิ์ = ทำเหมือนไม่มี ?preview เลย (draft → 404) ห้ามตอบ 403
 *    403 บอกคนนอกว่ามีหน้านี้อยู่ ซึ่งเผยหน้าที่ยังไม่ประกาศ
 */

declare(strict_types=1);

namespace App\Middlewares;

use App\Services\PreviewService;
use Core\Auth;

final class PreviewMiddleware
{
    /** บทบาทที่ดูตัวอย่างได้ — editor มาใน Phase 3 */
    private const ROLES = ['admin', 'editor'];

    public function handle(): void
    {
        if ((string) ($_GET['preview'] ?? '') !== '1') {
            return;
        }

        // refresh() อ่านสถานะบัญชีจากฐานข้อมูลใหม่ — บัญชีที่ถูกปิดแล้วดูตัวอย่างไม่ได้ทันที
        if (Auth::check() && Auth::refresh() && Auth::can(...self::ROLES)) {
            PreviewService::grant();
        }
    }
}
