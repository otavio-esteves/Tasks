<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CategoryManager;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_category_through_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->create([
            'team_id' => $team->id,
            'name' => 'Categoria Original',
            'slug' => 'categoria-original',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Nova Categoria')
            ->set('form.team_id', $team->id)
            ->set('form.description', 'Descricao nova')
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('categories', [
            'name' => 'Nova Categoria',
            'slug' => 'nova-categoria',
            'team_id' => $team->id,
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('edit', $category->id)
            ->set('form.name', 'Categoria Atualizada')
            ->set('form.team_id', $team->id)
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Categoria Atualizada',
            'slug' => 'categoria-atualizada',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('delete', $category->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_manager_rejects_duplicate_slug_inside_same_team(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        Category::factory()->create([
            'team_id' => $team->id,
            'name' => 'Iluminacao Publica',
            'slug' => 'iluminacao-publica',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Iluminacao Publica')
            ->set('form.team_id', $team->id)
            ->call('store')
            ->assertHasErrors(['form.name'])
            ->assertSee('Este nome resulta em um slug ja existente em outra categoria.');
    }

    public function test_same_slug_is_allowed_in_different_teams(): void
    {
        $admin = User::factory()->admin()->create();
        $sec1 = Team::factory()->create();
        $sec2 = Team::factory()->create();

        Category::factory()->create([
            'team_id' => $sec1->id,
            'name' => 'Manutencao',
            'slug' => 'manutencao',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Manutencao')
            ->set('form.team_id', $sec2->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Manutencao',
            'team_id' => $sec2->id,
        ]);
    }

    public function test_category_without_active_tasks_can_move_to_another_team(): void
    {
        $admin = User::factory()->admin()->create();
        $origin = Team::factory()->create();
        $destination = Team::factory()->create();
        $category = Category::factory()->for($origin)->create([
            'name' => 'Categoria Movel',
            'slug' => 'categoria-movel',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('edit', $category->id)
            ->set('form.team_id', $destination->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'team_id' => $destination->id,
        ]);
    }

    public function test_category_with_active_task_cannot_move_and_task_remains_coherent(): void
    {
        $admin = User::factory()->admin()->create();
        $origin = Team::factory()->create();
        $destination = Team::factory()->create();
        $category = Category::factory()->for($origin)->create();
        $task = Task::factory()->forCategory($category)->create();

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('edit', $category->id)
            ->set('form.team_id', $destination->id)
            ->set('form.name', 'Nome ainda editavel')
            ->call('store')
            ->assertHasErrors(['form.team_id'])
            ->assertSee('A categoria possui tarefas ativas e nao pode ser movida para outra equipe.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'team_id' => $origin->id,
            'name' => $category->name,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'team_id' => $origin->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_category_with_only_soft_deleted_tasks_can_move_to_another_team(): void
    {
        $admin = User::factory()->admin()->create();
        $origin = Team::factory()->create();
        $destination = Team::factory()->create();
        $category = Category::factory()->for($origin)->create([
            'name' => 'Categoria sem tarefas ativas',
            'slug' => 'categoria-sem-tarefas-ativas',
        ]);
        Task::factory()->forCategory($category)->create()->delete();

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('edit', $category->id)
            ->set('form.team_id', $destination->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertSame($destination->id, $category->refresh()->team_id);
    }

    public function test_category_with_task_can_still_update_name_and_description(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        Task::factory()->forCategory($category)->create();

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('edit', $category->id)
            ->set('form.name', 'Categoria Renomeada')
            ->set('form.description', 'Descricao atualizada')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'team_id' => $team->id,
            'name' => 'Categoria Renomeada',
            'description' => 'Descricao atualizada',
        ]);
    }

    public function test_recreating_trashed_category_slug_returns_friendly_validation_error(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create([
            'name' => 'Categoria Arquivada',
            'slug' => 'categoria-arquivada',
        ]);
        $category->delete();

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('create')
            ->set('form.team_id', $team->id)
            ->set('form.name', 'Categoria Arquivada')
            ->call('store')
            ->assertHasErrors(['form.name'])
            ->assertSee('Este nome resulta em um slug ja existente em outra categoria.');

        $this->assertSame(1, Category::withTrashed()->where('slug', 'categoria-arquivada')->count());
    }

    public function test_category_name_longer_than_database_limit_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        Livewire::actingAs($admin)
            ->test(CategoryManager::class)
            ->call('create')
            ->set('form.team_id', $team->id)
            ->set('form.name', str_repeat('a', 256))
            ->call('store')
            ->assertHasErrors(['form.name' => 'max']);
    }
}
