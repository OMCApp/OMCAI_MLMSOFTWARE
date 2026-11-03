<?php
/**
 * ชุดไอคอนกลางของระบบ — SVG inline ชุดเดียวทั้งระบบ
 *
 * ‼️ ห้ามผสมหลายชุด เพราะน้ำหนักเส้นและมุมมนจะไม่เข้ากัน
 * ‼️ ใช้ currentColor เพื่อให้เปลี่ยนตามโหมดและ token เอง
 * ‼️ ห้ามโหลดชุดไอคอนจาก CDN — เก็บไฟล์ในโปรเจค
 *
 * ไอคอนที่ใช้แทนความหมายต้องมีข้อความกำกับเสมอ (label, tooltip หรือ aria-label)
 */

declare(strict_types=1);

if (!function_exists('icon')) {
    /**
     * @param string $name  ชื่อไอคอน
     * @param string $class คลาสเพิ่มเติม
     */
    function icon(string $name, string $class = ''): string
    {
        static $paths = null;

        if ($paths === null) {
            $paths = [
                'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
                'home'      => '<path d="M4 11 12 4l8 7"/><path d="M6 10v9h12v-9"/>',
                'dashboard' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="4" rx="1"/><rect x="13" y="10" width="7" height="10" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/>',
                'history'   => '<circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/>',
                'inbox'     => '<path d="M4 13h4l2 3h4l2-3h4"/><path d="M5 6h14l1 7v5H4v-5z"/>',
                'book'      => '<path d="M5 5h9a3 3 0 0 1 3 3v11H8a3 3 0 0 1-3-3z"/><path d="M17 8h2v11h-2"/>',
                'help'      => '<circle cx="12" cy="12" r="8"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3 2.5v1.5"/><path d="M12 16.5h.01"/>',
                'logout'    => '<path d="M14 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2"/><path d="M17 12H9m8 0-3-3m3 3-3 3"/>',
                'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6l1.4 1.4m10 10 1.4 1.4m0-12.8-1.4 1.4m-10 10L5.6 18.4"/>',
                'moon'      => '<path d="M20 14a8 8 0 0 1-10-10 8 8 0 1 0 10 10z"/>',
                'globe'     => '<circle cx="12" cy="12" r="8"/><path d="M4 12h16M12 4a13 13 0 0 1 0 16 13 13 0 0 1 0-16z"/>',
                'view'      => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>',
                // ปุ่ม "เพิ่ม" คือปุ่มหลักของทุกหน้ารายการ ชุดไอคอนจึงต้องมีมาตั้งแต่ต้น
                'plus'      => '<path d="M12 5v14M5 12h14"/>',
                'edit'      => '<path d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17z"/>',
                'trash'     => '<path d="M5 7h14M10 7V5h4v2M6 7l1 13h10l1-13"/>',
                'download'  => '<path d="M12 4v10m0 0 4-4m-4 4-4-4"/><path d="M5 19h14"/>',
                'sort'      => '<path d="M8 9l4-4 4 4M8 15l4 4 4-4"/>',
                'sort-asc'  => '<path d="M8 14l4-4 4 4"/>',
                'sort-desc' => '<path d="M8 10l4 4 4-4"/>',
                'check'     => '<circle cx="12" cy="12" r="8"/><path d="M8.5 12.5 11 15l4.5-5"/>',
                'warning'   => '<path d="M12 4 3 19h18z"/><path d="M12 10v4M12 17h.01"/>',
                'danger'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5M12 16h.01"/>',
                'info'      => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5M12 8h.01"/>',
                'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
                'search'    => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
                'inbox-empty' => '<rect x="4" y="6" width="16" height="13" rx="2"/><path d="M4 13h4l2 3h4l2-3h4"/>',
                'bug'       => '<rect x="8" y="8" width="8" height="11" rx="4"/><path d="M8 12H4m16 0h-4M9 6l1.5 2m4.5-2-1.5 2M6 17H4m16 0h-2"/>',
                'sparkle'   => '<path d="M12 4l1.8 5.2L19 11l-5.2 1.8L12 18l-1.8-5.2L5 11l5.2-1.8z"/>',

                // ---- เว็บสาธารณะ (header · footer · บล็อก) — เส้น 1.6 มุมมนชุดเดียวกับข้างบน
                'tick'         => '<path d="m6 12.5 4 4 8-9"/>',
                'quote'        => '<path d="M5 17.5c0-4.5 1.5-8 5-10M14 17.5c0-4.5 1.5-8 5-10"/><path d="M5 17.5h4v-4H5zM14 17.5h4v-4h-4z"/>',
                'arrow-right'  =>'<path d="M5 12h14m0 0-5-5m5 5-5 5"/>',
                'chevron-down' => '<path d="m7 10 5 5 5-5"/>',
                'chevron-left' => '<path d="m14 7-5 5 5 5"/>',
                'chevron-right' => '<path d="m10 7 5 5-5 5"/>',
                'phone'     => '<path d="M6.5 4h3l1.5 4-2 1.3a10 10 0 0 0 5.7 5.7L16 13l4 1.5v3a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 4.5 6.2 2 2 0 0 1 6.5 4z"/>',
                'mail'      => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
                'chat'      => '<path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4 4v-4h0a2 2 0 0 1-2-2z"/><path d="M8.5 10h.01M12 10h.01M15.5 10h.01"/>',
                'map-pin'   => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
                'facebook'  => '<path d="M14 8.5h2.5V5H14a3.5 3.5 0 0 0-3.5 3.5V11H8v3.5h2.5V21H14v-6.5h2.4l.6-3.5h-3V9a.5.5 0 0 1 .5-.5z"/>',
                'calendar'  => '<rect x="4" y="5.5" width="16" height="14" rx="2"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/>',
                'clock'     => '<circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2"/>',
                'network'   => '<circle cx="12" cy="5.5" r="2.2"/><circle cx="5.5" cy="18" r="2.2"/><circle cx="18.5" cy="18" r="2.2"/><path d="M11 7.5 6.6 16M13 7.5l4.4 8.5M7.7 18h8.6"/>',
                'users'     => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.4"/><path d="M15.5 14.2A4.5 4.5 0 0 1 21 18.5"/>',
                'wallet'    => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H17v3"/><rect x="4" y="8" width="16" height="11" rx="2.5"/><path d="M16 13.5h.01"/>',
                'cart'      => '<path d="M3.5 4.5h2l2 10.5h10l2-7.5H7"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="16.5" cy="19" r="1.4"/>',
                'chart'     => '<path d="M4 20h16"/><rect x="5.5" y="12" width="3" height="6" rx="1"/><rect x="10.5" y="8" width="3" height="10" rx="1"/><rect x="15.5" y="4.5" width="3" height="13.5" rx="1"/>',
                'shield'    => '<path d="M12 3.5 19 6v5.5c0 4.2-3 7.6-7 9-4-1.4-7-4.8-7-9V6z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
                'plug'      => '<path d="M9 4v4M15 4v4M7 8h10v3a5 5 0 0 1-10 0z"/><path d="M12 16v4"/>',
                'sliders'   => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
                'layers'    => '<path d="m12 4 8.5 4.5L12 13 3.5 8.5z"/><path d="m3.5 12.5 8.5 4.5 8.5-4.5"/><path d="m3.5 16.5 8.5 4.5 8.5-4.5"/>',
                'calculator' => '<rect x="5" y="3.5" width="14" height="17" rx="2"/><path d="M8.5 7.5h7M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 16h.01M12 16h.01M15.5 16h.01"/>',
                'migrate'   => '<path d="M4 8h12m0 0-3.5-3.5M16 8l-3.5 3.5"/><path d="M20 16H8m0 0 3.5-3.5M8 16l3.5 3.5"/>',
                'grid'      => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>',
                'leaf'      => '<path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14z"/><path d="M5 19 13 11"/>',
                'heart'     => '<path d="M12 19.5s-7.5-4.4-7.5-10A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.5c0 5.6-7.5 10-7.5 10z"/>',
                'box'       => '<path d="m12 3.5 8 4v9l-8 4-8-4v-9z"/><path d="m4 7.5 8 4 8-4M12 11.5v9"/>',
                'store'     => '<path d="M4.5 9.5 6 4.5h12l1.5 5"/><path d="M4.5 9.5a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0"/><path d="M5.5 11.5V20h13v-8.5M10 20v-4.5h4V20"/>',
                'headset'   => '<path d="M5 14v-2a7 7 0 0 1 14 0v2"/><rect x="4" y="13.5" width="3.5" height="5" rx="1.2"/><rect x="16.5" y="13.5" width="3.5" height="5" rx="1.2"/><path d="M18 18.5a3 3 0 0 1-3 2.5h-2"/>',
                'code'      => '<path d="m8.5 8-4 4 4 4M15.5 8l4 4-4 4M13.5 5.5l-3 13"/>',
                'lock'      => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>',
                'plan-binary'   => '<circle cx="12" cy="5" r="2"/><circle cx="6" cy="18.5" r="2"/><circle cx="18" cy="18.5" r="2"/><path d="M11 6.8 7 16.7M13 6.8l4 9.9"/>',
                'plan-unilevel' => '<circle cx="12" cy="5" r="2"/><path d="M12 7v4.5M5 11.5h14M5 11.5v4.5M12 11.5v4.5M19 11.5v4.5"/><circle cx="5" cy="18.5" r="1.6"/><circle cx="12" cy="18.5" r="1.6"/><circle cx="19" cy="18.5" r="1.6"/>',
                'plan-matrix'   => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 9.3h16M4 14.7h16M9.3 4v16M14.7 4v16"/>',
                'plan-board'    => '<path d="M12 4.5 20 19H4z"/><path d="M8.2 12.5h7.6"/>',
                'plan-stair'    => '<path d="M4 19.5h4.5V15H13v-4.5h4.5V6H20"/>',
                'plan-hybrid'   => '<circle cx="12" cy="5" r="2"/><path d="M12 7v3.5M6 10.5h12M6 10.5l-1 5.5M18 10.5l1 5.5M12 10.5V16"/><circle cx="5" cy="18.5" r="1.6"/><circle cx="12" cy="18.5" r="1.6"/><circle cx="19" cy="18.5" r="1.6"/>',
            ];
        }

        $body = $paths[$name] ?? $paths['info'];

        return '<svg class="icon ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24"'
            . ' width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . $body . '</svg>';
    }
}
