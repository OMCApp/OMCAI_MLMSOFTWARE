<?php
/**
 * lead จากฟอร์มบนเว็บ — Phase 4 ต่อฟอร์ม (CSRF + honeypot + rate limit) · Phase 3 ทำหน้าจัดการ
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadNote;

final class LeadService
{
    /** ฟอร์มต่อ IP ต่อชั่วโมง — เกินนี้เป็นสแปม ไม่ใช่ลูกค้า */
    public const MAX_PER_HOUR = 5;

    public function __construct(private readonly Lead $leads = new Lead())
    {
    }

    public function throttled(string $ip): bool
    {
        return $this->leads->recentCountFromIp($ip) >= self::MAX_PER_HOUR;
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (!in_array((string) ($data['form_key'] ?? ''), Lead::FORM_KEYS, true)) {
            $errors['form_key'] = 'lead.error_form';
        }
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'lead.error_name';
        }
        if (trim((string) ($data['phone'] ?? '')) === '' && trim((string) ($data['email'] ?? '')) === ''
            && trim((string) ($data['line_id'] ?? '')) === '') {
            $errors['phone'] = 'lead.error_contact';
        }
        if (($data['email'] ?? '') !== '' && filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'lead.error_email';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->leads->create($data + ['status' => 'new']);
    }

    public function changeStatus(int $id, string $status): bool
    {
        if (!in_array($status, Lead::STATUSES, true)) {
            return false;
        }

        return $this->leads->update($id, ['status' => $status]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return $this->leads->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->leads->delete($id);
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /** คอลัมน์ของไฟล์ CSV — ลำดับนี้คือลำดับในไฟล์ */
    public const EXPORT_COLUMNS = [
        'id', 'created_at', 'status', 'form_key', 'locale', 'name', 'company', 'business_type', 'phone', 'email', 'line_id',
        'has_system', 'need', 'member_estimate', 'message', 'source_url', 'landing_page', 'referrer', 'utm', 'assignee',
    ];

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->leads->leadSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->leads->find($id);
    }

    /** คอลัมน์ leads.locale มาจาก migration ของ Phase 4 */
    public function hasLocale(): bool
    {
        return \Core\Database::hasColumn('leads', 'locale');
    }

    /** @return array<int, string> คอลัมน์ที่มาของ lead ที่มีในตารางแล้ว (กรองได้) */
    public function sourceColumns(): array
    {
        return array_values(array_filter(['landing_page', 'referrer', 'utm'], static fn (string $c): bool => \Core\Database::hasColumn('leads', $c)));
    }

    public function countNew(): int
    {
        return $this->leads->countNew();
    }

    /**
     * เปลี่ยนสถานะ / ผู้รับผิดชอบ — ผู้รับผิดชอบต้องเป็นผู้ใช้ที่ใช้งานอยู่และมีบทบาท admin/sales
     *
     * @return array<string, string> errors
     */
    public function manage(int $id, string $status, ?int $assignee): array
    {
        if (!in_array($status, Lead::STATUSES, true)) {
            return ['status' => 'cms.leads.error_status'];
        }
        if ($assignee !== null && !array_key_exists($assignee, $this->assignees())) {
            return ['assigned_to' => 'cms.leads.error_assignee'];
        }

        $this->leads->update($id, ['status' => $status, 'assigned_to' => $assignee]);

        return [];
    }

    /** @return array<int, string> id => ชื่อ (admin + sales ที่ใช้งานอยู่) */
    public function assignees(): array
    {
        $out = [];

        foreach ((new \App\Models\User())->activeWithRoles(['admin', 'sales']) as $row) {
            $out[(int) $row['id']] = (string) $row['display_name'];
        }

        return $out;
    }

    /** ‼️ โน้ตเพิ่มได้อย่างเดียว — ไม่มีแก้/ลบ (ประวัติการติดต่อลูกค้า) */
    public function addNote(int $leadId, string $body): bool
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 5000 || $this->leads->find($leadId) === null) {
            return false;
        }

        (new LeadNote())->create(['lead_id' => $leadId, 'user_id' => \Core\Auth::id(), 'body' => $body]);

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public function notes(int $leadId): array
    {
        return (new LeadNote())->forLead($leadId);
    }

    /**
     * แถวสำหรับ CSV ตามตัวกรองที่ใช้อยู่ (สูงสุด 5,000) — ค่าดิบ ผู้เขียนไฟล์ต้องผ่าน Security::csvCell ทุกช่อง
     *
     * @return \Generator<int, array<int, string>>
     */
    public function exportRows(array $filters, string $term): \Generator
    {
        yield self::EXPORT_COLUMNS;

        foreach ($this->leads->leadSearch($filters, $term, 'created_at', 'desc', 1, 5000)['rows'] as $row) {
            $line = [];

            foreach (self::EXPORT_COLUMNS as $column) {
                $line[] = (string) ($row[$column] ?? '');
            }

            yield $line;
        }
    }
}
