<?php
/**
 * งานเนื้อหาของ CMS — admin + editor (หน้า บทความ หมวด media เมนู redirect/404 FAQ testimonials case studies)
 *
 * ‼️ ชุดบทบาทอยู่ที่นี่ที่เดียว (project/SCOPE.md: editor = content + SEO + media + menus + FAQ)
 *    sales เปิดไม่ได้ — ได้ 403 จาก RoleMiddleware
 */

declare(strict_types=1);

namespace App\Middlewares;

final class ContentRoleMiddleware
{
    public const ROLES = ['admin', 'editor'];

    public function handle(): void
    {
        (new RoleMiddleware(self::ROLES))->handle();
    }
}
