<?php
/**
 * ผู้ใช้หลังบ้าน — บทบาท admin · editor · sales (project/SCOPE.md)
 *
 * ‼️ รหัสผ่าน: อย่างน้อย 10 ตัว · hash ด้วย password_hash() · ห้ามลง audit log (base Model mask ให้)
 * ‼️ เปลี่ยนรหัสผ่าน / บทบาท / ปิดบัญชี = เพิ่ม device_token_version → cookie อุปกรณ์เดิมใช้ไม่ได้ทั้งหมด
 * ‼️ ห้ามปิด/ลบ/ลดสิทธิ์แอดมินคนสุดท้าย และห้ามลบหรือปิดบัญชีตัวเอง (ล็อกตัวเองออก)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Core\Auth;

final class UserService
{
    public const ROLES        = ['admin', 'editor', 'sales'];
    public const MIN_PASSWORD = 10;

    public function __construct(private readonly User $users = new User())
    {
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->users->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->users->find($id);
    }

    /**
     * @param  array<string, mixed> $data username, display_name, email, role, is_active, password, password_confirm
     * @return array{id:int, errors:array<string,string>}
     */
    public function create(array $data): array
    {
        $errors = $this->validateProfile($data, null);
        $errors += $this->validatePassword((string) ($data['password'] ?? ''), (string) ($data['password_confirm'] ?? ''));

        if ($errors !== []) {
            return ['id' => 0, 'errors' => $errors];
        }

        $id = $this->users->create([
            'username'             => trim((string) $data['username']),
            'display_name'         => trim((string) $data['display_name']),
            'email'                => trim((string) ($data['email'] ?? '')) ?: null,
            'role'                 => (string) $data['role'],
            'is_active'            => !empty($data['is_active']) ? 1 : 0,
            'password_hash'        => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            // บัญชีที่แอดมินตั้งรหัสให้ ต้องเปลี่ยนเองตอนเข้าครั้งแรก — แอดมินไม่ควรรู้รหัสของคนอื่น
            'must_change_password' => 1,
            'locale'               => 'th',
            'theme'                => 'light',
        ]);

        return ['id' => $id, 'errors' => []];
    }

    /**
     * @param  array<string, mixed> $data display_name, email, role, is_active
     * @return array<string, string>
     */
    public function update(int $id, array $data): array
    {
        $user = $this->users->find($id);

        if ($user === null) {
            return ['id' => 'cms.users.error_missing'];
        }

        $data['username'] = $user['username'];
        $errors = $this->validateProfile($data, $id);
        $role   = (string) $data['role'];
        $active = !empty($data['is_active']) ? 1 : 0;

        if ($id === Auth::id() && ($role !== $user['role'] || $active !== (int) $user['is_active'])) {
            $errors['role'] = 'cms.users.error_self';
        }
        if ($user['role'] === 'admin' && ($role !== 'admin' || $active === 0) && $this->users->countActiveAdmins() <= 1) {
            $errors['role'] = 'cms.users.error_last_admin';
        }
        if ($errors !== []) {
            return $errors;
        }

        $changes = [
            'display_name' => trim((string) $data['display_name']),
            'email'        => trim((string) ($data['email'] ?? '')) ?: null,
            'role'         => $role,
            'is_active'    => $active,
        ];

        if ($role !== $user['role'] || $active !== (int) $user['is_active']) {
            $changes['device_token_version'] = (int) $user['device_token_version'] + 1;
        }

        $this->users->update($id, $changes);

        return [];
    }

    /**
     * แอดมินตั้งรหัสผ่านใหม่ให้ผู้ใช้ — ผู้ใช้ต้องเปลี่ยนเองอีกครั้งตอนเข้าระบบ
     *
     * @return array<string, string>
     */
    public function resetPassword(int $id, string $password, string $confirm): array
    {
        $user = $this->users->find($id);

        if ($user === null) {
            return ['password' => 'cms.users.error_missing'];
        }

        $errors = $this->validatePassword($password, $confirm);

        if ($errors !== []) {
            return $errors;
        }

        $this->users->update($id, [
            'password_hash'        => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => $id === Auth::id() ? 0 : 1,
            'device_token_version' => (int) $user['device_token_version'] + 1,
        ]);

        return [];
    }

    /**
     * ผู้ใช้เปลี่ยนรหัสผ่านของตัวเอง — ต้องใส่รหัสเดิม · ล้างธง must_change_password
     *
     * @return array<string, string>
     */
    public function changeOwnPassword(string $current, string $password, string $confirm): array
    {
        $id   = Auth::id();
        $user = $id === null ? null : $this->users->find($id);

        if ($user === null) {
            return ['current_password' => 'cms.users.error_missing'];
        }
        if (!password_verify($current, (string) $user['password_hash'])) {
            return ['current_password' => 'cms.account.error_current'];
        }

        $errors = $this->validatePassword($password, $confirm);

        if ($errors === [] && password_verify($password, (string) $user['password_hash'])) {
            $errors['password'] = 'cms.account.error_same';
        }
        if ($errors !== []) {
            return $errors;
        }

        $this->users->update((int) $id, [
            'password_hash'        => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => 0,
            'device_token_version' => (int) $user['device_token_version'] + 1,
        ]);

        // ล้างธงใน session ทันที — ไม่ต้องรอ refresh() รอบถัดไป
        Auth::refresh();

        return [];
    }

    /** ทำให้ cookie อุปกรณ์ทุกใบของผู้ใช้นี้ใช้ไม่ได้ */
    public function forgetDevices(int $id): bool
    {
        $user = $this->users->find($id);

        return $user !== null && $this->users->update($id, ['device_token_version' => (int) $user['device_token_version'] + 1]);
    }

    /** @return array<string, string> */
    public function delete(int $id): array
    {
        $user = $this->users->find($id);

        if ($user === null) {
            return ['id' => 'cms.users.error_missing'];
        }
        if ($id === Auth::id()) {
            return ['id' => 'cms.users.error_self'];
        }
        if ($user['role'] === 'admin' && (int) $user['is_active'] === 1 && $this->users->countActiveAdmins() <= 1) {
            return ['id' => 'cms.users.error_last_admin'];
        }

        $this->users->delete($id);

        return [];
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateProfile(array $data, ?int $id): array
    {
        $errors   = [];
        $username = trim((string) ($data['username'] ?? ''));

        if (preg_match('/^[a-z0-9._-]{3,50}$/', $username) !== 1) {
            $errors['username'] = 'cms.users.error_username';
        } elseif ($this->users->usernameTaken($username, $id)) {
            $errors['username'] = 'cms.users.error_username_taken';
        }
        if (trim((string) ($data['display_name'] ?? '')) === '') {
            $errors['display_name'] = 'cms.users.error_display_name';
        }

        $email = trim((string) ($data['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'cms.users.error_email';
        }
        if (!in_array((string) ($data['role'] ?? ''), self::ROLES, true)) {
            $errors['role'] = 'cms.users.error_role';
        }

        return $errors;
    }

    /** @return array<string, string> */
    private function validatePassword(string $password, string $confirm): array
    {
        if (mb_strlen($password) < self::MIN_PASSWORD || mb_strlen($password) > 200) {
            return ['password' => 'cms.account.error_length'];
        }
        if (!hash_equals($password, $confirm)) {
            return ['password_confirm' => 'cms.account.error_confirm'];
        }

        return [];
    }
}
