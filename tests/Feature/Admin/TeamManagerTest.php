<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\TeamManager;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_team_through_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create([
            'name' => 'Equipe Original',
            'slug' => 'equipe-original',
        ]);

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('create')
            ->set('form.name', 'Nova Equipe')
            ->set('form.description', 'Descricao nova')
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('teams', [
            'name' => 'Nova Equipe',
            'slug' => 'nova-equipe',
        ]);

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('edit', $team->id)
            ->set('form.name', 'Equipe Atualizada')
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Equipe Atualizada',
            'slug' => 'equipe-atualizada',
        ]);

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('delete', $team->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_team_manager_rejects_duplicate_name(): void
    {
        $admin = User::factory()->admin()->create();

        Team::factory()->create([
            'name' => 'Equipe de Operações',
            'slug' => 'equipe-de-operacoes',
        ]);

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('create')
            ->set('form.name', 'Equipe de Operações')
            ->call('store')
            ->assertHasErrors(['form.name'])
            ->assertSee('Ja existe uma equipe com este nome.');
    }

    public function test_empty_team_can_be_soft_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('delete', $team->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($team);
    }

    public function test_team_with_user_cannot_be_deleted_and_user_keeps_access(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('delete', $team->id)
            ->assertSee('A equipe possui usuarios, categorias ou tarefas ativas e nao pode ser removida.');

        $this->assertNotSoftDeleted($team);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('teams.tasks', $team));

        $this->get(route('teams.tasks', $team))->assertOk();
    }

    public function test_team_with_active_category_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        Category::factory()->for($team)->create();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('delete', $team->id)
            ->assertSee('A equipe possui usuarios, categorias ou tarefas ativas e nao pode ser removida.');

        $this->assertNotSoftDeleted($team);
    }

    public function test_team_with_active_task_cannot_be_deleted_even_if_category_is_trashed(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $task = Task::factory()->forTeam($team)->create();
        $task->category->delete();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('delete', $team->id)
            ->assertSee('A equipe possui usuarios, categorias ou tarefas ativas e nao pode ser removida.');

        $this->assertNotSoftDeleted($team);
    }

    public function test_soft_deleted_categories_and_tasks_do_not_block_team_deletion(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $task = Task::factory()->forTeam($team)->create();
        $category = $task->category;
        $task->delete();
        $category->delete();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('delete', $team->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($team);
    }

    public function test_recreating_trashed_team_name_returns_friendly_validation_error(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create([
            'name' => 'Equipe Arquivada',
            'slug' => 'equipe-arquivada',
        ]);
        $team->delete();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('create')
            ->set('form.name', 'Equipe Arquivada')
            ->call('store')
            ->assertHasErrors(['form.name'])
            ->assertSee('Ja existe uma equipe com este nome.');

        $this->assertSame(1, Team::withTrashed()->where('slug', 'equipe-arquivada')->count());
    }

    public function test_team_name_longer_than_database_limit_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(TeamManager::class)
            ->call('create')
            ->set('form.name', str_repeat('a', 256))
            ->call('store')
            ->assertHasErrors(['form.name' => 'max']);
    }
}
