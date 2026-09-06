<?php

namespace Tests\Feature\Authorization;

use App\Domain\Tasks\TaskStatus;
use App\Livewire\Team\TaskManager;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_user_cannot_edit_task_from_another_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $ownTeam->id]);
        $task = Task::factory()->forTeam($otherTeam)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->call('edit', $task->id)
            ->assertSee('Tarefa nao encontrada para esta equipe.');
    }

    public function test_team_user_can_edit_and_delete_own_task(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'title' => 'Tarefa original',
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->assertSet('form.taskId', $task->id)
            ->set('form.title', 'Tarefa atualizada')
            ->set('form.categoryId', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa atualizada',
            'team_id' => $team->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('delete', $task->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_checklist_changes_are_saved_when_closing_edit_modal(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'title' => 'Tarefa original',
        ]);

        $task->checklistItems()->create([
            'label' => 'Item existente',
            'is_completed' => false,
            'sort_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id, 'checklist')
            ->set('form.title', 'Titulo nao salvo')
            ->set('form.newChecklistItem', 'Nova etapa automatica')
            ->call('closeModal')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Nova etapa automatica',
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa original',
        ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => 'Titulo nao salvo',
        ]);
    }

    public function test_team_user_cannot_create_task_with_category_from_other_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $ownCategory = Category::factory()->create(['team_id' => $ownTeam->id]);
        $otherCategory = Category::factory()->create(['team_id' => $otherTeam->id]);
        $user = User::factory()->create(['team_id' => $ownTeam->id]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->set('form.title', 'Nova Tarefa')
            ->set('form.categoryId', $otherCategory->id)
            ->call('save')
            ->assertSee('A categoria selecionada nao pertence a esta equipe.');

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Nova Tarefa',
            'category_id' => $otherCategory->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->set('form.title', 'Tarefa valida')
            ->set('form.categoryId', $ownCategory->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa valida',
            'team_id' => $ownTeam->id,
            'category_id' => $ownCategory->id,
        ]);
    }

    public function test_team_user_cannot_update_task_with_category_from_other_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $ownCategory = Category::factory()->create(['team_id' => $ownTeam->id]);
        $otherCategory = Category::factory()->create(['team_id' => $otherTeam->id]);
        $user = User::factory()->create(['team_id' => $ownTeam->id]);
        $task = Task::factory()->forTeam($ownTeam)->create([
            'category_id' => $ownCategory->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->call('edit', $task->id)
            ->set('form.categoryId', $otherCategory->id)
            ->call('save')
            ->assertSee('A categoria selecionada nao pertence a esta equipe.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'category_id' => $ownCategory->id,
        ]);
    }

    public function test_team_user_cannot_mount_component_for_other_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $ownTeam->id]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $otherTeam])
            ->assertForbidden();
    }

    public function test_team_user_cannot_delete_task_from_another_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $ownTeam->id]);
        $task = Task::factory()->forTeam($otherTeam)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->call('delete', $task->id)
            ->assertSee('Tarefa nao encontrada para esta equipe.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'team_id' => $otherTeam->id,
            'deleted_at' => null,
        ]);
    }

    public function test_team_user_cannot_update_task_from_another_team_by_tampering_with_id(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $ownCategory = Category::factory()->create(['team_id' => $ownTeam->id]);
        $user = User::factory()->create(['team_id' => $ownTeam->id]);
        $task = Task::factory()->forTeam($otherTeam)->create([
            'title' => 'Tarefa externa',
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->set('form.taskId', $task->id)
            ->set('form.title', 'Tentativa de invasao')
            ->set('form.categoryId', $ownCategory->id)
            ->call('save')
            ->assertSee('Tarefa nao encontrada para esta equipe.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa externa',
            'team_id' => $otherTeam->id,
        ]);
    }

    public function test_team_user_can_change_status_of_own_task(): void
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
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_team_user_cannot_change_status_of_task_from_another_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $ownTeam->id]);
        $task = Task::factory()->forTeam($otherTeam)->create([
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $ownTeam])
            ->call('updateStatus', $task->id, TaskStatus::Completed->value)
            ->assertSee('Tarefa nao encontrada para esta equipe.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'team_id' => $otherTeam->id,
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_admin_can_manage_tasks_for_any_team(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'title' => 'Tarefa inicial',
        ]);

        Livewire::actingAs($admin)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa do admin')
            ->set('form.categoryId', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa do admin',
            'team_id' => $team->id,
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($admin)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->assertSet('form.taskId', $task->id)
            ->set('form.title', 'Tarefa editada pelo admin')
            ->set('form.categoryId', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa editada pelo admin',
        ]);

        Livewire::actingAs($admin)
            ->test(TaskManager::class, ['team' => $team])
            ->call('delete', $task->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }
}
