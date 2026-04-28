<?php

namespace App\Livewire\Forms;

use App\Application\ServiceOrders\Data\UpdateServiceOrderData;
use App\Models\ServiceOrder;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ServiceOrderForm extends Form
{
    public ?int $odsId = null;

    #[Validate('required|min:3')]
    public string $title = '';

    public string $location = '';

    #[Validate('required|integer')]
    public string|int $categoryId = '';

    public string $dueDate = '';

    public bool $isUrgent = false;

    public string $observation = '';

    #[Validate('required|string')]
    public string $currentStatus = 'pending';

    public string $newChecklistItem = '';

    #[Validate([
        'checklistItems.*.label' => 'nullable|string|max:255',
        'checklistItems.*.is_completed' => 'boolean',
    ])]
    public array $checklistItems = [];

    public array $originalChecklistItems = [];

    public array $historyItems = [];

    public function setServiceOrder(ServiceOrder $ods): void
    {
        $data = UpdateServiceOrderData::fromServiceOrder($ods)->toFormState();

        $this->odsId = $ods->id;
        $this->title = $data['title'];
        $this->location = $data['location'];
        $this->categoryId = $data['categoryId'];
        $this->dueDate = $data['dueDate'];
        $this->isUrgent = $data['isUrgent'];
        $this->observation = $data['observation'];
        $this->currentStatus = $data['status'] ?? 'pending';
        $this->checklistItems = $data['checklistItems'];
        $this->historyItems = $data['historyItems'];

        $this->originalChecklistItems = $this->normalizeChecklistItems($this->checklistItems);
    }

    public function addChecklistItem(): void
    {
        $label = trim($this->newChecklistItem);

        if ($label === '') {
            return;
        }

        $this->checklistItems[] = [
            'label' => $label,
            'is_completed' => false,
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
            'status' => $this->currentStatus,
            'checklist_items' => $this->checklistItems,
        ];
    }

    public function shouldPersistChecklistOnClose(): bool
    {
        if (! $this->odsId) {
            return false;
        }

        return $this->normalizeChecklistItems($this->checklistItems) !== $this->originalChecklistItems;
    }

    /**
     * @param  array<int, array{label?:string|null,is_completed?:bool}>  $items
     * @return list<array{label:string,is_completed:bool}>
     */
    public function normalizeChecklistItems(array $items): array
    {
        $normalized = [];

        foreach (array_values($items) as $item) {
            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'is_completed' => (bool) ($item['is_completed'] ?? false),
            ];
        }

        return $normalized;
    }
}
