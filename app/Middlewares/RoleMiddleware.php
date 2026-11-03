<?php
/**
 * จำกัดตามบทบาท
 *
 * ‼️ การซ่อนเมนูไม่ใช่การเช็คสิทธิ์ — ต้องกันที่นี่เสมอ
 *    และทดสอบด้วยการเรียก URL ตรง ไม่ใช่แค่ดูว่าเมนูหายไป
 */

declare(strict_types=1);

namespace App\Middlewares;

use App\Controllers\ErrorController;
use Core\Auth;
use Core\Logger;

final class RoleMiddleware
{
    /** @param array<int, string> $roles */
    public function __construct(private readonly array $roles = ['admin'])
    {
    }

    public function handle(): void
    {
        if (Auth::can(...$this->roles)) {
            return;
        }

        Logger::warning('forbidden', [
            'user_id' => Auth::id(),
            'role'    => Auth::role(),
            'path'    => current_path(),
        ]);

        // ‼️ 403 ต้องต่างจาก 404 ให้ชัด
        //    "เข้าได้แต่ไม่มีสิทธิ์" กับ "ไม่มีหน้านี้" คนละเรื่องกัน
        (new ErrorController())->show(403);
        exit;
    }
}
