<?php
/**
 * หน้าแรกและหน้าสรุป
 *
 * ‼️ Controller รับ input แล้วเรียก Service เท่านั้น ห้ามมี query และ business logic
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->view('home', ['title' => (string) config('name')]);
    }

    public function dashboard(): void
    {
        $this->view('dashboard', ['title' => __('common.view')]);
    }
}
