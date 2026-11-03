<?php
/**
 * คำถามที่พบบ่อย
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Faq;

final class FaqService
{
    public function __construct(private readonly Faq $faqs = new Faq())
    {
    }

    /** @return array<int, array{question:string, answer:string}> */
    public function published(string $group): array
    {
        return array_map(
            static fn (array $row): array => ['question' => (string) $row['question'], 'answer' => (string) $row['answer']],
            $this->faqs->publishedInGroup($group)
        );
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['question'] ?? '')) === '') {
            $errors['question'] = 'faq.error_question';
        }
        if (trim((string) ($data['answer'] ?? '')) === '') {
            $errors['answer'] = 'faq.error_answer';
        }
        if (!in_array((string) ($data['status'] ?? 'draft'), Faq::STATUSES, true)) {
            $errors['status'] = 'faq.error_status';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->faqs->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        return $this->faqs->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->faqs->delete($id);
    }
}
