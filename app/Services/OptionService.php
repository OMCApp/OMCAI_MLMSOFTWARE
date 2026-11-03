<?php
/**
 * กติกาของ dropdown ที่ค้นจากฝั่ง server
 *
 * เกณฑ์ทั้งหมดอยู่ที่นี่ที่เดียว (standard/components.md ข้อ 3ก)
 * ฝั่งหน้าเว็บจะส่งค่าอะไรมาก็ได้ ตัวเลขที่เชื่อได้คือตัวเลขที่ถูกบีบให้อยู่ในช่วงตรงนี้แล้ว
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Option;

final class OptionService
{
    /** พิมพ์น้อยกว่านี้ยังไม่ค้น — 1 ตัวอักษรได้ผลเกือบทั้งตารางและยิง query ทุกครั้งที่กดคีย์ */
    public const MIN_TERM = 2;

    /** จำนวนต่อครั้ง — มากกว่านี้ผู้ใช้ก็ไม่ไล่อ่านอยู่ดี ให้พิมพ์ต่อเร็วกว่า */
    public const LIMIT = 25;

    /** เพดานกันคนแก้ค่าใน URL ให้ดึงทั้งตาราง */
    public const MAX_LIMIT = 50;

    public function __construct(private readonly Option $options = new Option())
    {
    }

    /** @return array<string, mixed>|null แหล่งข้อมูลตามคีย์ · ไม่มีคืน null */
    public static function source(string $key): ?array
    {
        /** @var array<string, array<string, mixed>> $registry */
        $registry = (array) require dirname(__DIR__, 2) . '/config/options.php';

        return $registry[$key] ?? null;
    }

    /**
     * ค้นรายการสำหรับ dropdown
     *
     * @return array{items:array<int,array{value:string,label:string,hint:string}>, total:int, more:bool}
     */
    public function search(array $source, string $term, int $page = 1, int $limit = self::LIMIT): array
    {
        $term  = trim($term);
        $limit = max(1, min($limit, self::MAX_LIMIT));
        $page  = max(1, $page);

        // พิมพ์สั้นเกินไปแต่ยังไม่เคยพิมพ์อะไรเลย → คืนรายการแรกๆ ให้เห็นว่ามีอะไรบ้าง
        // พิมพ์ไปแล้ว 1 ตัวอักษร → ยังไม่ค้น รอให้ครบ MIN_TERM ก่อน
        if ($term !== '' && mb_strlen($term) < self::MIN_TERM) {
            return ['items' => [], 'total' => 0, 'more' => false];
        }

        $total  = $this->options->count($source, $term);
        $offset = ($page - 1) * $limit;

        return [
            'items' => $this->options->search($source, $term, $limit, $offset),
            'total' => $total,
            'more'  => $offset + $limit < $total,
        ];
    }

    /**
     * ค่าที่ฟอร์มส่งกลับมาเลือกได้จริงไหม
     *
     * ‼️ ทุกฟอร์มที่รับค่าจาก dropdown แบบค้นได้ ต้องเรียกข้อนี้ก่อนบันทึกเสมอ
     *    รายการที่หน้าเว็บเคยเห็นไม่ใช่ขอบเขตของค่าที่ส่งกลับมาได้ (standard/security.md ข้อ 4ก)
     */
    public function allows(string $key, string $value): bool
    {
        $source = self::source($key);

        return $source !== null && $value !== '' && $this->options->find($source, $value) !== null;
    }

    /**
     * ป้ายของค่าที่เลือกไว้ — ใช้ตอน render ฟอร์มที่มีค่าเดิมอยู่แล้ว
     *
     * ไม่มีข้อนี้ หน้าที่เปิดขึ้นมาแก้ไขจะโชว์ id เปล่าๆ เพราะรายการยังไม่ถูกโหลด
     */
    public function label(string $key, string $value): string
    {
        $source = self::source($key);
        $row    = $source === null || $value === '' ? null : $this->options->find($source, $value);

        return $row['label'] ?? '';
    }
}
