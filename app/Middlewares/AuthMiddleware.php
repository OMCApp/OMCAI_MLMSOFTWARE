<?php
/**
 * ต้องล็อกอินก่อนถึงเข้าได้
 *
 * ‼️ การเช็คสิทธิ์ต้องทำที่นี่เท่านั้น ห้ามเช็คกระจายในแต่ละ Controller
 *    เพราะจะมีจุดที่ลืมเช็คหลุดเสมอ
 *
 * ‼️ บัญชีที่ต้องตั้งรหัสผ่านใหม่ (users.must_change_password) ทำได้แค่สองอย่าง:
 *    เปิด/ส่งหน้าเปลี่ยนรหัสผ่าน และออกจากระบบ — ทุก request อื่นถูกพาไปหน้าเปลี่ยนรหัสผ่าน
 *    บังคับที่นี่ที่เดียว ไม่ใช่ในแต่ละ Controller
 */

declare(strict_types=1);

namespace App\Middlewares;

use Core\Auth;
use Core\Path;
use Core\Session;

final class AuthMiddleware
{
    /** path ที่บัญชีที่ต้องเปลี่ยนรหัสผ่านยังเข้าได้ */
    public const PASSWORD_PATH = '/admin/account/password';
    private const ALLOWED_WHILE_FORCED = [self::PASSWORD_PATH, '/admin/logout'];

    public function handle(): void
    {
        // refresh() อ่านสถานะบัญชีจากฐานข้อมูลใหม่ทุก request — ถูกปิด/ลบแล้วหลุดทันที
        if (Auth::check() && Auth::refresh()) {
            if (Auth::mustChangePassword() && !in_array(current_path(), self::ALLOWED_WHILE_FORCED, true)) {
                Session::flash('warning', __('cms.account.must_change'));

                header('Location: ' . Path::url(self::PASSWORD_PATH), true, 302);
                exit;
            }

            return;
        }

        Session::flash('warning', __('auth.session_expired'));

        header('Location: ' . Path::url('/admin/login'), true, 302);
        exit;
    }
}
