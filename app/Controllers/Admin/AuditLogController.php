<?php
/**
 * หน้าดูประวัติการใช้งานสำหรับแอดมิน
 *
 * ‼️ ต้องมีหน้าให้แอดมินดูและค้นหาได้ ไม่ใช่เก็บไว้ในตารางที่ต้องเปิด phpMyAdmin ดู
 * ‼️ ค่าใน old_values/new_values มาจากผู้ใช้ ต้อง escape ก่อนแสดงเสมอ
 *    ลืมเมื่อไหร่กลายเป็น stored XSS ที่ยิงใส่คนที่มีสิทธิ์สูงสุดพอดี
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\AuditLog;
use Core\Controller;
use Core\Request;
use Core\Security;

final class AuditLogController extends Controller
{
    public function index(): void
    {
        $request = new Request();
        $model   = new AuditLog();

        // ‼️ ตัวกรอง หน้า และการเรียง อ่านจาก query string ไม่ใช่ session
        //    เก็บใน session แล้วแชร์ลิงก์รายงานไม่ได้ กด back ค่าหาย เปิดสองแท็บตีกัน
        $filters = [
            'action'     => (string) $request->input('action', ''),
            'table_name' => (string) $request->input('table_name', ''),
            'user_id'    => (string) $request->input('user_id', ''),
            'from'       => (string) $request->input('from', ''),
            'to'         => (string) $request->input('to', ''),
        ];

        $sort      = $request->pick('sort', AuditLog::SORTABLE, 'created_at');
        $direction = $request->pick('dir', ['asc', 'desc'], 'desc');
        $page      = $request->page();
        $perPage   = $request->perPage();

        if ((string) $request->input('export', '') === 'csv') {
            $this->export($model, $filters, $sort, $direction);
        }

        $result = $model->search($filters, $sort, $direction, $page, $perPage);

        $this->view('admin/audit-logs', [
            'title'   => __('manual.title'),
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'filters' => $filters,
            'sort'    => $sort,
            'dir'     => $direction,
            'page'    => $page,
            'perPage' => $perPage,
            'actions' => $model->actions(),
        ]);
    }

    /**
     * ‼️ ส่งออกตามตัวกรองที่ใช้อยู่ ไม่ใช่ทั้งตาราง
     *    และต้องกัน formula injection ก่อนเขียนทุกช่อง
     *
     * @param array<string, mixed> $filters
     */
    private function export(AuditLog $model, array $filters, string $sort, string $direction): never
    {
        $result = $model->search($filters, $sort, $direction, 1, 5000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-logs-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'wb');

        // BOM — ไม่มีแล้ว Excel บน Windows อ่านภาษาไทยเป็นตัวขยะ
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['id', 'created_at', 'actor', 'action', 'table_name', 'record_id', 'ip_address']);

        foreach ($result['rows'] as $row) {
            fputcsv($out, [
                Security::csvCell($row['id']),
                Security::csvCell($row['created_at']),
                Security::csvCell($row['actor'] ?? ''),
                Security::csvCell($row['action']),
                Security::csvCell($row['table_name'] ?? ''),
                Security::csvCell($row['record_id'] ?? ''),
                Security::csvCell($row['ip_address'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }
}
