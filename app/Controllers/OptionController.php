<?php
/**
 * รายการให้ dropdown ที่ค้นจากฝั่ง server
 *
 * ‼️ สิทธิ์ของแต่ละแหล่งเช็คที่ OptionAccessMiddleware แล้ว — ห้ามเช็คซ้ำที่นี่
 * ‼️ ที่นี่ไม่มี logic — รับค่า เรียก Service ตอบ JSON (กฎเหล็กข้อ 1)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OptionService;
use Core\Controller;
use Core\Request;

final class OptionController extends Controller
{
    public function search(string $key): never
    {
        $source = OptionService::source($key);

        if ($source === null) {
            $this->json(false, null, __('error.404_title'), 404);
        }

        $request = new Request();
        $result  = (new OptionService())->search(
            $source,
            (string) $request->input('q', ''),
            $request->page(),
        );

        $this->json(true, $result + ['min' => OptionService::MIN_TERM]);
    }
}
