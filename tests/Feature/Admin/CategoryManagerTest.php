<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CategoryManager;
use App\Models\Category;
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
}
