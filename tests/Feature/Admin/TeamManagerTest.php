<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\TeamManager;
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
        $admin = User::factory()->create(['team_id' => null]);
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
        $admin = User::factory()->create(['team_id' => null]);

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
}
