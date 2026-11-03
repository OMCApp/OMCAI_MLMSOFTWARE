<?php
/**
 * ต้องล็อกอินก่อนถึงเข้าได้
 *
 * ‼️ การเช็คสิทธิ์ต้องทำที่นี่เท่านั้น ห้ามเช็คกระจายในแต่ละ Controller
 *    เพราะจะมีจุดที่ลืมเช็คหลุดเสมอ
 */

declare(strict_types=1);

namespace App\Middlewares;

use Core\Auth;
use Core\Path;
use Core\Session;

final class AuthMiddleware
{
    public function handle(): void
    {
        // refresh() อ่านสถานะบัญชีจากฐานข้อมูลใหม่ทุก request — ถูกปิด/ลบแล้วหลุดทันที
        if (Auth::check() && Auth::refresh()) {
            return;
        }

        Session::flash('warning', __('auth.session_expired'));

        header('Location: ' . Path::url('/login'), true, 302);
        exit;
    }
}
