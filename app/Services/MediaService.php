<?php
/**
 * ไฟล์สื่อ — แปลง media id เป็นข้อมูลรูปที่ view/SEO ใช้ได้ทันที + คลังสื่อของ CMS (อัปโหลด/แปลง WebP/ขนาดย่อย)
 *
 * ‼️ รูปของ CMS เก็บใน public/uploads/YYYY/MM/ (เสิร์ฟตรงและ cache ได้) — ต่างจากมาตรฐานที่เก็บไฟล์ใน storage/
 *    เพราะรูปของเว็บสาธารณะต้องโหลดตรงจาก web server (project/NOTES.md Phase 3)
 * ‼️ รับเฉพาะ jpg/png/gif/webp — ตรวจ mime จริงด้วย finfo (Core\Uploader) · ไม่รับ SVG (ฝังสคริปต์ได้)
 * ‼️ alt บังคับตอนอัปโหลดและตอนแก้
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Core\Logger;
use Core\Uploader;

final class MediaService
{
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const WIDTHS     = [480, 768, 1200, 1600];
    private const QUALITY   = 82;

    /** โฟลเดอร์รูปเดิมของ WordPress ที่ลงทะเบียนเข้าคลังได้โดยไม่ย้ายไฟล์ */
    public const LEGACY_DIR = '/wp-content/uploads';

    public function __construct(private readonly Media $media = new Media())
    {
    }

    /**
     * @param  array<int, int|null> $ids
     * 'path' = ต้นฉบับ (og:image / schema) · 'src' + 'srcset' = WebP สำหรับแสดงบนหน้า (ถ้ามี)
     *
     * @return array<int, array{id:int,path:string,src:string,srcset:array<int,string>,alt:string,width:?int,height:?int,mime:string}>
     */
    public function images(array $ids): array
    {
        $out = [];

        foreach ($this->media->findMany(array_filter($ids, static fn ($id) => $id !== null)) as $id => $row) {
            $display  = self::imageData($row);
            $out[$id] = [
                'id'     => $id,
                'path'   => (string) $row['path'],
                'src'    => $display['src'],
                'srcset' => $display['srcset'],
                'alt'    => (string) $row['alt'],
                'width'  => $row['width'] !== null ? (int) $row['width'] : null,
                'height' => $row['height'] !== null ? (int) $row['height'] : null,
                'mime'   => (string) $row['mime'],
            ];
        }

        return $out;
    }

    /** @return array{id:int,path:string,alt:string,width:?int,height:?int,mime:string}|null */
    public function image(?int $id): ?array
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        return $this->images([$id])[$id] ?? null;
    }

    /**
     * รูปในรูปแบบที่ partial ของบล็อกรับ ['src','alt','width','height'] — ใช้ WebP ขนาดเต็มถ้ามี
     *
     * @param  array<string, mixed> $row
     * @return array{src:string,alt:string,width:?int,height:?int}
     */
    public static function imageData(array $row): array
    {
        $sizes = json_decode((string) ($row['sizes'] ?? ''), true);
        $src   = is_array($sizes) && is_string($sizes['webp'] ?? null) ? $sizes['webp'] : (string) $row['path'];

        // srcset: รูปย่อทุกขนาด (คีย์ตัวเลข = ความกว้าง) + ตัวเต็ม — partial ใส่ asset() ให้แต่ละ URL เอง
        $srcset = [];
        foreach (is_array($sizes) ? $sizes : [] as $width => $url) {
            if (is_int($width) && is_string($url)) {
                $srcset[$width] = $url;
            }
        }
        if ($srcset !== [] && $row['width'] !== null) {
            $srcset[(int) $row['width']] = $src;
        }
        ksort($srcset);

        return [
            'src'    => $src,
            'srcset' => $srcset,
            'alt'    => (string) $row['alt'],
            'width'  => $row['width'] !== null ? (int) $row['width'] : null,
            'height' => $row['height'] !== null ? (int) $row['height'] : null,
        ];
    }

    /** id ของรูปที่ URL นี้ชี้ (ต้นฉบับหรือ WebP) — 0 ถ้าไม่พบ */
    public function idByUrl(string $url): int
    {
        $row = $this->media->findByUrl($url);

        return $row === null ? 0 : (int) $row['id'];
    }

    /**
     * ‼️ alt บังคับ (PLAN.md "SEO ที่ทุกหน้า public ต้องมี")
     *
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['alt'] ?? '')) === '') {
            $errors['alt'] = 'cms.media.error_alt_required';
        }
        if (!str_starts_with((string) ($data['path'] ?? ''), '/')) {
            $errors['path'] = 'cms.media.error_path';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->media->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return $this->media->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->media->delete($id);
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->media->find($id);
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(string $term, int $page, int $perPage): array
    {
        return $this->media->adminSearch([], $term, 'id', 'desc', $page, $perPage);
    }

    /** @return array<int, string> id => ป้าย (ชื่อ · path) สำหรับช่องเลือกรูป */
    public function pickerOptions(): array
    {
        $out = [];

        foreach ($this->media->allForPicker() as $row) {
            $name = (string) ($row['title'] ?: basename((string) $row['path']));
            $out[(int) $row['id']] = $name . ((string) $row['alt'] === '' ? ' ⚠' : '');
        }

        return $out;
    }

    /**
     * รูปสำหรับปุ่ม "แทรกรูป" ของตัวแก้บทความ — เฉพาะรูปที่มี alt แล้ว (alt บังคับ)
     *
     * @return array<int, array{label:string, src:string, alt:string, width:?int, height:?int}>
     */
    public function editorImages(): array
    {
        $out = [];

        foreach ($this->media->allForEditor() as $row) {
            $out[(int) $row['id']] = ['label' => (string) ($row['title'] ?: basename((string) $row['path']))] + self::imageData($row);
        }

        return $out;
    }

    /**
     * อัปโหลดรูปใหม่: ตรวจชนิด → บันทึก → WebP + ขนาดย่อย → แถว media
     *
     * @param  array<string, mixed> $file รายการจาก $_FILES
     * @return array{ok:bool, id:int, errors:array<string,string>}
     */
    public function upload(?array $file, string $alt, string $title): array
    {
        $errors = [];

        if (trim($alt) === '') {
            $errors['alt'] = 'cms.media.error_alt_required';
        }
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['file'] = 'cms.media.error_file_required';
        }
        if ($errors !== []) {
            return ['ok' => false, 'id' => 0, 'errors' => $errors];
        }

        $uploader = new Uploader((string) config('paths.media'), self::EXTENSIONS, (int) config('upload.max_bytes', 5_242_880));
        $stored   = $uploader->store((array) $file);

        if (!$stored['ok']) {
            return ['ok' => false, 'id' => 0, 'errors' => ['file' => 'cms.media.error_upload_' . $stored['error']]];
        }

        $path   = '/uploads/' . $stored['path'];
        $absolute = (string) config('paths.public') . $path;
        $info   = @getimagesize($absolute);

        if ($info === false) {
            $uploader->remove($stored['path']);

            return ['ok' => false, 'id' => 0, 'errors' => ['file' => 'cms.media.error_upload_bad_type']];
        }

        $sizes = $this->variants($absolute, $path, (int) $info[0], (int) $info[1], (string) $stored['mime']);

        $id = $this->media->create([
            'path'   => $path,
            'mime'   => $stored['mime'],
            'width'  => (int) $info[0],
            'height' => (int) $info[1],
            'alt'    => trim($alt),
            'title'  => trim($title) !== '' ? trim($title) : null,
            'sizes'  => $sizes === [] ? null : (string) json_encode($sizes, JSON_UNESCAPED_SLASHES),
            'bytes'  => (int) $stored['size'],
        ]);

        return ['ok' => true, 'id' => $id, 'errors' => []];
    }

    /**
     * WebP ขนาดเต็ม + ความกว้าง 480/768/1200/1600 (เฉพาะที่เล็กกว่าต้นฉบับ) ด้วย GD
     *
     * ‼️ GIF ไม่ย่อ (ภาพเคลื่อนไหวจะเหลือเฟรมเดียว) · GD ทำไม่ได้ = เก็บต้นฉบับอย่างเดียว ไม่ล้ม
     *
     * @return array<string, string> 'webp' => url · '<width>' => url
     */
    private function variants(string $absolute, string $path, int $width, int $height, string $mime): array
    {
        if ($mime === 'image/gif' || !function_exists('imagewebp') || $width <= 0 || $height <= 0) {
            return [];
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolute),
            'image/png'  => @imagecreatefrompng($absolute),
            'image/webp' => @imagecreatefromwebp($absolute),
            default      => false,
        };

        if ($source === false) {
            Logger::warning('media_gd_failed', ['path' => $path]);

            return [];
        }

        $base  = preg_replace('/\.[a-z0-9]+$/i', '', $path);
        $sizes = [];

        if ($mime !== 'image/webp' && $this->writeWebp($source, (string) config('paths.public') . $base . '.webp')) {
            $sizes['webp'] = $base . '.webp';
        }

        foreach (self::WIDTHS as $target) {
            if ($target >= $width) {
                continue;
            }

            $targetHeight = max(1, (int) round($height * $target / $width));
            $resized      = imagecreatetruecolor($target, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $target, $targetHeight, $width, $height);

            if ($this->writeWebp($resized, (string) config('paths.public') . $base . '-' . $target . '.webp')) {
                $sizes[(string) $target] = $base . '-' . $target . '.webp';
            }

            imagedestroy($resized);
        }

        imagedestroy($source);

        return $sizes;
    }

    /**
     * WebP ขนาดเต็ม + รูปย่อ ของไฟล์ที่อยู่ใน public/ แล้ว (รูปเดิมของ WordPress) — ใช้โดย tools/media/build-legacy-webp.php
     *
     * @return array<string, string> แบบเดียวกับ media.sizes · [] ถ้าแปลงไม่ได้
     */
    public function webpVariants(string $path): array
    {
        $absolute = (string) config('paths.public') . $path;
        $info     = is_file($absolute) ? @getimagesize($absolute) : false;

        return $info === false ? [] : $this->variants($absolute, $path, (int) $info[0], (int) $info[1], (string) $info['mime']);
    }

    private function writeWebp(\GdImage $image, string $file): bool
    {
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $ok = @imagewebp($image, $file, self::QUALITY);

        if ($ok) {
            @chmod($file, 0o644);
        }

        return $ok;
    }

    /**
     * แก้ alt / title
     *
     * @return array<string, string>
     */
    public function updateMeta(int $id, string $alt, string $title): array
    {
        if (trim($alt) === '') {
            return ['alt' => 'cms.media.error_alt_required'];
        }

        $this->media->update($id, ['alt' => trim($alt), 'title' => trim($title) !== '' ? trim($title) : null]);

        return [];
    }

    /**
     * ที่ที่รูปนี้ถูกใช้อยู่ — หน้า (บล็อก/OG) · บทความ (เนื้อหา/รูปเด่น/OG) · testimonials · case studies · settings
     *
     * ‼️ เทียบ path ด้วย LOCATE ไม่ใช่ LIKE — _ และ % ในชื่อไฟล์เป็น wildcard ของ LIKE
     *
     * @return array<int, array{kind:string, id:int, title:string}>
     */
    public function usage(int $id): array
    {
        $row = $this->media->find($id);

        if ($row === null) {
            return [];
        }

        $urls = [(string) $row['path']];

        foreach ((array) json_decode((string) ($row['sizes'] ?? ''), true) as $url) {
            if (is_string($url)) {
                $urls[] = $url;
            }
        }

        $usage = $this->media->usage($id, array_values(array_unique($urls)));

        // settings อ้างรูปด้วย media_id (OG เริ่มต้น · โลโก้ลูกค้า) — ไล่ใน PHP เพราะรูปแบบข้อความ JSON ต่างกันระหว่าง MySQL/MariaDB
        foreach (SettingService::keysReferencingMedia($id) as $key) {
            $usage[] = ['kind' => 'setting', 'id' => 0, 'title' => $key];
        }

        return $usage;
    }

    /**
     * ลบ — ถูกใช้อยู่ = ไม่ลบ (คืนรายการที่ใช้) · ไฟล์บนดิสก์ลบเฉพาะที่อัปโหลดผ่าน CMS (public/uploads)
     *
     * @return array{ok:bool, usage:array<int, array{kind:string, id:int, title:string}>}
     */
    public function remove(int $id): array
    {
        $usage = $this->usage($id);

        if ($usage !== []) {
            return ['ok' => false, 'usage' => $usage];
        }

        $row = $this->media->find($id);

        if ($row === null || !$this->media->delete($id)) {
            return ['ok' => false, 'usage' => []];
        }

        // รูปเดิมของ WordPress ห้ามลบไฟล์ — URL เดิมยังมี backlink/Google Images
        if (str_starts_with((string) $row['path'], '/uploads/')) {
            $public = (string) config('paths.public');
            $files  = [(string) $row['path']];

            foreach ((array) json_decode((string) ($row['sizes'] ?? ''), true) as $url) {
                if (is_string($url)) {
                    $files[] = $url;
                }
            }

            foreach ($files as $url) {
                $file = $public . str_replace(['..', "\0"], '', $url);

                if (str_starts_with($url, '/uploads/') && is_file($file) && !@unlink($file)) {
                    // แถวถูกลบแล้ว ไฟล์ค้างไม่อันตราย (ไม่มีที่ไหนอ้าง) แต่ต้องรู้ไว้
                    Logger::warning('media_file_not_removed', ['file' => $url, 'error' => error_get_last()['message'] ?? '']);
                }
            }
        }

        return ['ok' => true, 'usage' => []];
    }

    /**
     * ลงทะเบียนรูปเดิมใต้ public/wp-content/uploads/ ที่ยังไม่อยู่ในคลัง — ไม่ย้ายไฟล์ ไม่สร้าง WebP
     *
     * ‼️ alt ว่างไว้ (ไม่แต่งเอง) — หน้าคลังแสดงป้าย "ขาด alt" ให้คนเติม
     *
     * @return int จำนวนที่เพิ่ม
     */
    public function importLegacy(): int
    {
        $dir = (string) config('paths.public') . self::LEGACY_DIR;

        if (!is_dir($dir)) {
            return 0;
        }

        $known = array_flip($this->media->allPaths());
        $added = 0;

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());

            if (!$file->isFile() || !in_array($ext, self::EXTENSIONS, true)) {
                continue;
            }

            $url = self::LEGACY_DIR . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));

            if (isset($known[$url])) {
                continue;
            }

            $info = @getimagesize($file->getPathname());

            if ($info === false) {
                continue;
            }

            $this->media->create([
                'path'   => $url,
                'mime'   => (string) $info['mime'],
                'width'  => (int) $info[0],
                'height' => (int) $info[1],
                'alt'    => '',
                'title'  => null,
                'bytes'  => (int) $file->getSize(),
            ]);

            $known[$url] = true;
            $added++;
        }

        return $added;
    }

    /** จำนวนรูปที่ยังไม่มี alt */
    public function missingAltCount(): int
    {
        return $this->media->missingAltCount();
    }
}
