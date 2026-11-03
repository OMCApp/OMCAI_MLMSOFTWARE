<?php
/**
 * สลับภาษาและโหมดแสดงผล
 *
 * ‼️ สลับแล้วต้องอยู่หน้าเดิม ไม่เด้งกลับหน้าแรก
 *    และค่าที่เลือกต้องคงอยู่ข้ามอุปกรณ์ จึงบันทึกลง users ด้วยถ้าล็อกอินอยู่
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Lang;
use Core\Request;
use Core\Session;

final class PreferenceController extends Controller
{
    public function language(): void
    {
        $request = new Request();
        $locale  = $request->pick('locale', Lang::enabled(), Lang::locale());

        Lang::switchTo($locale);
        Auth::remember('locale', $locale);

        $this->back();
    }

    public function theme(): void
    {
        $request = new Request();
        $theme   = $request->pick('theme', ['light', 'dark'], theme());

        Session::put('theme', $theme);
        Auth::remember('theme', $theme);

        $this->back();
    }
}
