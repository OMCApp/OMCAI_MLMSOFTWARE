<?php
/**
 * หน้าแรกของหลังบ้าน (/admin) — ตัวเลขสรุปตามบทบาท + ทางลัด
 *
 * ‼️ Controller รับ input แล้วเรียก Service เท่านั้น ห้ามมี query และ business logic
 * ‼️ หน้าแรกของเว็บ (/) เป็นหน้าสาธารณะจากตาราง pages (SiteController)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DashboardService;
use Core\Controller;

final class HomeController extends Controller
{
    public function dashboard(): void
    {
        $this->view('dashboard', [
            'title'     => __('cms.dashboard.title'),
            'pageLead'  => __('cms.dashboard.lead'),
            'pageWidth' => 'wide',
            'summary'   => (new DashboardService())->summary(),
        ]);
    }
}
