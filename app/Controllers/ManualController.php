<?php
/**
 * คู่มือการใช้งาน
 *
 * ‼️ ห้ามเอา slug จาก URL ไปประกอบเป็น path ไฟล์ตรงๆ ต้องเทียบทะเบียนก่อนเสมอ
 *    ไม่งั้นกลายเป็นช่องอ่านไฟล์นอกโฟลเดอร์ (path traversal)
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Lang;

final class ManualController extends Controller
{
    public function index(): void
    {
        $groups = [];

        foreach ($this->visible() as $slug => $entry) {
            /* ‼️ ชื่อบทความต้องมาจากตัวบทความ ไม่ใช่จาก slug และไม่ใช่จากทะเบียน
               slug เป็นภาษาอังกฤษเสมอและไม่ใช่ภาษาคน — แสดง slug คือหน้าที่ผู้ใช้อ่านไม่รู้เรื่อง
               ทุกบทความมี $manual_title / $manual_summary อยู่แล้วตามที่ standard/features/manual.md บังคับ
               การไม่ใช้มันคือการทิ้งของที่บังคับให้ทำ

               และห้ามเก็บชื่อไว้ใน config/manuals.php ซ้ำอีกที่ — วันหนึ่งจะไม่ตรงกับชื่อในบทความ */
            $meta = $this->meta($slug);

            $groups[$entry['group']][] = ['slug' => $slug] + $meta + $entry;
        }

        foreach ($groups as $name => $items) {
            usort($items, static fn (array $a, array $b) => $a['order'] <=> $b['order']);
            $groups[$name] = $items;
        }

        $this->view('manuals/index', [
            'title'     => __('manual.title'),
            'pageLead'  => __('manual.lead'),
            'pageWidth' => 'list',
            'groups'    => $groups,
        ]);
    }

    public function show(string $slug): void
    {
        $registry = $this->visible();

        if (!isset($registry[$slug])) {
            (new ErrorController())->show(404);

            return;
        }

        $root      = dirname(__DIR__, 2) . '/app/Views/manuals/';
        $locale    = Lang::locale();
        $fallback  = (string) config('default_locale', 'th');
        $translated = true;

        $path = $root . $locale . '/' . $slug . '.php';

        if (!is_file($path)) {
            $path       = $root . $fallback . '/' . $slug . '.php';
            $translated = false;
        }

        if (!is_file($path)) {
            (new ErrorController())->show(404);

            return;
        }

        // ชื่อหน้าคือชื่อบทความ ไม่ใช่ "คู่มือการใช้งาน" ซ้ำทุกบทความ — หน้ารวมคู่มือขึ้นไปอยู่ใน breadcrumb
        $meta = $this->meta($slug);

        $this->view('manuals/show', [
            'title'          => $meta['title'],
            'pageLead'       => $meta['summary'],
            'pageBreadcrumb' => [
                ['label' => __('manual.title'), 'url' => '/manual'],
                ['label' => $meta['title']],
            ],
            'slug'       => $slug,
            'article'    => $path,
            'translated' => $translated,
        ]);
    }

    /**
     * อ่านชื่อและคำโปรยจากตัวบทความ
     *
     * ‼️ ต้องทำในเมธอดแยก เพราะ include ใช้ขอบเขตตัวแปรร่วม
     *    ถ้า include ในเมธอดที่มีตัวแปรอื่นอยู่ ตัวแปรของบทความจะทับตัวแปรของ Controller
     *    (ตระกูลเดียวกับบั๊ก $rows ที่ทำให้ตารางหายทั้งระบบ)
     *
     * ‼️ ต้องกลืน output ทิ้ง เพราะไฟล์บทความพิมพ์เนื้อหาออกมาเมื่อถูก include
     *
     * @return array{title: string, summary: string}
     */
    private function meta(string $slug): array
    {
        $root     = dirname(__DIR__, 2) . '/app/Views/manuals/';
        $fallback = (string) config('default_locale', 'th');
        $path     = $root . Lang::locale() . '/' . $slug . '.php';

        if (!is_file($path)) {
            $path = $root . $fallback . '/' . $slug . '.php';
        }

        if (!is_file($path)) {
            return ['title' => $slug, 'summary' => ''];
        }

        $manual_title   = '';
        $manual_summary = '';

        ob_start();
        include $path;
        ob_end_clean();

        return [
            // ยังไม่มีชื่อในบทความก็คืน slug ไปก่อน ดีกว่าหน้าว่าง — และเห็นชัดว่าต้องไปเติม
            'title'   => $manual_title !== '' ? $manual_title : $slug,
            'summary' => $manual_summary,
        ];
    }

    /**
     * บทความที่ผู้ใช้คนนี้มีสิทธิ์เห็น
     *
     * ‼️ บทความของแอดมินห้ามโผล่ให้ผู้ใช้ทั่วไปเห็น ทั้งในหน้ารวมและตอนเปิดตรง
     *    ต้องกันด้วยการเช็คสิทธิ์จริง ไม่ใช่แค่ซ่อนลิงก์
     *
     * @return array<string, array<string, mixed>>
     */
    private function visible(): array
    {
        $registry = (array) require dirname(__DIR__, 2) . '/config/manuals.php';
        $out      = [];

        foreach ($registry as $slug => $entry) {
            $roles = (array) ($entry['roles'] ?? []);

            if ($roles !== [] && !Auth::can(...$roles)) {
                continue;
            }

            $out[$slug] = $entry;
        }

        return $out;
    }
}
