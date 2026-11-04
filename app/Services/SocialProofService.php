<?php
/**
 * testimonials + case studies
 *
 * ‼️ ของปลอมห้ามขึ้นเว็บ (BRIEF · QUESTIONS.md) — บล็อกและหน้าที่ใช้ข้อมูลนี้
 *    ต้องซ่อนตัวเองเมื่อ published() คืนค่าว่าง ห้ามมีข้อมูลตัวอย่างเติมแทน
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\CaseStudy;
use App\Models\Testimonial;

final class SocialProofService
{
    public function __construct(
        private readonly Testimonial $testimonials = new Testimonial(),
        private readonly CaseStudy $caseStudies = new CaseStudy(),
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function testimonials(): array
    {
        return $this->testimonials->published();
    }

    /** @return array<int, array<string, mixed>> */
    public function caseStudies(): array
    {
        return $this->caseStudies->published();
    }

    /**
     * รีวิวในรูปที่บล็อก testimonials รับ (quote, name, role, photo) — เฉพาะที่เผยแพร่ + ยืนยันการอนุญาตแล้ว
     *
     * @return list<array<string, mixed>>
     */
    public function testimonialItems(): array
    {
        $rows   = $this->testimonials->published();
        $photos = (new \App\Models\Media())->findMany(array_map(static fn (array $r): int => (int) ($r['photo_id'] ?? 0), $rows));
        $out    = [];

        foreach ($rows as $row) {
            $item = [
                'quote' => (string) $row['quote'],
                'name'  => (string) $row['person_name'],
                'role'  => trim(implode(' · ', array_filter([(string) ($row['person_title'] ?? ''), (string) ($row['company'] ?? '')], 'strlen'))),
            ];

            if (isset($photos[(int) ($row['photo_id'] ?? 0)])) {
                $item['photo'] = MediaService::imageData($photos[(int) $row['photo_id']]);
            }

            $out[] = $item;
        }

        return $out;
    }

    /**
     * ‼️ เผยแพร่ได้เฉพาะเมื่อติ๊ก "ได้รับอนุญาตเผยแพร่จากลูกค้าแล้ว" — บันทึกว่าใครยืนยันเมื่อไหร่
     *    ติ๊กออก = ล้างการยืนยัน และสถานะเผยแพร่จะไม่ผ่าน validate
     *
     * @param  array<string, mixed>      $data
     * @param  array<string, mixed>|null $before แถวเดิม (แก้ไข) — คงการยืนยันเดิมถ้ายังติ๊กอยู่
     * @return array<string, mixed>
     */
    public static function withConsent(array $data, bool $consent, ?array $before): array
    {
        if (!$consent) {
            $data['consent_confirmed_at'] = null;
            $data['consent_confirmed_by'] = null;

            return $data;
        }

        if ($before !== null && ($before['consent_confirmed_at'] ?? null) !== null) {
            return $data;
        }

        $data['consent_confirmed_at'] = date('Y-m-d H:i:s');
        $data['consent_confirmed_by'] = \Core\Auth::id();

        return $data;
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function searchTestimonials(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->testimonials->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public function searchCaseStudies(array $filters, string $term, string $sort, string $dir, int $page, int $perPage): array
    {
        return $this->caseStudies->adminSearch($filters, $term, $sort, $dir, $page, $perPage);
    }

    /** @return array<string, mixed>|null */
    public function findTestimonial(int $id): ?array
    {
        return $this->testimonials->find($id);
    }

    /** @return array<string, mixed>|null */
    public function findCaseStudy(int $id): ?array
    {
        return $this->caseStudies->find($id);
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateTestimonial(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['person_name'] ?? '')) === '') {
            $errors['person_name'] = 'cms.testimonial.error_name';
        }
        if (trim((string) ($data['quote'] ?? '')) === '') {
            $errors['quote'] = 'cms.testimonial.error_quote';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Testimonial::STATUSES, true)) {
            $errors['status'] = 'cms.testimonial.error_status';
        }
        if (($data['status'] ?? '') === 'published' && ($data['consent_confirmed_at'] ?? null) === null) {
            $errors['consent'] = 'cms.social.error_consent';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateCaseStudy(array $data): array
    {
        $errors = [];

        if (preg_match('~^[^/\s?#]+$~u', (string) ($data['slug'] ?? '')) !== 1) {
            $errors['slug'] = 'cms.case_study.error_slug';
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'cms.case_study.error_title';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), CaseStudy::STATUSES, true)) {
            $errors['status'] = 'cms.case_study.error_status';
        }
        if (($data['status'] ?? '') === 'published' && ($data['consent_confirmed_at'] ?? null) === null) {
            $errors['consent'] = 'cms.social.error_consent';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function createTestimonial(array $data): int
    {
        return $this->testimonials->create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateTestimonial(int $id, array $data): bool
    {
        return $this->testimonials->update($id, $data);
    }

    public function deleteTestimonial(int $id): bool
    {
        return $this->testimonials->delete($id);
    }

    /** @param array<string, mixed> $data */
    public function createCaseStudy(array $data): int
    {
        return $this->caseStudies->create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateCaseStudy(int $id, array $data): bool
    {
        return $this->caseStudies->update($id, $data);
    }

    public function deleteCaseStudy(int $id): bool
    {
        return $this->caseStudies->delete($id);
    }
}
