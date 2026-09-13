<?php

namespace App\Application\Teams\Data;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class TeamMutationData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $icon,
    ) {}

    /**
     * @param  array{name:string,description?:string|null,icon?:string}  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name: trim($data['name']),
            description: self::normalizeNullableString($data['description'] ?? null),
            icon: $data['icon'] ?? 'buildings',
        );
    }

    /**
     * @return array{name:string,description:string|null,icon:string}
     */
    public function toPersistenceArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
        ];
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
