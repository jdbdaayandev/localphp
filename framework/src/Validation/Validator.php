<?php

declare(strict_types=1);

namespace LocalPHP\Validation;

use DateTimeImmutable;

final class Validator
{
    private array $errors = [];
    private array $validated = [];
    private array $messages;

    public function __construct(private readonly array $data, private readonly array $rules, array $messages = [])
    {
        $this->messages = $messages;
        $this->validate();
    }

    public static function make(array $data, array $rules, array $messages = []): self
    { return new self($data, $rules, $messages); }

    private function validate(): void
    {
        foreach ($this->rules as $field => $fieldRules) {
            $rules = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);
            $present = array_key_exists($field, $this->data);
            $value = $this->data[$field] ?? null;
            $nullable = in_array('nullable', $rules, true);
            $required = in_array('required', $rules, true);
            if (in_array('sometimes', $rules, true) && !$present) continue;
            foreach ($rules as $ruleSpec) {
                if ($ruleSpec === 'nullable' || $ruleSpec === 'sometimes') continue;
                if ($ruleSpec === 'sometimes' && !$present) continue;
                [$rule, $parameter] = array_pad(explode(':', (string) $ruleSpec, 2), 2, null);
                if (!$present || $value === null || $value === '') {
                    if ($rule === 'required' && (!$present || $value === null || $value === '')) $this->addError($field, $rule, 'The :field field is required.');
                    if ($nullable || !$required) continue;
                    if ($rule !== 'required') continue;
                }
                $valid = match ($rule) {
                    'required' => $present && $value !== null && $value !== '' && $value !== [],
                    'string' => is_string($value),
                    'integer', 'int' => filter_var($value, FILTER_VALIDATE_INT) !== false,
                    'numeric' => is_numeric($value),
                    'boolean', 'bool' => in_array($value, [true, false, 0, 1, '0', '1'], true),
                    'array' => is_array($value),
                    'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                    'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
                    'alpha' => is_string($value) && preg_match('/^[\pL]+$/u', $value) === 1,
                    'alpha_num' => is_string($value) && preg_match('/^[\pL\pN]+$/u', $value) === 1,
                    'alpha_dash' => is_string($value) && preg_match('/^[\pL\pN_-]+$/u', $value) === 1,
                    'min' => $this->size($value) >= (float) $parameter,
                    'max' => $this->size($value) <= (float) $parameter,
                    'between' => $this->size($value) >= (float) explode(',', (string)$parameter)[0] && $this->size($value) <= (float) (explode(',', (string)$parameter)[1] ?? INF),
                    'in' => in_array((string)$value, explode(',', (string)$parameter), true),
                    'not_in' => !in_array((string)$value, explode(',', (string)$parameter), true),
                    'confirmed' => array_key_exists($field.'_confirmation', $this->data) && $value === $this->data[$field.'_confirmation'],
                    'same' => array_key_exists((string)$parameter, $this->data) && $value === $this->data[$parameter],
                    'different' => !array_key_exists((string)$parameter, $this->data) || $value !== $this->data[$parameter],
                    'date' => is_string($value) && strtotime($value) !== false,
                    'date_format' => $this->validDateFormat($value, (string)$parameter),
                    'regex' => @preg_match((string)$parameter, (string)$value) === 1,
                    'accepted' => in_array($value, ['yes', 'on', 1, '1', true, 'true'], true),
                    'digits' => ctype_digit((string)$value) && strlen((string)$value) === (int)$parameter,
                    'uuid' => is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1,
                    'json' => $this->isJson($value),
                    'unique' => $this->unique($parameter, $value),
                    'exists' => $this->exists($parameter, $value),
                    'sometimes' => true,
                    default => true,
                };
                if (!$valid) $this->addError($field, $rule, $this->defaultMessage($field, $rule, $parameter));
            }
            if (!isset($this->errors[$field]) && $present) $this->validated[$field] = $value;
        }
    }

    private function unique(?string $parameter, mixed $value): bool
    {
        if (!$parameter) return false;
        [$table, $column, $ignoreValue, $idColumn] = array_pad(explode(',', $parameter), 4, null);
        $column ??= 'id';
        $query = \LocalPHP\Database\DB::table($table)->where($column, $value);
        if ($ignoreValue !== null && $ignoreValue !== '') $query->where($idColumn ?: 'id', '!=', $ignoreValue);
        return !$query->exists();
    }
    private function exists(?string $parameter, mixed $value): bool
    {
        if (!$parameter) return false; [$table, $column] = array_pad(explode(',', $parameter, 2), 2, 'id');
        return \LocalPHP\Database\DB::table($table)->where($column, $value)->exists();
    }
    private function isJson(mixed $value): bool { if (!is_string($value)) return false; json_decode($value); return json_last_error() === JSON_ERROR_NONE; }
    private function size(mixed $value): float
    { if (is_numeric($value)) return (float)$value; if (is_array($value) || $value instanceof \Countable) return count($value); return function_exists('mb_strlen') ? mb_strlen((string)$value) : strlen((string)$value); }
    private function validDateFormat(mixed $value, string $format): bool
    { if (!is_string($value)) return false; $d = DateTimeImmutable::createFromFormat('!'.$format, $value); return $d !== false && $d->format($format) === $value; }
    private function defaultMessage(string $field, string $rule, ?string $parameter): string
    { $label = str_replace('_', ' ', $field); return match ($rule) { 'email' => "The {$label} must be a valid email address.", 'min' => "The {$label} must be at least {$parameter}.", 'max' => "The {$label} may not be greater than {$parameter}.", 'in' => "The selected {$label} is invalid.", 'confirmed' => "The {$label} confirmation does not match.", 'numeric' => "The {$label} must be numeric.", 'integer','int' => "The {$label} must be an integer.", 'string' => "The {$label} must be a string.", 'required' => "The {$label} field is required.", default => "The {$label} field is invalid." }; }
    private function addError(string $field, string $rule, string $fallback): void
    { $this->errors[$field][] = str_replace(':field', str_replace('_',' ',$field), $this->messages[$field.'.'.$rule] ?? $this->messages[$field] ?? $fallback); }
    public function passes(): bool { return $this->errors === []; }
    public function fails(): bool { return !$this->passes(); }
    public function errors(): array { return $this->errors; }
    public function validated(): array { if ($this->fails()) throw new ValidationException($this->errors); return $this->validated; }
    public function first(?string $field = null): ?string { if ($field !== null) return $this->errors[$field][0] ?? null; foreach ($this->errors as $messages) if (isset($messages[0])) return $messages[0]; return null; }
}
