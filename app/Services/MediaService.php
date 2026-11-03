<?php
/**
 * ไฟล์สื่อ — แปลง media id เป็นข้อมูลรูปที่ view/SEO ใช้ได้ทันที
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;

final class MediaService
{
    public function __construct(private readonly Media $media = new Media())
    {
    }

    /**
     * @param  array<int, int|null> $ids
     * @return array<int, array{id:int,path:string,alt:string,width:?int,height:?int,mime:string}>
     */
    public function images(array $ids): array
    {
        $out = [];

        foreach ($this->media->findMany(array_filter($ids, static fn ($id) => $id !== null)) as $id => $row) {
            $out[$id] = [
                'id'     => $id,
                'path'   => (string) $row['path'],
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
     * ‼️ alt บังคับ (PLAN.md "SEO ที่ทุกหน้า public ต้องมี")
     *
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['alt'] ?? '')) === '') {
            $errors['alt'] = 'media.error_alt_required';
        }
        if (!str_starts_with((string) ($data['path'] ?? ''), '/')) {
            $errors['path'] = 'media.error_path';
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
}
