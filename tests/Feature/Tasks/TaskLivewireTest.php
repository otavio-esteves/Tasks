<?php

namespace Tests\Feature\Tasks;

use App\Domain\Tasks\TaskStatus;
use App\Livewire\Team\TaskManager;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_task_manager(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->get(route('teams.tasks', $team))
            ->assertOk()
            ->assertSee($team->name);
    }

    public function test_can_create_task_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Nova Tarefa Teste')
            ->set('form.location', 'Rua de Teste')
            ->set('form.categoryId', $category->id)
            ->set('form.isUrgent', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('task-saved');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Nova Tarefa Teste',
            'location' => 'Rua de Teste',
            'category_id' => $category->id,
            'is_urgent' => true,
            'team_id' => $team->id,
        ]);
    }

    public function test_can_edit_task_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'title' => 'Titulo Antigo',
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->assertSet('form.title', 'Titulo Antigo')
            ->set('form.title', 'Titulo Atualizado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('task-saved');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Titulo Atualizado',
        ]);
    }

    public function test_can_update_status_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('updateStatus', $task->id, TaskStatus::InProgress->value)
            ->assertHasNoErrors()
            ->assertDispatched('task-status-updated');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_can_filter_task_by_search(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        Task::factory()->forTeam($team)->create([
            'title' => 'Reparo Luz',
            'status' => TaskStatus::Pending,
        ]);
        Task::factory()->forTeam($team)->create([
            'title' => 'Poda Arvore',
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('search', 'Luz')
            ->assertSee('Reparo Luz')
            ->assertDontSee('Poda Arvore');
    }

    public function test_can_manage_checklist_items_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->set('form.newChecklistItem', 'Etapa 1')
            ->call('addChecklistItem')
            ->assertCount('form.checklistItems', 1)
            ->assertSet('form.checklistItems.0.label', 'Etapa 1');

        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Etapa 1',
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->call('toggleChecklistItem', 0)
            ->assertSet('form.checklistItems.0.is_completed', true);

        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Etapa 1',
            'is_completed' => true,
        ]);
    }

    public function test_generic_save_does_not_change_status_or_create_false_history(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forCategory($category)->create([
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->set('form.currentStatus', TaskStatus::Completed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Pending->value,
        ]);
        $this->assertDatabaseCount('task_histories', 0);
    }

    public function test_new_task_always_starts_pending_even_if_form_status_is_tampered(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa protegida')
            ->set('form.categoryId', $category->id)
            ->set('form.currentStatus', TaskStatus::Completed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa protegida',
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_task_fields_longer_than_database_limits_are_rejected(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', str_repeat('a', 256))
            ->set('form.location', str_repeat('b', 256))
            ->set('form.categoryId', $category->id)
            ->set('form.checklistItems', [[
                'label' => str_repeat('c', 256),
                'is_completed' => false,
            ]])
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'max',
                'form.location' => 'max',
                'form.checklistItems.0.label' => 'max',
            ]);
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa com prazo invalido')
            ->set('form.categoryId', $category->id)
            ->set('form.dueDate', '31/02/2026')
            ->call('save')
            ->assertHasErrors(['form.dueDate' => 'date_format']);
    }

    public function test_missing_category_is_rejected_before_use_case(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa sem categoria valida')
            ->set('form.categoryId', 999999)
            ->call('save')
            ->assertHasErrors(['form.categoryId' => 'exists']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_quick_category_creation_uses_admin_name_rules(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('newCategoryName', 'ab')
            ->call('createNewCategory')
            ->assertHasErrors(['newCategoryName' => 'min'])
            ->set('newCategoryName', str_repeat('a', 256))
            ->call('createNewCategory')
            ->assertHasErrors(['newCategoryName' => 'max']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_invalid_status_is_rejected_by_status_endpoint(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forTeam($team)->create([
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('updateStatus', $task->id, 'invalid')
            ->assertHasErrors(['status']);

        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
    }
}
