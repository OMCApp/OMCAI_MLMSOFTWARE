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
            $errors['from_path'] = 'redirect.error_from_path';
        }
        if (!in_array($code, Redirect::CODES, true)) {
            $errors['code'] = 'redirect.error_code';
        }
        if (!in_array((string) ($data['match_type'] ?? 'exact'), Redirect::MATCH_TYPES, true)) {
            $errors['match_type'] = 'redirect.error_match_type';
        }
        if ($code !== 410 && (string) ($data['to_path'] ?? '') === '') {
            $errors['to_path'] = 'redirect.error_to_path';
        }

        return $errors;
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
