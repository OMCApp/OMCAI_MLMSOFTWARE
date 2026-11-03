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
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validateTestimonial(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['person_name'] ?? '')) === '') {
            $errors['person_name'] = 'testimonial.error_name';
        }
        if (trim((string) ($data['quote'] ?? '')) === '') {
            $errors['quote'] = 'testimonial.error_quote';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Testimonial::STATUSES, true)) {
            $errors['status'] = 'testimonial.error_status';
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

        if (preg_match('#^[^/\s?#]+$#u', (string) ($data['slug'] ?? '')) !== 1) {
            $errors['slug'] = 'case_study.error_slug';
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'case_study.error_title';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), CaseStudy::STATUSES, true)) {
            $errors['status'] = 'case_study.error_status';
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
