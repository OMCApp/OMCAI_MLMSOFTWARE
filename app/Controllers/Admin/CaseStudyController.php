<?php
/**
 * ผลงานลูกค้า / case study (CMS) — ‼️ เผยแพร่ได้เฉพาะเมื่อติ๊ก "ได้รับอนุญาตเผยแพร่จากลูกค้าแล้ว"
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\CaseStudy;
use App\Services\HtmlSanitizer;
use App\Services\MediaService;
use App\Services\SocialProofService;

final class CaseStudyController extends AdminController
{
    private SocialProofService $proof;

    public function __construct()
    {
        parent::__construct();
        $this->proof = new SocialProofService();
    }

    public function index(): void
    {
        $p       = $this->listParams(CaseStudy::SORTABLE, 'sort', 'asc');
        $filters = $this->filters(['status' => CaseStudy::STATUSES]);
        $result  = $this->proof->searchCaseStudies($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['consent'] = $row['consent_confirmed_at'] !== null ? 'yes' : 'no';
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.case_studies.title'),
            'pageLead'    => __('cms.case_studies.lead'),
            'pageActions' => [['label' => __('cms.case_studies.add'), 'url' => '/admin/case-studies/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => [PageController::statusFilter($filters['status'])],
            'listColumns' => [
                ['key' => 'title', 'label' => __('cms.field.title'), 'sort' => 'title', 'primary' => true, 'limit' => 70],
                ['key' => 'client_name', 'label' => __('cms.case_studies.client')],
                ['key' => 'consent', 'label' => __('cms.social.consent_col'), 'format' => 'badge', 'lang' => 'cms.social.consent_'],
                ['key' => 'status', 'label' => __('cms.field.status'), 'sort' => 'status', 'format' => 'badge', 'lang' => 'cms.status.'],
            ],
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/case-studies/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/case-studies/{id}', 'confirm' => 'title'],
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
        $row = $this->proof->findCaseStudy((int) $id);

        if ($row === null) {
            $this->notFound();
        }

        $this->form((int) $id, $row);
    }

    public function store(): void
    {
        $data   = SocialProofService::withConsent($this->input(), $this->bool('consent'), null);
        $errors = $this->proof->validateCaseStudy($data);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $id = $this->proof->createCaseStudy($data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/case-studies/' . $id);
    }

    public function update(string $id): void
    {
        $before = $this->proof->findCaseStudy((int) $id);

        if ($before === null) {
            $this->notFound();
        }

        $data   = SocialProofService::withConsent($this->input(), $this->bool('consent'), $before);
        $errors = $this->proof->validateCaseStudy($data + ['consent_confirmed_at' => $before['consent_confirmed_at']], (int) $id);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $this->proof->updateCaseStudy((int) $id, $data);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/case-studies/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->proof->findCaseStudy((int) $id) === null) {
            $this->notFound();
        }

        $this->proof->deleteCaseStudy((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/case-studies');
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        $status = $this->request->pick('status', CaseStudy::STATUSES, 'draft');

        return [
            'slug'             => rawurldecode($this->str('slug', 200)),
            'title'            => $this->str('title', 255),
            'client_name'      => $this->nullable('client_name', 150),
            'industry'         => $this->nullable('industry', 100),
            'summary'          => $this->nullable('summary', 3000),
            'content'          => (new HtmlSanitizer())->clean((string) $this->request->input('content', '')),
            'cover_image_id'   => $this->intOrNull('cover_image_id'),
            'status'           => $status,
            'published_at'     => $this->datetime('published_at') ?? ($status === 'published' ? date('Y-m-d H:i:s') : null),
            'sort'             => (int) $this->request->input('sort', 0),
            'seo_title'        => $this->nullable('seo_title', 255),
            'meta_description' => $this->nullable('meta_description', 500),
        ];
    }

    /** @param array<string, mixed> $row */
    private function form(?int $id, array $row): void
    {
        $media = new MediaService();
        $consentHint = ($row['consent_confirmed_at'] ?? null) !== null
            ? __('cms.social.consent_recorded', ['date' => format_date((string) $row['consent_confirmed_at'], true)])
            : __('cms.social.consent_hint');

        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.case_studies.add') : __('cms.case_studies.edit'),
            'pageWidth'      => 'wide',
            'pageBreadcrumb' => [['label' => __('cms.case_studies.title'), 'url' => '/admin/case-studies'],
                                 ['label' => $id === null ? __('cms.case_studies.add') : (string) $row['title']]],
            'formAction'     => $id === null ? '/admin/case-studies' : '/admin/case-studies/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [['fields' => [
                ['name' => 'title', 'label' => __('cms.field.title'), 'value' => $row['title'] ?? '', 'required' => true],
                ['name' => 'slug', 'label' => __('cms.field.slug'), 'value' => $row['slug'] ?? '', 'required' => true],
                ['name' => 'client_name', 'label' => __('cms.case_studies.client'), 'value' => $row['client_name'] ?? ''],
                ['name' => 'industry', 'label' => __('cms.case_studies.industry'), 'value' => $row['industry'] ?? ''],
                ['name' => 'summary', 'label' => __('cms.field.excerpt'), 'type' => 'textarea', 'value' => $row['summary'] ?? ''],
                ['name' => 'content', 'label' => __('cms.field.content'), 'type' => 'html', 'value' => $row['content'] ?? ''],
                ['name' => 'cover_image_id', 'label' => __('cms.field.featured_image'), 'type' => 'media', 'value' => (string) ($row['cover_image_id'] ?? '')],
                ['name' => 'seo_title', 'label' => __('cms.seo.seo_title'), 'value' => $row['seo_title'] ?? '', 'counter' => 60],
                ['name' => 'meta_description', 'label' => __('cms.seo.meta_description'), 'type' => 'textarea', 'value' => $row['meta_description'] ?? '', 'counter' => 160],
            ]], ['title' => __('cms.social.publish_section'), 'fields' => [
                ['name' => 'consent', 'label' => __('cms.social.consent'), 'type' => 'checkbox', 'value' => ($row['consent_confirmed_at'] ?? null) !== null,
                 'hint' => $consentHint],
                ['name' => 'status', 'label' => __('cms.field.status'), 'type' => 'select', 'value' => $row['status'] ?? 'draft',
                 'options' => PageController::statusOptions(), 'width' => 'medium'],
                ['name' => 'published_at', 'label' => __('cms.field.published_at'), 'type' => 'datetime-local', 'width' => 'medium',
                 'value' => ($row['published_at'] ?? null) !== null ? date('Y-m-d\TH:i', (int) strtotime((string) $row['published_at'])) : ''],
                ['name' => 'sort', 'label' => __('cms.field.sort'), 'type' => 'number', 'value' => (string) ($row['sort'] ?? 0), 'width' => 'short'],
            ]]],
            'cmsMediaOptions' => $media->pickerOptions(),
            'cmsMediaData'    => $media->editorImages(),
            'formCancel'     => '/admin/case-studies',
            'formDelete'     => $id === null ? null : ['url' => '/admin/case-studies/' . $id, 'name' => (string) $row['title']],
        ]);
    }
}
