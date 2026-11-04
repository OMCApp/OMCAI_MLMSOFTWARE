<?php
/**
 * ผู้สนใจที่ส่งฟอร์มจากเว็บ (Phase 4 ทำฟอร์ม · Phase 3 ทำหน้าจัดการ)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use App\Models\Concerns\AdminSearch;
use Core\Model;

final class Lead extends Model
{
    use AdminSearch;

    protected array $searchColumns = ['name', 'company', 'phone', 'email', 'line_id'];
    protected array $filterColumns = ['status', 'form_key', 'assigned_to', 'locale'];

    protected string $table = 'leads';

    protected array $fillable = [
        'form_key', 'name', 'company', 'business_type', 'phone', 'email', 'line_id', 'has_system',
        'need', 'member_estimate', 'message', 'source_url', 'utm', 'ip', 'status', 'assigned_to', 'notes',
        'locale', 'landing_page', 'referrer',
    ];

    public const STATUSES  = ['new', 'contacted', 'qualified', 'won', 'lost'];
    public const FORM_KEYS = ['demo', 'quote', 'consult', 'contact', 'migration'];
    public const SORTABLE  = ['id', 'created_at', 'status', 'form_key'];

    /**
     * รายการของหน้าจัดการ lead — ตัวกรองของ AdminSearch + ช่วงวันที่สร้าง
     *
     * @param  array<string, string> $filters status, form_key, assigned_to, locale, from, to
     * @return array{rows:array<int,array<string,mixed>>,total:int}
     */
    public function leadSearch(array $filters, string $term, string $sort, string $direction, int $page, int $perPage): array
    {
        $where    = ['l.deleted_at IS NULL'];
        $bindings = [];

        foreach ($this->filterColumns as $column) {
            $value = (string) ($filters[$column] ?? '');

            if ($value !== '' && Database::hasColumn('leads', $column)) {
                $where[]                  = 'l.' . $column . ' = :f_' . $column;
                $bindings['f_' . $column] = $value;
            }
        }

        // ที่มาของ lead (Phase 4 เพิ่ม landing_page / referrer) — ค้นแบบมีคำนี้อยู่ · utm ค้นในข้อความ JSON
        foreach (['landing_page', 'referrer', 'utm'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            if ($value !== '' && Database::hasColumn('leads', $column)) {
                $where[]                  = 'CAST(l.' . $column . ' AS CHAR) LIKE :t_' . $column;
                $bindings['t_' . $column] = '%' . addcslashes(mb_substr($value, 0, 200), '\\%_') . '%';
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($filters['from'] ?? '')) === 1) {
            $where[]          = 'l.created_at >= :from';
            $bindings['from'] = $filters['from'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($filters['to'] ?? '')) === 1) {
            $where[]        = 'l.created_at <= :to';
            $bindings['to'] = $filters['to'] . ' 23:59:59';
        }

        $term = trim($term);

        if ($term !== '') {
            $or = [];

            foreach ($this->searchColumns as $index => $column) {
                $or[]                   = 'l.' . $column . ' LIKE :q' . $index;
                $bindings['q' . $index] = '%' . addcslashes($term, '\\%_') . '%';
            }

            $where[] = '(' . implode(' OR ', $or) . ')';
        }

        $sort      = in_array($sort, self::SORTABLE, true) ? $sort : 'created_at';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $clause    = ' WHERE ' . implode(' AND ', $where);
        $perPage   = max(1, min($perPage, 5000));

        $total = (int) (Database::selectOne('SELECT COUNT(*) AS total FROM leads l' . $clause, $bindings)['total'] ?? 0);
        $rows  = Database::select(
            'SELECT l.*, u.display_name AS assignee FROM leads l LEFT JOIN users u ON u.id = l.assigned_to'
            . $clause . ' ORDER BY l.' . $sort . ' ' . $direction . ', l.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . max(0, ($page - 1) * $perPage),
            $bindings
        );

        return ['rows' => $rows, 'total' => $total];
    }

    public function countNew(): int
    {
        return (int) (Database::selectOne("SELECT COUNT(*) AS n FROM leads WHERE status = 'new' AND deleted_at IS NULL")['n'] ?? 0);
    }

    /** จำนวนฟอร์มจาก IP นี้ในชั่วโมงที่ผ่านมา — ใช้ทำ rate limit ของฟอร์มสาธารณะ */
    public function recentCountFromIp(string $ip): int
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total FROM leads WHERE ip = :ip AND created_at > (NOW() - INTERVAL 1 HOUR)',
            ['ip' => $ip]
        );

        return (int) ($row['total'] ?? 0);
    }
}
