<?php
/**
 * ฐานของ Controller หลังบ้าน (CMS) — อ่านพารามิเตอร์ของหน้ารายการ · แปลง key ข้อผิดพลาดเป็นข้อความ · 404
 *
 * ‼️ ไม่มีการเช็คสิทธิ์ที่นี่ — สิทธิ์อยู่ที่ middleware ของกลุ่ม route (config/routes.php) เท่านั้น
 * ‼️ ไม่มี business logic — แค่รับ input ส่งให้ Service แล้วส่งผลให้ view
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\ErrorController;
use Core\Controller;
use Core\Request;

abstract class AdminController extends Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * พารามิเตอร์ของหน้ารายการจาก query string (ไม่ใช่ session — แชร์ลิงก์ได้ กด back ได้)
     *
     * @param  array<int, string> $sortable
     * @return array{q:string, sort:string, dir:string, page:int, per:int}
     */
    protected function listParams(array $sortable, string $defaultSort, string $defaultDir = 'desc'): array
    {
        return [
            'q'    => mb_substr((string) $this->request->input('q', ''), 0, 100),
            'sort' => $this->request->pick('sort', $sortable, $defaultSort),
            'dir'  => $this->request->pick('dir', ['asc', 'desc'], $defaultDir),
            'page' => $this->request->page(),
            'per'  => $this->request->perPage(),
        ];
    }

    /**
     * ค่าตัวกรองจาก query string — ผ่าน allowlist ของแต่ละตัว
     *
     * @param  array<string, array<int, string>> $allowed ชื่อ => ค่าที่ยอมรับ
     * @return array<string, string>
     */
    protected function filters(array $allowed): array
    {
        $out = [];

        foreach ($allowed as $name => $values) {
            $out[$name] = $this->request->pick($name, $values, '');
        }

        return $out;
    }

    /**
     * @param  array<string, string> $errors ชื่อช่อง => key ของข้อความ
     * @return array<string, string>          ชื่อช่อง => ข้อความที่แปลแล้ว
     */
    protected function messages(array $errors): array
    {
        return array_map(static fn (string $key): string => __($key), $errors);
    }

    /**
     * กลับหน้าฟอร์มพร้อมข้อความผิดพลาด (แปลแล้ว) และค่าที่กรอกไว้
     *
     * @param array<string, string> $errors
     */
    protected function fail(array $errors): never
    {
        $this->flash('danger', __('cms.common.fix_errors'));
        $this->backWithErrors($this->messages($errors), $this->request->all());
    }

    protected function notFound(): never
    {
        (new ErrorController())->show(404);
        exit;
    }

    protected function str(string $key, int $max = 1000): string
    {
        return mb_substr(trim((string) $this->request->input($key, '')), 0, $max);
    }

    /** ค่าว่าง = null (คอลัมน์ที่ไม่บังคับ) */
    protected function nullable(string $key, int $max = 1000): ?string
    {
        $value = $this->str($key, $max);

        return $value === '' ? null : $value;
    }

    protected function intOrNull(string $key): ?int
    {
        $value = (int) $this->request->input($key, 0);

        return $value > 0 ? $value : null;
    }

    protected function bool(string $key): bool
    {
        return !empty($this->request->input($key));
    }

    /** datetime-local (YYYY-MM-DDTHH:MM) → DATETIME · ว่าง/ผิดรูปแบบ = null */
    protected function datetime(string $key): ?string
    {
        $value = $this->str($key, 30);

        if ($value === '' || ($time = strtotime($value)) === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $time);
    }
}
