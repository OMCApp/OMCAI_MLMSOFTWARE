<?php
/**
 * รับเรื่องที่ผู้ใช้แจ้งเข้ามา
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SupportService;
use Core\Controller;
use Core\Request;

final class SupportController extends Controller
{
    public function store(): void
    {
        $request = new Request();
        $service = new SupportService();

        $result = $service->submit($request->all(), $request->file('attachment'));

        if (!$result['ok']) {
            $this->flash('danger', __('support.' . ($result['error'] === 'throttled' ? 'throttled' : 'failed')));
            $this->back();
        }

        $this->flash('success', __('support.received'));
        $this->back();
    }
}
