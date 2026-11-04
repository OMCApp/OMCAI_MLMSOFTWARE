<?php
/**
 * ฟังก์ชันสั้นที่ View เรียกตลอด
 *
 * ทั้งหมดเป็นเพียงทางลัดไปยังคลาสใน core/ — ไม่มี logic ของตัวเอง
 */

declare(strict_types=1);

use Core\Path;
use Core\Security;
use Core\Session;
use Core\Lang;

if (!function_exists('e')) {
    /**
     * ‼️ ทุก output ที่มาจากผู้ใช้ต้องผ่านฟังก์ชันนี้ก่อนแสดงเสมอ
     *    รวมถึงหน้าแอดมินด้วย — ลืมเมื่อไหร่ สคริปต์จะรันในเบราว์เซอร์ของคนที่มีสิทธิ์สูงสุด
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    /**
     * ‼️ ทุกข้อความที่ผู้ใช้เห็นต้องผ่านฟังก์ชันนี้ ห้าม hardcode ในหน้า
     *    ผลลัพธ์แสดงได้ตรงๆ อย่างปลอดภัย เพราะไฟล์ภาษาห้ามมีแท็ก HTML
     *    และค่าที่แทนใน placeholder ถูก escape ให้แล้ว
     *
     * @param array<string, mixed> $replace
     */
    function __(string $key, array $replace = []): string
    {
        return Lang::get($key, $replace);
    }
}

if (!function_exists('url')) {
    /**
     * ‼️ ทุกลิงก์และ asset ต้องผ่านฟังก์ชันนี้
     *    ห้ามเขียน href="/login" ตรงๆ เพราะระบบต้องเปิดได้ทั้งที่ root และใต้ subdirectory
     */
    function url(string $path = '/'): string
    {
        return Path::url($path);
    }
}

if (!function_exists('query_meter')) {
    /**
     * จำนวน query และเวลาที่ใช้ของหน้านี้ — สำหรับแสดงใน footer โหมด development
     *
     * ‼️ เป็น helper เพราะ View ห้ามเรียก core class ตรงๆ
     *    ในโครงนี้ View คุยกับระบบผ่าน helper เสมอ (e · url · config · __)
     *    ยกเว้นเมื่อไหร่ก็เปิดทางให้หน้าถัดไปเรียก Database ตรงๆ ได้จริงๆ
     */
    function query_meter(): string
    {
        return Core\Database::queryCount() . ' query · '
            . number_format(Core\Database::queryMs(), 1) . ' ms';
    }
}

if (!function_exists('asset')) {
    /**
     * ‼️ ไฟล์ static ทุกตัวต้องผ่านตัวนี้ ห้ามใช้ url() ตรงๆ
     *
     *    deploy ของมาตรฐานนี้คือการอัปไฟล์ทับผ่าน FTP ซึ่งไม่เปลี่ยนชื่อไฟล์
     *    เบราว์เซอร์ของผู้ใช้เดิมจึงใช้ app.css กับ app.js ตัวเก่าต่อไปจนกว่าจะ hard refresh
     *    ผลคือแก้บั๊กหน้าตาแล้วผู้ใช้ยังเห็นของเดิม และไม่มีใครรู้ว่าทำไม
     *
     *    เกิดขึ้นจริงตอนทดสอบ component แนบไฟล์ — แก้ CSS แล้วเบราว์เซอร์ยังใช้ตัวเก่า
     *    ทำให้เกือบสรุปผิดว่าโค้ดที่เพิ่งแก้ไม่ทำงาน
     *
     *    ใช้เวลาแก้ไขไฟล์เป็นเลขเวอร์ชัน จึงเปลี่ยนเองทุกครั้งที่ deploy โดยไม่ต้องจำ
     */
    function asset(string $path): string
    {
        $file = dirname(__DIR__) . '/public' . $path;
        $stamp = is_file($file) ? (string) filemtime($file) : '0';

        return url($path) . '?v=' . $stamp;
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        return Path::current();
    }
}

if (!function_exists('csrf_field')) {
    /** ‼️ ทุกฟอร์ม POST/PUT/PATCH/DELETE ต้องมีฟังก์ชันนี้ */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Security::token()) . '">';
    }
}

if (!function_exists('method_field')) {
    /** ฟอร์ม HTML ส่งได้แค่ GET/POST จึงต้องบอก method จริงผ่าน hidden field */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('old')) {
    /** คืนค่าที่ผู้ใช้กรอกไว้เมื่อ validate ไม่ผ่าน */
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::old($key, $default);
    }
}

if (!function_exists('field_error')) {
    /** ข้อความผิดพลาดของช่องนั้น — แสดงใต้ช่องที่ผิด ไม่กองรวมบนสุด */
    function field_error(string $key): ?string
    {
        return Session::error($key);
    }
}

if (!function_exists('has_errors')) {
    function has_errors(): bool
    {
        return Session::errors() !== [];
    }
}

if (!function_exists('flashes')) {
    /** @return array<int, array{type:string,message:string,reference:string}> */
    function flashes(): array
    {
        return Session::flashes();
    }
}

if (!function_exists('config')) {
    /** อ่านค่าจาก config ที่โหลดไว้แล้ว รองรับ key แบบจุด */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = $GLOBALS['__config'] ?? [];
        }

        $value = $config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('format_date')) {
    /**
     * ‼️ ห้ามเรียก date() กระจายในหลาย view
     *    รูปแบบวันที่ต้องผูกกับภาษา ไม่งั้นเพิ่มภาษาแล้วต้องไล่แก้ทุกหน้า
     *    ฐานข้อมูลเก็บเป็นคริสต์ศักราชเสมอ แปลงตอนแสดงเท่านั้น
     */
    function format_date(?string $value, bool $withTime = false): string
    {
        return Lang::formatDate($value, $withTime);
    }
}

if (!function_exists('format_money')) {
    function format_money(int|float|string|null $value, int $decimals = 2): string
    {
        return Lang::formatNumber($value, $decimals);
    }
}

if (!function_exists('locale')) {
    function locale(): string
    {
        return Lang::locale();
    }
}

if (!function_exists('theme')) {
    /** โหมดที่ผู้ใช้เลือก — เขียนลง <html data-theme="..."> เพื่อไม่ให้หน้ากะพริบตอนโหลด */
    function theme(): string
    {
        $value = Session::get('theme');

        return in_array($value, ['light', 'dark'], true)
            ? $value
            : (string) config('ui.default_theme', 'light');
    }
}

if (!function_exists('hero_rendered')) {
    /**
     * บอกว่าหน้านี้วาดส่วนเปิด (hero) ไปแล้วหรือยัง
     *
     * ‼️ มีไว้เพื่อทำให้ "hero กับ page-head อยู่ร่วมกันไม่ได้" เป็นกลไก ไม่ใช่กฎที่ต้องจำ
     *    ทั้งคู่วาด <h1> ถ้ามีทั้งสองอันจะได้ <h1> สองตัวในหน้าเดียว
     *    ซึ่งแปลว่าไม่มีตัวไหนเป็นหัวจริง และ screen reader อ่านโครงหน้าผิด
     *
     * ‼️ ที่ใช้ static ได้เพราะ Controller::render() capture หน้าก่อน layout เสมอ
     *    hero.php (อยู่ในหน้า) จึงทำงานก่อน page-head.php (อยู่ใน layout) เสมอ
     *    ส่งผ่านตัวแปรธรรมดาไม่ได้ เพราะ capture() แต่ละครั้ง extract คนละขอบเขต
     */
    function hero_rendered(bool $markAsRendered = false): bool
    {
        static $rendered = false;

        if ($markAsRendered) {
            $rendered = true;
        }

        return $rendered;
    }
}

if (!function_exists('page_public')) {
    /**
     * บอกว่าหน้านี้เป็นหน้าสาธารณะหรือหน้าหลังบ้าน
     *
     * ‼️ มีไว้เพราะ design.md มีกฎการจัดหน้าสองชุดที่ขัดกันโดยตรง
     *    5.1-5.15 เขียนสำหรับหน้าหลังบ้าน — ความกว้างเดียว ขอบตรงกัน ไม่มีอะไรเซอร์ไพรส์
     *    5.16 อนุญาตให้หน้าสาธารณะชนขอบจอ ทับภาพ และใช้พาดหัวใหญ่กว่าหลายเท่า
     *
     *    ไม่มีตัวบอกว่าหน้าไหนใช้ชุดไหน แปลว่าปล่อยให้เดาเอง ซึ่งผิดได้สองทาง
     *    เอากฎหลังบ้านไปใช้กับ landing จะได้หน้าจืด · เอา 5.16 ไปใช้กับหน้ารายงาน
     *    จะได้หน้าหลังบ้านที่มี hero เต็มจอ
     *
     * ‼️ หน้าที่มี hero ถูกทำเครื่องหมายให้เองจาก hero.php — สิ่งที่ต้องจำคือสิ่งที่จะถูกลืม
     *    หน้าสาธารณะที่ไม่มี hero (บทความ นโยบาย) ต้องเรียก page_public(true) เอง
     */
    function page_public(bool $markAsPublic = false): bool
    {
        static $public = false;

        if ($markAsPublic) {
            $public = true;
        }

        return $public;
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * nonce ของ <script> — มีค่าเฉพาะหน้าที่ CSP ใช้ nonce (หน้าสาธารณะที่มีรหัสติดตาม · Security::allowTracking)
     * หน้าอื่นคืน '' (CSP ปกติใช้ 'self' ไม่ต้องมี nonce)
     *
     * ‼️ ใส่ใน <script src> ทุกตัวของ layout ที่อาจมีรหัสติดตาม — ไม่งั้นสคริปต์ของเราเองถูก 'strict-dynamic' บล็อก
     *    ใช้: <?php if (csp_nonce() !== ''): ?> nonce="<?= e(csp_nonce()) ?>"<?php endif; ?>
     */
    function csp_nonce(): string
    {
        return Security::nonce();
    }
}
