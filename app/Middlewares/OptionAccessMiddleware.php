<?php
/**
 * สิทธิ์ของแหล่งข้อมูล dropdown — จุดเดียวที่ตัดสินว่าใครค้นแหล่งไหนได้
 *
 * ‼️ เช็คที่นี่ ไม่ใช่ใน OptionController (กฎเหล็กข้อ 4)
 *    แหล่งข้อมูลเพิ่มได้เรื่อยๆ ใน config/options.php ถ้าเช็คในตัว Controller
 *    วันหนึ่งจะมีแหล่งที่ลืมเช็คแล้วกลายเป็นช่องไล่อ่านรายชื่อทั้งตารางทีละตัวอักษร
 *
 * ‼️ การค้นคือการอ่านข้อมูล ไม่ใช่แค่ "ช่วยกรอกฟอร์ม"
 *    ผู้ใช้ที่ไม่ควรเห็นรายชื่อลูกค้า ก็ไม่ควรค้นรายชื่อลูกค้าได้เช่นกัน
 */

declare(strict_types=1);

namespace App\Middlewares;

use App\Controllers\ErrorController;
use App\Services\OptionService;
use Core\Auth;
use Core\Logger;

final class OptionAccessMiddleware
{
    public function handle(): void
    {
        // คีย์คือส่วนสุดท้ายของ /options/{key} — route มีรูปแบบเดียวเสมอ
        $key    = basename(current_path());
        $source = OptionService::source($key);

        if ($source === null) {
            (new ErrorController())->show(404);
            exit;
        }

        $roles = (array) ($source['roles'] ?? []);

        if ($roles === [] || Auth::can(...$roles)) {
            return;
        }

        Logger::warning('forbidden option source', [
            'user_id' => Auth::id(),
            'role'    => Auth::role(),
            'source'  => $key,
        ]);

        (new ErrorController())->show(403);
        exit;
    }
}
