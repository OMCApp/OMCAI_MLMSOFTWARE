<?php
/**
 * business logic ของระบบแจ้งปัญหา
 *
 * ‼️ ตัวอย่างว่า Service ทำอะไร — ตรวจเงื่อนไขธุรกิจ ประกอบ Model และเก็บบริบทให้เอง
 *    Controller เรียกใช้อย่างเดียว ไม่มี logic และไม่มี query
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportRequest;
use Core\Auth;
use Core\Lang;
use Core\Logger;
use Core\Path;
use Core\Session;
use Core\Uploader;

final class SupportService
{
    private const MAX_PER_HOUR = 5;

    public function __construct(
        private readonly SupportRequest $requests = new SupportRequest(),
    ) {
    }

    /**
     * รับเรื่องจากผู้ใช้
     *
     * @param  array<string, mixed> $input
     * @param  array<string, mixed>|null $file
     * @return array{ok:bool,error:string}
     */
    public function submit(array $input, ?array $file = null): array
    {
        $ip     = Auth::ip();
        $userId = Auth::id();

        // ระบบนี้เป็นจุดที่ผู้ใช้ภายนอกส่งข้อมูลเข้ามาตรงๆ จึงต้องกันสแปม
        if ($this->requests->recentCount($userId, $ip) >= self::MAX_PER_HOUR) {
            return ['ok' => false, 'error' => 'throttled'];
        }

        $type = (string) ($input['type'] ?? '');

        if (!in_array($type, SupportRequest::TYPES, true)) {
            return ['ok' => false, 'error' => 'failed'];
        }

        $message = trim((string) ($input['message'] ?? ''));

        if ($message === '' || mb_strlen($message) > 5000) {
            return ['ok' => false, 'error' => 'failed'];
        }

        // slug ที่รับมาต้องเทียบกับทะเบียนก่อนบันทึก ห้ามเชื่อค่าจาก client ตรงๆ
        $slug    = (string) ($input['manual_slug'] ?? '');
        $manuals = $this->manualRegistry();
        $slug    = isset($manuals[$slug]) ? $slug : null;

        $attachment = $this->storeAttachment($file);

        $data = [
            'user_id'         => $userId,
            'type'            => $type,
            // เก็บ URL เต็มรวม query string เพราะเคส "ข้อมูลแสดงผิด"
            // มักขึ้นกับตัวกรองที่ผู้ใช้เลือกอยู่ตอนนั้น
            'page_url'        => mb_substr((string) ($input['page_url'] ?? Path::full()), 0, 500),
            'error_reference' => Session::get('last_error_reference'),
            'manual_slug'     => $slug,
            'message'         => $message,
            'user_agent'      => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            'locale'          => Lang::locale(),
            'theme'           => theme(),
            'ip_address'      => $ip,
            'attachment_path' => $attachment['path'],
            'attachment_name' => $attachment['name'],
            'status'          => 'new',
        ];

        $id = $this->requests->create($data);

        Logger::info('support_request_created', ['id' => $id, 'type' => $type]);

        return ['ok' => true, 'error' => ''];
    }

    /**
     * @param  array<string, mixed>|null $file
     * @return array{path:?string,name:?string}
     */
    private function storeAttachment(?array $file): array
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'name' => null];
        }

        $uploader = new Uploader(
            (string) config('paths.uploads'),
            ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
            (int) config('upload.max_bytes', 5_242_880)
        );

        $result = $uploader->store($file, 'support');

        // แนบไฟล์ไม่สำเร็จไม่ควรทำให้เรื่องที่แจ้งหายไปด้วย
        if (!$result['ok']) {
            Logger::warning('support_attachment_failed', ['reason' => $result['error']]);

            return ['path' => null, 'name' => null];
        }

        return ['path' => $result['path'], 'name' => $result['original']];
    }

    /** @return array<string, array<string, mixed>> */
    private function manualRegistry(): array
    {
        static $registry = null;

        if ($registry === null) {
            $path     = dirname(__DIR__, 2) . '/config/manuals.php';
            $registry = is_file($path) ? (array) require $path : [];
        }

        return $registry;
    }

    /**
     * เปลี่ยนสถานะโดยแอดมิน
     *
     * @return bool
     */
    public function changeStatus(int $id, string $status, string $note): bool
    {
        if (!in_array($status, SupportRequest::STATUSES, true)) {
            return false;
        }

        return $this->requests->update($id, [
            'status'      => $status,
            'admin_note'  => mb_substr($note, 0, 5000),
            'resolved_at' => in_array($status, ['done', 'rejected'], true) ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
