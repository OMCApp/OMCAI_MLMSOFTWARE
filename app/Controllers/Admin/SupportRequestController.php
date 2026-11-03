<?php
/**
 * หน้าจัดการเรื่องที่ผู้ใช้แจ้งเข้ามา
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\ErrorController;
use App\Models\SupportRequest;
use App\Services\SupportService;
use Core\Controller;
use Core\Request;

final class SupportRequestController extends Controller
{
    public function index(): void
    {
        $request = new Request();
        $model   = new SupportRequest();

        $sort    = $request->pick('sort', SupportRequest::SORTABLE, 'created_at');
        $dir     = $request->pick('dir', ['asc', 'desc'], 'desc');
        $status  = $request->pick('status', SupportRequest::STATUSES, '');
        $type    = $request->pick('type', SupportRequest::TYPES, '');

        $where = array_filter([
            'status' => $status === '' ? null : $status,
            'type'   => $type === '' ? null : $type,
        ], static fn ($value) => $value !== null);

        $result = $model->paginate(
            $where,
            $sort,
            $dir,
            $request->page(),
            $request->perPage(),
            SupportRequest::SORTABLE
        );

        $this->view('admin/support-requests', [
            'title'       => __('support.title'),
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'sort'        => $sort,
            'dir'         => $dir,
            'status'      => $status,
            'type'        => $type,
            'page'        => $request->page(),
            'perPage'     => $request->perPage(),
            'mostAsked'   => $model->mostAskedManuals(),
        ]);
    }

    public function show(string $id): void
    {
        $record = (new SupportRequest())->find((int) $id);

        if ($record === null) {
            (new ErrorController())->show(404);

            return;
        }

        $this->view('admin/support-request', [
            'title'  => __('support.title'),
            'record' => $record,
        ]);
    }

    public function update(string $id): void
    {
        $request = new Request();
        $service = new SupportService();

        $ok = $service->changeStatus(
            (int) $id,
            (string) $request->input('status', ''),
            (string) $request->input('admin_note', '')
        );

        $this->flash($ok ? 'success' : 'danger', $ok ? __('flash.updated', ['name' => '#' . $id]) : __('flash.failed'));
        $this->redirect('/admin/support-requests/' . (int) $id);
    }
}
