<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\ServiceOrders\Contracts\ServiceOrderRepository;
use App\Application\ServiceOrders\Data\CreateServiceOrderData;
use App\Application\ServiceOrders\Data\ServiceOrderListResult;
use App\Application\ServiceOrders\Data\UpdateServiceOrderData;
use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Models\ServiceOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentServiceOrderRepository implements ServiceOrderRepository
{
    public function createForSecretariat(int $secretariatId, int $userId, CreateServiceOrderData $data): ServiceOrder
    {
        return DB::transaction(function () use ($secretariatId, $userId, $data): ServiceOrder {
            $serviceOrder = ServiceOrder::create([
                ...$data->toPersistenceArray(),
                'secretariat_id' => $secretariatId,
            ]);

            if ($data->checklistItems !== []) {
                $serviceOrder->checklistItems()->createMany($data->checklistItemsForPersistence());
            }

            $serviceOrder->histories()->create([
                'description' => 'Ordem de serviço criada.',
                'user_id' => $userId,
            ]);

            return $serviceOrder->fresh(['category', 'checklistItems', 'histories.user']);
        });
    }

    public function findByIdForSecretariat(int $secretariatId, int $serviceOrderId): ?ServiceOrder
    {
        return ServiceOrder::query()
            ->with(['category', 'checklistItems', 'histories.user'])
            ->forSecretariat($secretariatId)
            ->whereKey($serviceOrderId)
            ->first();
    }

    public function update(ServiceOrder $serviceOrder, int $userId, UpdateServiceOrderData $data): ServiceOrder
    {
        return DB::transaction(function () use ($serviceOrder, $userId, $data): ServiceOrder {
            $original = $serviceOrder->getOriginal();

            // Capture old checklist state as a simple list
            $oldChecklist = $serviceOrder->checklistItems->map(fn ($c) => [
                'label' => $c->label,
                'is_completed' => (bool) $c->is_completed,
            ])->toArray();

            $serviceOrder->fill($data->toPersistenceArray());
            $changes = $serviceOrder->getDirty();
            $serviceOrder->save();

            // Persist new checklist
            $newChecklist = $data->checklistItemsForPersistence();
            $serviceOrder->checklistItems()->delete();
            if ($data->checklistItems !== []) {
                $serviceOrder->checklistItems()->createMany($newChecklist);
            }

            $logs = [];
            $metadata = [];

            // Standard field changes
            if (array_key_exists('title', $changes)) {
                $old = $original['title'] ?? '';
                $new = $changes['title'];
                $logs[] = "Título alterado de '{$old}' para '{$new}'.";
                $metadata['title'] = ['from' => $old, 'to' => $new];
            }
            if (array_key_exists('location', $changes)) {
                $old = $original['location'] ?? 'Nenhum';
                $new = $changes['location'] ?? 'Nenhum';
                $logs[] = "Localização alterada de '{$old}' para '{$new}'.";
                $metadata['location'] = ['from' => $old, 'to' => $new];
            }
            if (array_key_exists('due_date', $changes)) {
                $old = isset($original['due_date']) ? Carbon::parse($original['due_date'])->format('d/m/Y') : 'Não definido';
                $new = isset($changes['due_date']) ? Carbon::parse($changes['due_date'])->format('d/m/Y') : 'Não definido';
                $logs[] = "Prazo alterado de {$old} para {$new}.";
                $metadata['due_date'] = ['from' => $old, 'to' => $new];
            }
            if (array_key_exists('is_urgent', $changes)) {
                $old = ! empty($original['is_urgent']) ? 'Urgente' : 'Normal';
                $new = ! empty($changes['is_urgent']) ? 'Urgente' : 'Normal';
                $logs[] = "Prioridade alterada de {$old} para {$new}.";
                $metadata['is_urgent'] = ['from' => $old, 'to' => $new];
            }
            if (array_key_exists('observation', $changes)) {
                $logs[] = 'Observação atualizada.';
                $metadata['observation'] = ['from' => $original['observation'] ?? '', 'to' => $changes['observation'] ?? ''];
            }
            if (array_key_exists('category_id', $changes)) {
                $logs[] = 'Categoria alterada.';
                $metadata['category_id'] = ['from' => $original['category_id'] ?? '', 'to' => $changes['category_id'] ?? ''];
            }

            // Improved Checklist Diff Logic
            $newChecklistSimplified = array_map(fn ($item) => [
                'label' => $item['label'],
                'is_completed' => (bool) $item['is_completed'],
            ], $newChecklist);

            if (json_encode($oldChecklist) !== json_encode($newChecklistSimplified)) {
                $metadata['checklist'] = ['from' => $oldChecklist, 'to' => $newChecklistSimplified];

                // Track item occurrences to detect changes even with duplicate labels
                $oldMap = [];
                foreach ($oldChecklist as $item) {
                    $oldMap[$item['label']][] = $item['is_completed'];
                }
                $newMap = [];
                foreach ($newChecklistSimplified as $item) {
                    $newMap[$item['label']][] = $item['is_completed'];
                }

                $allLabels = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));

                foreach ($allLabels as $label) {
                    $oldStatusList = $oldMap[$label] ?? [];
                    $newStatusList = $newMap[$label] ?? [];

                    $oldCount = count($oldStatusList);
                    $newCount = count($newStatusList);

                    if ($newCount > $oldCount) {
                        for ($i = 0; $i < ($newCount - $oldCount); $i++) {
                            $logs[] = "Item '{$label}' adicionado ao checklist.";
                        }
                    } elseif ($newCount < $oldCount) {
                        for ($i = 0; $i < ($oldCount - $newCount); $i++) {
                            $logs[] = "Item '{$label}' removido do checklist.";
                        }
                    }

                    // For remaining items (intersection), check status changes
                    $checkCount = min($oldCount, $newCount);
                    // This is a simplification: it just checks if the count of completed items changed for this label
                    $oldCompleted = count(array_filter($oldStatusList));
                    $newCompleted = count(array_filter($newStatusList));

                    if ($newCompleted > $oldCompleted) {
                        for ($i = 0; $i < ($newCompleted - $oldCompleted); $i++) {
                            $logs[] = "Item '{$label}' concluído.";
                        }
                    } elseif ($newCompleted < $oldCompleted) {
                        for ($i = 0; $i < ($oldCompleted - $newCompleted); $i++) {
                            $logs[] = "Item '{$label}' marcado como pendente.";
                        }
                    }
                }
            }

            if ($logs !== []) {
                $serviceOrder->histories()->create([
                    'description' => implode("\n", $logs),
                    'user_id' => $userId,
                    'metadata' => $metadata,
                ]);
            }

            return $serviceOrder->fresh(['category', 'checklistItems', 'histories.user']);
        });
    }

    public function changeStatus(ServiceOrder $serviceOrder, int $userId, ServiceOrderStatus $status): ServiceOrder
    {
        return DB::transaction(function () use ($serviceOrder, $userId, $status): ServiceOrder {
            $oldStatus = $serviceOrder->status;
            $serviceOrder->changeStatus($status);
            $serviceOrder->histories()->create([
                'description' => "Status alterado de {$oldStatus->label()} para {$status->label()}.",
                'user_id' => $userId,
                'metadata' => [
                    'status' => [
                        'from' => $oldStatus->value,
                        'to' => $status->value,
                    ],
                ],
            ]);

            return $serviceOrder->fresh(['category', 'checklistItems', 'histories.user']);
        });
    }

    public function delete(ServiceOrder $serviceOrder): void
    {
        DB::transaction(function () use ($serviceOrder): void {
            $serviceOrder->delete();
        });
    }

    public function listForSecretariat(int $secretariatId, string $search = '', array $filters = [], int $perPage = 15): ServiceOrderListResult
    {
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $status = $filters['status'] ?? null;
        $urgent = $filters['urgent'] ?? null;
        $quickFilter = $filters['quick_filter'] ?? null;
        $today = Carbon::today()->toDateString();

        $baseQuery = ServiceOrder::query()
            ->with('category')
            ->forSecretariat($secretariatId)
            ->search($search)
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($urgent !== null, fn ($query) => $query->where('is_urgent', (bool) $urgent));

        $listQuery = clone $baseQuery;

        $listQuery->when($quickFilter === 'urgent', fn ($query) => $query->where('is_urgent', true))
            ->when($quickFilter === 'in_progress', fn ($query) => $query->where('status', ServiceOrderStatus::InProgress->value))
            ->when($quickFilter === 'completed', fn ($query) => $query->where('status', ServiceOrderStatus::Completed->value))
            ->when($quickFilter === 'overdue', fn ($query) => $query
                ->whereDate('due_date', '<', $today)
                ->where('status', '!=', ServiceOrderStatus::Completed->value));

        $isCompletedRequested = $status === ServiceOrderStatus::Completed->value || $quickFilter === 'completed';

        if (! $isCompletedRequested) {
            $listQuery->where('status', '!=', ServiceOrderStatus::Completed->value);
        }

        $serviceOrders = $listQuery
            ->orderBy('is_urgent', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $summaryBase = clone $baseQuery;
        if ($status !== ServiceOrderStatus::Completed->value) {
            $summaryBase->where('status', '!=', ServiceOrderStatus::Completed->value);
        }

        $summary = [
            'total' => (clone $summaryBase)->toBase()->count(),
            'urgent' => (clone $summaryBase)->where('is_urgent', true)->toBase()->count(),
            'overdue' => (clone $summaryBase)
                ->whereDate('due_date', '<', $today)
                ->where('status', '!=', ServiceOrderStatus::Completed->value)
                ->toBase()->count(),
            'in_progress' => (clone $summaryBase)->where('status', ServiceOrderStatus::InProgress->value)->toBase()->count(),
            'completed' => (clone $baseQuery)->where('status', ServiceOrderStatus::Completed->value)->toBase()->count(),
        ];

        return new ServiceOrderListResult($serviceOrders, $summary);
    }
}
