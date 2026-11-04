<?php
/**
 * คำถามที่พบบ่อย (CMS) — กลุ่ม · ลำดับ · สถานะ · ภาษา
 * บล็อก faq ของหน้าดึงตามกลุ่ม (data.group) เฉพาะที่ published
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Faq;
use App\Services\FaqService;
use App\Services\TranslationService;

final class FaqController extends AdminController
{
    private FaqService $faqs;

    public function __construct()
    {
        parent::__construct();
        $this->faqs = new FaqService();
    }

    public function index(): void
    {
        $p       = $this->listParams(Faq::SORTABLE, 'sort', 'asc');
        $filters = $this->filters(['status' => Faq::STATUSES, 'locale' => TranslationService::locales()]);
        $filters['group_key'] = mb_substr($this->str('group_key', 50), 0, 50);
        $result  = $this->faqs->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);
        $result['rows'] = PageController::withLocaleLabels($result['rows']);

        $this->view('admin/crud/index', [
            'title'       => __('cms.faqs.title'),
            'pageLead'    => __('cms.faqs.lead'),
            'pageActions' => [['label' => __('cms.faqs.add'), 'url' => '/admin/faqs/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => [
                PageController::statusFilter($filters['status']),
                PageController::localeFilter($filters['locale']),
                ['name' => 'group_key', 'label' => __('cms.faqs.group'), 'type' => 'text', 'value' => $filters['group_key']],
            ],
            'listColumns' => [
                ['key' => 'question', 'label' => __('cms.faqs.question'), 'primary' => true, 'limit' => 80],
                ['key' => 'group_key', 'label' => __('cms.faqs.group'), 'sort' => 'group_key', 'format' => 'badge', 'badgeKind' => 'type'],
                ['key' => 'locale_label', 'label' => __('cms.field.locale')],
                ['key' => 'sort', 'label' => __('cms.field.sort'), 'sort' => 'sort', 'format' => 'number'],
                ['key' => 'status', 'label' => __('cms.field.status'), 'sort' => 'status', 'format' => 'badge', 'lang' => 'cms.status.'],
            ],
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/faqs/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/faqs/{id}', 'confirm' => 'question'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['group_key' => 'general', 'sort' => 0, 'status' => 'draft', 'locale' => TranslationService::default()]);
    }

    public function edit(string $id): void
    {
        $row = $this->faqs->find((int) $id);

        if ($row === null) {
            $this->notFound();
        }

        $this->form((int) $id, $row);
    }

    public function store(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $id = $this->faqs->create($data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/faqs/' . $id);
    }

    public function update(string $id): void
    {
        if ($this->faqs->find((int) $id) === null) {
            $this->notFound();
        }

        $data   = $this->input();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->faqs->update((int) $id, $data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/faqs/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->faqs->find((int) $id) === null) {
            $this->notFound();
        }

        $this->faqs->delete((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/faqs');
    }

    /** @return array<string, string> */
    private function validate(array $data): array
    {
        $errors = $this->faqs->validate($data);

        if (preg_match('/^[a-z0-9_-]{1,50}$/', (string) $data['group_key']) !== 1) {
            $errors['group_key'] = 'cms.faq.error_group';
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return [
            'question'  => $this->str('question', 500),
            'answer'    => $this->str('answer', 5000),
            'group_key' => strtolower($this->str('group_key', 50)),
            'locale'    => TranslationService::normalize((string) $this->request->input('locale', '')),
            'sort'      => (int) $this->request->input('sort', 0),
            'status'    => $this->request->pick('status', Faq::STATUSES, 'draft'),
        ];
    }

    /** @param array<string, mixed> $row */
    private function form(?int $id, array $row): void
    {
        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.faqs.add') : __('cms.faqs.edit'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.faqs.title'), 'url' => '/admin/faqs'],
                                 ['label' => $id === null ? __('cms.faqs.add') : mb_substr((string) $row['question'], 0, 60)]],
            'formAction'     => $id === null ? '/admin/faqs' : '/admin/faqs/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [['fields' => [
                ['name' => 'question', 'label' => __('cms.faqs.question'), 'value' => $row['question'] ?? '', 'required' => true],
                ['name' => 'answer', 'label' => __('cms.faqs.answer'), 'type' => 'textarea', 'rows' => 6, 'value' => $row['answer'] ?? '', 'required' => true],
            ]], ['grid' => true, 'fields' => [
                ['name' => 'group_key', 'label' => __('cms.faqs.group'), 'value' => $row['group_key'] ?? 'general', 'hint' => __('cms.faqs.group_hint'), 'width' => 'medium'],
                ['name' => 'locale', 'label' => __('cms.field.locale'), 'type' => 'select', 'value' => (string) ($row['locale'] ?? TranslationService::default()),
                 'options' => PageController::localeOptions(), 'width' => 'medium'],
                ['name' => 'sort', 'label' => __('cms.field.sort'), 'type' => 'number', 'value' => (string) ($row['sort'] ?? 0), 'width' => 'short'],
                ['name' => 'status', 'label' => __('cms.field.status'), 'type' => 'select', 'value' => $row['status'] ?? 'draft',
                 'options' => PageController::statusOptions(), 'width' => 'medium'],
            ]]],
            'formCancel'     => '/admin/faqs',
            'formDelete'     => $id === null ? null : ['url' => '/admin/faqs/' . $id, 'name' => mb_substr((string) $row['question'], 0, 60)],
        ]);
    }
}
