<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SecretariatManager;
use App\Models\Secretariat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecretariatManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_secretariat_through_livewire(): void
    {
        $admin = User::factory()->create(['secretariat_id' => null]);
        $secretariat = Secretariat::factory()->create([
            'name' => 'Secretaria Original',
            'slug' => 'secretaria-original',
        ]);

        Livewire::actingAs($admin)
            ->test(SecretariatManager::class)
            ->call('create')
            ->set('form.name', 'Nova Secretaria')
            ->set('form.description', 'Descricao nova')
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('secretariats', [
            'name' => 'Nova Secretaria',
            'slug' => 'nova-secretaria',
        ]);

        Livewire::actingAs($admin)
            ->test(SecretariatManager::class)
            ->call('edit', $secretariat->id)
            ->set('form.name', 'Secretaria Atualizada')
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        $this->assertDatabaseHas('secretariats', [
            'id' => $secretariat->id,
            'name' => 'Secretaria Atualizada',
            'slug' => 'secretaria-atualizada',
        ]);

        Livewire::actingAs($admin)
            ->test(SecretariatManager::class)
            ->call('delete', $secretariat->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('secretariats', [
            'id' => $secretariat->id,
        ]);
    }

    public function test_secretariat_manager_rejects_duplicate_name(): void
    {
        $admin = User::factory()->create(['secretariat_id' => null]);

        Secretariat::factory()->create([
            'name' => 'Secretaria de Obras',
            'slug' => 'secretaria-de-obras',
        ]);

        Livewire::actingAs($admin)
            ->test(SecretariatManager::class)
            ->call('create')
            ->set('form.name', 'Secretaria de Obras')
            ->call('store')
            ->assertHasErrors(['form.name'])
            ->assertSee('Ja existe uma secretaria com este nome.');
    }
}
