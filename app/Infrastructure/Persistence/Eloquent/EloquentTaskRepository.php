<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\ChecklistItemData;
use App\Application\Tasks\Data\CreateTaskData;
use App\Application\Tasks\Data\TaskListResult;
use App\Application\Tasks\Data\UpdateTaskData;
use App\Domain\Tasks\TaskStatus;
use App\Models\Task;
use App\Models\TaskChecklist;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentTaskRepository implements TaskRepository
{
    public function countActivePending(): int
    {
        return Task::query()
            ->where('status', TaskStatus::Pending->value)
            ->count();
    }

    public function createForTeam(int $teamId, int $userId, CreateTaskData $data): Task
    {
        return DB::transaction(function () use ($teamId, $userId, $data): Task {
            $task = Task::create([
                ...$data->toPersistenceArray(),
                'team_id' => $teamId,
            ]);

            if ($data->checklistItems !== []) {
                $task->checklistItems()->createMany($data->checklistItemsForPersistence());
            }

            $task->histories()->create([
                'description' => 'Tarefa criada.',
                'user_id' => $userId,
            ]);

            return $task->fresh(['category', 'checklistItems', 'histories.user']);
        });
    }

    public function findByIdForTeam(int $teamId, int $taskId): ?Task
    {
        return Task::query()
            ->with(['category', 'checklistItems', 'histories.user'])
            ->forTeam($teamId)
            ->whereKey($taskId)
            ->first();
    }

    public function update(Task $task, int $userId, UpdateTaskData $data): Task
    {
        return DB::transaction(function () use ($task, $userId, $data): Task {
            $original = $task->getOriginal();

            $oldChecklist = $this->checklistSnapshot($task);

            $task->fill($data->toPersistenceArray());
            $changes = $task->getDirty();
            $task->save();

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

            $checklistLogs = $this->syncChecklist($task, $data);
            $logs = [...$logs, ...$checklistLogs];
            $newChecklist = $this->checklistSnapshotFromData($data);

            if ($oldChecklist !== $newChecklist) {
                $metadata['checklist'] = ['from' => $oldChecklist, 'to' => $newChecklist];
            }

            if ($logs !== []) {
                $task->histories()->create([
                    'description' => implode("\n", $logs),
                    'user_id' => $userId,
                    'metadata' => $metadata,
                ]);
            }

            return $task->fresh(['category', 'checklistItems', 'histories.user']);
        });
    }

    /**
     * @return list<string>
     */
    private function syncChecklist(Task $task, UpdateTaskData $data): array
    {
        /** @var array<int, TaskChecklist> $existing */
        $existing = $task->checklistItems->keyBy('id')->all();
        $retainedIds = [];
        $logs = [];
        $orderChanged = false;

        foreach ($data->checklistItems as $checklistItem) {
            $item = $checklistItem->id !== null
                ? ($existing[$checklistItem->id] ?? null)
                : null;

            if ($item === null || isset($retainedIds[$item->id])) {
                $task->checklistItems()->create($checklistItem->toPersistenceArray());
                $logs[] = "Item '{$checklistItem->label}' adicionado ao checklist.";

                continue;
            }

            $oldLabel = $item->label;
            $oldCompleted = (bool) $item->is_completed;
            $oldSortOrder = $item->sort_order;
            $retainedIds[$item->id] = true;

            $item->fill($checklistItem->toPersistenceArray());

            if ($item->isDirty()) {
                $item->save();
            }

            if ($oldLabel !== $checklistItem->label) {
                $logs[] = "Item '{$oldLabel}' renomeado para '{$checklistItem->label}'.";
            }

            if ($oldCompleted !== $checklistItem->isCompleted) {
                $logs[] = $checklistItem->isCompleted
                    ? "Item '{$checklistItem->label}' concluído."
                    : "Item '{$checklistItem->label}' marcado como pendente.";
            }

            $orderChanged = $orderChanged || $oldSortOrder !== $checklistItem->sortOrder;
        }

        foreach ($existing as $id => $item) {
            if (isset($retainedIds[$id])) {
                continue;
            }

            $logs[] = "Item '{$item->label}' removido do checklist.";
            $item->delete();
        }

        if ($orderChanged) {
            $logs[] = 'Ordem do checklist atualizada.';
        }

        return $logs;
    }

    /**
     * @return list<array{label:string,is_completed:bool}>
     */
    private function checklistSnapshot(Task $task): array
    {
        return array_values($task->checklistItems->map(fn (TaskChecklist $item): array => [
            'label' => $item->label,
            'is_completed' => (bool) $item->is_completed,
        ])->all());
    }

    /**
     * @return list<array{label:string,is_completed:bool}>
     */
    private function checklistSnapshotFromData(UpdateTaskData $data): array
    {
        return array_map(fn (ChecklistItemData $item): array => [
            'label' => $item->label,
            'is_completed' => $item->isCompleted,
        ], $data->checklistItems);
    }

    public function changeStatus(Task $task, int $userId, TaskStatus $status): Task
    {
        return DB::transaction(function () use ($task, $userId, $status): Task {
            $oldStatus = $task->status;
            $task->changeStatus($status);
            $task->histories()->create([
                'description' => "Status alterado de {$oldStatus->label()} para {$status->label()}.",
                'user_id' => $userId,
                'metadata' => [
                    'status' => [
                        'from' => $oldStatus->value,
                        'to' => $status->value,
                    ],
                ],
            ]);

            return $task->fresh(['category', 'checklistItems', 'histories.user']);
        });
    }

    public function delete(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $task->delete();
        });
    }

    public function listForTeam(int $teamId, string $search = '', array $filters = [], int $perPage = 15): TaskListResult
    {
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $status = $filters['status'] ?? null;
        $urgent = $filters['urgent'] ?? null;
        $quickFilter = $filters['quick_filter'] ?? null;
        $today = Carbon::today()->toDateString();

        $baseQuery = Task::query()
            ->with('category')
            ->forTeam($teamId)
            ->search($search)
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($urgent !== null, fn ($query) => $query->where('is_urgent', (bool) $urgent));

        $listQuery = clone $baseQuery;

        $listQuery->when($quickFilter === 'urgent', fn ($query) => $query->where('is_urgent', true))
            ->when($quickFilter === 'in_progress', fn ($query) => $query->where('status', TaskStatus::InProgress->value))
            ->when($quickFilter === 'completed', fn ($query) => $query->where('status', TaskStatus::Completed->value))
            ->when($quickFilter === 'overdue', fn ($query) => $query
                ->whereDate('due_date', '<', $today)
                ->where('status', '!=', TaskStatus::Completed->value));

        $isCompletedRequested = $status === TaskStatus::Completed->value
            || in_array($quickFilter, ['completed', 'total'], true);

        if (! $isCompletedRequested) {
            $listQuery->where('status', '!=', TaskStatus::Completed->value);
        }

        $tasks = $listQuery
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $summaryBase = clone $baseQuery;
        if ($status !== TaskStatus::Completed->value) {
            $summaryBase->where('status', '!=', TaskStatus::Completed->value);
        }

        $summary = [
            'total' => (clone $baseQuery)->toBase()->count(),
            'urgent' => (clone $summaryBase)->where('is_urgent', true)->toBase()->count(),
            'overdue' => (clone $summaryBase)
                ->whereDate('due_date', '<', $today)
                ->where('status', '!=', TaskStatus::Completed->value)
                ->toBase()->count(),
            'in_progress' => (clone $summaryBase)->where('status', TaskStatus::InProgress->value)->toBase()->count(),
            'completed' => (clone $baseQuery)->where('status', TaskStatus::Completed->value)->toBase()->count(),
        ];

        return new TaskListResult($tasks, $summary);
    }
}
