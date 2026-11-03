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
            ];
        }

        $body = $paths[$name] ?? $paths['info'];

        return '<svg class="icon ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24"'
            . ' width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . $body . '</svg>';
    }
}
