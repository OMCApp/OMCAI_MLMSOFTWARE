<?php
/**
 * รีวิวจากลูกค้า (CMS) — ‼️ เผยแพร่ได้เฉพาะเมื่อติ๊ก "ได้รับอนุญาตเผยแพร่จากลูกค้าแล้ว"
 * เว็บแสดงเฉพาะ published + ยืนยันการอนุญาตแล้ว (SocialProofService / Testimonial::published)
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Testimonial;
use App\Services\MediaService;
use App\Services\SocialProofService;

final class TestimonialController extends AdminController
{
    private SocialProofService $proof;

    public function __construct()
    {
        parent::__construct();
        $this->proof = new SocialProofService();
    }

    public function index(): void
    {
        $p       = $this->listParams(Testimonial::SORTABLE, 'sort', 'asc');
        $filters = $this->filters(['status' => Testimonial::STATUSES]);
        $result  = $this->proof->searchTestimonials($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['consent'] = $row['consent_confirmed_at'] !== null ? 'yes' : 'no';
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.testimonials.title'),
            'pageLead'    => __('cms.testimonials.lead'),
            'pageActions' => [['label' => __('cms.testimonials.add'), 'url' => '/admin/testimonials/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => [PageController::statusFilter($filters['status'])],
            'listColumns' => [
                ['key' => 'person_name', 'label' => __('cms.testimonials.person_name'), 'sort' => 'person_name', 'primary' => true],
                ['key' => 'company', 'label' => __('cms.testimonials.company')],
                ['key' => 'quote', 'label' => __('cms.testimonials.quote'), 'limit' => 60],
                ['key' => 'consent', 'label' => __('cms.social.consent_col'), 'format' => 'badge', 'lang' => 'cms.social.consent_'],
                ['key' => 'status', 'label' => __('cms.field.status'), 'sort' => 'status', 'format' => 'badge', 'lang' => 'cms.status.'],
            ],
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/testimonials/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/testimonials/{id}', 'confirm' => 'person_name'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['status' => 'draft', 'sort' => 0]);
    }

    public function edit(string $id): void
    {
        $row = $this->proof->findTestimonial((int) $id);

        if ($row === null) {
            $this->notFound();
        }

        $this->form((int) $id, $row);
    }

    public function store(): void
    {
        $data   = SocialProofService::withConsent($this->input(), $this->bool('consent'), null);
        $errors = $this->proof->validateTestimonial($data);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $id = $this->proof->createTestimonial($data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/testimonials/' . $id);
    }

    public function update(string $id): void
    {
        $before = $this->proof->findTestimonial((int) $id);

        if ($before === null) {
            $this->notFound();
        }

        $data   = SocialProofService::withConsent($this->input(), $this->bool('consent'), $before);
        $errors = $this->proof->validateTestimonial($data + ['consent_confirmed_at' => $before['consent_confirmed_at']]);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->proof->updateTestimonial((int) $id, $data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/testimonials/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->proof->findTestimonial((int) $id) === null) {
            $this->notFound();
        }

        $this->proof->deleteTestimonial((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/testimonials');
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return [
            'person_name'  => $this->str('person_name', 150),
            'person_title' => $this->nullable('person_title', 150),
            'company'      => $this->nullable('company', 150),
            'quote'        => $this->str('quote', 3000),
            'photo_id'     => $this->intOrNull('photo_id'),
            'consent_note' => $this->nullable('consent_note', 255),
            'sort'         => (int) $this->request->input('sort', 0),
            'status'       => $this->request->pick('status', Testimonial::STATUSES, 'draft'),
        ];
    }

    /** @param array<string, mixed> $row */
    private function form(?int $id, array $row): void
    {
        $consentHint = ($row['consent_confirmed_at'] ?? null) !== null
            ? __('cms.social.consent_recorded', ['date' => format_date((string) $row['consent_confirmed_at'], true)])
            : __('cms.social.consent_hint');

        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.testimonials.add') : __('cms.testimonials.edit'),
            'pageWidth'      => 'form',
            'pageBreadcrumb' => [['label' => __('cms.testimonials.title'), 'url' => '/admin/testimonials'],
                                 ['label' => $id === null ? __('cms.testimonials.add') : (string) $row['person_name']]],
            'formAction'     => $id === null ? '/admin/testimonials' : '/admin/testimonials/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [['fields' => [
                ['name' => 'person_name', 'label' => __('cms.testimonials.person_name'), 'value' => $row['person_name'] ?? '', 'required' => true],
                ['name' => 'person_title', 'label' => __('cms.testimonials.person_title'), 'value' => $row['person_title'] ?? ''],
                ['name' => 'company', 'label' => __('cms.testimonials.company'), 'value' => $row['company'] ?? ''],
                ['name' => 'quote', 'label' => __('cms.testimonials.quote'), 'type' => 'textarea', 'value' => $row['quote'] ?? '', 'required' => true],
                ['name' => 'photo_id', 'label' => __('cms.testimonials.photo'), 'type' => 'media', 'value' => (string) ($row['photo_id'] ?? '')],
            ]], ['title' => __('cms.social.publish_section'), 'fields' => [
                ['name' => 'consent', 'label' => __('cms.social.consent'), 'type' => 'checkbox', 'value' => ($row['consent_confirmed_at'] ?? null) !== null,
                 'hint' => $consentHint],
                ['name' => 'consent_note', 'label' => __('cms.social.consent_note'), 'value' => $row['consent_note'] ?? '', 'hint' => __('cms.social.consent_note_hint')],
                ['name' => 'status', 'label' => __('cms.field.status'), 'type' => 'select', 'value' => $row['status'] ?? 'draft',
                 'options' => PageController::statusOptions(), 'width' => 'medium'],
                ['name' => 'sort', 'label' => __('cms.field.sort'), 'type' => 'number', 'value' => (string) ($row['sort'] ?? 0), 'width' => 'short'],
            ]]],
            'cmsMediaOptions' => (new MediaService())->pickerOptions(),
            'formCancel'     => '/admin/testimonials',
            'formDelete'     => $id === null ? null : ['url' => '/admin/testimonials/' . $id, 'name' => (string) $row['person_name']],
        ]);
    }
}
