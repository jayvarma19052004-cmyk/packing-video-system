<?php
/**
 * Input Validator
 * Validates user input and data
 */

class Validator {
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    /**
     * Add data to validate
     */
    public function setData(array $data): self {
        $this->data = $data;
        return $this;
    }

    /**
     * Validate required field
     */
    public function required(string $field, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (empty($value)) {
            $this->errors[$field][] = "$label is required";
        }

        return $this;
    }

    /**
     * Validate email
     */
    public function email(string $field, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "$label must be a valid email";
        }

        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $length, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && strlen($value) < $length) {
            $this->errors[$field][] = "$label must be at least $length characters";
        }

        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $length, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && strlen($value) > $length) {
            $this->errors[$field][] = "$label must be at most $length characters";
        }

        return $this;
    }

    /**
     * Validate numeric
     */
    public function numeric(string $field, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "$label must be numeric";
        }

        return $this;
    }

    /**
     * Validate integer
     */
    public function integer(string $field, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = "$label must be an integer";
        }

        return $this;
    }

    /**
     * Validate URL
     */
    public function url(string $field, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "$label must be a valid URL";
        }

        return $this;
    }

    /**
     * Validate in array
     */
    public function in(string $field, array $values, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;

        if (!empty($value) && !in_array($value, $values)) {
            $this->errors[$field][] = "$label must be one of: " . implode(', ', $values);
        }

        return $this;
    }

    /**
     * Validate match
     */
    public function match(string $field, string $matchField, string $label = null): self {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;
        $matchValue = $this->data[$matchField] ?? null;

        if (!empty($value) && $value !== $matchValue) {
            $this->errors[$field][] = "$label does not match";
        }

        return $this;
    }

    /**
     * Validate unique in database
     */
    public function unique(string $field, string $table, string $column = null, ?int $excludeId = null): self {
        $column = $column ?? $field;
        $value = $this->data[$field] ?? null;

        if (empty($value)) {
            return $this;
        }

        $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $params = [$value];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = Database::fetchOne($sql, $params);
        if ($result && $result['count'] > 0) {
            $this->errors[$field][] = "$field already exists";
        }

        return $this;
    }

    /**
     * Validate exists in database
     */
    public function exists(string $field, string $table, string $column = null): self {
        $column = $column ?? $field;
        $value = $this->data[$field] ?? null;

        if (empty($value)) {
            return $this;
        }

        $result = Database::fetchOne("SELECT COUNT(*) as count FROM $table WHERE $column = ?", [$value]);
        if (!$result || $result['count'] === 0) {
            $this->errors[$field][] = "$field does not exist";
        }

        return $this;
    }

    /**
     * Custom validation
     */
    public function custom(string $field, callable $callback, string $message): self {
        $value = $this->data[$field] ?? null;

        if (!$callback($value)) {
            $this->errors[$field][] = $message;
        }

        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool {
        return !$this->passes();
    }

    /**
     * Get errors
     */
    public function errors(): array {
        return $this->errors;
    }

    /**
     * Get first error for field
     */
    public function firstError(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }
}
