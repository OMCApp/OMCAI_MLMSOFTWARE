<?php
/**
 * หน้าของเว็บสาธารณะ — URL มาจาก pages.path ตรงตัว (decode แล้ว ลงท้าย /)
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Page extends Model
{
    protected string $table = 'pages';

    protected array $fillable = [
        'parent_id', 'path', 'title', 'template', 'blocks', 'seo_title', 'meta_description',
        'canonical_override', 'robots', 'og_image_id', 'status', 'published_at', 'sort', 'locale', 'translation_group',
    ];

    /**
     * ทุกภาษาของหน้าเดียวกัน (รวมตัวเอง) — hreflang / ปุ่มสลับภาษา
     *
     * @return array<int, array<string, mixed>>
     */
    public function translations(string $group): array
    {
        return Database::select(
            'SELECT id, path, locale, status, published_at, robots, canonical_override FROM pages
             WHERE translation_group = :g AND deleted_at IS NULL ORDER BY id',
            ['g' => $group]
        );
    }

    /**
     * คู่ path ไทย → path ของคำแปลในภาษานี้ที่เผยแพร่แล้ว — เมนู/footer ของภาษาอื่น (App\Services\SiteService)
     *
     * @return array<int, array{th_path:string, tr_path:string}>
     */
    public function translatedPaths(string $locale): array
    {
        return Database::select(
            "SELECT th.path AS th_path, tr.path AS tr_path
               FROM pages th
               JOIN pages tr ON tr.translation_group = th.translation_group AND tr.locale = :locale
              WHERE th.locale = 'th' AND th.deleted_at IS NULL AND tr.deleted_at IS NULL
                AND tr.status = 'published' AND (tr.published_at IS NULL OR tr.published_at <= NOW())",
            ['locale' => $locale]
        );
    }

    /**
     * หน้าทั้งหมดที่มีกลุ่มคำแปล — sitemap ใช้จับคู่ hreflang ทีเดียว (ไม่ query ทีละหน้า)
     *
     * @return array<int, array<string, mixed>>
     */
    public function translationIndex(): array
    {
        return Database::select(
            "SELECT path, locale, translation_group FROM pages
             WHERE translation_group IS NOT NULL AND status = 'published' AND robots = 'index' AND deleted_at IS NULL
               AND (canonical_override IS NULL OR canonical_override = '')
               AND (published_at IS NULL OR published_at <= NOW())"
        );
    }

    public const STATUSES = ['draft', 'published'];
    public const ROBOTS   = ['index', 'noindex'];

    /** @return array<string, mixed>|null */
    public function findByPath(string $path): ?array
    {
        return Database::selectOne(
            'SELECT * FROM pages WHERE path = :path AND deleted_at IS NULL LIMIT 1',
            ['path' => $path]
        );
    }

    /**
     * ทุกหน้าแบบย่อ — ใช้หาเส้นทาง breadcrumb จาก parent_id โดยไม่ query ทีละชั้น
     *
     * @return array<int, array<string, mixed>>
     */
    public function outline(): array
    {
        return Database::select(
            'SELECT id, parent_id, path, title, status FROM pages WHERE deleted_at IS NULL'
        );
    }

    /**
     * หน้าที่เผยแพร่แล้วและให้ index ได้ — sitemap
     *
     * @return array<int, array<string, mixed>>
     */
    public function indexable(): array
    {
        return Database::select(
            "SELECT id, path, locale, translation_group, template, canonical_override, updated_at FROM pages
             WHERE status = 'published' AND robots = 'index' AND deleted_at IS NULL
               AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY sort, id"
        );
    }
}
