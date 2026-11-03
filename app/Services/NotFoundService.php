<?php
/**
 * บันทึก 404 ของเว็บสาธารณะ ให้แอดมินเปลี่ยนเป็น redirect ได้จากหน้าเดียว (Phase 3)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\NotFoundLog;
use Core\Logger;
use Throwable;

final class NotFoundService
{
    private const MAX = 500;

    public function __construct(private readonly NotFoundLog $log = new NotFoundLog())
    {
    }

    /**
     * ‼️ การบันทึกพังต้องไม่ทำให้หน้า 404 พังตาม — คนที่เจอ 404 ต้องเห็นหน้า 404 เสมอ
     */
    public function record(string $path, ?string $referrer, ?string $userAgent): void
    {
        try {
            $this->log->record(
                mb_substr($path, 0, self::MAX),
                $this->clip($referrer, self::MAX),
                $this->clip($userAgent, 255)
            );
        } catch (Throwable $e) {
            Logger::exception($e);
        }
    }

    public function dismiss(int $id): bool
    {
        return $this->log->delete($id);
    }

    private function clip(?string $value, int $max): ?string
    {
        if ($value === null || $value === '' || !mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        return mb_substr($value, 0, $max);
    }
}
