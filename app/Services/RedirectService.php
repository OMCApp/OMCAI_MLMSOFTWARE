<?php
/**
 * redirect ของเว็บสาธารณะ (ขั้นที่ 4 ของ PLAN.md "Routing สาธารณะ")
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Redirect;

final class RedirectService
{
    public function __construct(private readonly Redirect $redirects = new Redirect())
    {
    }

    /**
     * หากฎที่ตรงกับ path (decode แล้ว) — ตรงตัวก่อน แล้วค่อย prefix ที่ยาวที่สุด
     *
     * ‼️ กฎที่ชี้กลับมาที่ตัวเองถูกข้าม — ไม่งั้นได้ redirect loop ที่ Google เลิกตามทั้ง URL
     *
     * @return array<string, mixed>|null
     */
    public function match(string $path): ?array
    {
        $rule = $this->redirects->findExact($path);

        if ($rule === null) {
            foreach ($this->redirects->prefixRules() as $candidate) {
                if (str_starts_with($path, (string) $candidate['from_path'])) {
                    $rule = $candidate;
                    break;
                }
            }
        }

        if ($rule === null) {
            return null;
        }

        if ((int) $rule['code'] !== 410 && (string) ($rule['to_path'] ?? '') === $path) {
            return null;
        }

        $this->redirects->recordHit((int) $rule['id']);

        return $rule;
    }

    /**
     * ปลายทางของกฎ — path ภายใน (decode) หรือ URL เต็ม
     * query string ของ request ติดไปด้วย (utm ของแคมเปญเดิมไม่หาย)
     */
    public function target(array $rule, string $query): string
    {
        $to = (string) ($rule['to_path'] ?? '/');

        if ($query === '' || preg_match('#^https?://#i', $to) === 1) {
            return $to;
        }

        return $to . (str_contains($to, '?') ? '&' : '?') . $query;
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string> ชื่อช่อง => key ข้อความผิดพลาด (ว่าง = ผ่าน)
     */
    public function validate(array $data): array
    {
        $errors = [];
        $from   = (string) ($data['from_path'] ?? '');
        $code   = (int) ($data['code'] ?? 0);

        if (!str_starts_with($from, '/')) {
            $errors['from_path'] = 'cms.redirect.error_from_path';
        }
        if (!in_array($code, Redirect::CODES, true)) {
            $errors['code'] = 'cms.redirect.error_code';
        }
        if (!in_array((string) ($data['match_type'] ?? 'exact'), Redirect::MATCH_TYPES, true)) {
            $errors['match_type'] = 'cms.redirect.error_match_type';
        }
        if ($code !== 410 && (string) ($data['to_path'] ?? '') === '') {
            $errors['to_path'] = 'cms.redirect.error_to_path';
        }

        return $errors;
    }

    /**
     * ตรวจเต็มของ CMS: กฎพื้นฐาน + ต้นทางไม่ซ้ำ + ห้ามวน + ห้ามต่อกันเป็นทอด (chain)
     *
     * ‼️ chain (A→B แล้ว B→C) ทำให้ Google ตามช้าลงและเสีย signal — ปฏิเสธพร้อมบอกปลายทางสุดท้าย
     *    ผู้ใช้ติ๊ก "ชี้ไปปลายทางสุดท้าย" ได้ (resolve_chain) แล้ว to_path ถูกแทนให้
     *
     * @param  array<string, mixed> $data
     * @return array{errors: array<string, string>, data: array<string, mixed>, final: ?string}
     */
    public function validateForCms(array $data, ?int $id, bool $resolveChain): array
    {
        $errors = $this->validate($data);
        $from   = (string) ($data['from_path'] ?? '');
        $to     = (string) ($data['to_path'] ?? '');
        $final  = null;

        if (!isset($errors['from_path'])) {
            $existing = $this->redirects->findByFrom($from);

            if ($existing !== null && (int) $existing['id'] !== $id) {
                $errors['from_path'] = 'cms.redirect.error_from_taken';
            }
        }

        if ((int) ($data['code'] ?? 0) === 410 || isset($errors['to_path']) || $to === '' || preg_match('#^https?://#i', $to) === 1) {
            return ['errors' => $errors, 'data' => $data, 'final' => null];
        }

        if ($to === $from) {
            $errors['to_path'] = 'cms.redirect.error_loop';

            return ['errors' => $errors, 'data' => $data, 'final' => null];
        }

        // ไล่ตามกฎที่มีอยู่จากปลายทาง — ถ้าวนกลับมาที่ต้นทางคือ loop · ถ้ามีกฎต่อคือ chain
        $seen    = [$from => true];
        $cursor  = $to;
        $hops    = 0;

        while ($hops < 10) {
            $next = $this->redirects->findExact($cursor);

            if ($next === null || ($id !== null && (int) $next['id'] === $id)) {
                break;
            }
            if ((int) $next['code'] === 410 || (string) ($next['to_path'] ?? '') === '') {
                break;
            }

            $target = (string) $next['to_path'];

            if (isset($seen[$target]) || $target === $from) {
                $errors['to_path'] = 'cms.redirect.error_loop';

                return ['errors' => $errors, 'data' => $data, 'final' => null];
            }

            $seen[$cursor] = true;
            $cursor        = $target;
            $hops++;

            if (preg_match('#^https?://#i', $cursor) === 1) {
                break;
            }
        }

        if ($hops > 0) {
            $final = $cursor;

            if ($resolveChain) {
                $data['to_path'] = $final;
            } else {
                $errors['to_path'] = 'cms.redirect.error_chain';
            }
        }

        return ['errors' => $errors, 'data' => $data, 'final' => $final];
    }

    /**
     * กฎที่ชี้มาที่ path นี้ (กฎเดิมที่จะกลายเป็น chain ถ้า path นี้ถูก redirect ต่อ)
     *
     * @return array<int, array<string, mixed>>
     */
    public function pointingTo(string $path): array
    {
        return $this->redirects->pointingTo($path);
    }

    /**
     * redirect 301 อัตโนมัติเมื่อ URL ของหน้า/บทความที่เผยแพร่แล้วเปลี่ยน
     * ‼️ กฎเดิมที่ชี้มาที่ URL เก่าถูกเปลี่ยนให้ชี้ URL ใหม่ตรงๆ (ไม่ให้เกิด chain) · กฎของ URL ใหม่ถูกปิด (ไม่ให้วน)
     */
    public function createForMove(string $oldPath, string $newPath): void
    {
        if ($oldPath === $newPath) {
            return;
        }

        foreach ($this->redirects->pointingTo($oldPath) as $rule) {
            $this->redirects->update((int) $rule['id'], ['to_path' => $newPath]);
        }

        $own = $this->redirects->findByFrom($newPath);

        if ($own !== null) {
            $this->redirects->update((int) $own['id'], ['enabled' => 0]);
        }

        $existing = $this->redirects->findByFrom($oldPath);
        $data     = ['from_path' => $oldPath, 'match_type' => 'exact', 'to_path' => $newPath, 'code' => 301,
                     'enabled' => 1, 'note' => 'auto: URL changed in CMS'];

        if ($existing !== null) {
            $this->redirects->update((int) $existing['id'], $data);

            return;
        }

        $this->redirects->create($data);
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function search(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->redirects->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->redirects->find($id);
    }

    public function totalHits(): int
    {
        return $this->redirects->totalHits();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->redirects->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return $this->redirects->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->redirects->delete($id);
    }
}
