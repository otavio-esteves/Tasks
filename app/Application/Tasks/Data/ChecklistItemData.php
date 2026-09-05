<?php

namespace App\Application\Tasks\Data;

use App\Models\TaskChecklist;

final readonly class ChecklistItemData
{
    public function __construct(
        public string $label,
        public bool $isCompleted,
        public int $sortOrder,
    ) {}

    /**
     * @param  array{label?:string|null,is_completed?:bool,sort_order?:int}  $data
     */
    public static function fromArray(array $data, int $defaultSortOrder): ?self
    {
        $label = self::normalizeNullableString($data['label'] ?? null);

        if ($label === null) {
            return null;
        }

        return new self(
            label: $label,
            isCompleted: (bool) ($data['is_completed'] ?? false),
            sortOrder: isset($data['sort_order']) ? (int) $data['sort_order'] : $defaultSortOrder,
        );
    }

    public static function fromModel(TaskChecklist $item): self
    {
        return new self(
            label: $item->label,
            isCompleted: (bool) $item->is_completed,
            sortOrder: $item->sort_order,
        );
    }

    /**
     * @return array{label:string,is_completed:bool,sort_order:int}
     */
    public function toPersistenceArray(): array
    {
        return [
            'label' => $this->label,
            'is_completed' => $this->isCompleted,
            'sort_order' => $this->sortOrder,
        ];
    }

    /**
     * @return array{label:string,is_completed:bool}
     */
    public function toFormState(): array
    {
        return [
            'label' => $this->label,
            'is_completed' => $this->isCompleted,
        ];
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
