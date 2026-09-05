<?php

namespace App\Application\Categories\Data;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class CategoryMutationData
{
    public function __construct(
        public string $name,
        public int $teamId,
        public ?string $description,
    ) {}

    /**
     * @param  array{name:string,team_id:int|string,description?:string|null}  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name: trim($data['name']),
            teamId: (int) $data['team_id'],
            description: self::normalizeNullableString($data['description'] ?? null),
        );
    }

    /**
     * @return array{name:string,team_id:int,description:string|null}
     */
    public function toPersistenceArray(): array
    {
        return [
            'name' => $this->name,
            'team_id' => $this->teamId,
            'description' => $this->description,
        ];
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
