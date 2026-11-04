<?php
/**
 * lead จากฟอร์มเว็บ — admin + sales (project/SCOPE.md: sales = lead อย่างเดียว)
 *
 * ‼️ editor เปิดไม่ได้ — ข้อมูลติดต่อของลูกค้าไม่ใช่งานเนื้อหา
 */

declare(strict_types=1);

namespace App\Middlewares;

final class LeadRoleMiddleware
{
    public const ROLES = ['admin', 'sales'];

    public function handle(): void
    {
        (new RoleMiddleware(self::ROLES))->handle();
    }
}
