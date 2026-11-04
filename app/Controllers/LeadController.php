<?php
/**
 * POST /lead/ — ฟอร์มขอ Demo / ใบเสนอราคา / ปรึกษา / ติดต่อ / ย้ายระบบ ของทุกภาษา
 *
 * ‼️ logic ทั้งหมดอยู่ที่ App\Services\LeadService — ที่นี่แค่แปลผลเป็น redirect
 *    สำเร็จ (และ honeypot) → หน้าขอบคุณของภาษานั้น · กรอกผิด/ส่งถี่เกิน → กลับหน้าเดิมที่ #lead-form พร้อมค่าที่กรอกและข้อความผิดพลาด
 * ‼️ CSRF ตรวจที่ public/index.php แล้ว (token + Origin)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LeadService;
use App\Services\LocaleService;
use Core\Auth;
use Core\Session;

final class LeadController extends PublicController
{
    public function store(): void
    {
        $result = (new LeadService())->submit($_POST, Auth::rateLimitKey());

        // ข้อความแจ้งเตือนเป็นภาษาของหน้าที่ส่งฟอร์ม
        LocaleService::use($result['locale']);

        if ($result['status'] === 'sent' || $result['status'] === 'spam') {
            $this->redirectTo(LeadService::thankYouPath($result['locale']), 303);
        }

        $errors = array_map(static fn (string $key): string => __($key), $result['errors']);

        Session::flash('danger', $result['status'] === 'throttled' ? __('lead.throttled') : __('lead.error_summary'));
        Session::keepInput($result['input'], $errors);

        $this->redirectTo($result['source_path'] . '#lead-form', 303);
    }
}
