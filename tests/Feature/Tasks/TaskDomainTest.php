<?php

namespace Tests\Feature\Tasks;

use App\Application\Tasks\ChangeTaskStatus;
use App\Application\Tasks\CreateTask;
use App\Application\Tasks\Data\CreateTaskData;
use App\Application\Tasks\Data\UpdateTaskData;
use App\Application\Tasks\DeleteTask;
use App\Application\Tasks\GetTask;
use App\Application\Tasks\UpdateTask;
use App\Domain\Tasks\Exceptions\InvalidTaskCategory;
use App\Domain\Tasks\Exceptions\TaskNotFound;
use App\Domain\Tasks\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_code_is_generated_from_persisted_id(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $data = CreateTaskData::fromArray([
            'title' => 'Tarefa 1',
            'location' => 'Rua A',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => false,
            'observation' => null,
        ]);

        $this->actingAs($user);

        $first = app(CreateTask::class)->handle($team->id, $user->id, $data);
        $second = app(CreateTask::class)->handle($team->id, $user->id, CreateTaskData::fromArray([
            'title' => 'Tarefa 2',
            'location' => 'Rua B',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => true,
            'observation' => null,
        ]));

        $this->assertSame(TaskStatus::Pending, $first->status);
        $this->assertMatchesRegularExpression('/^TASK-\d{6}$/', $first->code);
        $this->assertSame(Task::codeFromId($first->id), $first->code);
        $this->assertSame(Task::codeFromId($second->id), $second->code);
        $this->assertNotSame($first->code, $second->code);
    }

    public function test_task_can_be_created_with_checklist_items(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $task = app(CreateTask::class)->handle(
            $team->id,
            $user->id,
            CreateTaskData::fromArray([
                'title' => 'Tarefa com checklist',
                'location' => 'Rua A',
                'category_id' => $category->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
                'checklist_items' => [
                    ['label' => 'Visitar local', 'is_completed' => false],
                    ['label' => 'Executar atividade', 'is_completed' => true],
                ],
            ]),
        );

        $this->assertCount(2, $task->checklistItems);
        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Visitar local',
            'is_completed' => false,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Executar atividade',
            'is_completed' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_update_task_preserves_existing_status(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()
            ->forTeam($team)
            ->create([
                'category_id' => $category->id,
                'status' => TaskStatus::InProgress,
            ]);

        $this->actingAs($user);

        $updated = app(UpdateTask::class)->handle(
            $team->id,
            $user->id,
            $task->id,
            UpdateTaskData::fromArray([
                'title' => 'Titulo atualizado',
                'location' => 'Rua Atualizada',
                'category_id' => $category->id,
                'due_date' => '2026-05-01',
                'is_urgent' => true,
                'observation' => 'Obs atualizada',
            ]),
        );

        $this->assertSame(TaskStatus::InProgress, $updated->status);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_task_checklist_items_can_be_updated(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()
            ->forTeam($team)
            ->create([
                'category_id' => $category->id,
            ]);

        $task->checklistItems()->createMany([
            ['label' => 'Item antigo 1', 'is_completed' => false, 'sort_order' => 0],
            ['label' => 'Item antigo 2', 'is_completed' => false, 'sort_order' => 1],
        ]);

        $this->actingAs($user);

        $updated = app(UpdateTask::class)->handle(
            $team->id,
            $user->id,
            $task->id,
            UpdateTaskData::fromArray([
                'title' => 'Tarefa atualizada',
                'location' => 'Rua Atualizada',
                'category_id' => $category->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
                'checklist_items' => [
                    ['label' => 'Novo item 1', 'is_completed' => true],
                    ['label' => 'Novo item 2', 'is_completed' => false],
                ],
            ]),
        );

        $this->assertCount(2, $updated->checklistItems);
        $this->assertDatabaseMissing('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Item antigo 1',
        ]);
        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Novo item 1',
            'is_completed' => true,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Novo item 2',
            'is_completed' => false,
            'sort_order' => 1,
        ]);
    }

    public function test_empty_checklist_labels_are_ignored(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $task = app(CreateTask::class)->handle(
            $team->id,
            $user->id,
            CreateTaskData::fromArray([
                'title' => 'Tarefa com labels vazios',
                'location' => 'Rua A',
                'category_id' => $category->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
                'checklist_items' => [
                    ['label' => '  ', 'is_completed' => false],
                    ['label' => '', 'is_completed' => true],
                    ['label' => 'Item Valido', 'is_completed' => false],
                ],
            ]),
        );

        $this->assertCount(1, $task->checklistItems);
        $this->assertSame('Item Valido', $task->checklistItems[0]->label);
    }

    public function test_get_task_returns_scoped_record_with_checklist_items(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
        ]);

        $task->checklistItems()->createMany([
            ['label' => 'Item 1', 'is_completed' => false, 'sort_order' => 0],
            ['label' => 'Item 2', 'is_completed' => true, 'sort_order' => 1],
        ]);

        $loaded = app(GetTask::class)->handle($team->id, $task->id);

        $this->assertTrue($loaded->is($task));
        $this->assertCount(2, $loaded->checklistItems);
        $this->assertSame('Item 1', $loaded->checklistItems[0]->label);
    }

    public function test_get_task_rejects_record_from_other_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $task = Task::factory()->forTeam($otherTeam)->create();

        $this->expectException(TaskNotFound::class);

        app(GetTask::class)->handle($team->id, $task->id);
    }

    public function test_create_task_use_case_rejects_category_from_other_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $foreignCategory = Category::factory()->create(['team_id' => $otherTeam->id]);

        $this->actingAs($user);

        $this->expectException(InvalidTaskCategory::class);

        app(CreateTask::class)->handle(
            $team->id,
            $user->id,
            CreateTaskData::fromArray([
                'title' => 'Tarefa invalida',
                'location' => 'Rua X',
                'category_id' => $foreignCategory->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
            ]),
        );
    }

    public function test_delete_task_soft_deletes_scoped_record(): void
    {
        $team = Team::factory()->create();
        $task = Task::factory()->forTeam($team)->create();

        app(DeleteTask::class)->handle($team->id, $task->id);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_delete_task_rejects_record_from_other_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $task = Task::factory()->forTeam($otherTeam)->create();

        $this->expectException(TaskNotFound::class);

        app(DeleteTask::class)->handle($team->id, $task->id);
    }

    public function test_task_data_can_be_built_from_task_for_form_usage(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'title' => '  Tarefa teste  ',
            'location' => ' Rua A ',
            'observation' => ' Observacao ',
            'due_date' => '2026-05-10',
            'is_urgent' => true,
            'status' => TaskStatus::Pending,
        ]);

        $task->checklistItems()->createMany([
            ['label' => 'Item 1', 'is_completed' => false],
            ['label' => 'Item 2', 'is_completed' => true],
        ]);

        $data = UpdateTaskData::fromTask($task->fresh(['checklistItems', 'histories']));

        $this->assertSame([
            'title' => '  Tarefa teste  ',
            'location' => 'Rua A',
            'categoryId' => $category->id,
            'dueDate' => '2026-05-10',
            'isUrgent' => true,
            'observation' => 'Observacao',
            'checklistItems' => [
                ['label' => 'Item 1', 'is_completed' => false],
                ['label' => 'Item 2', 'is_completed' => true],
            ],
            'historyItems' => [],
        ], $data->toFormState());
    }

    public function test_task_status_transitions_are_flexible(): void
    {
        $task = Task::factory()->create([
            'status' => TaskStatus::Pending,
        ]);

        $task->changeStatus(TaskStatus::InProgress);
        $task->refresh();
        $this->assertSame(TaskStatus::InProgress, $task->status);

        $task->changeStatus(TaskStatus::Pending);
        $task->refresh();
        $this->assertSame(TaskStatus::Pending, $task->status);

        $task->changeStatus(TaskStatus::Completed);
        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);

        // Now allowed to move back from Completed
        $task->changeStatus(TaskStatus::InProgress);
        $task->refresh();
        $this->assertSame(TaskStatus::InProgress, $task->status);

        $task->changeStatus(TaskStatus::Pending);
        $task->refresh();
        $this->assertSame(TaskStatus::Pending, $task->status);
    }

    public function test_change_task_status_use_case_updates_scoped_record(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user);

        $updated = app(ChangeTaskStatus::class)->handle(
            $team->id,
            $user->id,
            $task->id,
            TaskStatus::InProgress,
        );

        $this->assertSame(TaskStatus::InProgress, $updated->status);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $history = $updated->histories()->first();
        $this->assertSame('Status alterado de Pendente para Em andamento.', $history->description);
        $this->assertSame('pending', $history->metadata['status']['from']);
        $this->assertSame('in_progress', $history->metadata['status']['to']);
    }

    public function test_task_code_is_unique_even_with_soft_deletes(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        $task1 = app(CreateTask::class)->handle($team->id, $user->id, CreateTaskData::fromArray([
            'title' => 'Tarefa 1',
            'location' => 'Rua A',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => false,
            'observation' => null,
        ]));

        $code1 = $task1->code;

        // Soft delete task1
        $task1->delete();

        // Create task2 - should have a different code because it will have a different ID
        $task2 = app(CreateTask::class)->handle($team->id, $user->id, CreateTaskData::fromArray([
            'title' => 'Tarefa 2',
            'location' => 'Rua B',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => false,
            'observation' => null,
        ]));

        $this->assertNotSame($code1, $task2->code);
        $this->assertDatabaseHas('tasks', ['code' => $code1]);
        $this->assertDatabaseHas('tasks', ['code' => $task2->code]);
    }
}
