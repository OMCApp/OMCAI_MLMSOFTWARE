<?php
/**
 * อีเมลแจ้งทีมขายเมื่อมี lead ใหม่ — สร้างจากแถวในฐานข้อมูล (ส่งซ้ำได้ทุกเมื่อ) และบันทึกผลที่ leads.notify_*
 *
 * ‼️ lead ถูกบันทึกก่อนเสมอ — ส่งไม่สำเร็จแล้ว lead ไม่หาย: notify_status = failed + ข้อความ error
 *    หน้าแรกหลังบ้านแจ้งเตือน (DashboardService) · ปุ่ม "ส่งอีเมลที่ค้างอีกครั้ง" ในหน้า lead
 *    และทุกครั้งที่มี lead ใหม่ส่งสำเร็จ ระบบลองส่งรายการที่ค้างให้อีก (ไม่ต้องมี cron)
 * ‼️ ห้าม throw ออกไปถึงผู้ส่งฟอร์ม — ฟอร์มสาธารณะต้องตอบ "ส่งแล้ว" เมื่อ lead บันทึกแล้ว
 * ‼️ อีเมลเป็นภาษาไทยเสมอ (ทีมขายเป็นคนไทย) ไม่ว่าผู้ส่งฟอร์มจะใช้ภาษาไหน
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use Core\Logger;

final class LeadNotifier
{
    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private readonly Lead $leads = new Lead(),
        private readonly MailService $mail = new MailService(),
    ) {
    }

    public static function recipient(): string
    {
        // CMS → ตั้งค่า → อีเมลรับแจ้ง lead · ว่าง = config/site.php lead_email
        return SiteService::leadEmail();
    }

    /** @return string ผล: sent | failed | skipped */
    public function notify(int $id): string
    {
        $lead = $this->leads->find($id);

        if ($lead === null) {
            return 'failed';
        }

        try {
            [$subject, $body] = $this->message($lead);
            $this->mail->send(self::recipient(), $subject, $body, (string) ($lead['email'] ?? '') !== '' ? (string) $lead['email'] : null);
            $status = $this->mail->lastStatus();
            $error  = $this->mail->lastError();
        } catch (\Throwable $e) {
            Logger::exception($e);
            $status = 'failed';
            $error  = mb_substr($e->getMessage(), 0, 250);
        }

        try {
            $this->leads->update($id, [
                'notify_status'   => $status,
                'notify_attempts' => (int) $lead['notify_attempts'] + ($status === 'skipped' ? 0 : 1),
                'notify_error'    => $status === 'sent' ? null : ($error !== '' ? $error : null),
                'notified_at'     => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            ]);
        } catch (\Throwable $e) {
            Logger::exception($e);
        }

        if ($status === 'failed') {
            Logger::warning('lead_notify_failed', ['lead' => $id, 'error' => $error]);
        }

        return $status;
    }

    /**
     * ส่งรายการที่ค้าง (failed / pending เกิน 10 นาที) — ไม่เกิน MAX_ATTEMPTS ครั้งต่อ lead
     *
     * @return array{sent:int,failed:int,skipped:int}
     */
    public function retryBacklog(int $limit = 20): array
    {
        $out = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($this->leads->notifyBacklog($limit) as $lead) {
            if ((int) $lead['notify_attempts'] >= self::MAX_ATTEMPTS) {
                continue;
            }
            $out[$this->notify((int) $lead['id'])]++;
        }

        return $out;
    }

    public function backlogCount(): int
    {
        return $this->leads->countNotifyBacklog();
    }

    /** อีเมลทดสอบจากหน้าตั้งค่า — ผลตรง ๆ ของเซิร์ฟเวอร์อีเมล (ไม่ยุ่งกับ lead) · ภาษาไทยเหมือนอีเมลแจ้ง lead */
    public function test(string $to): array
    {
        $previous = LocaleService::current();
        LocaleService::use(LocaleService::DEFAULT);
        $subject = __('lead.mail_test_subject', ['brand' => SettingService::brand()]);
        $body    = __('lead.mail_test_body') . "\n\n" . date('Y-m-d H:i:s');
        LocaleService::use($previous);

        $ok = $this->mail->send($to, html_entity_decode($subject, ENT_QUOTES, 'UTF-8'), html_entity_decode($body, ENT_QUOTES, 'UTF-8'));

        return ['ok' => $ok, 'status' => $this->mail->lastStatus(), 'error' => $this->mail->lastError()];
    }

    /**
     * @param  array<string, mixed> $lead แถวจากตาราง leads
     * @return array{0:string,1:string}
     */
    private function message(array $lead): array
    {
        $previous = LocaleService::current();
        LocaleService::use(LocaleService::DEFAULT);

        try {
            $options = BlockService::leadOptions();
            $value   = static fn (string $field): string => trim((string) ($lead[$field] ?? ''));
            $label   = static fn (string $field): string => $value($field) === '' ? '-' : (string) ($options[$field][$value($field)] ?? $value($field));
            $plain   = static fn (string $field): string => $value($field) === '' ? '-' : $value($field);
            $form    = __('lead.forms.' . (string) $lead['form_key']);

            $lines = [
                __('lead.mail_intro'),
                '',
                '#' . (int) $lead['id'] . ' · ' . $form,
                __('lead.name') . ': ' . $plain('name'),
                __('lead.company') . ': ' . $plain('company'),
                __('lead.phone') . ': ' . $plain('phone'),
                __('lead.email') . ': ' . $plain('email'),
                __('lead.line_id') . ': ' . $plain('line_id'),
                __('lead.business_type') . ': ' . $label('business_type'),
                __('lead.has_system') . ': ' . $label('has_system'),
                __('lead.need') . ': ' . $label('need'),
                __('lead.member_estimate') . ': ' . $label('member_estimate'),
                __('lead.message') . ': ' . $plain('message'),
                '',
                __('lead.locale') . ': ' . $plain('locale'),
                __('lead.source_page') . ': ' . $plain('source_url'),
                __('lead.utm') . ': ' . $plain('utm'),
                __('lead.mail_received') . ': ' . format_date((string) $lead['created_at'], true),
                '',
                // APP_URL ไม่ใช่ canonical — ลิงก์ต้องเปิด CMS ของ environment ที่รับ lead นี้ (staging ≠ production)
                __('lead.mail_cms_link') . ': ' . rtrim((string) config('url', ''), '/') . '/admin/leads/' . (int) $lead['id'],
            ];
            $subject = __('lead.mail_subject', ['form' => $form, 'name' => $plain('name')]);
        } finally {
            LocaleService::use($previous);
        }

        // __() escape ค่าที่แทนใน placeholder เป็น HTML entity — อีเมลเป็นข้อความล้วนจึงถอดกลับ
        return [html_entity_decode($subject, ENT_QUOTES, 'UTF-8'), html_entity_decode(implode("\n", $lines), ENT_QUOTES, 'UTF-8')];
    }
}
