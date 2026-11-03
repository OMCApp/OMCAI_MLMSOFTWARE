<?php
/**
 * ล็อกอินอยู่แล้วห้ามเข้าหน้า login ซ้ำ
 */

declare(strict_types=1);

namespace App\Middlewares;

use Core\Auth;
use Core\Path;

final class GuestMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            return;
        }

        header('Location: ' . Path::url('/admin'), true, 302);
        exit;
    }
}
