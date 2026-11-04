<?php
/**
 * บทความ (CMS) — URL /{Y}/{m}/{d}/{slug}/ จาก published_at · เนื้อหา HTML ผ่าน HtmlSanitizer ตอนบันทึก
 *
 * สิทธิ์: admin + editor (ContentRoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Post;
use App\Services\CategoryService;
use App\Services\MediaService;
use App\Services\PostService;
use App\Services\RevisionService;
use App\Services\TranslationService;
use Core\Path;

final class PostController extends AdminController
{
    private PostService $posts;

    public function __construct()
    {
        parent::__construct();
        $this->posts = new PostService();
    }

    public function index(): void
    {
        $p          = $this->listParams(Post::SORTABLE, 'published_at');
        $locales    = TranslationService::enabled('posts');
        $categories = (new CategoryService())->names();
        $filters    = $this->filters(['status' => Post::STATUSES, 'category_id' => array_map('strval', array_keys($categories))]
            + ($locales ? ['locale' => TranslationService::locales()] : []));
        $result     = $this->posts->search($filters, $p['q'], $p['sort'], $p['dir'], $p['page'], $p['per']);
        $missing    = TranslationService::missing('posts', $result['rows']);
        $result['rows'] = PageController::withLocaleLabels($result['rows']);

        foreach ($result['rows'] as $i => $row) {
            $result['rows'][$i]['category'] = $categories[(int) ($row['category_id'] ?? 0)] ?? '';
            $result['rows'][$i]['missing']  = PageController::missingLabel($missing[(int) $row['id']] ?? []);
        }

        $columns = [
            ['key' => 'title', 'label' => __('cms.field.title'), 'sort' => 'title', 'primary' => true, 'limit' => 70],
            ['key' => 'category', 'label' => __('cms.field.category')],
            ['key' => 'status', 'label' => __('cms.field.status'), 'sort' => 'status', 'format' => 'badge', 'lang' => 'cms.status.'],
            ['key' => 'published_at', 'label' => __('cms.field.published_at'), 'sort' => 'published_at', 'format' => 'date'],
        ];

        if ($locales) {
            $columns[] = ['key' => 'locale_label', 'label' => __('cms.field.locale'), 'format' => 'badge', 'badgeKind' => 'type'];
            $columns[] = ['key' => 'missing', 'label' => __('cms.translation.missing_col')];
        }

        $listFilters = [
            PageController::statusFilter($filters['status']),
            ['name' => 'category_id', 'label' => __('cms.field.category'), 'value' => $filters['category_id'], 'options' => ['' => '—'] + $categories],
        ];

        if ($locales) {
            $listFilters[] = PageController::localeFilter($filters['locale']);
        }

        $this->view('admin/crud/index', [
            'title'       => __('cms.posts.title'),
            'pageLead'    => __('cms.posts.lead'),
            'pageActions' => [['label' => __('cms.posts.add'), 'url' => '/admin/posts/create', 'icon' => 'plus', 'style' => 'primary']],
            'listQ'       => $p['q'],
            'listFilters' => $listFilters,
            'listColumns' => $columns,
            'listActions' => [
                ['icon' => 'edit', 'label' => __('common.edit'), 'url' => '/admin/posts/{id}'],
                ['icon' => 'trash', 'label' => __('common.delete'), 'url' => '/admin/posts/{id}', 'confirm' => 'title'],
            ],
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $p['page'], 'perPage' => $p['per'],
            'sort' => $p['sort'], 'dir' => $p['dir'],
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['status' => 'draft', 'robots' => 'index', 'author_name' => '', 'locale' => TranslationService::default()]);
    }

    public function edit(string $id): void
    {
        $post = $this->posts->find((int) $id);

        if ($post === null) {
            $this->notFound();
        }

        $this->form((int) $id, $post);
    }

    public function store(): void
    {
        $data   = $this->input();
        $errors = $this->posts->validateForCms($data, null);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $saved = $this->posts->save(null, $data, false);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/posts/' . $saved['id']);
    }

    public function update(string $id): void
    {
        if ($this->posts->find((int) $id) === null) {
            $this->notFound();
        }

        $data   = $this->input();
        $errors = $this->posts->validateForCms($data, (int) $id);

        if ($errors !== []) {
            $this->fail($errors);
        }

        $saved = $this->posts->save((int) $id, $data, $this->bool('redirect_old_path'));
        $this->flash('success', $saved['redirect'] !== null
            ? __('cms.common.saved_with_redirect', ['from' => $saved['redirect']])
            : __('cms.common.saved'));
        $this->redirect('/admin/posts/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        if ($this->posts->find((int) $id) === null) {
            $this->notFound();
        }

        $this->posts->delete((int) $id);
        $this->flash('success', __('cms.common.deleted'));
        $this->redirect('/admin/posts');
    }

    public function translate(string $id): void
    {
        $result = $this->posts->translate((int) $id, (string) $this->request->input('locale', ''));

        if ($result['error'] !== null) {
            $this->flash('warning', __($result['error']));
            $this->redirect('/admin/posts/' . ($result['id'] > 0 ? $result['id'] : (int) $id));
        }

        $this->flash('success', __('cms.translation.created'));
        $this->redirect('/admin/posts/' . $result['id']);
    }

    public function revision(string $id, string $revisionId): void
    {
        $post     = $this->posts->find((int) $id);
        $snapshot = (new RevisionService())->snapshot((int) $revisionId);

        if ($post === null || $snapshot === null || (int) ($snapshot['id'] ?? 0) !== (int) $id) {
            $this->notFound();
        }

        $this->view('admin/revision', [
            'title'           => __('cms.revisions.view'),
            'pageBreadcrumb'  => [['label' => __('cms.posts.title'), 'url' => '/admin/posts'],
                                  ['label' => (string) $post['title'], 'url' => '/admin/posts/' . (int) $id],
                                  ['label' => __('cms.revisions.view')]],
            'revisionFields'  => PageController::diff($post, $snapshot,
                ['title', 'excerpt', 'content', 'seo_title', 'meta_description', 'canonical_override', 'robots', 'author_name']),
            'revisionRestore' => '/admin/posts/' . (int) $id . '/revisions/' . (int) $revisionId . '/restore',
        ]);
    }

    public function restore(string $id, string $revisionId): void
    {
        if (!$this->posts->restore((int) $id, (int) $revisionId)) {
            $this->notFound();
        }

        $this->flash('success', __('cms.revisions.restored'));
        $this->redirect('/admin/posts/' . (int) $id);
    }

    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function input(): array
    {
        $status = $this->request->pick('status', Post::STATUSES, 'draft');
        $data   = [
            // slug ไทยได้ — เก็บแบบ decode
            'slug'               => rawurldecode($this->str('slug', 200)),
            'title'              => $this->str('title', 255),
            'excerpt'            => $this->nullable('excerpt', 2000),
            'content'            => (string) $this->request->input('content', ''),
            'featured_image_id'  => $this->intOrNull('featured_image_id'),
            'category_id'        => $this->intOrNull('category_id'),
            'author_name'        => $this->nullable('author_name', 100),
            'status'             => $status,
            'published_at'       => $this->datetime('published_at') ?? ($status === 'published' ? date('Y-m-d H:i:s') : null),
            'seo_title'          => $this->nullable('seo_title', 255),
            'meta_description'   => $this->nullable('meta_description', 500),
            'canonical_override' => $this->nullable('canonical_override', 500),
            'robots'             => $this->str('robots', 20) ?: 'index',
            'og_image_id'        => $this->intOrNull('og_image_id'),
        ];

        if (TranslationService::enabled('posts')) {
            $data['locale'] = TranslationService::normalize((string) $this->request->input('locale', ''));
        }

        return $data;
    }

    /** @param array<string, mixed> $post */
    private function form(?int $id, array $post): void
    {
        $media    = new MediaService();
        $locales  = TranslationService::enabled('posts');
        $isLive   = $id !== null && PostService::isLive($post);
        $url      = ($post['published_at'] ?? null) !== null && ($post['slug'] ?? '') !== '' ? PostService::permalink($post) : '/blog/';

        $main = [
            ['name' => 'title', 'label' => __('cms.field.title'), 'value' => $post['title'] ?? '', 'required' => true],
            ['name' => 'slug', 'label' => __('cms.field.slug'), 'value' => $post['slug'] ?? '', 'required' => true, 'hint' => __('cms.posts.slug_hint')],
        ];

        if ($isLive) {
            $main[] = ['name' => 'redirect_old_path', 'label' => __('cms.posts.redirect_old'), 'type' => 'checkbox', 'value' => true,
                       'hint' => __('cms.pages.redirect_old_hint', ['path' => $url])];
        }

        $main[] = ['name' => 'excerpt', 'label' => __('cms.field.excerpt'), 'type' => 'textarea', 'value' => $post['excerpt'] ?? '', 'rows' => 3];
        $main[] = ['name' => 'content', 'label' => __('cms.field.content'), 'type' => 'html', 'value' => $post['content'] ?? '',
                   'hint' => __('cms.posts.content_hint')];

        $meta = [
            ['name' => 'status', 'label' => __('cms.field.status'), 'type' => 'select', 'value' => $post['status'] ?? 'draft',
             'options' => PageController::statusOptions(), 'width' => 'medium'],
            ['name' => 'published_at', 'label' => __('cms.field.published_at'), 'type' => 'datetime-local', 'width' => 'medium',
             'value' => ($post['published_at'] ?? null) !== null ? date('Y-m-d\TH:i', (int) strtotime((string) $post['published_at'])) : '',
             'hint' => __('cms.posts.date_hint')],
            ['name' => 'category_id', 'label' => __('cms.field.category'), 'type' => 'select', 'value' => (string) ($post['category_id'] ?? ''),
             'options' => ['' => '—'] + (new CategoryService())->names(), 'width' => 'medium'],
            ['name' => 'author_name', 'label' => __('cms.field.author_name'), 'value' => $post['author_name'] ?? '', 'width' => 'medium'],
            ['name' => 'featured_image_id', 'label' => __('cms.field.featured_image'), 'type' => 'media', 'value' => (string) ($post['featured_image_id'] ?? '')],
        ];

        if ($locales) {
            $meta[] = ['name' => 'locale', 'label' => __('cms.field.locale'), 'type' => 'select', 'value' => (string) ($post['locale'] ?? TranslationService::default()),
                       'options' => PageController::localeOptions(), 'width' => 'medium'];
        }

        $this->view('admin/crud/form', [
            'title'          => $id === null ? __('cms.posts.add') : __('cms.posts.edit'),
            'pageWidth'      => 'wide',
            'pageBreadcrumb' => [['label' => __('cms.posts.title'), 'url' => '/admin/posts'],
                                 ['label' => $id === null ? __('cms.posts.add') : (string) $post['title']]],
            'formAction'     => $id === null ? '/admin/posts' : '/admin/posts/' . $id,
            'formMethod'     => $id === null ? 'POST' : 'PATCH',
            'formSections'   => [
                ['title' => __('cms.posts.section_main'), 'fields' => $main],
                ['title' => __('cms.pages.section_publish'), 'fields' => $meta, 'grid' => true],
            ],
            'formPanels'     => ['seo-panel'],
            'formAside'      => $id === null ? [] : ['translations', 'revisions'],
            'formCancel'     => '/admin/posts',
            'formDelete'     => $id === null ? null : ['url' => '/admin/posts/' . $id, 'name' => (string) $post['title']],
            'formLinks'      => $id === null ? [] : [[
                'label' => __('cms.common.preview'), 'icon' => 'view', 'external' => true,
                'url'   => url(Path::encode($url)) . '?preview=1',
            ]],
            'cmsMediaOptions' => $media->pickerOptions(),
            'cmsMediaData'    => $media->editorImages(),
            'seoValues'       => $post,
            'seoUrl'          => rtrim((string) config('seo.canonical_url'), '/') . Path::encode($url),
            'seoFallback'     => (string) ($post['title'] ?? ''),
            'cmsTranslations' => $id !== null && $locales ? [
                'id' => $id, 'current' => (string) ($post['locale'] ?? TranslationService::default()), 'base' => '/admin/posts',
                'rows' => TranslationService::siblings('posts', $post['translation_group'] ?? null),
            ] : null,
            'cmsRevisions'    => $id === null ? [] : (new RevisionService())->history('post', $id),
            'cmsRevisionBase' => '/admin/posts/' . $id,
        ]);
    }
}
