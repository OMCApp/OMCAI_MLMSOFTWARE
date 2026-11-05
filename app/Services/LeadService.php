<?php
/**
 * lead จากฟอร์มบนเว็บ — รับฟอร์ม (Phase 4) · หน้าจัดการ (Phase 3)
 *
 * ลำดับตอนรับฟอร์ม (submit)
 *   1. rate limit ต่อ IP (ตาราง lead_attempts — นับทุกครั้งที่ส่ง ไม่ใช่แค่ที่สำเร็จ) + lead ที่บันทึกแล้วต่อชั่วโมง
 *   2. honeypot (ช่อง website ต้องว่าง) — bot ได้หน้า "ขอบคุณ" เหมือนสำเร็จ แต่ไม่บันทึก ไม่ส่งอีเมล
 *   3. validate — ค่าของ select/radio ต้องอยู่ใน BlockService::LEAD_OPTIONS ชุดเดียว · ต้องมีเบอร์/อีเมล/LINE อย่างน้อยหนึ่ง
 *   4. บันทึก (ผ่าน base Model → audit log) แล้วค่อยส่งอีเมลแจ้งเตือน — อีเมลล้มเหลว lead ไม่หาย
 *
 * ‼️ CSRF ตรวจที่ public/index.php ก่อนถึงที่นี่
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadAttempt;
use Core\Logger;

final class LeadService
{
    /** lead ที่บันทึกได้ต่อ IP ต่อชั่วโมง — เกินนี้เป็นสแปม ไม่ใช่ลูกค้า */
    public const MAX_PER_HOUR = 5;

    /** การส่งฟอร์มทุกแบบ (รวมกรอกผิด) ต่อ IP ต่อ 15 นาที */
    public const MAX_ATTEMPTS = 10;
    public const WINDOW_MINUTES = 15;

    /** ความยาวสูงสุดของแต่ละช่อง (ตรงกับคอลัมน์ใน leads) */
    private const MAX_LEN = [
        'name' => 150, 'company' => 150, 'phone' => 50, 'email' => 190, 'line_id' => 100, 'message' => 3000,
    ];

    public function __construct(
        private readonly Lead $leads = new Lead(),
        private readonly LeadAttempt $attempts = new LeadAttempt(),
    ) {
    }

    public function throttled(string $ipKey): bool
    {
        return $this->attempts->countSince($ipKey, self::WINDOW_MINUTES) >= self::MAX_ATTEMPTS
            || $this->leads->recentCountFromIp($ipKey) >= self::MAX_PER_HOUR;
    }

    /**
     * @param  array<string, mixed> $input $_POST
     * @return array{status: 'sent'|'spam'|'invalid'|'throttled', errors: array<string,string>, input: array<string,string>,
     *               locale: string, source_path: string, id: ?int}
     *         errors = ชื่อช่อง => key ของข้อความใน lang (Controller แปลด้วย __())
     */
    public function submit(array $input, string $ipKey, ?string $cookieReferrer = null): array
    {
        $clean      = $this->clean($input);
        $sourcePath = self::internalPath((string) ($input['source_path'] ?? '/'));
        $locale     = LocaleService::localeOf($sourcePath);
        $result     = ['errors' => [], 'input' => $clean, 'locale' => $locale, 'source_path' => $sourcePath, 'id' => null];

        if ($this->throttled($ipKey)) {
            $this->attempts->record($ipKey, 'throttled');
            Logger::warning('lead_throttled', ['ip' => $ipKey]);

            return ['status' => 'throttled'] + $result;
        }

        // honeypot — ตอบเหมือนสำเร็จ bot จะได้ไม่ลองใหม่
        if (trim((string) ($input['website'] ?? '')) !== '') {
            $this->attempts->record($ipKey, 'spam');

            return ['status' => 'spam'] + $result;
        }

        $errors = $this->validate($clean);

        if ($errors !== []) {
            $this->attempts->record($ipKey, 'invalid');

            return ['status' => 'invalid', 'errors' => $errors] + $result;
        }

        $utm = LeadTrackingService::utm($input);
        $id  = $this->create([
            'form_key'        => $clean['form_key'],
            'locale'          => $locale,
            'name'            => $clean['name'],
            'company'         => self::nullable($clean['company']),
            'business_type'   => self::nullable($clean['business_type']),
            'phone'           => self::nullable($clean['phone']),
            'email'           => self::nullable($clean['email']),
            'line_id'         => self::nullable($clean['line_id']),
            'has_system'      => self::nullable($clean['has_system']),
            'need'            => self::nullable($clean['need']),
            'member_estimate' => self::nullable($clean['member_estimate']),
            'message'         => self::nullable($clean['message']),
            'source_url'      => SeoService::absolute($sourcePath),
            'landing_page'    => $sourcePath,
            'referrer'        => LeadTrackingService::referrer((string) ($input['ref'] ?? $cookieReferrer ?? '')),
            'utm'             => ($utm['first'] === [] && $utm['current'] === []) ? null
                : (string) json_encode(['first' => (object) $utm['first'], 'current' => (object) $utm['current']], JSON_UNESCAPED_UNICODE),
            'ip'              => mb_substr($ipKey, 0, 45),
        ]);

        $this->attempts->record($ipKey, 'sent');

        if (random_int(1, 50) === 1) {
            $this->attempts->prune();
        }

        // ‼️ lead อยู่ในฐานข้อมูลแล้ว — อีเมลล้มเหลวไม่ทำให้ผู้ส่งฟอร์มเห็น error (LeadNotifier บันทึกผลและแจ้งเตือนใน CMS)
        $notifier = new LeadNotifier();
        if ($notifier->notify($id) === 'sent') {
            $notifier->retryBacklog(3);
        }

        return ['status' => 'sent', 'id' => $id] + $result;
    }

    /**
     * @param  array<string, string> $data ค่าที่ผ่าน clean() แล้ว
     * @return array<string, string> ชื่อช่อง => key ของข้อความ
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
        if (($data['phone'] ?? '') !== '' && preg_match('/^\+?[0-9][0-9 ()\-.]{5,29}$/', (string) $data['phone']) !== 1) {
            $errors['phone'] = 'lead.error_phone';
        }

        // ‼️ ตัวเลือกต้องอยู่ในชุดเดียวกับที่ฟอร์มแสดง (BlockService::LEAD_OPTIONS) — ไม่รับค่าอื่นแม้แก้ HTML เอง
        foreach (BlockService::LEAD_OPTIONS as $field => $allowed) {
            $value = (string) ($data[$field] ?? '');

            if ($value !== '' && !in_array($value, $allowed, true)) {
                $errors[$field] = 'lead.error_option';
            }
        }

        foreach (self::MAX_LEN as $field => $max) {
            if (!isset($errors[$field]) && mb_strlen((string) ($data[$field] ?? '')) > $max) {
                $errors[$field] = 'lead.error_length';
            }
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

    /** หน้าขอบคุณของภาษานั้น (noindex) */
    public static function thankYouPath(string $locale): string
    {
        return SiteService::localizePath('/thank-you/', $locale);
    }

    /** path ภายในที่ปลอดภัยสำหรับ redirect กลับ — ห้ามเป็น //host หรือ URL เต็ม (open redirect) */
    public static function internalPath(string $path): string
    {
        $path = '/' . ltrim(explode('?', explode('#', $path, 2)[0], 2)[0], '/');

        if (str_starts_with($path, '//') || str_contains($path, '\\') || !mb_check_encoding($path, 'UTF-8')
            || preg_match('/[\x00-\x1F]/', $path) === 1 || mb_strlen($path) > 300) {
            return '/';
        }

        return $path;
    }

    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $input
     * @return array<string, string>
     */
    private function clean(array $input): array
    {
        $fields = ['form_key', 'name', 'company', 'phone', 'email', 'line_id', 'business_type', 'has_system', 'need', 'member_estimate', 'message'];
        $out    = [];

        foreach ($fields as $field) {
            $value = $input[$field] ?? '';
            $value = is_string($value) ? $value : '';
            $value = $field === 'message'
                ? trim(str_replace("\0", '', $value))
                : trim((string) preg_replace('/\s+/u', ' ', str_replace("\0", '', $value)));

            $out[$field] = $value;
        }

        $out['email'] = mb_strtolower($out['email']);

        return $out;
    }

    private static function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
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
