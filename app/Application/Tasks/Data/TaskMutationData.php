<?php

namespace App\Application\Tasks\Data;

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskHistory;
use Carbon\Carbon;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class TaskMutationData
{
    /**
     * @param  list<ChecklistItemData>  $checklistItems
     * @param  list<HistoryItemData>  $historyItems
     */
    public function __construct(
        public string $title,
        public ?string $location,
        public int $categoryId,
        public ?string $dueDate,
        public bool $isUrgent,
        public ?string $observation,
        public array $checklistItems = [],
        public array $historyItems = [],
    ) {}

    /**
     * @param  array{
     *     title:string,
     *     location:string|null,
     *     category_id:int|string,
     *     due_date:string|null,
     *     is_urgent:bool,
     *     observation:string|null,
     *     checklist_items?:array<int, array{id?:int|string|null,label?:string|null,is_completed?:bool,sort_order?:int}>
     * }  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            title: trim($data['title']),
            location: self::normalizeNullableString($data['location'] ?? null),
            categoryId: (int) $data['category_id'],
            dueDate: self::normalizeNullableString($data['due_date'] ?? null),
            isUrgent: (bool) $data['is_urgent'],
            observation: self::normalizeNullableString($data['observation'] ?? null),
            checklistItems: self::normalizeChecklistItems($data['checklist_items'] ?? []),
        );
    }

    public static function fromTask(Task $task): static
    {
        /** @var Carbon|null $dueDate */
        $dueDate = $task->due_date;

        return new static(
            title: $task->title,
            location: self::normalizeNullableString($task->location),
            categoryId: (int) $task->category_id,
            dueDate: $dueDate?->format('Y-m-d'),
            isUrgent: (bool) $task->is_urgent,
            observation: self::normalizeNullableString($task->observation),
            checklistItems: array_values(
                $task->checklistItems
                    ->map(fn (TaskChecklist $item) => ChecklistItemData::fromModel($item))
                    ->all(),
            ),
            historyItems: array_values(
                $task->histories
                    ->map(fn (TaskHistory $item) => HistoryItemData::fromModel($item))
                    ->all(),
            ),
        );
    }

    /**
     * @return array{
     *     title:string,
     *     location:string|null,
     *     category_id:int,
     *     due_date:string|null,
     *     is_urgent:bool,
     *     observation:string|null
     * }
     */
    public function toPersistenceArray(): array
    {
        return [
            'title' => $this->title,
            'location' => $this->location,
            'category_id' => $this->categoryId,
            'due_date' => $this->dueDate,
            'is_urgent' => $this->isUrgent,
            'observation' => $this->observation,
        ];
    }

    /**
     * @return list<array{label:string,is_completed:bool,sort_order:int}>
     */
    public function checklistItemsForPersistence(): array
    {
        return array_map(
            fn (ChecklistItemData $item) => $item->toPersistenceArray(),
            $this->checklistItems,
        );
    }

    /**
     * @return list<array{id:int|null,label:string,is_completed:bool,sort_order:int}>
     */
    public function checklistItemsForMutation(): array
    {
        return array_map(
            fn (ChecklistItemData $item) => $item->toFormState(),
            $this->checklistItems,
        );
    }

    /**
     * @return array{
     *     title:string,
     *     location:string,
     *     categoryId:int,
     *     dueDate:string,
     *     isUrgent:bool,
     *     observation:string,
     *     checklistItems:list<array{id:int|null,label:string,is_completed:bool,sort_order:int}>,
     *     historyItems:list<array{description:string,created_at:string|null,user_name:string|null,metadata:array|null}>
     * }
     */
    public function toFormState(): array
    {
        return [
            'title' => $this->title,
            'location' => $this->location ?? '',
            'categoryId' => $this->categoryId,
            'dueDate' => $this->dueDate ?? '',
            'isUrgent' => $this->isUrgent,
            'observation' => $this->observation ?? '',
            'checklistItems' => $this->checklistItemsForMutation(),
            'historyItems' => array_map(
                fn (HistoryItemData $item) => $item->toFormState(),
                $this->historyItems,
            ),
        ];
    }

    /**
     * @param  array<int, array{id?:int|string|null,label?:string|null,is_completed?:bool,sort_order?:int}>  $items
     * @return list<ChecklistItemData>
     */
    private static function normalizeChecklistItems(array $items): array
    {
        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            $checklistItem = ChecklistItemData::fromArray($item, $index);

            if ($checklistItem === null) {
                continue;
            }

            $normalized[] = $checklistItem;
        }

        return $normalized;
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
