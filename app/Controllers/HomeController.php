<?php
/**
 * หน้าแรกและหน้าสรุป
 *
 * ‼️ Controller รับ input แล้วเรียก Service เท่านั้น ห้ามมี query และ business logic
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;

/**
 * ‼️ หน้าแรกของเว็บ (/) เป็นหน้าสาธารณะจากตาราง pages แล้ว (SiteController) — ที่นี่เหลือแค่หน้าแรกของหลังบ้าน /admin
 */
final class HomeController extends Controller
{
    public function dashboard(): void
    {
        $this->view('dashboard', ['title' => __('common.view')]);
    }
}
