<?php
/**
 * หน้าดูตัวอย่างของ design system ระหว่างที่ยังไม่มี CMS (Phase 1)
 *
 * ‼️ route นี้ประกาศเฉพาะ environment ที่ไม่ใช่ production (config/routes.php)
 *    production จึงได้ 404 จาก Router เหมือน URL ที่ไม่มีอยู่ — ไม่ต้องเช็คซ้ำที่นี่
 * ‼️ เนื้อหามาจาก config/home-blocks.php ซึ่งเป็นค่าตั้งต้นของหน้าแรก — Phase 2/3 ย้ายลงตาราง pages
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BlockService;
use App\Services\SiteService;
use Core\Controller;

final class PreviewController extends Controller
{
    public function home(): void
    {
        header('X-Robots-Tag: noindex, nofollow', true);

        $config = dirname(__DIR__, 2) . '/config';
        $blocks = (array) require $config . '/home-blocks.php';

        // ?all=1 — ต่อท้ายด้วยบล็อกที่หน้าแรกไม่ใช้ เพื่อตรวจหน้าตาของบล็อกทุกชนิด
        if (isset($_GET['all'])) {
            $blocks = array_merge($blocks, (array) require $config . '/preview-blocks.php');
        }

        $this->view('preview/home', [
            'title'           => 'OMC MLM Software',
            'metaDescription' => '',
            'site'            => SiteService::layout('/'),
            'blocks'          => BlockService::prepare($blocks),
        ], 'site');
    }
}
