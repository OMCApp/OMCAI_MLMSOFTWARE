<?php
/**
 * อ่าน input และ validate
 *
 * ‼️ ห้ามเชื่อ $_POST / $_GET / $_FILES ตรงๆ ต้อง validate ก่อนใช้เสมอ
 *    การ validate ตามเงื่อนไขธุรกิจอยู่ใน Service ส่วนที่นี่ตรวจชนิดและรูปแบบ
 */

declare(strict_types=1);

namespace Core;

final class Request
{
    /** @var array<string, string> ชื่อช่อง => ข้อความผิดพลาด */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $clean = [];

    public function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper((string) $_POST['_method']);

            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofed;
            }
        }

        return $method;
    }

    public function isWrite(): bool
    {
        return in_array($this->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    /**
     * ค่าจาก query string ที่ต้องผ่าน allowlist
     *
     * ‼️ ใช้กับชื่อคอลัมน์สำหรับเรียงลำดับและทิศทางเสมอ
     *    จุดนี้คือช่องโหว่ที่หลุดบ่อยที่สุดในหน้าที่มีการเรียงลำดับ
     *
     * @param array<int, string> $allowed
     */
    public function pick(string $key, array $allowed, string $default): string
    {
        $value = (string) $this->input($key, '');

        return in_array($value, $allowed, true) ? $value : $default;
    }

    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    /** @param array<int, int> $allowed */
    public function perPage(array $allowed = [10, 25, 50, 100], int $default = 25): int
    {
        $value = (int) $this->input('per', $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    // -------------------------------------------------------------- validate

    /**
     * ตรวจตามกฎแล้วเก็บทั้งค่าที่สะอาดและข้อความผิดพลาด
     *
     * @param  array<string, string> $rules  ชื่อช่อง => 'required|email|max_len:100'
     * @param  array<string, string> $labels ชื่อช่อง => ชื่อที่แสดงให้ผู้ใช้เห็น
     * @return bool ผ่านทั้งหมดหรือไม่
     */
    public function validate(array $rules, array $labels = []): bool
    {
        $this->errors = [];
        $this->clean  = [];

        foreach ($rules as $field => $ruleset) {
            $value = $this->input($field);
            $label = $labels[$field] ?? $field;

            foreach (explode('|', $ruleset) as $rule) {
                if ($rule === '') {
                    continue;
                }

                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                $message = $this->apply((string) $name, $arg, $value, $label, $field);

                if ($message !== null) {
                    $this->errors[$field] = $message;
                    break;
                }
            }

            if (!isset($this->errors[$field])) {
                $this->clean[$field] = $value;
            }
        }

        return $this->errors === [];
    }

    private function apply(string $name, ?string $arg, mixed $value, string $label, string $field): ?string
    {
        $empty = $value === null || $value === '' || $value === [];

        if ($name === 'required') {
            return $empty ? Lang::get('validation.required', ['field' => $label]) : null;
        }

        // กฎอื่นข้ามได้ถ้าไม่ได้กรอกและไม่ได้บังคับ
        if ($empty) {
            return null;
        }

        $text = (string) $value;

        return match ($name) {
            'email' => filter_var($text, FILTER_VALIDATE_EMAIL) === false
                ? Lang::get('validation.email', ['field' => $label]) : null,

            'integer' => filter_var($text, FILTER_VALIDATE_INT) === false
                ? Lang::get('validation.integer', ['field' => $label]) : null,

            'numeric' => !is_numeric($text)
                ? Lang::get('validation.numeric', ['field' => $label]) : null,

            'min' => (float) $text < (float) $arg
                ? Lang::get('validation.min', ['field' => $label, 'value' => (string) $arg]) : null,

            'max' => (float) $text > (float) $arg
                ? Lang::get('validation.max', ['field' => $label, 'value' => (string) $arg]) : null,

            'min_len' => mb_strlen($text) < (int) $arg
                ? Lang::get('validation.min_len', ['field' => $label, 'value' => (string) $arg]) : null,

            'max_len' => mb_strlen($text) > (int) $arg
                ? Lang::get('validation.max_len', ['field' => $label, 'value' => (string) $arg]) : null,

            'in' => !in_array($text, explode(',', (string) $arg), true)
                ? Lang::get('validation.in', ['field' => $label]) : null,

            'date' => strtotime($text) === false
                ? Lang::get('validation.date', ['field' => $label]) : null,

            'confirmed' => $text !== (string) $this->input($field . '_confirm')
                ? Lang::get('validation.confirmed', ['field' => $label]) : null,

            default => null,
        };
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> ค่าที่ผ่านการตรวจแล้วเท่านั้น */
    public function clean(): array
    {
        return $this->clean;
    }

    /** ใส่ข้อความผิดพลาดจากเงื่อนไขธุรกิจใน Service */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }
}
