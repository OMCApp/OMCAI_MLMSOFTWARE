<?php
/**
 * เสิร์ฟไฟล์แนบที่เก็บอยู่นอก public/
 *
 * ‼️ หา path จาก id ในฐานข้อมูล ไม่ใช่รับ path จาก URL
 *    ถ้ารับ path ตรงๆ จะกลายเป็นช่องอ่านไฟล์ทั้งเครื่องทันที
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SupportRequest;
use Core\Auth;
use Core\Controller;

final class FileController extends Controller
{
    public function show(string $id): void
    {
        $record = (new SupportRequest())->find((int) $id);

        if ($record === null || ($record['attachment_path'] ?? null) === null) {
            (new ErrorController())->show(404);

            return;
        }

        // เจ้าของเรื่องหรือแอดมินเท่านั้น — สิทธิ์ระดับแถว ไม่ใช่แค่ระดับหน้า
        $isOwner = $record['user_id'] !== null && (int) $record['user_id'] === Auth::id();

        if (!$isOwner && !Auth::can('admin')) {
            (new ErrorController())->show(403);

            return;
        }

        $base = rtrim((string) config('paths.uploads'), '/');
        $path = $base . '/' . ltrim(str_replace(['..', "\0"], '', (string) $record['attachment_path']), '/');

        if (!is_file($path)) {
            (new ErrorController())->show(404);

            return;
        }

        $name = (string) ($record['attachment_name'] ?? 'file');

        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');

        readfile($path);
        exit;
    }
}
