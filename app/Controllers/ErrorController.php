<?php
/**
 * หน้า error ทุกแบบ — ใช้ layout กลางและมีทางกลับเสมอ
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;

final class ErrorController extends Controller
{
    private const PAGES = [403, 404, 500];

    public function show(int $status = 404): void
    {
        $status = in_array($status, self::PAGES, true) ? $status : 404;

        http_response_code($status);

        $this->view('errors/' . $status, [
            'title'  => __('error.' . $status . '_title'),
            'status' => $status,
        ]);
    }
}
