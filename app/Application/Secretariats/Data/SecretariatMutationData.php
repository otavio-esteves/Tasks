<?php

namespace App\Application\Secretariats\Data;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class SecretariatMutationData
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    /**
     * @param  array{name:string,description?:string|null}  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name: trim($data['name']),
            description: self::normalizeNullableString($data['description'] ?? null),
        );
    }

    /**
     * @return array{name:string,description:string|null}
     */
    public function toPersistenceArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
