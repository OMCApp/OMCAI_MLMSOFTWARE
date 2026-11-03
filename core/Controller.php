<?php
/**
 * base Controller — Controller ทุกตัวสืบทอดจากที่นี่
 *
 * ‼️ Controller รับ input → เรียก Service → ส่งผลลัพธ์ให้ View เท่านั้น
 *    ห้ามมี business logic และห้ามมี query
 *    ถ้าเริ่มมี if เงื่อนไขธุรกิจ แปลว่าวางผิดชั้น ให้ย้ายลง Service
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

abstract class Controller
{
    /**
     * แสดงหน้าโดยใช้ layout กลาง
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $page, array $data = [], string $layout = 'app'): void
    {
        echo $this->render($page, $data, $layout);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $page, array $data = [], string $layout = 'app'): string
    {
        $content = $this->capture('pages/' . $page, $data);

        if ($layout === '') {
            return $content;
        }

        return $this->capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function capture(string $template, array $data): string
    {
        $path = dirname(__DIR__) . '/app/Views/' . $template . '.php';

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;

        return (string) ob_get_clean();
    }

    /** ไปหน้าอื่น — เติม base path ให้แล้ว ส่งเส้นทางภายในแอปเข้ามาได้ตรงๆ */
    protected function redirect(string $path): never
    {
        header('Location: ' . Path::url($path), true, 302);
        exit;
    }

    /** กลับหน้าเดิม ใช้คู่กับการคืนค่าที่กรอกไว้เมื่อ validate ไม่ผ่าน */
    protected function back(): never
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

        // เชื่อ referer เฉพาะที่เป็นเส้นทางในระบบเราเอง
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

        if ($referer !== '' && str_contains($referer, $host)) {
            header('Location: ' . $referer, true, 302);
            exit;
        }

        $this->redirect('/');
    }

    /**
     * รูปแบบคำตอบ JSON เดียวกันทั้งระบบ
     *
     * @param array<string, mixed>|null $data
     */
    protected function json(bool $success, ?array $data = null, string $message = '', int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo (string) json_encode([
            'success' => $success,
            'data'    => $data,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * แจ้งผลการกระทำแล้วไปหน้าถัดไป
     *
     * @param string $type success | warning | danger | info
     */
    protected function flash(string $type, string $message, string $reference = ''): void
    {
        Session::flash($type, $message, $reference);
    }

    /**
     * กลับหน้าเดิมพร้อมข้อความผิดพลาดและค่าที่กรอกไว้
     *
     * @param array<string, string> $errors  ชื่อช่อง => ข้อความ
     * @param array<string, mixed>  $input
     */
    protected function backWithErrors(array $errors, array $input): never
    {
        Session::keepInput($input, $errors);
        $this->back();
    }
}
