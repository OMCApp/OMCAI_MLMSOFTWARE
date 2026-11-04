<?php
/**
 * บัญชีของฉัน — เปลี่ยนรหัสผ่านของตัวเอง (ทุกบทบาท)
 *
 * ‼️ บัญชีที่ must_change_password = 1 ถูก AuthMiddleware พามาที่นี่ทุก request จนกว่าจะเปลี่ยนสำเร็จ
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\UserService;
use Core\Auth;

final class AccountController extends AdminController
{
    public function form(): void
    {
        $this->view('admin/crud/form', [
            'title'        => __('cms.account.title'),
            'pageLead'     => Auth::mustChangePassword() ? __('cms.account.must_change') : __('cms.account.lead'),
            'pageWidth'    => 'form',
            'formAction'   => '/admin/account/password',
            'formMethod'   => 'PATCH',
            'formSections' => [['fields' => array_merge([
                ['name' => 'current_password', 'label' => __('cms.account.current'), 'type' => 'password', 'value' => '', 'required' => true, 'width' => 'medium'],
            ], UserController::passwordFields())]],
            'formSubmit'   => __('cms.account.change'),
            'formCancel'   => '/admin',
        ]);
    }

    public function update(): void
    {
        $errors = (new UserService())->changeOwnPassword(
            (string) $this->request->input('current_password', ''),
            (string) $this->request->input('password', ''),
            (string) $this->request->input('password_confirm', '')
        );

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->flash('success', __('cms.account.changed'));
        $this->redirect('/admin');
    }
}
