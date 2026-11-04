<?php
/**
 * ผู้ใช้หลังบ้าน — รายการ · เพิ่ม · แก้ (บทบาท/เปิด-ปิด) · ตั้งรหัสใหม่ · ออกจากทุกอุปกรณ์ · ลบ
 *
 * สิทธิ์: admin เท่านั้น (RoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\User;
use App\Services\UserService;

final class UserController extends AdminController
{
    private UserService $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserService();
    }

    public function index(): void
    {
        $p       = $this->listParams(User::SORTABLE, 'username', 'asc');
        $filters = $this->filters(['role' => UserService::ROLES, 'is_active' => ['0', '1']]);
        $result  = $this->users->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['state'] = (int) $row['is_active'] === 1 ? 'active' : 'inactive';
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.users.title'),
            'pageLead'    => __('cms.users.lead'),
            'pageActions' => [['label' => __('cms.users.add'), 'url' => '/admin/users/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => [
                ['name' => 'role', 'label' => __('cms.users.role'), 'value' => $filters['role'], 'options' => ['' => '—'] + self::roleOptions()],
                ['name' => 'is_active', 'label' => __('cms.users.state'), 'value' => $filters['is_active'],
                 'options' => ['' => '—', '1' => __('cms.users.state_active'), '0' => __('cms.users.state_inactive')]],
            ],
            'listColumns' => [
                ['key' => 'display_name', 'label' => __('cms.users.display_name'), 'sort' => 'display_name', 'primary' => true],
                ['key' => 'username', 'label' => __('auth.username'), 'sort' => 'username'],
                ['key' => 'role', 'label' => __('cms.users.role'), 'sort' => 'role', 'format' => 'badge', 'badgeKind' => 'type', 'lang' => 'cms.role.'],
                ['key' => 'state', 'label' => __('cms.users.state'), 'format' => 'badge', 'lang' => 'cms.users.state_'],
                ['key' => 'created_at', 'label' => __('common.created_at'), 'sort' => 'created_at', 'format' => 'date'],
            ],
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/users/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/users/{id}', 'confirm' => 'username'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        $this->view('admin/crud/form', [
            'title'          => __('cms.users.add'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.users.title'), 'url' => '/admin/users'], ['label' => __('cms.users.add')]],
            'formAction'     => '/admin/users',
            'formMethod'     => 'POST',
            'formSections'   => [
                ['fields' => array_merge([
                    ['name' => 'username', 'label' => __('auth.username'), 'value' => '', 'required' => true, 'hint' => __('cms.users.username_hint'), 'width' => 'medium'],
                ], $this->profileFields(['role' => 'editor', 'is_active' => 1]))],
                ['title' => __('cms.users.password_section'), 'hint' => __('cms.users.password_create_hint'), 'fields' => self::passwordFields()],
            ],
            'formCancel'     => '/admin/users',
        ]);
    }

    public function store(): void
    {
        $result = $this->users->create($this->request->all());

        if ($result['errors'] !== []) {
            $this->fail($result['errors']);
        }

        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/users/' . $result['id']);
    }

    public function edit(string $id): void
    {
        $user = $this->users->find((int) $id);

        if ($user === null) {
            $this->notFound();
        }

        $this->view('admin/user', [
            'title'          => __('cms.users.edit'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.users.title'), 'url' => '/admin/users'], ['label' => (string) $user['username']]],
            'userRow'        => $user,
            'userProfile'    => $this->profileFields($user),
            'userPassword'   => self::passwordFields(),
        ]);
    }

    public function update(string $id): void
    {
        if ($this->users->find((int) $id) === null) {
            $this->notFound();
        }

        $errors = $this->users->update((int) $id, $this->request->all());

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/users/' . (int) $id);
    }

    public function password(string $id): void
    {
        $errors = $this->users->resetPassword((int) $id, (string) $this->request->input('password', ''), (string) $this->request->input('password_confirm', ''));

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->flash('success', __('cms.users.password_reset'));
        $this->redirect('/admin/users/' . (int) $id);
    }

    public function devices(string $id): void
    {
        if (!$this->users->forgetDevices((int) $id)) {
            $this->notFound();
        }

        $this->flash('success', __('cms.users.devices_forgotten'));
        $this->redirect('/admin/users/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        $errors = $this->users->delete((int) $id);

        if ($errors !== []) {
            $this->flash('danger', __((string) reset($errors)));
            $this->redirect('/admin/users');
        }

        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/users');
    }

    /** @return array<string, string> */
    public static function roleOptions(): array
    {
        $out = [];

        foreach (UserService::ROLES as $role) {
            $out[$role] = __('cms.role.' . $role);
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public static function passwordFields(): array
    {
        return [
            ['name' => 'password', 'label' => __('auth.password'), 'type' => 'password', 'value' => '', 'hint' => __('cms.account.length_hint'), 'width' => 'medium'],
            ['name' => 'password_confirm', 'label' => __('cms.account.confirm'), 'type' => 'password', 'value' => '', 'width' => 'medium'],
        ];
    }

    /**
     * @param  array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    private function profileFields(array $user): array
    {
        return [
            ['name' => 'display_name', 'label' => __('cms.users.display_name'), 'value' => $user['display_name'] ?? '', 'required' => true],
            ['name' => 'email', 'label' => __('cms.users.email'), 'type' => 'email', 'value' => $user['email'] ?? ''],
            ['name' => 'role', 'label' => __('cms.users.role'), 'type' => 'select', 'value' => $user['role'] ?? 'editor',
             'options' => self::roleOptions(), 'hint' => __('cms.users.role_hint'), 'width' => 'medium'],
            ['name' => 'is_active', 'label' => __('cms.users.state_active'), 'type' => 'checkbox', 'value' => (int) ($user['is_active'] ?? 1) === 1,
             'hint' => __('cms.users.active_hint')],
        ];
    }
}
