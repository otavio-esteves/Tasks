<?php

namespace App\Livewire\Team;

use App\Application\Categories\Data\CreateCategoryData;
use App\Application\Categories\SaveCategory;
use App\Application\Tasks\ChangeTaskStatus;
use App\Application\Tasks\CreateTask;
use App\Application\Tasks\Data\CreateTaskData;
use App\Application\Tasks\Data\TaskListResult;
use App\Application\Tasks\Data\UpdateTaskData;
use App\Application\Tasks\DeleteTask;
use App\Application\Tasks\GetTask;
use App\Application\Tasks\Queries\ListTasks;
use App\Application\Tasks\UpdateTask;
use App\Domain\Categories\Exceptions\CategorySlugAlreadyExists;
use App\Domain\Tasks\Exceptions\InvalidTaskCategory;
use App\Domain\Tasks\Exceptions\InvalidTaskStatusTransition;
use App\Domain\Tasks\Exceptions\TaskNotFound;
use App\Domain\Tasks\TaskStatus;
use App\Livewire\Actions\Logout;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Livewire\Forms\TaskForm;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class TaskManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions, WithPagination;

    private const INLINE_EDITABLE_FIELDS = ['title', 'location', 'category_id', 'due_date'];

    public Team $team;

    public TaskForm $form;

    public string $search = '';

    public string $filterCategoryId = '';

    public string $filterStatus = '';

    public string $filterUrgent = '';

    public string $quickFilter = '';

    public string $newCategoryName = '';

    public bool $showCategoryModal = false;

    public ?int $inlineEditingTaskId = null;

    public string $inlineEditingField = '';

    public mixed $inlineEditValue = null;

    public function mount(Team $team): void
    {
        $this->authorize('view', $team);
        $this->authorize('viewAny', [Task::class, $team]);
        $this->team = $team;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryId(): void
    {
        $this->quickFilter = '';
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->quickFilter = '';
        $this->resetPage();
    }

    public function updatingFilterUrgent(): void
    {
        $this->quickFilter = '';
        $this->resetPage();
    }

    public function updated($property, $value): void
    {
        if ($property === 'form.categoryId' && $value === 'new') {
            $this->openCategoryModal();
        }
    }

    public function openCategoryModal(): void
    {
        $this->authorize('create', Category::class);
        $this->newCategoryName = '';
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        if ($this->form->categoryId === 'new') {
            $this->form->categoryId = '';
        }
    }

    public function createNewCategory(SaveCategory $saveCategory): void
    {
        $this->authorize('create', Category::class);
        $this->newCategoryName = trim($this->newCategoryName);

        $validated = $this->validate([
            'newCategoryName' => ['required', 'string', 'min:3', 'max:255'],
        ]);
        $name = trim($validated['newCategoryName']);

        try {
            $data = CreateCategoryData::fromArray([
                'name' => $name,
                'team_id' => $this->team->id,
            ]);

            $category = $saveCategory->handle(null, $data);

            // Refresh categories list
            $this->team->load('categories');

            // Select the new category
            $this->form->categoryId = $category->id;
            $this->newCategoryName = '';
            $this->showCategoryModal = false;
        } catch (CategorySlugAlreadyExists $e) {
            $this->addError('newCategoryName', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->addError('newCategoryName', 'Erro ao criar categoria.');
        }
    }

    public function addChecklistItem(?GetTask $getTask = null, ?UpdateTask $updateTask = null): void
    {
        $this->form->addChecklistItem();

        if ($this->form->taskId) {
            $this->persistChecklistChanges(
                $getTask ?? app(GetTask::class),
                $updateTask ?? app(UpdateTask::class)
            );
        }
    }

    public function removeChecklistItem(int $index, ?GetTask $getTask = null, ?UpdateTask $updateTask = null): void
    {
        $this->form->removeChecklistItem($index);

        if ($this->form->taskId) {
            $this->persistChecklistChanges(
                $getTask ?? app(GetTask::class),
                $updateTask ?? app(UpdateTask::class)
            );
        }
    }

    public function closeModal(?GetTask $getTask = null, ?UpdateTask $updateTask = null): void
    {
        $getTask = $getTask ?? app(GetTask::class);
        $updateTask = $updateTask ?? app(UpdateTask::class);

        if (trim((string) $this->form->newChecklistItem) !== '') {
            $this->form->addChecklistItem();
        }

        try {
            if ($this->form->shouldPersistChecklistOnClose()) {
                $this->persistChecklistChanges($getTask, $updateTask);
            }

            $this->resetForm();
            $this->dispatch('task-modal-closed');
        } catch (InvalidTaskCategory|TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Não foi possível salvar a checklist agora. Tente novamente.', 'error');
        }
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function selectStatus(string $status): void
    {
        if ($this->form->taskId) {
            $this->updateStatus((int) $this->form->taskId, $status);
        }
    }

    public function updateStatus(
        int $id,
        string $status,
        ?GetTask $getTask = null,
        ?ChangeTaskStatus $changeTaskStatus = null
    ): void {
        $getTask = $getTask ?? app(GetTask::class);
        $changeTaskStatus = $changeTaskStatus ?? app(ChangeTaskStatus::class);

        $validated = Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::enum(TaskStatus::class)]],
            ['status.enum' => 'O status selecionado e invalido.'],
        )->validate();

        try {
            $task = $getTask->handle($this->team->id, $id);
            $this->authorize('update', $task);

            $changeTaskStatus->handle($this->team->id, auth()->id(), $id, TaskStatus::from((string) $validated['status']));

            if ((int) $this->form->taskId === $id) {
                $this->edit($id, 'details', $getTask);
            }

            $this->dispatch('task-status-updated');
        } catch (InvalidTaskCategory|InvalidTaskStatusTransition|TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel atualizar o status agora. Tente novamente.', 'error');
        }
    }

    public function cycleStatus(
        int $id,
        ?GetTask $getTask = null,
        ?ChangeTaskStatus $changeTaskStatus = null
    ): void {
        $getTask = $getTask ?? app(GetTask::class);
        $changeTaskStatus = $changeTaskStatus ?? app(ChangeTaskStatus::class);

        try {
            $task = $getTask->handle($this->team->id, $id);
            $this->authorize('update', $task);

            $statuses = TaskStatus::cases();
            $currentIndex = array_search($task->status, $statuses, true);
            $nextStatus = $statuses[((int) $currentIndex + 1) % count($statuses)];

            $changeTaskStatus->handle($this->team->id, auth()->id(), $id, $nextStatus);
            $this->dispatch('task-status-updated');
        } catch (InvalidTaskCategory|InvalidTaskStatusTransition|TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel atualizar o status agora. Tente novamente.', 'error');
        }
    }

    public function startInlineEdit(int $id, string $field, ?GetTask $getTask = null): void
    {
        if (! in_array($field, self::INLINE_EDITABLE_FIELDS, true)) {
            return;
        }

        $getTask = $getTask ?? app(GetTask::class);

        try {
            $task = $getTask->handle($this->team->id, $id);
            $this->authorize('update', $task);
            $data = UpdateTaskData::fromTask($task);

            $this->inlineEditingTaskId = $id;
            $this->inlineEditingField = $field;
            $this->inlineEditValue = match ($field) {
                'title' => $data->title,
                'location' => $data->location ?? '',
                'category_id' => $data->categoryId,
                'due_date' => $data->dueDate ?? '',
            };

            $this->resetErrorBag('inlineEditValue');
        } catch (TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel editar esta informacao agora.', 'error');
        }
    }

    public function saveInlineEdit(?GetTask $getTask = null, ?UpdateTask $updateTask = null): void
    {
        if ($this->inlineEditingTaskId === null || ! in_array($this->inlineEditingField, self::INLINE_EDITABLE_FIELDS, true)) {
            return;
        }

        $validated = Validator::make(
            ['inlineEditValue' => $this->inlineEditValue],
            ['inlineEditValue' => $this->inlineValidationRules()],
        )->validate();

        $getTask = $getTask ?? app(GetTask::class);
        $updateTask = $updateTask ?? app(UpdateTask::class);

        try {
            $task = $getTask->handle($this->team->id, $this->inlineEditingTaskId);
            $this->authorize('update', $task);

            $data = $this->taskDataWithOverride($task, $this->inlineEditingField, $validated['inlineEditValue'] ?? null);
            $updateTask->handle($this->team->id, auth()->id(), $task->id, $data);

            $this->cancelInlineEdit();
            $this->dispatch('task-inline-updated');
        } catch (InvalidTaskCategory|TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel salvar esta informacao agora.', 'error');
        }
    }

    public function toggleInlineUrgency(
        int $id,
        ?GetTask $getTask = null,
        ?UpdateTask $updateTask = null
    ): void {
        $getTask = $getTask ?? app(GetTask::class);
        $updateTask = $updateTask ?? app(UpdateTask::class);

        try {
            $task = $getTask->handle($this->team->id, $id);
            $this->authorize('update', $task);

            $data = $this->taskDataWithOverride($task, 'is_urgent', ! $task->is_urgent);
            $updateTask->handle($this->team->id, auth()->id(), $task->id, $data);

            $this->dispatch('task-inline-updated');
        } catch (InvalidTaskCategory|TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel atualizar a prioridade agora.', 'error');
        }
    }

    public function cancelInlineEdit(): void
    {
        $this->reset(['inlineEditingTaskId', 'inlineEditingField', 'inlineEditValue']);
        $this->resetErrorBag('inlineEditValue');
    }

    public function save(
        ?GetTask $getTask = null,
        ?CreateTask $createTask = null,
        ?UpdateTask $updateTask = null
    ): void {
        $getTask = $getTask ?? app(GetTask::class);
        $createTask = $createTask ?? app(CreateTask::class);
        $updateTask = $updateTask ?? app(UpdateTask::class);

        $this->form->validate();

        try {
            if ($this->form->taskId) {
                $task = $getTask->handle($this->team->id, (int) $this->form->taskId);
                $this->authorize('update', $task);

                $data = UpdateTaskData::fromArray($this->form->formState());
                $updateTask->handle($this->team->id, auth()->id(), (int) $this->form->taskId, $data);
            } else {
                $this->authorize('create', [Task::class, $this->team]);

                $data = CreateTaskData::fromArray($this->form->formState());
                $createTask->handle($this->team->id, auth()->id(), $data);
            }

            $this->resetForm();
            $this->dispatch('task-saved');
        } catch (InvalidTaskCategory|TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel salvar a tarefa agora. Tente novamente.', 'error');
        }
    }

    public function edit(int $id, string $view = 'details', ?GetTask $getTask = null): void
    {
        $getTask = $getTask ?? app(GetTask::class);

        try {
            $task = $getTask->handle($this->team->id, $id);
            $this->authorize('update', $task);

            $this->form->setTask($task);
            $this->dispatch('open-task-modal', mode: 'edit', view: $view);
        } catch (TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel carregar a tarefa.', 'error');
        }
    }

    public function delete(int $id, ?GetTask $getTask = null, ?DeleteTask $deleteTask = null): void
    {
        $getTask = $getTask ?? app(GetTask::class);
        $deleteTask = $deleteTask ?? app(DeleteTask::class);

        try {
            $task = $getTask->handle($this->team->id, $id);
            $this->authorize('delete', $task);

            $deleteTask->handle($this->team->id, $id);

            session()->flash('success', 'Tarefa removida com sucesso!');
        } catch (TaskNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Nao foi possivel remover a tarefa agora.', 'error');
        }
    }

    public function resetForm(): void
    {
        $this->form->reset();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterCategoryId', 'filterStatus', 'filterUrgent', 'quickFilter']);
        $this->resetPage();
    }

    public function applyQuickFilter(string $filter): void
    {
        $this->quickFilter = $this->quickFilter === $filter
            ? ''
            : $filter;

        $this->resetPage();
    }

    public function render(ListTasks $listTasks)
    {
        $this->authorize('viewAny', [Task::class, $this->team]);

        /** @var TaskListResult $listing */
        $listing = $listTasks->handle($this->team->id, $this->search, $this->listFilters(), 15);

        return view('livewire.team.task-manager', [
            'tasks' => $listing->tasks,
            'summary' => $listing->summary,
            'categories' => $this->team->categories,
            'statusOptions' => TaskStatus::cases(),
        ])->layout('layouts.app');
    }

    /**
     * @return array{category_id?:int,status?:string,urgent?:bool,quick_filter?:string}
     */
    private function listFilters(): array
    {
        $filters = [];

        if ($this->filterCategoryId !== '') {
            $filters['category_id'] = (int) $this->filterCategoryId;
        }

        if ($this->filterStatus !== '') {
            $filters['status'] = (string) $this->filterStatus;
        }

        if ($this->filterUrgent !== '') {
            $filters['urgent'] = $this->filterUrgent === '1';
        }

        if ($this->quickFilter !== '') {
            $filters['quick_filter'] = (string) $this->quickFilter;
        }

        return $filters;
    }

    public function toggleChecklistItem(int $index, ?GetTask $getTask = null, ?UpdateTask $updateTask = null): void
    {
        $this->form->checklistItems[$index]['is_completed'] = ! ($this->form->checklistItems[$index]['is_completed'] ?? false);

        if ($this->form->taskId) {
            $this->persistChecklistChanges(
                $getTask ?? app(GetTask::class),
                $updateTask ?? app(UpdateTask::class)
            );
        }
    }

    private function persistChecklistChanges(GetTask $getTask, UpdateTask $updateTask): void
    {
        $task = $getTask->handle($this->team->id, (int) $this->form->taskId);
        $this->authorize('update', $task);

        /** @var Carbon|null $dueDate */
        $dueDate = $task->due_date;

        $data = UpdateTaskData::fromArray([
            'title' => $task->title,
            'location' => $task->location ?? '',
            'category_id' => $task->category_id,
            'due_date' => $dueDate?->format('Y-m-d') ?? '',
            'is_urgent' => (bool) $task->is_urgent,
            'observation' => $task->observation ?? '',
            'checklist_items' => $this->form->checklistItems,
        ]);

        $updated = $updateTask->handle($this->team->id, auth()->id(), (int) $this->form->taskId, $data);

        $toState = UpdateTaskData::fromTask($updated)->toFormState();
        $this->form->checklistItems = $toState['checklistItems'];
        $this->form->historyItems = $toState['historyItems'];
        $this->form->originalChecklistItems = $this->form->normalizeChecklistItems($this->form->checklistItems);
    }

    /**
     * @return array<int, mixed>
     */
    private function inlineValidationRules(): array
    {
        return match ($this->inlineEditingField) {
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id,deleted_at,NULL',
            ],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            default => [],
        };
    }

    private function taskDataWithOverride(Task $task, string $field, mixed $value): UpdateTaskData
    {
        $current = UpdateTaskData::fromTask($task);
        $state = [
            ...$current->toPersistenceArray(),
            'checklist_items' => $current->checklistItemsForMutation(),
        ];
        $state[$field] = $value;

        return UpdateTaskData::fromArray($state);
    }
}
