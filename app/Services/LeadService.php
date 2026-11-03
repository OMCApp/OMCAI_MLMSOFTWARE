<?php
/**
 * lead จากฟอร์มบนเว็บ — Phase 4 ต่อฟอร์ม (CSRF + honeypot + rate limit) · Phase 3 ทำหน้าจัดการ
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;

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
}
