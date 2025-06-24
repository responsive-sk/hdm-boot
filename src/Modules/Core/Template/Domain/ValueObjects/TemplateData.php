<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Domain\ValueObjects;

/**
 * Template Data Value Object.
 *
 * Represents template variables with validation and type safety.
 */
final readonly class TemplateData
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data
    ) {
        $this->validate($data);
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Create empty template data.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Get all data as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get specific value.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get all keys.
     *
     * @return array<string>
     */
    public function getKeys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Count data items.
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Check if data is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Merge with other template data.
     */
    public function merge(self $other): self
    {
        return new self(array_merge($this->data, $other->data));
    }

    /**
     * Add single value.
     *
     * @param mixed $value
     */
    public function with(string $key, $value): self
    {
        $newData = $this->data;
        $newData[$key] = $value;

        return new self($newData);
    }

    /**
     * Remove key.
     */
    public function without(string $key): self
    {
        $newData = $this->data;
        unset($newData[$key]);

        return new self($newData);
    }

    /**
     * Filter data by keys.
     *
     * @param array<string> $keys
     */
    public function only(array $keys): self
    {
        $filtered = array_intersect_key($this->data, array_flip($keys));

        return new self($filtered);
    }

    /**
     * Exclude keys from data.
     *
     * @param array<string> $keys
     */
    public function except(array $keys): self
    {
        $filtered = array_diff_key($this->data, array_flip($keys));

        return new self($filtered);
    }

    /**
     * Validate template data.
     *
     * @param array<string, mixed> $data
     */
    private function validate(array $data): void
    {
        foreach (array_keys($data) as $key) {
            // @phpstan-ignore-next-line function.alreadyNarrowedType
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Template data keys must be strings');
            }

            if (empty($key)) {
                throw new \InvalidArgumentException('Template data keys cannot be empty');
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                throw new \InvalidArgumentException(
                    "Template data key '{$key}' must be a valid variable name"
                );
            }
        }
    }

    /**
     * Convert to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    /**
     * Create from JSON.
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON must decode to an array');
        }

        /** @var array<string, mixed> $typedData */
        $typedData = $data;

        return new self($typedData);
    }
}
