<?php
/**
 * lead จากฟอร์มเว็บ — รายการ (กรองสถานะ/ฟอร์ม/วันที่/ภาษา) · รายละเอียด · เปลี่ยนสถานะ · มอบหมาย · โน้ต (เพิ่มอย่างเดียว)
 * · ส่งออก CSV (UTF-8 + BOM ให้ Excel อ่านไทยได้) · ลบ (soft)
 *
 * สิทธิ์: admin + sales (LeadRoleMiddleware ที่กลุ่ม route) — editor เปิดไม่ได้
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Lead;
use App\Services\LeadService;
use App\Services\TranslationService;
use Core\Security;

final class LeadController extends AdminController
{
    private LeadService $leads;

    public function __construct()
    {
        parent::__construct();
        $this->leads = new LeadService();
    }

    public function index(): void
    {
        $p       = $this->listParams(Lead::SORTABLE, 'created_at');
        $filters = $this->currentFilters();
        $result  = $this->leads->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);
        $result['rows'] = PageController::withLocaleLabels($result['rows']);
        $locales = $this->leads->hasLocale();

        $columns = [
            ['key' => 'name', 'label' => __('cms.leads.name'), 'primary' => true],
            ['key' => 'company', 'label' => __('cms.leads.company')],
            ['key' => 'form_key', 'label' => __('cms.leads.form'), 'sort' => 'form_key', 'format' => 'badge', 'badgeKind' => 'type', 'lang' => 'cms.leads.form_'],
            ['key' => 'status', 'label' => __('cms.field.status'), 'sort' => 'status', 'format' => 'badge', 'lang' => 'cms.leads.status_'],
            ['key' => 'assignee', 'label' => __('cms.leads.assignee')],
            ['key' => 'created_at', 'label' => __('cms.leads.received'), 'sort' => 'created_at', 'format' => 'datetime'],
        ];

        if ($locales) {
            array_splice($columns, 3, 0, [['key' => 'locale_label', 'label' => __('cms.field.locale')]]);
        }

        $listFilters = [
            ['name' => 'status', 'label' => __('cms.field.status'), 'value' => $filters['status'], 'options' => ['' => '—'] + self::statusOptions()],
            ['name' => 'form_key', 'label' => __('cms.leads.form'), 'value' => $filters['form_key'], 'options' => ['' => '—'] + self::formOptions()],
            ['name' => 'from', 'label' => __('date.from'), 'type' => 'date', 'value' => $filters['from']],
            ['name' => 'to', 'label' => __('date.to'), 'type' => 'date', 'value' => $filters['to']],
        ];

        if ($locales) {
            $listFilters[] = PageController::localeFilter($filters['locale']);
        }

        foreach ($this->leads->sourceColumns() as $column) {
            $listFilters[] = ['name' => $column, 'label' => __('cms.leads.' . $column), 'type' => 'text', 'value' => $filters[$column]];
        }

        $exportQuery = array_filter($filters + ['q' => $p['q']], static fn ($v): bool => (string) $v !== '');

        $this->view('admin/crud/index', [
            'title'       => __('cms.leads.title'),
            'pageLead'    => __('cms.leads.lead'),
            'pageActions' => [['label' => __('common.export'), 'url' => '/admin/leads/export' . ($exportQuery === [] ? '' : '?' . http_build_query($exportQuery)),
                               'icon' => 'download']],
            'listQ'       => $p['q'],
            'listFilters' => $listFilters,
            'listColumns' => $columns,
            'listActions' => [
                ['icon' => 'view', 'label' => __('common.view'), 'url' => '/admin/leads/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/leads/{id}', 'confirm' => 'name'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function show(string $id): void
    {
        $lead = $this->leads->find((int) $id);

        if ($lead === null) {
            $this->notFound();
        }

        $utm = json_decode((string) ($lead['utm'] ?? ''), true);

        $this->view('admin/lead', [
            'title'          => __('cms.leads.detail'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.leads.title'), 'url' => '/admin/leads'], ['label' => (string) $lead['name']]],
            'lead'           => $lead,
            'leadUtm'        => is_array($utm) ? $utm : [],
            'leadNotes'      => $this->leads->notes((int) $id),
            'leadStatuses'   => self::statusOptions(),
            'leadAssignees'  => ['' => '—'] + $this->leads->assignees(),
        ]);
    }

    public function update(string $id): void
    {
        if ($this->leads->find((int) $id) === null) {
            $this->notFound();
        }

        $errors = $this->leads->manage((int) $id, (string) $this->request->input('status', ''), $this->intOrNull('assigned_to'));

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/leads/' . (int) $id);
    }

    public function note(string $id): void
    {
        if ($this->leads->find((int) $id) === null) {
            $this->notFound();
        }

        if (!$this->leads->addNote((int) $id, (string) $this->request->input('body', ''))) {
            $this->fail(['body' => 'cms.leads.error_note']);
        }

        $this->flash('success', __('cms.leads.note_added'));
        $this->redirect('/admin/leads/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->leads->find((int) $id) === null) {
            $this->notFound();
        }

        $this->leads->delete((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/leads');
    }

    /** ‼️ ส่งออกตามตัวกรองที่ใช้อยู่ · BOM ให้ Excel อ่านไทยได้ · ทุกช่องกัน CSV injection (LeadService) */
    public function export(): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="leads-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");

        foreach ($this->leads->exportRows($this->currentFilters(), mb_substr((string) $this->request->input('q', ''), 0, 100)) as $line) {
            // ‼️ ทุกช่องกัน CSV injection — ค่าขึ้นต้น = + - @ เป็นสูตรใน Excel
            fputcsv($out, array_map(static fn ($cell): string => Security::csvCell($cell), $line));
        }

        fclose($out);
        exit;
    }

    /** @return array<string, string> */
    private function currentFilters(): array
    {
        $filters = $this->filters([
            'status'   => Lead::STATUSES,
            'form_key' => Lead::FORM_KEYS,
            'locale'   => TranslationService::locales(),
        ]);

        foreach (['from', 'to'] as $key) {
            $value         = $this->str($key, 10);
            $filters[$key] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
        }

        // ข้อความค้นในที่มาของ lead — ผูก placeholder ใน Model
        foreach (['landing_page', 'referrer', 'utm'] as $key) {
            $filters[$key] = $this->str($key, 200);
        }

        return $filters;
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        $out = [];

        foreach (Lead::STATUSES as $status) {
            $out[$status] = __('cms.leads.status_' . $status);
        }

        return $out;
    }

    /** @return array<string, string> */
    private static function formOptions(): array
    {
        $out = [];

        foreach (Lead::FORM_KEYS as $key) {
            $out[$key] = __('cms.leads.form_' . $key);
        }

        return $out;
    }
}
