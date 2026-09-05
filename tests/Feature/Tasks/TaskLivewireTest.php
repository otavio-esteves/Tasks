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
}
