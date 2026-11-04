<?php
/**
 * redirect (CMS) — 301/302/410 · ตรงตัว/prefix · ห้ามวน · ห้ามต่อเป็นทอด (เสนอให้ชี้ปลายทางสุดท้าย)
 * + บันทึก 404 (เรียงตามจำนวนครั้ง) ที่สร้าง redirect ได้คลิกเดียว หรือซ่อนทิ้ง
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use App\Services\NotFoundService;
use App\Services\RedirectService;

final class RedirectController extends AdminController
{
    private RedirectService $redirects;

    public function __construct()
    {
        parent::__construct();
        $this->redirects = new RedirectService();
    }

    public function index(): void
    {
        $p       = $this->listParams(Redirect::SORTABLE, 'updated_at');
        $filters = $this->filters(['code' => ['301', '302', '410'], 'match_type' => Redirect::MATCH_TYPES, 'enabled' => ['0', '1']]);
        $result  = $this->redirects->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['state'] = (int) $row['enabled'] === 1 ? 'on' : 'off';
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.redirects.title'),
            'pageLead'    => __('cms.redirects.lead'),
            'pageActions' => [['label' => __('cms.redirects.add'), 'url' => '/admin/redirects/create', 'icon' => 'plus', 'style' => 'primary']],
            'listLinks'   => [['label' => __('cms.not_found.title'), 'url' => '/admin/not-found', 'icon' => 'warning']],
            'listQ'       => $p['q'],
            'listFilters' => [
                ['name' => 'code', 'label' => __('cms.redirects.code'), 'value' => $filters['code'],
                 'options' => ['' => '—', '301' => '301', '302' => '302', '410' => '410']],
                ['name' => 'enabled', 'label' => __('cms.redirects.enabled'), 'value' => $filters['enabled'],
                 'options' => ['' => '—', '1' => __('cms.redirects.state_on'), '0' => __('cms.redirects.state_off')]],
            ],
            'listColumns' => [
                ['key' => 'from_path', 'label' => __('cms.redirects.from'), 'sort' => 'from_path', 'primary' => true, 'limit' => 60],
                ['key' => 'to_path', 'label' => __('cms.redirects.to'), 'limit' => 60],
                ['key' => 'code', 'label' => __('cms.redirects.code'), 'sort' => 'code', 'format' => 'badge', 'badgeKind' => 'type'],
                ['key' => 'match_type', 'label' => __('cms.redirects.match'), 'lang' => 'cms.redirects.match_'],
                ['key' => 'state', 'label' => __('cms.redirects.enabled'), 'format' => 'badge', 'lang' => 'cms.redirects.state_'],
                ['key' => 'hits', 'label' => __('cms.redirects.hits'), 'sort' => 'hits', 'format' => 'number'],
                ['key' => 'last_hit_at', 'label' => __('cms.redirects.last_hit'), 'sort' => 'last_hit_at', 'format' => 'datetime'],
            ],
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/redirects/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/redirects/{id}', 'confirm' => 'from_path'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        // มาจากหน้า 404 → เติมต้นทางให้
        $this->form(null, ['from_path' => $this->str('from', 500), 'to_path' => '', 'code' => 301, 'match_type' => 'exact', 'enabled' => 1, 'note' => '']);
    }

    public function edit(string $id): void
    {
        $row = $this->redirects->find((int) $id);

        if ($row === null) {
            $this->notFound();
        }

        $this->form((int) $id, $row);
    }

    public function store(): void
    {
        $checked = $this->redirects->validateForCms($this->input(), null, $this->bool('resolve_chain'));

        if ($checked['errors'] !== []) {
            $this->failWithChain($checked);
        }

        $id = $this->redirects->create($checked['data']);
        $this->flash('success', __('cms.common.saved'));
        $this->warnIncoming((string) $checked['data']['from_path']);
        $this->redirect('/admin/redirects/' . $id);
    }

    public function update(string $id): void
    {
        if ($this->redirects->find((int) $id) === null) {
            $this->notFound();
        }

        $checked = $this->redirects->validateForCms($this->input(), (int) $id, $this->bool('resolve_chain'));

        if ($checked['errors'] !== []) {
            $this->failWithChain($checked);
        }

        $this->redirects->update((int) $id, $checked['data']);
        $this->flash('success', __('cms.common.saved'));
        $this->warnIncoming((string) $checked['data']['from_path']);
        $this->redirect('/admin/redirects/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->redirects->find((int) $id) === null) {
            $this->notFound();
        }

        $this->redirects->delete((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/redirects');
    }

    // ------------------------------------------------------------- 404 log

    public function notFoundIndex(): void
    {
        $p      = $this->listParams(NotFoundLog::SORTABLE, 'hits');
        $result = (new NotFoundService())->search($p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['from_encoded'] = (string) $row['path'];
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.not_found.title'),
            'pageLead'    => __('cms.not_found.lead'),
            'listLinks'   => [['label' => __('cms.redirects.title'), 'url' => '/admin/redirects', 'icon' => 'link']],
            'listQ'       => $p['q'],
            'listColumns' => [
                ['key' => 'path', 'label' => __('cms.not_found.path'), 'sort' => 'path', 'primary' => true, 'limit' => 70],
                ['key' => 'hits', 'label' => __('cms.redirects.hits'), 'sort' => 'hits', 'format' => 'number'],
                ['key' => 'last_seen_at', 'label' => __('cms.not_found.last_seen'), 'sort' => 'last_seen_at', 'format' => 'datetime'],
                ['key' => 'referrer', 'label' => __('cms.not_found.referrer'), 'limit' => 50],
            ],
            'listActions' => [
                ['icon' => 'link', 'label' => __('cms.not_found.create_redirect'), 'url' => '/admin/redirects/create?from={from_encoded}'],
                ['icon' => 'trash', 'label' => __('cms.not_found.ignore'), 'url' => '/admin/not-found/{id}', 'confirm' => 'path'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function notFoundDismiss(string $id): void
    {
        $service = new NotFoundService();

        if ($service->find((int) $id) === null) {
            $this->notFound();
        }

        $service->dismiss((int) $id);
        $this->flash('success', __('cms.not_found.ignored'));
        $this->redirect('/admin/not-found');
    }

    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function input(): array
    {
        $code = (int) $this->request->input('code', 301);
        $to   = $this->str('to_path', 500);

        return [
            'from_path'  => rawurldecode($this->str('from_path', 500)),
            'to_path'    => $code === 410 ? null : (preg_match('#^https?://#i', $to) === 1 ? $to : rawurldecode($to)),
            'code'       => $code,
            'match_type' => $this->request->pick('match_type', Redirect::MATCH_TYPES, 'exact'),
            'enabled'    => $this->bool('enabled') ? 1 : 0,
            'note'       => $this->nullable('note', 255),
        ];
    }

    /** chain → บอกปลายทางสุดท้ายในข้อความ และให้ติ๊ก "ชี้ไปปลายทางสุดท้าย" แล้วบันทึกอีกครั้ง */
    private function failWithChain(array $checked): never
    {
        if (($checked['errors']['to_path'] ?? '') === 'cms.redirect.error_chain') {
            $this->flash('warning', __('cms.redirects.chain_offer', ['target' => (string) $checked['final']]));
        }

        $this->fail($checked['errors']);
    }

    /** กฎอื่นที่ชี้มาที่ต้นทางนี้จะกลายเป็น chain — เตือน (ไม่บล็อก) */
    private function warnIncoming(string $from): void
    {
        $incoming = $this->redirects->pointingTo($from);

        if ($incoming !== []) {
            $this->flash('warning', __('cms.redirects.incoming_warning', ['count' => (string) count($incoming)]));
        }
    }

    /** @param array<string, mixed> $row */
    private function form(?int $id, array $row): void
    {
        $fields = [
            ['name' => 'from_path', 'label' => __('cms.redirects.from'), 'value' => $row['from_path'] ?? '', 'required' => true, 'hint' => __('cms.redirects.from_hint')],
            ['name' => 'to_path', 'label' => __('cms.redirects.to'), 'value' => $row['to_path'] ?? '', 'hint' => __('cms.redirects.to_hint')],
            ['name' => 'resolve_chain', 'label' => __('cms.redirects.resolve_chain'), 'type' => 'checkbox', 'value' => false,
             'hint' => __('cms.redirects.resolve_chain_hint')],
            ['name' => 'code', 'label' => __('cms.redirects.code'), 'type' => 'select', 'value' => (string) ($row['code'] ?? 301), 'width' => 'medium',
             'options' => ['301' => __('cms.redirects.code_301'), '302' => __('cms.redirects.code_302'), '410' => __('cms.redirects.code_410')]],
            ['name' => 'match_type', 'label' => __('cms.redirects.match'), 'type' => 'select', 'value' => (string) ($row['match_type'] ?? 'exact'),
             'width' => 'medium', 'options' => ['exact' => __('cms.redirects.match_exact'), 'prefix' => __('cms.redirects.match_prefix')]],
            ['name' => 'enabled', 'label' => __('cms.redirects.enabled'), 'type' => 'checkbox', 'value' => (int) ($row['enabled'] ?? 1) === 1],
            ['name' => 'note', 'label' => __('cms.redirects.note'), 'value' => $row['note'] ?? ''],
        ];

        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.redirects.add') : __('cms.redirects.edit'),
            'pageWidth'      => 'form',
            'pageLead'       => $id === null ? '' : __('cms.redirects.stats', [
                'hits' => (string) (int) ($row['hits'] ?? 0), 'last' => format_date($row['last_hit_at'] ?? null, true),
            ]),
            'pageBreadcrumb' => [['label' => __('cms.redirects.title'), 'url' => '/admin/redirects'],
                                 ['label' => $id === null ? __('cms.redirects.add') : (string) $row['from_path']]],
            'formAction'     => $id === null ? '/admin/redirects' : '/admin/redirects/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [['fields' => $fields]],
            'formCancel'     => '/admin/redirects',
            'formDelete'     => $id === null ? null : ['url' => '/admin/redirects/' . $id, 'name' => (string) $row['from_path']],
        ]);
    }
}
