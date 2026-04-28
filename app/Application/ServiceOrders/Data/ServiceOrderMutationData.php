<?php

namespace App\Application\ServiceOrders\Data;

use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Models\OdsChecklist;
use App\Models\OdsHistory;
use App\Models\ServiceOrder;
use Carbon\Carbon;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class ServiceOrderMutationData
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
        public ?string $status = null,
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
     *     status?:string|null,
     *     checklist_items?:array<int, array{label?:string|null,is_completed?:bool,sort_order?:int}>
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
            status: self::normalizeNullableString($data['status'] ?? null),
            checklistItems: self::normalizeChecklistItems($data['checklist_items'] ?? []),
        );
    }

    public static function fromServiceOrder(ServiceOrder $serviceOrder): static
    {
        /** @var Carbon|null $dueDate */
        $dueDate = $serviceOrder->due_date;

        /** @var ServiceOrderStatus $status */
        $status = $serviceOrder->status;

        return new static(
            title: $serviceOrder->title,
            location: self::normalizeNullableString($serviceOrder->location),
            categoryId: (int) $serviceOrder->category_id,
            dueDate: $dueDate?->format('Y-m-d'),
            isUrgent: (bool) $serviceOrder->is_urgent,
            observation: self::normalizeNullableString($serviceOrder->observation),
            status: $status->value,
            checklistItems: array_values(
                $serviceOrder->checklistItems
                    ->map(fn (OdsChecklist $item) => ChecklistItemData::fromModel($item))
                    ->all(),
            ),
            historyItems: array_values(
                $serviceOrder->histories
                    ->map(fn (OdsHistory $item) => HistoryItemData::fromModel($item))
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
     *     observation:string|null,
     *     status?:string|null
     * }
     */
    public function toPersistenceArray(): array
    {
        $data = [
            'title' => $this->title,
            'location' => $this->location,
            'category_id' => $this->categoryId,
            'due_date' => $this->dueDate,
            'is_urgent' => $this->isUrgent,
            'observation' => $this->observation,
        ];

        if ($this->status) {
            $data['status'] = $this->status;
        }

        return $data;
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
     * @return array{
     *     title:string,
     *     location:string,
     *     categoryId:int,
     *     dueDate:string,
     *     isUrgent:bool,
     *     observation:string,
     *     status:string|null,
     *     checklistItems:list<array{label:string,is_completed:bool}>,
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
            'status' => $this->status,
            'checklistItems' => array_map(
                fn (ChecklistItemData $item) => $item->toFormState(),
                $this->checklistItems,
            ),
            'historyItems' => array_map(
                fn (HistoryItemData $item) => $item->toFormState(),
                $this->historyItems,
            ),
        ];
    }

    /**
     * @param  array<int, array{label?:string|null,is_completed?:bool,sort_order?:int}>  $items
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
