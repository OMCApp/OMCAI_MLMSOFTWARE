<?php
/**
 * ทะเบียนบทความคู่มือ
 *
 * ‼️ ห้ามเอา slug จาก URL ไปประกอบเป็น path ไฟล์ตรงๆ ต้องเทียบกับทะเบียนนี้ก่อนเสมอ
 *    ไม่งั้นกลายเป็นช่องอ่านไฟล์นอกโฟลเดอร์ (path traversal)
 *
 * routes = หน้าที่กดปุ่มคู่มือแล้วมาที่บทความนี้
 *          ทำให้พาไปบทความของหน้าที่ผู้ใช้ยืนอยู่ได้ตรง ไม่ต้องโยนไปหน้ารวมให้หาเอง
 *
 * icon   = ชื่อไอคอนจากชุดกลางเท่านั้น ห้ามไฟล์ SVG แยก
 *          เก็บที่นี่เพราะทะเบียนคืออธิบายโครงสร้างของบทความอยู่แล้ว ไม่ใช่ hardcode ในหน้า
 *
 * roles  = ว่างคือทุกคนดูได้ · บทความของแอดมินห้ามโผล่ให้ผู้ใช้ทั่วไปเห็น
 *          ต้องกันด้วยการเช็คสิทธิ์จริง ไม่ใช่แค่ซ่อนลิงก์
 *
 * ‼️ แก้ UI ของหน้าไหน ต้องเปิดบทความของหน้านั้นดูว่ายังตรงอยู่ไหม ในคอมมิตเดียวกัน
 *    คู่มือที่บอกวิธีผิดแย่กว่าไม่มีคู่มือ
 */

declare(strict_types=1);

return [

    'getting-started' => [
        'group'  => 'basics',
        'icon'   => 'home',
        'order'  => 10,
        'roles'  => [],
        'routes' => ['/'],
    ],

    'signing-in' => [
        'group'  => 'basics',
        'icon'   => 'logout',
        'order'  => 20,
        'roles'  => [],
        'routes' => ['/admin/login'],
    ],

    'reading-tables' => [
        'group'  => 'basics',
        'icon'   => 'view',
        'order'  => 30,
        'roles'  => [],
        'routes' => ['/admin'],
    ],

    'reporting-an-issue' => [
        'group'  => 'basics',
        'icon'   => 'bug',
        'order'  => 40,
        'roles'  => [],
        'routes' => [],
    ],

    'audit-logs' => [
        'group'  => 'admin',
        'icon'   => 'history',
        'order'  => 10,
        'roles'  => ['admin'],
        'routes' => ['/admin/audit-logs'],
    ],

    'handling-support-requests' => [
        'group'  => 'admin',
        'icon'   => 'inbox',
        'order'  => 20,
        'roles'  => ['admin'],
        'routes' => ['/admin/support-requests'],
    ],
];
