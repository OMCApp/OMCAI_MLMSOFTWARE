<?php
/**
 * เข้าและออกจากระบบ
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Request;

final class AuthController extends Controller
{
    public function form(): void
    {
        $this->view('login', ['title' => __('auth.login')], 'auth');
    }

    public function login(): void
    {
        $request = new Request();

        $ok = $request->validate(
            ['username' => 'required|max_len:100', 'password' => 'required|max_len:200'],
            ['username' => __('auth.username'), 'password' => __('auth.password')]
        );

        if (!$ok) {
            // คืนค่าที่กรอกไว้ ไม่ให้ผู้ใช้ต้องกรอกใหม่หมด
            $this->backWithErrors($request->errors(), ['username' => $request->input('username')]);
        }

        $result = Auth::attempt(
            (string) $request->input('username'),
            (string) $request->input('password')
        );

        if (!$result['ok']) {
            $message = $result['reason'] === 'throttled'
                ? __('auth.throttled', ['minutes' => (string) $result['minutes']])
                : __('auth.failed');

            $this->flash('danger', $message);
            $this->backWithErrors([], ['username' => $request->input('username')]);
        }

        $this->redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();

        $this->flash('success', __('auth.logged_out'));
        $this->redirect('/admin/login');
    }
}
