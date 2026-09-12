<?php

namespace App\Livewire\Forms;

use App\Application\Tasks\Data\UpdateTaskData;
use App\Models\Task;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TaskForm extends Form
{
    public ?int $taskId = null;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('required|integer|exists:categories,id,deleted_at,NULL')]
    public string|int $categoryId = '';

    #[Validate('nullable|date_format:Y-m-d')]
    public string $dueDate = '';

    #[Validate('boolean')]
    public bool $isUrgent = false;

    #[Validate('nullable|string')]
    public string $observation = '';

    public string $currentStatus = 'pending';

    #[Validate('nullable|string|max:255')]
    public string $newChecklistItem = '';

    #[Validate([
        'checklistItems.*.id' => 'nullable|integer',
        'checklistItems.*.label' => 'nullable|string|max:255',
        'checklistItems.*.is_completed' => 'boolean',
        'checklistItems.*.sort_order' => 'integer|min:0',
    ])]
    public array $checklistItems = [];

    public array $originalChecklistItems = [];

    public array $historyItems = [];

    public function setTask(Task $task): void
    {
        $data = UpdateTaskData::fromTask($task)->toFormState();

        $this->taskId = $task->id;
        $this->title = $data['title'];
        $this->location = $data['location'];
        $this->categoryId = $data['categoryId'];
        $this->dueDate = $data['dueDate'];
        $this->isUrgent = $data['isUrgent'];
        $this->observation = $data['observation'];
        $this->currentStatus = $task->status->value;
        $this->checklistItems = $data['checklistItems'];
        $this->historyItems = $data['historyItems'];

        $this->originalChecklistItems = $this->normalizeChecklistItems($this->checklistItems);
    }

    public function addChecklistItem(): void
    {
        $this->validateOnly('newChecklistItem');

        $label = trim($this->newChecklistItem);

        if ($label === '') {
            return;
        }

        $this->checklistItems[] = [
            'id' => null,
            'label' => $label,
            'is_completed' => false,
            'sort_order' => $this->nextChecklistSortOrder(),
        ];

        $this->newChecklistItem = '';
    }

    public function removeChecklistItem(int $index): void
    {
        unset($this->checklistItems[$index]);
        $this->checklistItems = array_values($this->checklistItems);
    }

    public function formState(): array
    {
        return [
            'title' => $this->title,
            'location' => $this->location,
            'category_id' => $this->categoryId,
            'due_date' => $this->dueDate,
            'is_urgent' => $this->isUrgent,
            'observation' => $this->observation,
            'checklist_items' => $this->checklistItems,
        ];
    }

    public function shouldPersistChecklistOnClose(): bool
    {
        if (! $this->taskId) {
            return false;
        }

        return $this->normalizeChecklistItems($this->checklistItems) !== $this->originalChecklistItems;
    }

    /**
     * @param  array<int, array{id?:int|string|null,label?:string|null,is_completed?:bool,sort_order?:int}>  $items
     * @return list<array{id:int|null,label:string,is_completed:bool,sort_order:int}>
     */
    public function normalizeChecklistItems(array $items): array
    {
        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'id' => isset($item['id']) ? (int) $item['id'] : null,
                'label' => $label,
                'is_completed' => (bool) ($item['is_completed'] ?? false),
                'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : $index,
            ];
        }

        return $normalized;
    }

    private function nextChecklistSortOrder(): int
    {
        if ($this->checklistItems === []) {
            return 0;
        }

        return max(array_map(
            fn (array $item): int => (int) ($item['sort_order'] ?? 0),
            $this->checklistItems,
        )) + 1;
    }
}
