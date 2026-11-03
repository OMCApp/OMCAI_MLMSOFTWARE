<?php
/**
 * อ่านรายการให้ dropdown ที่ค้นจากฝั่ง server
 *
 * ‼️ ตารางและคอลัมน์ทุกตัวมาจาก config/options.php เท่านั้น — ผูก placeholder กับชื่อตารางไม่ได้
 *    ค่าที่มาจากผู้ใช้มีแค่คำค้นกับจำนวน ซึ่งผูก placeholder ได้ทั้งคู่
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;

final class Option
{
    /**
     * ชื่อตารางและคอลัมน์ที่ยอมให้ต่อเข้า SQL ได้
     *
     * ‼️ ด่านนี้ต้องอยู่ในไฟล์นี้ ไม่ใช่เชื่อว่า config ถูกเสมอ
     *    วันที่มีคนทำให้ค่าใน config มาจากที่อื่นได้ ด่านนี้คือสิ่งเดียวที่ยังกันอยู่
     */
    private const IDENTIFIER = '/^[a-z][a-z0-9_]*$/';

    /**
     * @param  array<string, mixed> $source แหล่งข้อมูลจาก config/options.php
     * @return array<int, array{value:string, label:string, hint:string}>
     */
    public function search(array $source, string $term, int $limit, int $offset = 0): array
    {
        [$where, $bindings] = $this->conditions($source, $term);

        /* LIMIT/OFFSET ผูก placeholder ไม่ได้ทุกไดรเวอร์ จึงบังคับเป็น int แล้วต่อเข้าไป
           ค่าทั้งสองถูกจำกัดช่วงมาแล้วที่ OptionService ก่อนถึงตรงนี้ */
        $rows = Database::select(
            'SELECT ' . $this->columns($source) . ' FROM ' . $this->identifier($source, 'table') . $where
            . ' ORDER BY ' . $this->order($source) . ' ASC'
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );

        return array_map(static fn (array $row): array => [
            'value' => (string) $row['value'],
            'label' => (string) $row['label'],
            'hint'  => (string) ($row['hint'] ?? ''),
        ], $rows);
    }

    /** @param array<string, mixed> $source */
    public function count(array $source, string $term): int
    {
        [$where, $bindings] = $this->conditions($source, $term);

        return (int) (Database::selectOne(
            'SELECT COUNT(*) AS total FROM ' . $source['table'] . $where,
            $bindings
        )['total'] ?? 0);
    }

    /**
     * หาแถวเดียวจากค่าที่ฟอร์มส่งมา — ใช้ตอน validate ว่าค่านั้นเลือกได้จริง
     *
     * @param  array<string, mixed> $source
     * @return array{value:string, label:string, hint:string}|null
     */
    public function find(array $source, string $value): ?array
    {
        [$where, $bindings] = $this->conditions($source, '');

        $row = Database::selectOne(
            'SELECT ' . $this->columns($source) . ' FROM ' . $this->identifier($source, 'table')
            . ($where === '' ? ' WHERE ' : $where . ' AND ') . $this->identifier($source, 'value') . ' = :value',
            $bindings + ['value' => $value]
        );

        return $row === null ? null : [
            'value' => (string) $row['value'],
            'label' => (string) $row['label'],
            'hint'  => (string) ($row['hint'] ?? ''),
        ];
    }

    /** คอลัมน์ทั้งหมดที่แหล่งนี้ประกาศไว้ — ใช้เป็น allowlist ของทุกจุดที่ต่อชื่อคอลัมน์เข้า SQL */
    private function declared(array $source): array
    {
        return array_values(array_unique(array_filter([
            (string) ($source['value'] ?? ''),
            (string) ($source['label'] ?? ''),
            (string) ($source['hint'] ?? ''),
            (string) ($source['order'] ?? ''),
            ...array_map('strval', (array) ($source['search'] ?? [])),
            ...array_map('strval', array_keys((array) ($source['where'] ?? []))),
        ])));
    }

    /** ชื่อตาราง/คอลัมน์ที่ผ่านด่านรูปแบบแล้ว — ไม่ผ่านคือ config เขียนผิด ต้องดังทันที ไม่ใช่ปล่อยเข้า SQL */
    private function identifier(array $source, string $key): string
    {
        $name = (string) ($source[$key] ?? '');

        if (preg_match(self::IDENTIFIER, $name) !== 1) {
            throw new \RuntimeException('option source has an invalid ' . $key);
        }

        return $name;
    }

    /** คอลัมน์ที่ยอมให้เรียง — ต้องเป็นคอลัมน์ที่แหล่งนี้ประกาศไว้เท่านั้น */
    private function order(array $source): string
    {
        $order = $this->identifier($source, 'order');

        if (!in_array($order, $this->declared($source), true)) {
            throw new \RuntimeException('option source orders by an undeclared column');
        }

        return $order;
    }

    /** ช่องที่ส่งกลับไปให้หน้าเว็บ — ตั้งชื่อ alias ตายตัว หน้าเว็บจึงไม่ต้องรู้ชื่อคอลัมน์จริง */
    private function columns(array $source): string
    {
        $columns = $this->identifier($source, 'value') . ' AS value, '
            . $this->identifier($source, 'label') . ' AS label';

        return isset($source['hint']) ? $columns . ', ' . $this->identifier($source, 'hint') . ' AS hint' : $columns;
    }

    /**
     * @param  array<string, mixed> $source
     * @return array{0:string, 1:array<string, mixed>}
     */
    private function conditions(array $source, string $term): array
    {
        $parts     = [];
        $bindings  = [];
        $declared  = $this->declared($source);

        foreach ((array) ($source['where'] ?? []) as $column => $value) {
            $column = (string) $column;

            if (!in_array($column, $declared, true) || preg_match(self::IDENTIFIER, $column) !== 1) {
                throw new \RuntimeException('option source filters by an invalid column');
            }

            $parts[]                      = $column . ' = :fixed_' . $column;
            $bindings['fixed_' . $column] = $value;
        }

        if ($term !== '') {
            /* ‼️ % และ _ ในคำค้นต้อง escape ไม่งั้นผู้ใช้พิมพ์ % แล้วได้ทั้งตาราง
               ‼️ ตัวอักษร escape ใช้ ! ไม่ใช่ \ — MySQL ถอด \ ในสตริงให้อีกชั้น
                  ผลคือ ESCAPE ได้อักขระสองตัวและฐานข้อมูลอื่น (เช่น SQLite) ฟ้อง error ทันที
                  และต้อง escape ตัว ! เองก่อนตัวอื่นเสมอ */
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
            $like    = [];

            foreach ((array) $source['search'] as $index => $column) {
                $like[]                     = $column . ' LIKE :term_' . $index . " ESCAPE '!'";
                $bindings['term_' . $index] = '%' . $escaped . '%';
            }

            $parts[] = '(' . implode(' OR ', $like) . ')';
        }

        return [$parts === [] ? '' : ' WHERE ' . implode(' AND ', $parts), $bindings];
    }
}
