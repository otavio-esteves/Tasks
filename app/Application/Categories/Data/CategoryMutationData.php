<?php

namespace App\Application\Categories\Data;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class CategoryMutationData
{
    public function __construct(
        public string $name,
        public int $secretariatId,
        public ?string $description,
    ) {}

    /**
     * @param  array{name:string,secretariat_id:int|string,description?:string|null}  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name: trim($data['name']),
            secretariatId: (int) $data['secretariat_id'],
            description: self::normalizeNullableString($data['description'] ?? null),
        );
    }

    /**
     * @return array{name:string,secretariat_id:int,description:string|null}
     */
    public function toPersistenceArray(): array
    {
        return [
            'name' => $this->name,
            'secretariat_id' => $this->secretariatId,
            'description' => $this->description,
        ];
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
