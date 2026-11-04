<?php
/**
 * อัปโหลดไฟล์อย่างปลอดภัย
 *
 * ‼️ กฎที่ห้ามลัดแม้แต่ข้อเดียว
 *    · ตรวจ mime จริงด้วย finfo ห้ามเชื่อ $_FILES['type'] ที่ client ส่งมาเพราะปลอมได้
 *    · ใช้ allowlist ไม่ใช่ blocklist
 *    · สุ่มชื่อไฟล์ใหม่ตอนบันทึก แต่เก็บชื่อเดิมไว้ในฐานข้อมูลเพื่อแสดงผล
 *    · เก็บนอก public/ แล้วเสิร์ฟผ่าน Controller ที่ตรวจสิทธิ์ก่อนเสมอ
 */

declare(strict_types=1);

namespace Core;

final class Uploader
{
    /** ชนิดที่อนุญาต — นามสกุล => mime ที่ยอมรับ */
    private const ALLOWED = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];

    /**
     * @param array<int, string> $extensions นามสกุลที่ยอมรับสำหรับจุดนี้
     */
    public function __construct(
        private readonly string $baseDir,
        private readonly array $extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        private readonly int $maxBytes = 5_242_880,
    ) {
    }

    /**
     * รับไฟล์เดียว
     *
     * @param  array<string, mixed> $file รายการจาก $_FILES
     * @return array{ok:bool,error:string,path:string,original:string,mime:string,size:int}
     */
    public function store(array $file, string $folder = ''): array
    {
        $fail = static fn (string $error): array => [
            'ok' => false, 'error' => $error, 'path' => '',
            'original' => '', 'mime' => '', 'size' => 0,
        ];

        $original = (string) ($file['name'] ?? '');
        $tmp      = (string) ($file['tmp_name'] ?? '');
        $error    = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $size     = (int) ($file['size'] ?? 0);

        if ($error !== UPLOAD_ERR_OK || $tmp === '') {
            return $fail('failed');
        }

        // ‼️ กันไม่ให้ถูกหลอกด้วย path ที่ไม่ได้มาจากการอัปโหลดจริง
        if (!is_uploaded_file($tmp)) {
            return $fail('failed');
        }

        if ($size <= 0 || $size > $this->maxBytes) {
            return $fail('too_large');
        }

        $extension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->extensions, true) || !isset(self::ALLOWED[$extension])) {
            return $fail('bad_type');
        }

        // ‼️ ตรวจ mime จริงจากเนื้อไฟล์ ไม่ใช่จากสิ่งที่ client บอกมา
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo === false ? '' : (string) finfo_file($finfo, $tmp);

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (!in_array($mime, self::ALLOWED[$extension], true)) {
            return $fail('bad_type');
        }

        $directory = $this->resolveDirectory($folder);

        if ($directory === null) {
            return $fail('failed');
        }

        // ชื่อบนดิสก์สุ่มใหม่เสมอ — ชื่อเดิมเก็บไว้ในฐานข้อมูลเพื่อแสดงผลและตอนดาวน์โหลด
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $path = $directory . '/' . $name;

        if (!move_uploaded_file($tmp, $path)) {
            return $fail('failed');
        }

        @chmod($path, 0o644);

        return [
            'ok'       => true,
            'error'    => '',
            'path'     => $this->relative($path),
            'original' => Security::safeFilename($original) === '' ? 'file' : mb_substr($original, 0, 255),
            'mime'     => $mime,
            'size'     => $size,
        ];
    }

    /**
     * รับหลายไฟล์จาก input เดียว
     *
     * ‼️ ไฟล์ที่ไม่ผ่านเงื่อนไขต้องบอกเหตุผลรายไฟล์ ไม่ใช่ปฏิเสธทั้งชุด
     *
     * @param  array<string, mixed> $files
     * @return array<int, array{ok:bool,error:string,path:string,original:string,mime:string,size:int}>
     */
    public function storeMany(array $files, string $folder = ''): array
    {
        $names = $files['name'] ?? null;

        if (!is_array($names)) {
            return [$this->store($files, $folder)];
        }

        $out = [];

        foreach (array_keys($names) as $index) {
            $out[] = $this->store([
                'name'     => $files['name'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error'    => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$index] ?? 0,
            ], $folder);
        }

        return $out;
    }

    /** แยกโฟลเดอร์ตามปี/เดือน ไม่กองไว้โฟลเดอร์เดียว */
    private function resolveDirectory(string $folder): ?string
    {
        // ‼️ safeFilename('') คืน 'file' — โฟลเดอร์ว่างต้องคงว่าง ไม่งั้นไฟล์ไปอยู่ใต้ /file/YYYY/MM
        $folder = $folder === '' ? '' : trim(Security::safeFilename($folder), '/');
        $path   = rtrim($this->baseDir, '/')
            . ($folder === '' ? '' : '/' . $folder)
            . '/' . date('Y/m');

        if (!is_dir($path) && !@mkdir($path, 0o755, true) && !is_dir($path)) {
            return null;
        }

        return $path;
    }

    private function relative(string $path): string
    {
        $base = str_replace('\\', '/', rtrim($this->baseDir, '/'));
        $path = str_replace('\\', '/', $path);

        return ltrim(substr($path, strlen($base)), '/');
    }

    /** ลบไฟล์ที่เก็บไว้ — รับ path แบบ relative ที่ store() คืนมาเท่านั้น */
    public function remove(string $relative): bool
    {
        $relative = str_replace(['..', "\0"], '', $relative);
        $path     = rtrim($this->baseDir, '/') . '/' . ltrim($relative, '/');

        return is_file($path) && @unlink($path);
    }

    public function maxBytes(): int
    {
        return $this->maxBytes;
    }

    /** @return array<int, string> */
    public function extensions(): array
    {
        return $this->extensions;
    }
}
